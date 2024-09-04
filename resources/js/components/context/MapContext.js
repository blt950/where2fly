import { createContext } from "react";

export const MapContext = createContext({
    airports: [],
    focusAirport: undefined,
    highlightedAircrafts: [],
    primaryAirport: undefined,
    reverseDirection: undefined,
    setFocusAirport: undefined,
    setShowAirportIdCard: undefined,
    userAuthenticated: undefined,
});