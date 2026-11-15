import Dropdown from './Dropdown';

const flightLinks = ({ airlineIata, airlineIcao, flightNumber }, departure, arrival) => {
    const iataFlight = `${airlineIata}${flightNumber}`.toLowerCase();
    const icaoFlight = `${airlineIcao}${flightNumber}`.toLowerCase();

    return [
        ['Simbrief', `https://dispatch.simbrief.com/options/custom?orig=${departure}&dest=${arrival}&airline=${airlineIcao}&fltnum=${flightNumber}&utm_campaign=where2fly.today`],
        ['Flightradar24', `https://www.flightradar24.com/data/flights/${iataFlight}?utm_campaign=where2fly.today`],
        ['FlightAware', `https://www.flightaware.com/live/flight/${icaoFlight}?utm_campaign=where2fly.today`],
        ['AirNavRadar', `https://www.airnavradar.com/data/flights/${iataFlight}?utm_campaign=where2fly.today`],
    ];
};

// A flight's callsign, opening links to plan or track it. `departure`/`arrival` are ICAO codes.
const FlightMenu = ({ flight, departure, arrival, before }) => (
    <Dropdown className="dropdown fs-6 text-info link-underline-info link-underline-opacity-25-hover ps-0"
        toggleClassName="btn btn-xs btn-xs-light" label={flight.callsign} before={before}>
        {flightLinks(flight, departure, arrival).map(([label, href]) => (
            <li key={label}><a className="dropdown-item" href={href} target="_blank" rel="noreferrer">{label}</a></li>
        ))}
    </Dropdown>
);

export default FlightMenu;
