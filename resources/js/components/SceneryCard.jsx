import { useEffect, useState, useRef, useContext } from 'react';
import { CardContext } from './context/CardContext';

function SceneryCard({ airportId }) {
    const dataCache = useRef({});
    const [data, setData] = useState(null);
    const { setShowSceneryIdCard } = useContext(CardContext);

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
                        <u-tablist>
                            {Object.keys(data).map((key) => (
                                <u-tab key={key}>{key}</u-tab>
                            ))}
                        </u-tablist>
                    
                        {Object.keys(data).map((key) => (
                            <u-tabpanel key={key}>
                                {data[key].map((item, index) => (
                                    <div key={index} className="scenery-row">
                                        
                                        <div className="title d-flex flex-row justify-content-between align-items-center">
                                            <div className="d-flex align-items-center gap-2">
                                                <span className="developer">{item.developer}</span>
                                                {(item.fsac && item.ratingAverage > 0) && (
                                                    <span className="star"><i className="far fa-star"></i>{parseFloat(item.ratingAverage).toFixed(1)}</span>
                                                )}
                                            </div>
                                            {item.payware > 0 ? (
                                                <span className="badge bg-info">Payware</span>
                                            ) : (
                                                (item.payware == -1 ? (
                                                    <span className="badge bg-danger">Included</span>
                                                ) : (
                                                    <span className="badge bg-success">Freeware</span>
                                                ))
                                            )}
                                        </div>

                                        {(item.fsac && item.cheapestPrice.EUR > 0) && (
                                            <div className="link">
                                                <a href={item.cheapestLink} target="_blank" className="text-white">
                                                    €{parseFloat(item.cheapestPrice.EUR).toFixed(2)} at {item.cheapestStore}</a> <i className="fas fa-up-right-from-square"></i>
                                            </div>
                                        )}

                                        {(item.fsac && item.cheapestPrice.EUR > 0) ? (
                                            <a href={item.link} target="_blank" className="btn btn-outline-primary btn-sm">See more prices <i className="fas fa-up-right-from-square"></i></a>
                                        ) : (
                                            (item.link == 'https://www.flightsimulator.com/') ? (
                                                <i>Included in the simulator</i>
                                            ) : (
                                                <a href={item.link} target="_blank" className="btn btn-outline-primary btn-sm">{item.linkDomain ? item.linkDomain : 'FS Addon Compare'} <i className="fas fa-up-right-from-square"></i></a>
                                            )
                                        )}
                                        
                                    </div>
                                ))}
                            </u-tabpanel>
                        ))}
                    </u-tabs>
                )}

                <a href={route('scenery.create', {airport: airportId})} className="btn btn-outline-success btn-sm font-work-sans mt-2" target="_blank">
                    <i className="fas fa-plus"></i> Add missing scenery
                </a>
            </>
        ) : (
            <p className="mb-0"><i className="fas fa-spinner-third fa-spin"></i>&nbsp;&nbsp;Loading</p>
        )}
        </div>
    )
}

export default SceneryCard;