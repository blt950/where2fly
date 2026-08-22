import { createContext, useContext } from 'react';

export const MapGLContext = createContext(null);
export const useMapGL = () => useContext(MapGLContext);
