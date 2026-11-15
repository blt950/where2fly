import { useContext, useState } from 'react';

import AirportCard from './AirportCard';
import FlightsCard from './FlightsCard';
import SceneryCard from './SceneryCard';
import { MapContext } from './context/MapContext';

const track = (interaction) => window.umami?.track('Interactions', { interaction });

// Which cards are open beside the selected airport. The airport card always is; flights only on a
// route (a primary airport), scenery on request, or straight away where the scene asks for it.
// The bodies are shared, only this wrapper decides the layout: a stack beside the panel for now.
function CardHost({ airportId }) {

    const { findAirport, focusAirport, primaryAirport, reverseDirection, initialCard, setFocusAirport, setShowAirportIdCard } = useContext(MapContext);
    const [cards, setCards] = useState({ airportId: null, flights: false, airline: null, scenery: false });

    // A new airport starts from the scene's default cards. Adjusted during render so the first
    // frame for the new airport never shows the previous one's flights or scenery.
    if (cards.airportId !== airportId) {
        setCards({ airportId, flights: false, airline: null, scenery: initialCard === 'scenery' });
    }

    const focused = findAirport(focusAirport);
    const primary = primaryAirport ? findAirport(primaryAirport) : null;
    const [departure, arrival] = reverseDirection ? [focused, primary] : [primary, focused];

    const openFlights = (airline) => {
        track('Open flights card');
        setCards((current) => ({ ...current, flights: true, airline }));
    };

    const openScenery = () => {
        track('Open scenery card');
        setCards((current) => ({ ...current, scenery: true }));
    };

    // focusAirport is cleared too, so clicking the same marker again reopens the card
    const closeAll = () => {
        setShowAirportIdCard(null);
        setFocusAirport(null);
    };

    return (
        <div className="popup-container">
            <div className="popup-scroll">
                <AirportCard airportId={airportId} onOpenFlights={openFlights} onOpenScenery={openScenery} onClose={closeAll} />

                {cards.flights && primary && focused && (
                    <FlightsCard departureAirportId={departure.id} arrivalAirportId={arrival.id} airline={cards.airline}
                        onAirlineChange={(airline) => setCards((current) => ({ ...current, airline }))}
                        onClose={() => setCards((current) => ({ ...current, flights: false }))} />
                )}

                {cards.scenery && focused && (
                    <SceneryCard airportId={focused.icao} onClose={() => setCards((current) => ({ ...current, scenery: false }))} />
                )}
            </div>
        </div>
    );
}

export default CardHost;
