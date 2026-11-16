// The selected airport lives in the URL (?airport=ICAO), so Back closes the card and a link can
// open one. Rewriting the query this way encodes it exactly as a native form submit does.
export const AIRPORT_PARAM = 'airport';

const parse = (url) => new URL(url, window.location.origin);

export const selectedAirportIn = (url) => parse(url).searchParams.get(AIRPORT_PARAM);

export const withSelectedAirport = (url, icao) => {
    const parsed = parse(url);

    if (icao) {
        parsed.searchParams.set(AIRPORT_PARAM, icao);
    } else {
        parsed.searchParams.delete(AIRPORT_PARAM);
    }

    return parsed.pathname + parsed.search + parsed.hash;
};
