import { useState } from 'react';
import { Head, router } from '@inertiajs/react';

import { useShellMap } from '../components/shell/ShellMapContext';
import { withSelectedAirport } from '../components/shell/selection';
import Flag from '../components/ui/Flag';
import Legend from '../components/ui/Legend';
import PageTitle from '../components/ui/PageTitle';
import ScoreIcon from '../components/ui/ScoreIcon';
import TAF from '../components/ui/TAF';
import { trackInteraction } from '../components/utils/metrics';
import { useRowInView } from '../components/utils/useRowInView';
import { useSortableRows } from '../components/utils/useSortableRows';

const VISIBLE_ROWS = 10;
const feet = (ft) => ft.toLocaleString('en-US');
const metres = (ft) => Math.round(ft * 0.3048).toLocaleString('en-US');

const SORT_COLUMNS = {
    rank: (airport) => airport.rank,
    airport: (airport) => airport.icao,
    distance: (airport) => airport.distance,
    time: (airport) => airport.airtime,
    forecast: (airport) => airport.scores.length,
};

// "Edit filters" hands the query back to the search form as old input. The server fills in the
// sort boxes a form leaves out when unchecked, so they come back as they were. "Bookmark" only explains how.
const SearchActions = ({ query }) => (
    <div className="d-flex flex-wrap gap-2" style={{ marginTop: '0.5rem' }}>
        <button id="editSearchBtn" type="button" className="btn btn-sm btn-outline-warning mb-1"
            onClick={() => router.post(route('search.edit'), query)}>
            <i className="fa-sharp fa-pencil"></i> Edit filters
        </button>
        <button id="bookmarkBtn" type="button" className="btn btn-sm btn-outline-warning mb-1" onClick={() => {
            trackInteraction('Bookmark search');
            alert('To save these search filters, manually bookmark this page in your browser.');
        }}>
            <i className="fa-sharp fa-bookmark"></i> Bookmark search
        </button>
    </div>
);

const PrimaryAirport = ({ airport }) => (
    <div className="results-container">
        <dl>
            <dt>Airport</dt>
            <dd><Flag country={airport.isoCountry} name={airport.countryName} /> {airport.icao}</dd>
            <dd>{airport.name}</dd>
        </dl>

        <dl>
            <dt>Runway</dt>
            <dd className="rwy-feet">{feet(airport.longestRunwayFt)} ft</dd>
            <dd className="rwy-meters text-opacity-50">{metres(airport.longestRunwayFt)} m</dd>
        </dl>

        {airport.scores.length > 0 && (
            <dl>
                <dt>Forecast</dt>
                <dd>{airport.scores.map((score) => <ScoreIcon key={score.reason} score={score} />)}</dd>
            </dl>
        )}

        <dl>
            <dt>Weather</dt>
            <dd>
                {airport.metar ? (
                    <>
                        {airport.metar}
                        <span className="d-block mt-2">
                            {airport.taf
                                ? <TAF taf={airport.taf} label="Show TAF" className="d-block btn btn-outline-light btn-sm" />
                                : <><i className="fa-sharp fa-cloud-xmark"></i> No TAF available</>}
                        </span>
                    </>
                ) : (
                    <><i className="fa-sharp fa-info-square"></i> No METAR available</>
                )}
            </dd>
        </dl>
    </div>
);

const SearchResults = ({
    query, direction, suggestedAirport, bearingWarning, searchVersionWarning, noInterestingScores,
    primaryAirport, suggestions, scoreTypes,
}) => {

    const { focusAirport, setFocusAirport } = useShellMap();
    const rowRef = useRowInView(focusAirport);
    const [expanded, setExpanded] = useState(suggestions.length <= VISIBLE_ROWS);
    const [randomising, setRandomising] = useState(false);
    const { rows, headerProps } = useSortableRows(suggestions, SORT_COLUMNS);
    const suggestionSide = direction === 'departure' ? 'arrival' : 'departure';

    // Focus can come from the map: a row behind "Show more" is unfolded during render, so it is
    // there to scroll to. The primary airport has no row.
    if (!expanded && suggestions.some((airport) => airport.icao === focusAirport)) {
        setExpanded(true);
    }

    // Sorting shows every row, so the sorted order isn't cut off at the fold
    const sortableHeader = (key) => {
        const props = headerProps(key);

        return { ...props, onClick: () => { setExpanded(true); props.onClick(); } };
    };

    // Same query, new random anchor. A fresh visit, not reload(), so the page and map start over.
    const randomise = () => router.visit(withSelectedAirport(window.location.href, null), {
        onStart: () => setRandomising(true),
        onFinish: () => setRandomising(false),
    });

    return (
        <div>
            <Head title="Results" />

            <PageTitle title="Search results">
                <SearchActions query={query} />
            </PageTitle>

            <div className="container">
                {searchVersionWarning && (
                    <div className="alert alert-warning mb-4">
                        <p className="mb-0"><i className="fa-sharp fa-warning"></i> {searchVersionWarning}</p>
                    </div>
                )}

                {bearingWarning && (
                    <div className="alert alert-warning">
                        <p className="mb-0"><i className="fa-sharp fa-warning"></i> {bearingWarning}</p>
                    </div>
                )}

                <div className="results-header">
                    {suggestedAirport ? (
                        <div className="d-flex flex-wrap justify-content-between gap-2">
                            <h2 className="mb-0">
                                <i className={`fa-sharp fa-plane-${direction}`}></i> {direction[0].toUpperCase() + direction.slice(1)} suggestion
                            </h2>
                            <button id="randomiseBtn" type="button" className="btn btn-sm btn-outline-warning" disabled={randomising} onClick={randomise}>
                                Randomise{' '}
                                {randomising
                                    ? <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    : <i className="fa-sharp fa-shuffle"></i>}
                            </button>
                        </div>
                    ) : (
                        <h2 className="mb-0">
                            <i className={`fa-sharp fa-plane-${direction}`}></i> {direction[0].toUpperCase() + direction.slice(1)}
                        </h2>
                    )}
                </div>

                <PrimaryAirport airport={primaryAirport} />

                <div className="results-header">
                    <h2 className="mb-0">
                        <i className={`fa-sharp fa-plane-${suggestionSide}`}></i> {suggestionSide === 'arrival' ? 'Arrival' : 'Departure'} suggestions
                    </h2>
                </div>

                <div className="table-responsive">
                    <table className="table table-hover text-start sortable asc mb-0">
                        <thead>
                            <tr>
                                <th scope="col" {...sortableHeader('rank')}>#</th>
                                <th scope="col" {...sortableHeader('airport')}>Airport</th>
                                <th scope="col" {...sortableHeader('distance')}>Distance</th>
                                <th scope="col" className="results-time" {...sortableHeader('time')}>Time</th>
                                <th scope="col" {...sortableHeader('forecast')}>Forecast</th>
                            </tr>
                            {noInterestingScores && (
                                <tr>
                                    <th className="text-center text-info fw-normal pt-3 pb-3" colSpan="9">
                                        <i className="fa-sharp fa-info-square"></i> None of the airports in your range has interesting weather or ATC
                                    </th>
                                </tr>
                            )}
                        </thead>
                        <tbody>
                            {rows.map((airport, index) => (
                                <tr key={airport.icao} ref={rowRef(airport.icao)}
                                    className={`pointer${!expanded && index >= VISIBLE_ROWS ? ' showmore-hidden' : ''}${focusAirport === airport.icao ? ' active' : ''}`}
                                    data-airport-icao={airport.icao}
                                    onClick={() => setFocusAirport(airport.icao)}>
                                    <th scope="row">{airport.rank}</th>
                                    <td>
                                        <div><Flag country={airport.isoCountry} name={airport.countryName} /> {airport.icao}</div>
                                        {airport.name}
                                    </td>
                                    <td>
                                        {airport.distance}nm
                                        <span className="results-time-folded">{airport.airtimeLabel}h</span>
                                    </td>
                                    <td className="results-time">{airport.airtimeLabel}h</td>
                                    <td className="fs-5">
                                        {airport.scores.map((score) => <ScoreIcon key={score.reason} score={score} highlighted={score.highlighted} />)}
                                    </td>
                                </tr>
                            ))}

                            {suggestions.length === 0 && (
                                <tr>
                                    <th colSpan="9" className="text-center text-warning">
                                        <i className="fa-sharp fa-exclamation-triangle"></i> No results matched your criteria
                                    </th>
                                </tr>
                            )}
                            {!expanded && (
                                <tr id="showMoreRow">
                                    <th colSpan="10" className="text-center">
                                        <button id="showMoreBtn" className="btn btn-outline-primary" onClick={() => setExpanded(true)}>Show more</button>
                                    </th>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Legend scoreTypes={scoreTypes} />
            </div>
        </div>
    );
};

SearchResults.mapScene = ({ airportMapData, primaryAirport, direction, highlightedAircrafts }) => ({
    airports: airportMapData,
    cluster: false,
    clusterTone: 'search',
    primaryAirport: primaryAirport.icao,
    reverseDirection: direction !== 'departure',
    highlightedAircrafts,
    camera: { type: 'fit' },
    persistView: false,
    showLists: false,
    gateLabels: true,
});

SearchResults.showMapHint = false;

// Results stay as the search left the drawer: searched from a raised form, they show raised
SearchResults.drawer = 'keep';

export default SearchResults;
