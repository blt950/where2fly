import React from 'react';
import ReactDOM from 'react-dom/client';
import AirportCard from './AirportCard';

function PopupContainer({ airportId }) {
    return (
        <div className="popup-container">
            {airportId && <AirportCard airportId={airportId} />}
        </div>
    );
}

export default PopupContainer;
