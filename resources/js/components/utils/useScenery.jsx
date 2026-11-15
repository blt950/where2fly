import { useCachedPost } from './useCachedPost';

// The endpoint answers 404 when an airport has no sceneries: that's an empty result, not an error
export const useScenery = (icao, enabled = true) =>
    useCachedPost(route('api.airport.scenery'), icao ? { airportIcao: icao } : null, { enabled, emptyOn404: true });

// Distinct developers across every simulator: one scenery sold for two simulators counts once
export const sceneryCount = (data) => new Set(Object.values(data ?? {}).flat().map((item) => item.developer)).size;
