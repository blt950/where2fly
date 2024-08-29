import React from 'react';
import ReactDOM from 'react-dom/client';
import AirportCard from './AirportCard';

function PopupContainer({ showAirportCard }) {
    return (
        <div className="popup-container">
            {showAirportCard && <AirportCard />}
        </div>
    );
}

export default PopupContainer;
