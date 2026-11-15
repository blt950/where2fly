import { useContext, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

import AirportCard from './AirportCard';
import FlightsCard from './FlightsCard';
import SceneryCard from './SceneryCard';
import { MapContext } from './context/MapContext';
import { trackInteraction } from './utils/metrics';
import { useMapLayout } from './context/MapLayoutContext';
import { useFlights } from './utils/useFlights';
import { sceneryCount, useScenery } from './utils/useScenery';

const ESCAPE_OWNERS = 'input, textarea, select, [contenteditable], .map-controls, [data-bs-toggle="dropdown"], .dropdown-menu';
const ESCAPE_OWNERS_OPEN = () => Boolean(document.querySelector('.dropdown-menu.show, .modal.show'));

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
    // airport, then the cards altogether. Not while something else owns Esc (a field, a menu, the map controls).
    const onEscape = useRef(null);
    onEscape.current = () => {
        if (compact ? cards.tab !== 'airport' : cards.scenery || cards.flights) {
            update(compact ? { tab: 'airport' } : cards.scenery ? { scenery: false } : { flights: false });
        } else {
            closeAll();
        }
    };

    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.key !== 'Escape' || event.defaultPrevented || ESCAPE_OWNERS_OPEN()) return;
            if (event.target instanceof Element && event.target.closest(ESCAPE_OWNERS)) return;

            onEscape.current();
        };

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);

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

        // u-tabs supplies the tab semantics and keyboard support; React drives the selection
        // through aria-selected and renders only the selected panel, so Flights fetches on demand
        const tabs = [
            { id: 'airport', icon: 'fa-tower-control', label: focused?.icao ?? 'Airport' },
            // No flights is nothing to open: dimmed and inert, as the desktop button is. No scenery
            // still opens, for "Add missing scenery", and just shows no count.
            ...(onRoute ? [{ id: 'flights', icon: 'fa-plane-up', label: 'Flights', count: flightsTotal, inert: flightsTotal === 0 }] : []),
            { id: 'scenery', icon: 'fa-map', label: 'Scenery', count: sceneryTotal || null },
        ];
        const backToAirport = () => update({ tab: 'airport' });

        return createPortal(
            <u-tabs className="card-tabs-host">
                <div className="card-tabs-bar">
                    <button type="button" className="card-tabs-back" aria-label="Back to the list" onClick={closeAll}>
                        <i className="fa-sharp fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <u-tablist className="card-tabs" aria-label="Airport details">
                        {tabs.map((tab) => (
                            <u-tab key={tab.id} id={`card-tab-${tab.id}`} aria-controls={`card-panel-${tab.id}`}
                                className={tab.inert ? 'card-tab-empty' : undefined} aria-disabled={tab.inert ? 'true' : undefined}
                                aria-selected={cards.tab === tab.id ? 'true' : 'false'} onClick={() => !tab.inert && update({ tab: tab.id })}>
                                <i className={`fa-sharp ${tab.icon}`} aria-hidden="true"></i>
                                <span className="card-tab-label">{tab.label}</span>
                                {tab.count !== undefined && tab.count !== null && (
                                    <span className="card-tab-count count-pill" aria-label={`${tab.count} available`}>{tab.count}</span>
                                )}
                            </u-tab>
                        ))}
                    </u-tablist>
                </div>
                {tabs.map((tab) => (
                    <u-tabpanel key={tab.id} id={`card-panel-${tab.id}`} className="card-tab-panel">
                        {cards.tab === tab.id && tab.id === 'airport' && airportCard}
                        {cards.tab === tab.id && tab.id === 'flights' && flightsCard(backToAirport)}
                        {cards.tab === tab.id && tab.id === 'scenery' && sceneryCard(backToAirport)}
                    </u-tabpanel>
                ))}
            </u-tabs>,
            cardSlot,
        );
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
