import { createContext } from "react";

export const MapContext = createContext({
    airports: [],
    setAirports: undefined,
    focusAirport: undefined,
    highlightedAircrafts: [],
    initialCard: null,
    primaryAirport: undefined,
    reverseDirection: undefined,
    setFocusAirport: undefined,
    setShowAirportIdCard: undefined,
    userAuthenticated: undefined,
});