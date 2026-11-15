import { usePage } from '@inertiajs/react';

// The hint over the map from parts/map.blade.php.
const MapHint = () => {
    const user = usePage().props.auth?.user;

    if (!user) {
        return (
            <div className="hint">
                <i className="fa-sharp fa-lightbulb-on"></i> Create an account to fill the map with your own scenery list.
            </div>
        );
    }

    if (!user.hasReadFeedback) {
        return (
            <div className="hint hint-feedback">
                <i className="fa-sharp fa-box-ballot"></i> Have your say on what get built next on our new feedback page.
            </div>
        );
    }

    if (user.listsCount === 0) {
        return (
            <div className="hint">
                <i className="fa-sharp fa-lightbulb-on"></i> Create a scenery list to fill the map with your own data.
            </div>
        );
    }

    return null;
};

export default MapHint;
