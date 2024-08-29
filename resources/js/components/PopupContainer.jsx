import React from 'react';
import ReactDOM from 'react-dom/client';

function PopupContainer() {
    return (
        <div className="card">
            hello
        </div>
    );
}

export default PopupContainer;

const root = ReactDOM.createRoot(document.getElementById('popup-container'));
root.render(
    <PopupContainer/>
);
