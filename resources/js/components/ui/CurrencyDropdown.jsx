function CurrencyDropdown({ currencies, currency, setCurrency }) {
    return (
        <>
            <div className="btn-group">
                <button className="btn btn-sm mb-2 btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {currencies.find(c => c.code === currency).code} {currencies.find(c => c.code === currency).symbol}
                </button>
                <ul className="dropdown-menu">
                    {currencies.map((c) => (
                        <li key={c.code}><button className="dropdown-item" onClick={() => setCurrency(c.code)}>{c.code} {c.symbol}</button></li>
                    ))}
                </ul>
            </div>
        </>
    );
}

export default CurrencyDropdown;
