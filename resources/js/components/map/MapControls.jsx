import { useEffect, useRef, useState } from 'react';

const LAYERS = [
    { key: 'terminator', label: 'Day & night', icon: 'fa-moon' },
    { key: 'terrain', label: 'Terrain relief', icon: 'fa-mountains' },
    { key: 'weather', label: 'Precipitation', icon: 'fa-cloud-rain' },
];

const PROJECTIONS = [
    { value: 'globe', label: '3D globe', icon: 'fa-earth-europe' },
    { value: 'mercator', label: '2D map', icon: 'fa-map' },
];

const MapControls = ({ preferences, onChange }) => {

    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

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
                className="map-controls-toggle"
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
