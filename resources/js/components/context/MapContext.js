import { createContext } from "react";

export const MapContext = createContext({
    airports: [],
    focusAirport: undefined,
    setFocusAirport: undefined,
    setAirportId: undefined,
    setShowAirportCard: undefined
});