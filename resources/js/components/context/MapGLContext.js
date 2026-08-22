import { createContext, useContext } from 'react';

// Holds the maplibregl.Map, but stays null until 'style.load' — consumers can therefore
// assume any map they receive is ready to take addSource/addLayer calls.
export const MapGLContext = createContext(null);

export const useMapGL = () => useContext(MapGLContext);
