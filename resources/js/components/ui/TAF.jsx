import { useState, useEffect, useRef } from 'react';

function TAF({ icao }) {
    const [tafReport, setTafReport] = useState(null);
    const tafRef = useRef({});

    // Reset the tafReport when the ICAO code changes
    useEffect(() => {
        // Load the saved TAF report from the ref or null
        setTafReport(tafRef.current[icao] || null);
    }, [icao]);

    const handleClick = () => {
        fetch('https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao=' + icao)
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP error " + response.status);
                }
                return response.text()
            })
            .then(text => {
                if (text === "") {
                    setTafReport('Not Available');
                    tafRef.current[icao] = 'Not Available';
                } else {
                    var lines = text.match(/[^\r\n]+/g);
                    setTafReport(lines[lines.length - 1]);
                    tafRef.current[icao] = lines[lines.length - 1];
                }
            })
            .catch(error => {
                setTafReport('TAF Fetch failed');
            });
    };

    return (
        <>
            {tafReport ? (
                <>{tafReport}</>
            ) : (
                <button className="btn btn-outline-light btn-sm" onClick={handleClick}>Fetch</button>
            )}
        </>
    );
}

export default TAF;
