import { useState, useEffect } from 'react';

function TAF({ taf }) {
    const [revealed, setRevealed] = useState(false);

    // Hide the TAF again when the focused airport changes
    useEffect(() => {
        setRevealed(false);
    }, [taf]);

    const handleClick = () => {
        if (window.umami) {
            umami.track('Interactions', { interaction: 'Fetch TAF' });
        }

        setRevealed(true);
    };

    return (
        <>
            {revealed ? (
                <>{taf ? taf : 'Not Available'}</>
            ) : (
                <button className="btn btn-outline-light btn-sm" onClick={handleClick}>Show</button>
            )}
        </>
    );
}

export default TAF;
