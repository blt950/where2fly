import { useEffect, useState, useRef } from 'react';
import CurrencyDropdown from './ui/CurrencyDropdown';

import { useScenery } from './utils/useScenery';
import { useTooltips } from './utils/useTooltips';

function SceneryCard({ airportId, onClose }) {
    const cardRef = useRef(null);
    const tabsRef = useRef(null);
    const data = useScenery(airportId);
    const [selectedSimulator, setSelectedSimulator] = useState(null);
    const [currency, setCurrency] = useState(localStorage.getItem('currency') || 'EUR');

    const currencies = [
        { code: 'EUR', symbol: '€' },
        { code: 'USD', symbol: '$' },
        { code: 'GBP', symbol: '£' },
        { code: 'AUD', symbol: 'A$' },
        { code: 'CAD', symbol: 'C$' },
    ]

    useTooltips(cardRef, [data]);

    // Keep the chosen simulator selected between airports.
    useEffect(() => {
        if (!data || !selectedSimulator || !tabsRef.current) return;

        const index = Object.keys(data).indexOf(selectedSimulator);
        if (index >= 0) tabsRef.current.selectedIndex = index;
    }, [data, selectedSimulator]);

    useEffect(() => {
        localStorage.setItem('currency', currency);
    }, [currency]);

    return (
        <div className="popup-card" ref={cardRef}>
        {data !== null ? (
            <>
                {/* The drawer's Scenery tab already names the card, so this header hides there */}
                <div className="d-flex justify-content-between card-header-row">
                    <h2>Scenery</h2>
                    <button className="btn-close" aria-label="Close scenery card" onClick={onClose}></button>
                </div>

                {Object.keys(data).length === 0 ? (
                    <p>No scenery available</p>
                ) : (
                    <u-tabs ref={tabsRef} key={Object.keys(data).join('|')}>
                        <div className="d-flex flex-row justify-content-between">
                            <u-tablist>
                                {Object.keys(data).map((key, index) => (
                                    <u-tab key={key} aria-selected={index === 0 ? "true" : "false"} aria-controls={key} onClick={() => setSelectedSimulator(key)}>{key}</u-tab>
                                ))}
                            </u-tablist>

                            <CurrencyDropdown currencies={currencies} currency={currency} setCurrency={setCurrency}/>
                        </div>
                    
                        {Object.keys(data).map((key) => (
                            <u-tabpanel id={key} key={key}>
                                {data[key].map((item, index) => (
                                    <a href={item.link} target="_blank" key={index} className="scenery-row" data-scenery-id={item.id}>
                                        <div>
                                            <div className="title d-flex flex-row justify-content-between align-items-center">
                                                <div className="d-flex align-items-center flex-wrap">
                                                    <span className="developer">{item.developer}</span>
                                                </div>
                                            </div>

                                            {(item.fsac && item.cheapestPrice.EUR > 0) && (
                                                <p className="mb-1" style={{marginTop: '-5px'}}>Starting at {currencies.find(c => c.code === currency).symbol}{parseFloat(item.cheapestPrice[currency]).toFixed(2)}</p>
                                            )}

                                            {item.payware > 0 ? (
                                                <span className="badge bg-info">Payware
                                                    {(item.fsac && item.ratingAverage > 0) && (
                                                        <>
                                                        &nbsp;
                                                        <span className="star"><i className="fa-sharp fa-star"></i>{parseFloat(item.ratingAverage).toFixed(1)}</span>
                                                        </>
                                                    )}
                                                </span>
                                            ) : (
                                                (item.payware == -1 ? (
                                                    <span className="badge bg-danger">Included in simulator</span>
                                                ) : (
                                                    <span className="badge bg-success">Freeware</span>
                                                ))
                                            )}
                                        </div>
                                        <div className="d-flex align-items-center">
                                            {(item.fsac ? (
                                                <>
                                                See prices
                                                <i className="fa-sharp fa-solid fa-chevron-right fs-5"></i>
                                                </>
                                            ) : (
                                                (item.payware != -1 && (
                                                    <>
                                                    { item.linkDomain }
                                                    <i className="fa-sharp fa-solid fa-chevron-right fs-5"></i>
                                                    </>
                                                ))
                                            ))}
                                        </div>
                                    </a>
                                ))}
                            </u-tabpanel>
                        ))}
                    </u-tabs>
                    
                )}

                <div className="d-flex flex-row justify-content-between align-items-end flex-wrap">
                    <span className="pb-1">
                        Prices are excl. tax.
                    </span>
                    <a href={route('scenery.create', {airport: airportId})} className="btn btn-outline-success btn-sm mt-3" target="_blank">
                        <i className="fa-sharp fa-plus"></i> Add missing scenery
                    </a>
                </div>
            </>
        ) : (
            <p className="mb-0"><i className="fa-sharp fa-spinner-third fa-spin"></i>&nbsp;&nbsp;Loading</p>
        )}
        </div>
    )
}

export default SceneryCard;