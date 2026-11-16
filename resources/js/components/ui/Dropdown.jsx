// A Bootstrap dropdown positioned by CSS instead of Popper. Popper's transformed menu, over the
// phone drawer, was composited as a black block across the map (Chromium). One behaviour everywhere.
const Dropdown = ({ label, className = 'dropdown', toggleClassName, menuClassName, before, children }) => (
    <div className={className}>
        {before}
        <button className={`${toggleClassName} dropdown-toggle`} type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
            {label}
        </button>
        <ul className={menuClassName ? `dropdown-menu ${menuClassName}` : 'dropdown-menu'}>{children}</ul>
    </div>
);

export default Dropdown;
