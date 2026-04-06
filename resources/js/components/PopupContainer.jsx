import React from 'react';
import AirportCard from './AirportCard';

function PopupContainer({ airportId }) {
    return (
        <div className="popup-container">
            <div className="popup-scroll">
                {airportId && <AirportCard airportId={airportId} />}
            </div>
        </div>
    );
}

export default PopupContainer;
