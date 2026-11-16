const PAYWARE = { 1: ['bg-info', 'Payware'], 0: ['bg-success', 'Freeware'] };

// What an airport already has, above the contribution and review forms. A row from
// SceneryController::existingSceneries(); `current` is the scenery under review.
const ExistingSceneries = ({ sceneries }) => (
    <div className="alert alert-warning mb-3">
        <b><i className="fa-sharp fa-info-circle"></i> These sceneries are already in the database</b>
        {sceneries.map((scenery) => {
            const [tone, label] = PAYWARE[scenery.payware] ?? ['bg-danger', 'Included'];

            return (
                <div key={scenery.id}>
                    <span className="badge bg-dark">{scenery.simulator}</span>{' '}
                    <span className={`badge ${tone}`}>{label}</span>{' '}
                    {scenery.developer}
                    {scenery.current ? <b> (This review)</b> : !scenery.published && ' (Awaiting review)'}
                </div>
            );
        })}
    </div>
);

export default ExistingSceneries;
