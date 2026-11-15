import { useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';

import Combobox from '../components/form/Combobox';
import FieldError from '../components/form/FieldError';
import RangeSlider from '../components/form/RangeSlider';
import SearchTabs from '../components/form/SearchTabs';
import ToggleGroup, { triState } from '../components/form/ToggleGroup';
import { formToOld, formUrl } from '../components/form/formQuery';
import { trackSearch } from '../components/form/searchMetrics';
import PageTitle from '../components/ui/PageTitle';
import { trackInteraction } from '../components/utils/metrics';
import { useTooltips } from '../components/utils/useTooltips';

const LOCALE = 'de-DE';
const FILTERS_KEY = 'filterExpanded';
const AIRPORT_SIZES = [['small_airport', 'Small'], ['medium_airport', 'Medium'], ['large_airport', 'Large']];
const DIRECTIONS = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
const DIRECTION_IDS = { N: 'north', NE: 'northeast', E: 'east', SE: 'southeast', S: 'south', SW: 'southwest', W: 'west', NW: 'northwest' };
const EXCLUSIVE_AREAS = ['Anywhere', 'Domestic'];

const capitalise = (text) => text[0].toUpperCase() + text.slice(1);
const thousands = (value) => value.toLocaleString(LOCALE);

// sessionStorage throws where site data is blocked; the panel just starts closed then
const readExpanded = () => { try { return sessionStorage.getItem(FILTERS_KEY) === 'true'; } catch { return false; } };
const writeExpanded = (value) => { try { sessionStorage.setItem(FILTERS_KEY, String(value)); } catch { /* blocked */ } };

// The destination inputs as combobox items: flat entries, then each group under a divider
const areaItems = (inputs, { withExclusive }) => Object.entries(inputs).flatMap(([key, value]) => {
    if (value !== null && typeof value === 'object') {
        return [{ divider: key }, ...Object.entries(value).map(([code, label]) => ({ value: code, label }))];
    }

    return withExclusive || !EXCLUSIVE_AREAS.includes(key) ? [{ value: key, label: value }] : [];
});

const SearchForm = ({
    anchor, area, suggestionSide, prefilledIcao, old, airlines, aircrafts, destinationInputs, lists,
    aircraftTypes, scoreTypes, searchVersion,
}) => {

    const errors = usePage().props.errors ?? {};
    const formRef = useRef(null);
    const [icao, setIcao] = useState(prefilledIcao ?? old.icao ?? '');
    const [expanded, setExpanded] = useState(readExpanded);
    const [submitting, setSubmitting] = useState(false);

    useTooltips(formRef, []);

    // The anchor whitelist only narrows which random airport gets drawn, so a typed ICAO locks it
    const anchorLocked = icao.trim().length > 0;
    const listItems = lists.map((list) => ({ value: String(list.id), label: list.name }));
    const scoresOld = old.scores;
    const sizes = old.destinationAirportSize ?? AIRPORT_SIZES.map(([value]) => value);
    const checkedByDefault = (value) => (value === undefined ? true : Boolean(Number(value)));
    const sliderStart = (min, max, defaults) => [Number(old[min] ?? defaults[0]), Number(old[max] ?? defaults[1])];

    const toggleFilters = () => {
        if (!expanded) {
            trackInteraction('Expand Filters');
        }

        writeExpanded(!expanded);
        setExpanded(!expanded);
    };

    const submit = (event) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        const form = formRef.current;

        trackSearch(form);
        // Back from the results restores this entry's props, so record what was searched in them
        router.replace({ props: (props) => ({ ...props, prefilledIcao: null, old: formToOld(form, ['sortByWeather', 'sortByATC']) }), preserveState: true, preserveScroll: true });
        router.visit(formUrl(form), {
            onStart: () => setSubmitting(true),
            onFinish: () => setSubmitting(false),
        });
    };

    const submitButton = (
        <button type="submit" className="submitBtn btn btn-primary text-uppercase" disabled={submitting}>
            Search{' '}
            {submitting
                ? <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                : <i className="fa-sharp fa-search"></i>}
        </button>
    );

    const scoreRows = (prefix) => Object.entries(scoreTypes)
        .filter(([reason]) => reason.startsWith(prefix))
        .map(([reason, type]) => (
            <div className="mt-1" key={reason}>
                <ToggleGroup name={`scores[${reason}]`} idPrefix={reason} options={triState()}
                    value={scoresOld ? scoresOld[reason] : '0'} />
                <i className={`ms-2 fa-sharp ${type.icon}`}></i>&nbsp;{type.desc}
            </div>
        ));

    return (
        <>
            <Head title="" />

            <PageTitle title="Search for your flight" subtitle="Find destinations based on your weather or coverage criteria" />

            <div className="container">
                <SearchTabs />

                {errors.searchVersion && (
                    <div className="validation-error mt-2 mb-2">
                        <i className="fa-sharp fa-exclamation-triangle"></i> Your bookmarked search is incompatible with the new version of Where2Fly. Please review filters, search again and refresh your bookmark.
                    </div>
                )}

                <form id="form" ref={formRef} action={route('search')} method="GET" onSubmit={submit}>
                    <div className="row g-3 justify-content-center">
                        <div className="col-xs-12 text-start">
                            <label htmlFor="icao">{capitalise(anchor)} (ICAO)</label>
                            <input type="text" className="form-control" id="icao" name="icao" placeholder="Random" maxLength="4"
                                value={icao} onChange={(event) => setIcao(event.target.value.toUpperCase())} />
                            <input type="hidden" name="direction" value={anchor} />
                            <FieldError message={errors.icao} />
                        </div>

                        <div className="col-xs-12 text-start">
                            <label htmlFor="destination-input">{capitalise(area)} Area</label>
                            <Combobox id="destination" name="destinations[]" taller placeholder="Anywhere"
                                items={areaItems(destinationInputs, { withExclusive: true })}
                                selected={old.destinations ?? []} exclusive={EXCLUSIVE_AREAS} />
                            <FieldError message={errors.destinations} />
                        </div>

                        <div className="col-xs-12 text-start">
                            <label htmlFor="codeletter">
                                Aircraft Type{' '}
                                <i className="fa-sharp fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-title="Select the aircraft closest to what you want to fly. This is used to calculate airtime and find compatible airports"></i>
                            </label>
                            <select className="form-control" id="codeletter" name="codeletter" defaultValue={old.codeletter ?? 'JM'}>
                                {Object.entries(aircraftTypes).map(([code, type]) => (
                                    <option key={code} value={code}>{type.description} - {type.name}</option>
                                ))}
                            </select>
                            <FieldError message={errors.codeletter} />
                        </div>

                        <div className="col-xs-12 text-start">
                            <label>Intended Air Time</label>
                            <RangeSlider id="slider-airtime" names={['airtimeMin', 'airtimeMax']} range={[0, 12]} step={1}
                                start={sliderStart('airtimeMin', 'airtimeMax', [0, 5])}
                                describe={([min, max]) => `${min}-${max}${max === 12 ? '+' : ''} hours`} />
                        </div>

                        <div className="col-xs-12 text-start">
                            <label htmlFor="whitelist-input">{capitalise(suggestionSide)} Whitelist</label>
                            <Combobox id="whitelist" name={`${suggestionSide}Whitelists[]`} placeholder="Restrict your search"
                                items={listItems} selected={(old[`${suggestionSide}Whitelists`] ?? old.whitelists ?? []).map(String)} />
                        </div>

                        <div className="col-xs-12 text-start">
                            <label>Order by</label>
                            {[['sortByWeather', 'Worst Weather'], ['sortByATC', 'ATC Coverage']].map(([name, label]) => (
                                <div className="form-check mb-0" key={name}>
                                    <input className="form-check-input" type="checkbox" value="1" id={name} name={name}
                                        defaultChecked={checkedByDefault(old[name])} />
                                    <label className="form-check-label" htmlFor={name}>{label}</label>
                                    <FieldError message={errors[name]} />
                                </div>
                            ))}
                        </div>

                        <div className="col-sm-12 align-self-start">{submitButton}</div>
                    </div>

                    <FieldError message={errors.airportNotFound} className="validation-error mt-2" icon={false} />
                    <FieldError message={errors.bearingWarning} className="validation-error mt-2" icon={false} />

                    <div id="filters" className={expanded ? '' : 'hide-filters'}>
                        <div className="row g-3 mt-3 pb-4 justify-content-center bt">
                            <div className="col-xs-12 text-start">
                                <label htmlFor="aircraft-input">Aircraft</label>
                                <Combobox id="aircraft" name="aircrafts[]" placeholder="All aircraft"
                                    items={aircrafts.map((code) => ({ value: code, label: code }))} selected={old.aircrafts ?? []} />
                                <FieldError message={errors.aircrafts} />

                                <label htmlFor="airlines-input" className="pt-4">Airlines</label>
                                <Combobox id="airlines" name="airlines[]" placeholder="All airlines"
                                    items={airlines.map((airline) => ({ value: airline.icao, label: `${airline.name} (${airline.icao})` }))}
                                    selected={old.airlines ?? []} />
                                <FieldError message={errors.airlines} />

                                <label htmlFor="destinationExclusions-input" className="pt-4">{capitalise(area)} Exclusions</label>
                                <Combobox id="destinationExclusions" name="destinationExclusions[]" taller placeholder="None"
                                    items={areaItems(destinationInputs, { withExclusive: false })} selected={old.destinationExclusions ?? []} />
                                <FieldError message={errors.destinationExclusions} />

                                <div id="anchorWhitelistGroup">
                                    <label htmlFor="anchorWhitelist-input" className="pt-4">
                                        {capitalise(anchor)} Whitelist{' '}
                                        {anchorLocked && <i className="fa-sharp fa-lock" id="anchorWhitelistLockIcon"></i>}
                                    </label>
                                    <Combobox id="anchorWhitelist" name={`${anchor}Whitelists[]`} placeholder="Anywhere"
                                        items={listItems} selected={(old[`${anchor}Whitelists`] ?? []).map(String)}
                                        disabled={anchorLocked} hidden={anchorLocked} />
                                    {anchorLocked && <p id="anchorWhitelistLocked" className="filter-hint">Clear the ICAO field to enable</p>}
                                </div>
                            </div>

                            <div className="col-sm-12 text-start">
                                <label>Meteo Condition</label>
                                <div>
                                    <ToggleGroup name="metcondition" labelClass="btn-filter-width-meteo" value={old.metcondition ?? 'ANY'} options={[
                                        { value: 'ANY', id: 'all', tone: 'light', label: 'Any' },
                                        { value: 'IFR', id: 'ifr', tone: 'red', label: 'IFR' },
                                        { value: 'VFR', id: 'vfr', tone: 'green', label: 'VFR' },
                                    ]} />
                                </div>

                                <label className="pt-4">Weather parameters</label>
                                {scoreRows('METAR')}
                            </div>

                            <div className="col-sm-12 text-start">
                                <label>Network parameters</label>
                                {scoreRows('VATSIM')}

                                <label className="pt-4">Destination parameters</label>
                                <div className="mt-1">
                                    <ToggleGroup name="destinationWithRoutesOnly" options={triState()} value={old.destinationWithRoutesOnly ?? '0'} />
                                    <i className="ms-2 fa-sharp fa-route"></i>&nbsp;With routes only
                                </div>
                                <div className="mt-1">
                                    <ToggleGroup name="destinationRunwayLights" options={triState()} value={old.destinationRunwayLights ?? '0'} />
                                    <i className="ms-2 fa-sharp fa-lightbulb-on"></i>&nbsp;Runway with lights
                                </div>
                                <div className="mt-1">
                                    <ToggleGroup name="destinationAirbases" options={triState(['light', 'light', 'green'])} value={old.destinationAirbases ?? '-1'} />
                                    <i className="ms-2 fa-sharp fa-jet-fighter"></i>&nbsp;Airbases
                                </div>
                            </div>

                            <div className="col-sm-12 text-start">
                                <div className="col-xs-12 text-start">
                                    <label>Intended Distance</label>
                                    <RangeSlider id="slider-distance" names={['distanceMin', 'distanceMax']} range={[0, 6000]} step={100}
                                        start={sliderStart('distanceMin', 'distanceMax', [0, 6000])}
                                        describe={([min, max]) => `${thousands(min)}-${thousands(max)}${max === 6000 ? '+' : ''} NM`} />
                                    <FieldError message={errors.distanceMin} />
                                    <FieldError message={errors.distanceMax} />
                                </div>

                                <div>
                                    <label className="pt-4">{capitalise(anchor)} Temperature</label>
                                    <RangeSlider id="slider-temperature" names={['temperatureMin', 'temperatureMax']} range={[-60, 60]} step={2}
                                        start={sliderStart('temperatureMin', 'temperatureMax', [-60, 60])}
                                        describe={([min, max]) => `${min} to ${max}C`} />
                                </div>

                                <div>
                                    <label className="pt-4">{capitalise(anchor)} Elevation</label>
                                    <RangeSlider id="slider-elevation" names={['elevationMin', 'elevationMax']} range={[-2000, 18000]} step={1000}
                                        start={sliderStart('elevationMin', 'elevationMax', [-2000, 18000])}
                                        describe={([min, max]) => `${thousands(min)}-${thousands(max)}ft`} />
                                </div>

                                <div>
                                    <label className="pt-4">{capitalise(anchor)} Runway Length</label>
                                    <RangeSlider id="slider-rwy" names={['rwyLengthMin', 'rwyLengthMax']} range={[0, 17000]} step={500}
                                        start={sliderStart('rwyLengthMin', 'rwyLengthMax', [0, 17000])}
                                        describe={([min, max]) => (
                                            <>
                                                {thousands(min)}-{thousands(max)}ft{' '}
                                                <span className="text-white text-opacity-75">({thousands(Math.round(min / 3.2808))}-{thousands(Math.round(max / 3.2808))}m)</span>
                                            </>
                                        )} />
                                </div>

                                <label className="pt-4">Flight direction</label>
                                <FieldError message={errors.flightDirection} />
                                <div className="mt-1">
                                    <ToggleGroup name="flightDirection" value={old.flightDirection ?? '0'} options={[
                                        { value: '0', id: 'neutral', tone: 'light', label: <><i className="fa-sharp fa-slash-forward"></i><span className="visually-hidden">Neutral</span></> },
                                        ...DIRECTIONS.map((direction) => ({ value: direction, id: DIRECTION_IDS[direction], tone: 'green', label: direction })),
                                    ]} />
                                </div>

                                <label className="d-block pt-4">Airport Size</label>
                                <div>
                                    {AIRPORT_SIZES.map(([value, label]) => (
                                        <div className="form-check form-check-inline mb-0 me-reduced" key={value}>
                                            <input className="form-check-input" type="checkbox" value={value} name="destinationAirportSize[]"
                                                id={`destinationAirportSize${label}`} defaultChecked={sizes.includes(value)} />
                                            <label className="form-check-label" htmlFor={`destinationAirportSize${label}`}>{label}</label>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="col-sm-12 align-self-start">{submitButton}</div>
                        </div>
                    </div>

                    <div className="row g-3 mt-3 justify-content-center">
                        <div className="col-sm-12 align-self-end mb-3">
                            <div className="expandFilterGroup">
                                <div className="divider"></div>
                                <button type="button" id="expandFilters" className={`button${expanded ? ' active' : ''}`} onClick={toggleFilters}>
                                    {expanded ? 'Hide filters' : 'Show more filters'} <i className="fa-sharp fa-chevron-down expand-chevron"></i>
                                </button>
                                <div className="divider"></div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="searchVersion" value={searchVersion} />
                </form>
            </div>
        </>
    );
};

export default SearchForm;
