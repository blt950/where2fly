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
                .catch(error => console.error(error.message));
        }
    }, [airportId]);

    return (
        <div className="popup-card">
        {data ? (
            <>
                <div className="d-flex justify-content-between">
                    <h2>Scenery</h2>
                    <button className="btn-close" aria-label="Close scenery card" onClick={() => setShowSceneryIdCard(null)}></button>
                </div>

                {!data.sceneries.length ? (
                    <p>No scenery available</p>
                ) : (
                    data.sceneries.map((scenery) => (
                        <a key={scenery.id} href={scenery.link} className="d-block btn btn-outline-light font-work-sans text-start mt-2" target="_blank">

                            {scenery.simulators.map((simulator) => (
                                <span key={simulator.id} className="badge bg-blue me-1">
                                    {simulator.shortened_name}
                                </span>
                            ))}

                            {scenery.payware === -1 ? (
                                <span className="badge bg-danger">Included</span>
                            ) : scenery.payware === 0 ? (
                                <span className="badge bg-success">Freeware</span>
                            ) : (
                                <span className="badge bg-info">Payware</span>
                            )}
                            &nbsp;{scenery.author} <i className="fas fa-up-right-from-square float-end pt-1"></i>
                        </a>
                    ))
                )}

                <a href={route('scenery.create', {airport: airportId})} className="btn btn-outline-primary btn-sm font-work-sans mt-2" target="_blank">
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