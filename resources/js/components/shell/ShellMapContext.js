import { createContext, useContext } from 'react';

// What a page may do with the shell's map: read and change the focused airport, ping one, and
// hear about an ICAO the map could not find ({ icao, at }).
export const ShellMapContext = createContext({
    focusAirport: null,
    setFocusAirport: () => {},
    pingAirport: () => {},
    airportNotFound: null,
});

export const useShellMap = () => useContext(ShellMapContext);
