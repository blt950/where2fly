import { createContext } from "react";

export const MapContext = createContext({
    airports: [],
    setAirports: undefined,
    focusAirport: undefined,
    highlightedAircrafts: [],
    initialCard: null,
    // Opens a URL: a client-side visit in the Inertia shell, a page load on Blade pages
    navigate: (url) => window.location.assign(url),
    primaryAirport: undefined,
    reverseDirection: undefined,
    setFocusAirport: undefined,
    setShowAirportIdCard: undefined,
    userAuthenticated: undefined,
});