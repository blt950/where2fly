import { createContext, useContext } from 'react';

// What a page may do with the shell's map: read and change the focused airport, ping one.
export const ShellMapContext = createContext({
    focusAirport: null,
    setFocusAirport: () => {},
    pingAirport: () => {},
});

export const useShellMap = () => useContext(ShellMapContext);
