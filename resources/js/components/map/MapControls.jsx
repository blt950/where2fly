import { useEffect, useRef, useState } from 'react';

const LAYERS = [
    { key: 'terminator', label: 'Day & night', icon: 'fa-moon' },
    { key: 'terrain', label: 'Terrain relief', icon: 'fa-mountains' },
    { key: 'weather', label: 'Precipitation', icon: 'fa-cloud-rain' },
];

// Enough to tell working from not working at a glance; the titles are there for anyone who
// hovers, not as on-screen copy.
const WEATHER_STATUS = {
    loading: { icon: 'fa-circle-notch fa-spin', title: 'Loading radar' },
    live: { icon: 'fa-signal-stream', title: 'Radar is live' },
    error: { icon: 'fa-triangle-exclamation', title: 'Radar unavailable' },
    zoom: { icon: 'fa-magnifying-glass-minus', title: 'Zoom out to see radar' },
};

const PROJECTIONS = [
    { value: 'globe', label: '3D globe', icon: 'fa-earth-europe' },
    { value: 'mercator', label: '2D map', icon: 'fa-map' },
];

const MapControls = ({ preferences, onChange, weatherStatus }) => {

    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

    // The full status lives inside the panel, which is closed most of the time — so a broken
    // feed, and only a broken feed, also marks the collapsed button.
    const showsFault = preferences.weather && weatherStatus === 'error';

    // Standard dropdown dismissal. Without it the panel covers the hint card with no way back.
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onPointerDown = (event) => {
            if (!containerRef.current?.contains(event.target)) { setOpen(false); }
        };
        const onKeyDown = (event) => {
            if (event.key === 'Escape') { setOpen(false); }
        };

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    return (
        <div className="map-controls" ref={containerRef}>
            <button
                type="button"
                className={`map-controls-toggle${showsFault ? ' map-controls-toggle--fault' : ''}`}
                aria-expanded={open}
                aria-label="Map layers and projection"
                title="Map layers and projection"
                onClick={() => setOpen((wasOpen) => !wasOpen)}
            >
                <i className="fa-sharp fa-layer-group" aria-hidden="true"></i>
            </button>

            {open && (
                <div className="map-controls-panel">
                    <fieldset>
                        <legend>Layers</legend>
                        {LAYERS.map(({ key, label, icon }) => (
                            <div className="form-check" key={key}>
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    id={`map-layer-${key}`}
                                    checked={preferences[key]}
                                    onChange={() => onChange({ ...preferences, [key]: !preferences[key] })}
                                />
                                <label className="form-check-label" htmlFor={`map-layer-${key}`}>
                                    <i className={`fa-sharp ${icon}`} aria-hidden="true"></i> {label}
                                </label>
                                {key === 'weather' && preferences.weather && (
                                    <span
                                        className={`map-controls-status map-controls-status--${weatherStatus}`}
                                        title={WEATHER_STATUS[weatherStatus]?.title}
                                        aria-label={WEATHER_STATUS[weatherStatus]?.title}
                                        role="status"
                                    >
                                        <i className={`fa-sharp ${WEATHER_STATUS[weatherStatus]?.icon}`} aria-hidden="true"></i>
                                    </span>
                                )}
                            </div>
                        ))}
                    </fieldset>

                    <fieldset>
                        <legend>Projection</legend>
                        {PROJECTIONS.map(({ value, label, icon }) => (
                            <div className="form-check" key={value}>
                                <input
                                    className="form-check-input"
                                    type="radio"
                                    name="map-projection"
                                    id={`map-projection-${value}`}
                                    checked={preferences.projection === value}
                                    onChange={() => onChange({ ...preferences, projection: value })}
                                />
                                <label className="form-check-label" htmlFor={`map-projection-${value}`}>
                                    <i className={`fa-sharp ${icon}`} aria-hidden="true"></i> {label}
                                </label>
                            </div>
                        ))}
                    </fieldset>
                </div>
            )}
        </div>
    );
};

export default MapControls;
