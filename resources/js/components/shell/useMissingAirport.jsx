import { useState } from 'react';

// An airport the map couldn't find (a typo in ?airport=, the Scenery search box), said once in
// the alerts on any page; gone with the next page or the next airport found
export const useMissingAirport = (component, focusAirport) => {
    const [missing, setMissing] = useState(null);
    const [seen, setSeen] = useState({ component, focus: focusAirport });

    if (component !== seen.component || (focusAirport && focusAirport !== seen.focus)) {
        setSeen({ component, focus: focusAirport });
        setMissing(null);
    }

    const notices = missing
        ? [{ key: 'airport-not-found', variant: 'warning', text: `No airport found for "${missing}". Check the ICAO code and try again.` }]
        : [];

    return { notices, onAirportNotFound: setMissing };
};
