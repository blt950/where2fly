import { useCachedPost } from './useCachedPost';

// Every airline's flights on the route; the card filters by airline itself
export const useFlights = (departureAirportId, arrivalAirportId, highlightedAircrafts, enabled = true) => useCachedPost(
    route('api.airport.flights'),
    departureAirportId && arrivalAirportId ? { departureAirportId, arrivalAirportId, highlightedAircrafts } : null,
    { enabled },
);
