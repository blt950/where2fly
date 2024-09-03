import { createContext } from "react";

export const CardContext = createContext({
    showFlightsIdCard: null,
    setShowFlightsIdCard: undefined,
    
    showSceneryIdCard: null,
    setShowSceneryIdCard: undefined
});