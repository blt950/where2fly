import { useContext, useState } from 'react';
import { createPortal } from 'react-dom';

import AirportCard from './AirportCard';
import FlightsCard from './FlightsCard';
import SceneryCard from './SceneryCard';
import { MapContext } from './context/MapContext';
import { useMapLayout } from './context/MapLayoutContext';

const track = (interaction) => window.umami?.track('Interactions', { interaction });

const freshCards = (airportId, initialCard) => ({
    airportId,
    flights: false,
    airline: null,
    scenery: initialCard === 'scenery',
    tab: initialCard === 'scenery' ? 'scenery' : 'airport',
});

// Which cards sit beside the selected airport: the airport card always; flights only on a route
// (a primary airport); scenery on request, or straight away where the scene asks for it. The
// card bodies are shared; only the wrapper differs: a stack beside the panel on wide screens,
// tabs inside the drawer (portalled into its card slot) on the compact layout.
function CardHost({ airportId }) {

    const { findAirport, focusAirport, primaryAirport, reverseDirection, initialCard, setFocusAirport, setShowAirportIdCard } = useContext(MapContext);
    const { compact, cardSlot } = useMapLayout();
    const [cards, setCards] = useState(() => freshCards(null, initialCard));

    // A new airport starts from the scene's default cards. Adjusted during render so the first
    // frame for the new airport never shows the previous one's flights or scenery.
    if (cards.airportId !== airportId) {
        setCards(freshCards(airportId, initialCard));
    }

    const focused = findAirport(focusAirport);
    const primary = primaryAirport ? findAirport(primaryAirport) : null;
    const [departure, arrival] = reverseDirection ? [focused, primary] : [primary, focused];
    const onRoute = Boolean(primary && focused);

    const update = (changes) => setCards((current) => ({ ...current, ...changes }));

    const openFlights = (airline) => {
        track('Open flights card');
        update({ flights: true, airline, tab: 'flights' });
    };

    const openScenery = () => {
        track('Open scenery card');
        update({ scenery: true, tab: 'scenery' });
    };

    // focusAirport is cleared too, so clicking the same marker again reopens the card
    const closeAll = () => {
        setShowAirportIdCard(null);
        setFocusAirport(null);
    };

    const airportCard = <AirportCard airportId={airportId} onOpenFlights={openFlights} onOpenScenery={openScenery} onClose={closeAll} />;
    const flightsCard = (onClose) => onRoute && (
        <FlightsCard departureAirportId={departure.id} arrivalAirportId={arrival.id} airline={cards.airline}
            onAirlineChange={(airline) => update({ airline })} onClose={onClose} />
    );
    const sceneryCard = (onClose) => focused && <SceneryCard airportId={focused.icao} onClose={onClose} />;

    if (compact) {
        if (!cardSlot) {
            return null;
        }

        // One stable destination per tab; a card's own close button returns to the airport
        const tabs = [
            { id: 'airport', label: focused?.icao ?? 'Airport' },
            ...(onRoute ? [{ id: 'flights', label: 'Flights' }] : []),
            { id: 'scenery', label: 'Scenery' },
        ];
        const backToAirport = () => update({ tab: 'airport' });

        return createPortal(
            <div className="card-tabs-host">
                <div className="card-tabs" role="tablist" aria-label="Airport details">
                    <button type="button" className="card-tabs-back" aria-label="Back to the list" onClick={closeAll}>
                        <i className="fa-sharp fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    {tabs.map((tab) => (
                        <button key={tab.id} type="button" role="tab" id={`card-tab-${tab.id}`} aria-controls="card-tab-panel"
                            aria-selected={cards.tab === tab.id} onClick={() => update({ tab: tab.id })}>
                            {tab.label}
                        </button>
                    ))}
                </div>
                <div id="card-tab-panel" role="tabpanel" aria-labelledby={`card-tab-${cards.tab}`}>
                    {cards.tab === 'airport' && airportCard}
                    {cards.tab === 'flights' && flightsCard(backToAirport)}
                    {cards.tab === 'scenery' && sceneryCard(backToAirport)}
                </div>
            </div>,
            cardSlot,
        );
    }

    return (
        <div className="popup-container">
            <div className="popup-scroll">
                {airportCard}
                {cards.flights && flightsCard(() => update({ flights: false }))}
                {cards.scenery && sceneryCard(() => update({ scenery: false }))}
            </div>
        </div>
    );
}

export default CardHost;
