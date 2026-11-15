import { Link, usePage } from '@inertiajs/react';

// The compact layout's main navigation. The three map pages are tabs; everything the header
// carries besides them sits behind "More". layouts/bottomnav.blade.php is its Blade twin.
// `onNavigate` lets the shell bring a collapsed drawer back, even when the tab is the page already open
const BottomNav = ({ onNavigate }) => {
    const { auth, unreadFeedback, hasNotifications, appVersion } = usePage().props;
    const user = auth?.user;
    const is = (...names) => names.some((name) => route().current(name));

    const tab = (href, active, icon, label) => (
        <Link href={href} className="bottom-nav-tab" aria-current={active ? 'page' : undefined} onClick={onNavigate}>
            <i className={`fa-sharp ${icon}`} aria-hidden="true"></i>
            <span>{label}</span>
        </Link>
    );

    // Pages already in the shell are client-side visits; the rest are ordinary page loads
    const item = (href, active, label, { inShell = false, external = false } = {}) => {
        const props = { className: `dropdown-item${active ? ' active' : ''}`, href, 'aria-current': active ? 'page' : undefined };

        return (
            <li>
                {inShell
                    ? <Link {...props} onClick={onNavigate}>{label}</Link>
                    : <a {...props} {...(external ? { target: '_blank', rel: 'noopener' } : {})}>{label}</a>}
            </li>
        );
    };

    const moreActive = is('feedback*', 'user.account', 'list.*', 'admin', 'login', 'register', 'changelog', 'privacy', 'api');

    return (
        <nav className="bottom-nav" aria-label="Main">
            {tab(route('front'), is('front*', 'search*'), 'fa-search', 'Search')}
            {tab(route('top'), is('top*'), 'fa-list', 'Top List')}
            {tab(route('scenery'), is('scenery', 'scenery.filtered'), 'fa-map', 'Scenery')}

            {/* Static display, not Popper: a transform-positioned menu over the drawer was composited
                as a black block across the map in Chromium */}
            <div className="dropup bottom-nav-more">
                <button type="button" className="bottom-nav-tab" data-bs-toggle="dropdown" data-bs-display="static"
                    aria-expanded="false" aria-current={moreActive ? 'page' : undefined}>
                    <i className={`fa-sharp ${hasNotifications || unreadFeedback ? 'fa-bell text-warning' : 'fa-bars'}`} aria-hidden="true"></i>
                    <span>More</span>
                </button>
                <ul className="dropdown-menu dropdown-menu-end">
                    {item(route('feedback'), is('feedback*'), `Feedback${unreadFeedback ? ' (new)' : ''}`)}
                    {user ? (
                        <>
                            {item(route('user.account'), is('user.account'), `Settings (${user.username})`)}
                            {item(route('list.index'), is('list.*'), 'My Lists')}
                            {user.isAdmin && item(route('admin'), is('admin'), `Admin${hasNotifications ? ` (${hasNotifications})` : ''}`)}
                            {item(route('user.logout'), false, 'Logout')}
                        </>
                    ) : item(route('login'), is('login', 'register'), 'Login')}
                    <li><hr className="dropdown-divider" /></li>
                    {item('https://discord.gg/UkFg9Yy4gP', false, 'Discord', { external: true })}
                    {item('https://github.com/blt950/where2fly', false, 'GitHub', { external: true })}
                    {item('https://ko-fi.com/X7X21V3MEX', false, 'Buy me a coffee', { external: true })}
                    <li><hr className="dropdown-divider" /></li>
                    {item(route('changelog'), is('changelog'), 'Changelog', { inShell: true })}
                    {item(route('privacy'), is('privacy'), 'Privacy Policy', { inShell: true })}
                    {item(route('api'), is('api'), 'API', { inShell: true })}
                    <li><span className="dropdown-item-text text-white-50 small">{appVersion}</span></li>
                </ul>
            </div>
        </nav>
    );
};

export default BottomNav;
