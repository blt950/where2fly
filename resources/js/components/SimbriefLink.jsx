import React from 'react';

const SimbriefLink = ({ direction, primaryIcao, secondaryIcao }) => {
    const simbriefUrl = `orig=${direction === 'departure' ? primaryIcao : secondaryIcao}&dest=${direction === 'departure' ? secondaryIcao : primaryIcao}`;

    return (
        <>
            {direction ? (
                <a className="btn btn-outline-primary btn-sm font-work-sans" href={`https://dispatch.simbrief.com/options/custom?${simbriefUrl}`} target="_blank">
                    <span>SimBrief</span> <i className="fas fa-up-right-from-square"></i>
                </a>
            ) : (
                <a className="btn btn-outline-primary btn-sm font-work-sans" href={`https://dispatch.simbrief.com/options/custom?dest=${primaryIcao}`} target="_blank">
                    <span>SimBrief</span> <i className="fas fa-up-right-from-square"></i>
                </a>
            )}
        </>
    );
};

export default SimbriefLink;