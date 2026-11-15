import { createContext, useContext } from 'react';

// What a page may do with the shell's map: read and change the focused airport, and ping one.
// An ICAO the map can't find is the shell's to report (the alerts), not the page's.
export const ShellMapContext = createContext({
    focusAirport: null,
    setFocusAirport: () => {},
    pingAirport: () => {},
});

export const useShellMap = () => useContext(ShellMapContext);
