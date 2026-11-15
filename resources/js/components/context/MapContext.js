import { createContext } from "react";

export const MapContext = createContext({
    airports: [],
    setAirports: undefined,
    focusAirport: undefined,
    highlightedAircrafts: [],
    initialCard: null,
    // Opens a URL: the shell's client-side visit; a page load outside one (tests)
    navigate: (url) => window.location.assign(url),
    primaryAirport: undefined,
    reverseDirection: undefined,
    setFocusAirport: undefined,
    setShowAirportIdCard: undefined,
    userAuthenticated: undefined,
});