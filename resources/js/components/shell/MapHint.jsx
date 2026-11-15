import { usePage } from '@inertiajs/react';

// The hint over the map from MapHintHelper (`mapHint`)
const MapHint = () => {
    const hint = usePage().props.mapHint;

    return hint && (
        <div className="hint">
            <i className={`fa-sharp ${hint.icon}`}></i> {hint.text}
        </div>
    );
};

export default MapHint;
