import { useContext, useState } from 'react';
import { createPortal } from 'react-dom';

import AirportCard from './AirportCard';
import CardTabs from './CardTabs';
import FlightsCard from './FlightsCard';
import SceneryCard from './SceneryCard';
import { MapContext } from './context/MapContext';
import { trackInteraction } from './utils/metrics';
import { useMapLayout } from './context/MapLayoutContext';
import { useEscape } from './utils/useEscape';
import { useFlights } from './utils/useFlights';
import { sceneryCount, useScenery } from './utils/useScenery';

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
// tabs (CardTabs) portalled into the drawer's card slot on the compact layout.
function CardHost({ airportId }) {

    const { findAirport, focusAirport, primaryAirport, reverseDirection, highlightedAircrafts, initialCard, setFocusAirport, setShowAirportIdCard } = useContext(MapContext);
    const { compact, cardSlot, registerCards } = useMapLayout();
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

    // The drawer's tabs and the desktop card's buttons show how many flights and sceneries there
    // are, so they load up front; the cards share the same requests
    const scenery = useScenery(focused?.icao);
    const sceneryTotal = scenery ? sceneryCount(scenery) : null;
    const flights = useFlights(departure?.id, arrival?.id, highlightedAircrafts, onRoute);
    const flightsTotal = flights ? flights.flights?.length ?? 0 : null;

    const update = (changes) => setCards((current) => ({ ...current, ...changes }));

    const openFlights = (airline) => {
        trackInteraction('Open flights card');
        update({ flights: true, airline, tab: 'flights' });
    };

    const openScenery = () => {
        trackInteraction('Open scenery card');
        update({ scenery: true, tab: 'scenery' });
    };

    // focusAirport is cleared too, so clicking the same marker again reopens the card
    const closeAll = () => {
        setShowAirportIdCard(null);
        setFocusAirport(null);
    };

    // Esc does what the nearest close does: the topmost card on the stack, the tab back to the
    // airport, then the cards altogether
    useEscape(() => {
        if (compact ? cards.tab !== 'airport' : cards.scenery || cards.flights) {
            update(compact ? { tab: 'airport' } : cards.scenery ? { scenery: false } : { flights: false });
        } else {
            closeAll();
        }
    });

    // In tabs, each card leaves out what the tabs already cover (headers, close buttons, shortcuts)
    const airportCard = (
        <AirportCard airportId={airportId} onOpenFlights={openFlights} onOpenScenery={openScenery} onClose={closeAll} inTab={compact}
            counts={{ flights: onRoute ? flightsTotal : undefined, scenery: sceneryTotal }} />
    );
    const flightsCard = (onClose) => onRoute && (
        <FlightsCard departureAirportId={departure.id} arrivalAirportId={arrival.id} airline={cards.airline}
            onAirlineChange={(airline) => update({ airline })} onClose={onClose} inTab={compact} />
    );
    const sceneryCard = (onClose) => focused && <SceneryCard airportId={focused.icao} onClose={onClose} inTab={compact} />;

    if (compact) {
        if (!cardSlot) {
            return null;
        }

        const backToAirport = () => update({ tab: 'airport' });
        // No scenery still opens, for "Add missing scenery", and just shows no count. No flights is
        // nothing to open: inert, as the desktop button is.
        const tabs = [
            { id: 'airport', icon: 'fa-tower-control', label: focused?.icao ?? 'Airport', card: airportCard },
            { id: 'scenery', icon: 'fa-map', label: 'Scenery', count: sceneryTotal || null, card: sceneryCard(backToAirport) },
            ...(onRoute ? [{ id: 'flights', icon: 'fa-plane-up', label: 'Flights', count: flightsTotal, inert: flightsTotal === 0, card: flightsCard(backToAirport) }] : []),
        ];
        // The same interactions the desktop card's Flights and Scenery buttons report
        const selectTab = (id) => {
            if (id !== cards.tab && id !== 'airport') trackInteraction(`Open ${id} card`);
            update({ tab: id });
        };

        return createPortal(<CardTabs tabs={tabs} selected={cards.tab} onSelect={selectTab} onBack={closeAll} />, cardSlot);
    }

    return (
        <div className="popup-container" ref={registerCards}>
            <div className="popup-scroll">
                {airportCard}
                {cards.flights && flightsCard(() => update({ flights: false }))}
                {cards.scenery && sceneryCard(() => update({ scenery: false }))}
            </div>
        </div>
    );
}

export default CardHost;
