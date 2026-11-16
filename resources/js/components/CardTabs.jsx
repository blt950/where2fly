// The cards as tabs, for the drawer. u-tabs supplies the tab semantics and keyboard support;
// React drives the selection and renders only the selected card, so the others fetch on demand.
// A tab: { id, icon, label, card, count?, inert? }. An inert tab is dimmed and can't be opened.
const CardTabs = ({ tabs, selected, onSelect, onBack }) => (
    <u-tabs className="card-tabs-host">
        <div className="card-tabs-bar">
            <button type="button" className="card-tabs-back" aria-label="Back to the list" onClick={onBack}>
                <i className="fa-sharp fa-arrow-left" aria-hidden="true"></i>
            </button>
            <u-tablist className="card-tabs" aria-label="Airport details">
                {tabs.map((tab) => (
                    <u-tab key={tab.id} id={`card-tab-${tab.id}`} aria-controls={`card-panel-${tab.id}`}
                        className={tab.inert ? 'card-tab-empty' : undefined} aria-disabled={tab.inert ? 'true' : undefined}
                        aria-selected={selected === tab.id ? 'true' : 'false'} onClick={() => !tab.inert && onSelect(tab.id)}>
                        <i className={`fa-sharp ${tab.icon}`} aria-hidden="true"></i>
                        <span className="card-tab-label">{tab.label}</span>
                        {tab.count !== undefined && tab.count !== null && (
                            <span className="card-tab-count count-pill" aria-label={`${tab.count} available`}>{tab.count}</span>
                        )}
                    </u-tab>
                ))}
            </u-tablist>
        </div>
        {tabs.map((tab) => (
            <u-tabpanel key={tab.id} id={`card-panel-${tab.id}`} className="card-tab-panel">
                {selected === tab.id && tab.card}
            </u-tabpanel>
        ))}
    </u-tabs>
);

export default CardTabs;
