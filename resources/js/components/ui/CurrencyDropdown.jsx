import Dropdown from './Dropdown';

function CurrencyDropdown({ currencies, currency, setCurrency }) {
    const selected = currencies.find((c) => c.code === currency);

    return (
        <Dropdown className="btn-group" toggleClassName="btn btn-sm mb-2 btn-outline-light" label={`${selected.code} ${selected.symbol}`}>
            {currencies.map((c) => (
                <li key={c.code}><button className="dropdown-item" onClick={() => setCurrency(c.code)}>{c.code} {c.symbol}</button></li>
            ))}
        </Dropdown>
    );
}

export default CurrencyDropdown;
