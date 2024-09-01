import React from 'react';
import AirportCard from './AirportCard';

function PopupContainer({ airportId }) {
    return (
        <div className="popup-container">
            {airportId && <AirportCard airportId={airportId} />}
        </div>
    );
}

export default PopupContainer;
