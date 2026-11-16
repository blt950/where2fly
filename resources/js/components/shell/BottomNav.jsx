import { usePage } from '@inertiajs/react';

import NavItem from './NavItem';

// The compact layout's navigation, from NavigationHelper (`nav`). `onNavigate` lets the shell bring a
// collapsed drawer back, even for the page already open
const BottomNav = ({ onNavigate }) => {
    const { main, feedback, account, login, community, info, moreActive, version } = usePage().props.nav;

    const menuItem = (item, label = item.label) => (
        <li key={item.key}>
            <NavItem item={item} className={`dropdown-item${item.active ? ' active' : ''}`} onClick={onNavigate}>{label}</NavItem>
        </li>
    );

    return (
        <nav className="bottom-nav" aria-label="Main">
            {main.map((item) => (
                <NavItem key={item.key} item={item} className="bottom-nav-tab" onClick={onNavigate}>
                    <i className={`fa-sharp ${item.icon}`} aria-hidden="true"></i>
                    <span>{item.label}</span>
                </NavItem>
            ))}

            {/* Static display, not Popper: a transform-positioned menu over the drawer was composited
                as a black block across the map in Chromium */}
            <div className="dropup bottom-nav-more">
                <button type="button" className="bottom-nav-tab" data-bs-toggle="dropdown" data-bs-display="static"
                    aria-expanded="false" aria-current={moreActive ? 'page' : undefined}>
                    <i className={`fa-sharp ${account?.notifications || feedback.unread ? 'fa-bell text-warning' : 'fa-bars'}`} aria-hidden="true"></i>
                    <span>More</span>
                </button>
                <ul className="dropdown-menu dropdown-menu-end">
                    {menuItem(feedback, `${feedback.label}${feedback.unread ? ' (new)' : ''}`)}
                    {account && account.items.map((item) => menuItem(item, {
                        settings: `${item.label} (${account.label})`,
                        admin: `${item.label}${account.notifications ? ` (${account.notifications})` : ''}`,
                    }[item.key] ?? item.label))}
                    {login && menuItem(login)}
                    <li><hr className="dropdown-divider" /></li>
                    {community.map((item) => menuItem(item))}
                    <li><hr className="dropdown-divider" /></li>
                    {info.map((item) => menuItem(item))}
                    <li><span className="dropdown-item-text text-white-50 small">{version}</span></li>
                </ul>
            </div>
        </nav>
    );
};

export default BottomNav;
