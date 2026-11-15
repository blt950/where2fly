// React twin of layouts/legend.blade.php: phones have no hover, so the icons are spelled out.
const Legend = ({ scoreTypes }) => (
    <div className="d-block d-sm-none">
        <ul className="ps-0 mt-3" style={{ fontSize: '0.7em', listStyleType: 'none' }}>
            {Object.entries(scoreTypes).map(([reason, type]) => (
                <li key={reason}>
                    <i className={`fa-sharp ${type.icon}`}></i> {type.desc}&nbsp;
                </li>
            ))}
        </ul>
    </div>
);

export default Legend;
