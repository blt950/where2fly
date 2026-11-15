// The umami "Search" event, ported unchanged from searchForm.js: which filters a search used.
const INCLUDE_CHECKED = [
    'scores[METAR_WINDY]', 'scores[METAR_GUSTS]', 'scores[METAR_CROSSWIND]', 'scores[METAR_SIGHT]',
    'scores[METAR_RVR]', 'scores[METAR_CEILING]', 'scores[METAR_FOGGY]', 'scores[METAR_HEAVY_RAIN]',
    'scores[METAR_HEAVY_SNOW]', 'scores[METAR_THUNDERSTORM]', 'metcondition', 'scores[VATSIM_ATC]',
    'scores[VATSIM_EVENT]', 'scores[VATSIM_POPULAR]', 'destinationWithRoutesOnly', 'destinationRunwayLights',
    'destinationAirbases', 'flightDirection',
];

const SLIDER_RANGES = {
    hours: ['airtimeMin', 'airtimeMax'],
    distance: ['distanceMin', 'distanceMax'],
    temperature: ['temperatureMin', 'temperatureMax'],
    elevation: ['elevationMin', 'elevationMax'],
    rwyLength: ['rwyLengthMin', 'rwyLengthMax'],
};

const MULTISELECT_DEFAULTS = {
    destinations: ['Anywhere'],
    arrivalWhitelists: ['None'],
    departureWhitelists: ['None'],
    destinationExclusions: ['None'],
    airlines: ['Any'],
    aircrafts: ['Any'],
};

const MULTISELECTS = ['destinations[]', 'arrivalWhitelists[]', 'departureWhitelists[]', 'destinationExclusions[]', 'airlines[]', 'aircrafts[]'];

export const trackSearch = (form) => {
    if (!window.umami) {
        return;
    }

    const props = {};
    const multiselect = {};

    Array.from(form.elements).forEach((element) => {
        if (element.name === 'icao') props.icao = element.value || 'Random';
        if (element.name === 'codeletter') props.codeletter = element.value;
        if (element.name === 'sortByWeather') props.sortByWeather = element.checked;
        if (element.name === 'sortByATC') props.sortByATC = element.checked;

        if (MULTISELECTS.includes(element.name)) {
            multiselect[element.name.replace('[]', '')] = element.disabled ? [] : Array.from(element.selectedOptions).map((option) => option.value);
        }

        if (INCLUDE_CHECKED.includes(element.name) && element.checked) {
            props[element.name.replace(/^scores\[(.*)\]$/, '$1')] = element.value;
        }
    });

    const additions = Object.fromEntries(Object.entries(MULTISELECT_DEFAULTS)
        .map(([key, fallback]) => [key, multiselect[key]?.length ? multiselect[key] : fallback]));

    Object.entries(SLIDER_RANGES).forEach(([key, [min, max]]) => {
        if (form.elements[min] && form.elements[max]) {
            props[key] = `${form.elements[min].value}-${form.elements[max].value}`;
        }
    });

    umami.track('Search', { filter: { ...props, ...additions } });
};
