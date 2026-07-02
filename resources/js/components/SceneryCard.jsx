import { useEffect, useState, useRef, useContext } from 'react';
import { CardContext } from './context/CardContext';
import CurrencyDropdown from './ui/CurrencyDropdown';

import ExternalLinkTracker from './utils/ExternalLinkTracker';
import TooltipRefresh from './utils/TooltipRefresh';

function SceneryCard({ airportId }) {
    const dataCache = useRef({});
    const [data, setData] = useState(null);
    const [currency, setCurrency] = useState(localStorage.getItem('currency') || 'EUR');
    const { setShowSceneryIdCard } = useContext(CardContext);

    const currencies = [
        { code: 'EUR', symbol: '€' },
        { code: 'USD', symbol: '$' },
        { code: 'GBP', symbol: '£' },
        { code: 'AUD', symbol: 'A$' },
        { code: 'CAD', symbol: 'C$' },
    ]

    // Fetch airport data if it's not in the cache
    useEffect(() => {
        if (dataCache.current[airportId]) {
            setData(dataCache.current[airportId]);
        } else {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch(route('api.airport.scenery'), {
                    method: "POST",    
                    credentials: 'include',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ airportIcao: airportId })
                })
                .then(response => response.json())
                .then(data => {
                    dataCache.current[airportId] = data.data;
                    setData(data.data);
                })
                .catch(error => {
                    console.error(error.message)
                    if(error.response.status === 404) {
                        setData(undefined);
                    }
                });
        }
    }, [airportId]);

    useEffect(() => {
        ExternalLinkTracker();
        TooltipRefresh();
    }, [data]);

    useEffect(() => {
        localStorage.setItem('currency', currency);
    }, [currency]);

    return (
        <div className="popup-card">
        {data !== null ? (
            <>
                <div className="d-flex justify-content-between">
                    <h2>Scenery</h2>
                    <button className="btn-close" aria-label="Close scenery card" onClick={() => setShowSceneryIdCard(null)}></button>
                </div>

                {!data ? (
                    <p>No scenery available</p>
                ) : (

                    <u-tabs>
                        <div className="d-flex flex-row justify-content-between">
                            <u-tablist>
                                {Object.keys(data).map((key, index) => (
                                    <u-tab key={key} aria-selected={index === 0 ? "true" : "false"} aria-controls={key}>{key}</u-tab>
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

                <div className="d-flex flex-row justify-content-between align-items-end">
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