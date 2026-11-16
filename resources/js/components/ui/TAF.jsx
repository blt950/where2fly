import { useState, useEffect } from 'react';
import { trackInteraction } from '../utils/metrics';

function TAF({ taf, label = 'Show', className = 'btn btn-outline-light btn-sm' }) {
    const [revealed, setRevealed] = useState(false);

    // Hide the TAF again when the focused airport changes
    useEffect(() => {
        setRevealed(false);
    }, [taf]);

    const handleClick = () => {
        trackInteraction('Fetch TAF');

        setRevealed(true);
    };

    return (
        <>
            {revealed ? (
                <>{taf ? taf : 'Not Available'}</>
            ) : (
                <button className={className} onClick={handleClick}>{label}</button>
            )}
        </>
    );
}

export default TAF;
