import { useEffect, useRef, useState } from 'react';

import { MAP_THEMES } from './mapConfig';

const LAYERS = [
    { value: 'terminator', label: 'Day & night', icon: 'fa-moon' },
    { value: 'terrain', label: 'Terrain relief', icon: 'fa-mountains' },
    { value: 'weather', label: 'Precipitation', icon: 'fa-cloud-rain' },
];

const THEMES = Object.entries(MAP_THEMES).map(([value, { label }]) => ({ value, label }));

const PROJECTIONS = [
    { value: 'globe', label: '3D globe', icon: 'fa-earth-europe' },
    { value: 'mercator', label: '2D map', icon: 'fa-map' },
];

const PANEL_ID = 'map-controls-panel';

const WEATHER_STATUS = {
    loading: { icon: 'fa-circle-notch fa-spin', title: 'Loading radar' },
    error: { icon: 'fa-triangle-exclamation', title: 'Radar unavailable' },
};

// One row of the panel: a checkbox when it stands alone, a radio when it belongs to a group.
const Option = ({ id, name, label, icon, colour, checked, onChange, children }) => (
    <div className="form-check gap-1">
        <input
            className="form-check-input"
            type={name ? 'radio' : 'checkbox'}
            name={name}
            id={id}
            checked={checked}
            onChange={onChange}
        />
        <label className="form-check-label" htmlFor={id}>
            {icon && <i className={`fa-sharp ${icon}`} aria-hidden="true"></i>}
            {colour && <i className="fa-sharp fa-circle" aria-hidden="true" style={{ color: colour }}></i>}
            {' '}{label}
        </label>
        {children}
    </div>
);

const MapControls = ({ preferences, onChange, weatherStatus, lists }) => {

    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);
    const toggleRef = useRef(null);
    const showsFault = preferences.weather && weatherStatus === 'error';
    const status = preferences.weather ? WEATHER_STATUS[weatherStatus] : null;

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onPointerDown = (event) => {
            if (!containerRef.current?.contains(event.target)) { setOpen(false); }
        };
        const onKeyDown = (event) => {
            // Escape is only reachable from inside the panel, so focus has to go back to the
            // toggle rather than falling to the body.
            if (event.key === 'Escape') {
                setOpen(false);
                toggleRef.current?.focus();
            }
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
                ref={toggleRef}
                className={`map-controls-toggle${showsFault ? ' map-controls-toggle--fault' : ''}`}
                aria-expanded={open}
                aria-controls={PANEL_ID}
                aria-label="Map layers and projection"
                title="Map layers and projection"
                onClick={() => setOpen((wasOpen) => !wasOpen)}
            >
                <i className="fa-sharp fa-layer-group" aria-hidden="true"></i>
            </button>

            {open && (
                <div className="map-controls-panel" id={PANEL_ID}>
                    <fieldset>
                        <legend>Layers</legend>
                        {LAYERS.map(({ value, label, icon }) => (
                            <Option
                                key={value}
                                id={`map-layer-${value}`}
                                label={label}
                                icon={icon}
                                checked={preferences[value]}
                                onChange={() => onChange({ ...preferences, [value]: !preferences[value] })}
                            >
                                {value === 'weather' && status && (
                                    <span
                                        className={`map-controls-status map-controls-status--${weatherStatus}`}
                                        title={status.title}
                                        aria-label={status.title}
                                        role="status"
                                    >
                                        <i className={`fa-sharp ${status.icon}`} aria-hidden="true"></i>
                                    </span>
                                )}
                            </Option>
                        ))}
                    </fieldset>

                    {lists.length > 0 && (
                        <fieldset>
                            <legend>My lists</legend>
                            {lists.map(({ id, name, color }) => (
                                <Option
                                    key={id}
                                    id={`map-list-${id}`}
                                    label={name}
                                    colour={color}
                                    checked={preferences.lists?.[id] !== false}
                                    onChange={() => onChange({
                                        ...preferences,
                                        lists: { ...preferences.lists, [id]: preferences.lists?.[id] === false },
                                    })}
                                />
                            ))}
                        </fieldset>
                    )}

                    {[['theme', 'Colours', THEMES], ['projection', 'Projection', PROJECTIONS]].map(([key, legend, options]) => (
                        <fieldset key={key}>
                            <legend>{legend}</legend>
                            {options.map(({ value, label, icon }) => (
                                <Option
                                    key={value}
                                    id={`map-${key}-${value}`}
                                    name={`map-${key}`}
                                    label={label}
                                    icon={icon}
                                    checked={preferences[key] === value}
                                    onChange={() => onChange({ ...preferences, [key]: value })}
                                />
                            ))}
                        </fieldset>
                    ))}
                </div>
            )}
        </div>
    );
};

export default MapControls;
