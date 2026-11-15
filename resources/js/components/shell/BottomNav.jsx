import { Link, usePage } from '@inertiajs/react';

// The compact layout's main navigation. The three map pages are tabs; everything the header
// carries besides them sits behind "More".
const BottomNav = () => {
    const { auth, unreadFeedback, hasNotifications, appVersion } = usePage().props;
    const user = auth?.user;
    const is = (name) => route().current(name);

    const tab = (href, active, icon, label) => (
        <Link href={href} className="bottom-nav-tab" aria-current={active ? 'page' : undefined}>
            <i className={`fa-sharp ${icon}`} aria-hidden="true"></i>
            <span>{label}</span>
        </Link>
    );

    return (
        <nav className="bottom-nav" aria-label="Main">
            {tab(route('front'), is('front*') || is('search*'), 'fa-search', 'Search')}
            {tab(route('top'), is('top*'), 'fa-list', 'Top List')}
            {tab(route('scenery'), is('scenery') || is('scenery.filtered'), 'fa-map', 'Scenery')}

            {/* Static display, not Popper: a transform-positioned menu over the drawer was composited
                as a black block across the map in Chromium */}
            <div className="dropup bottom-nav-more">
                <button type="button" className="bottom-nav-tab" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i className={`fa-sharp ${hasNotifications || unreadFeedback ? 'fa-bell text-warning' : 'fa-bars'}`} aria-hidden="true"></i>
                    <span>More</span>
                </button>
                <ul className="dropdown-menu dropdown-menu-end">
                    <li><a className="dropdown-item" href={route('feedback')}>Feedback{unreadFeedback ? ' (new)' : ''}</a></li>
                    {user ? (
                        <>
                            <li><a className="dropdown-item" href={route('user.account')}>Settings ({user.username})</a></li>
                            <li><a className="dropdown-item" href={route('list.index')}>My Lists</a></li>
                            {user.isAdmin && (
                                <li><a className="dropdown-item" href={route('admin')}>Admin{hasNotifications ? ` (${hasNotifications})` : ''}</a></li>
                            )}
                            <li><a className="dropdown-item" href={route('user.logout')}>Logout</a></li>
                        </>
                    ) : (
                        <li><a className="dropdown-item" href={route('login')}>Login</a></li>
                    )}
                    <li><hr className="dropdown-divider" /></li>
                    <li><a className="dropdown-item" href="https://discord.gg/UkFg9Yy4gP" target="_blank">Discord</a></li>
                    <li><a className="dropdown-item" href="https://github.com/blt950/where2fly" target="_blank">GitHub</a></li>
                    <li><a className="dropdown-item" href="https://ko-fi.com/X7X21V3MEX" target="_blank">Buy me a coffee</a></li>
                    <li><hr className="dropdown-divider" /></li>
                    <li><a className="dropdown-item" href={route('changelog')}>Changelog</a></li>
                    <li><a className="dropdown-item" href={route('privacy')}>Privacy Policy</a></li>
                    <li><a className="dropdown-item" href={route('api')}>API</a></li>
                    <li><span className="dropdown-item-text text-white-50 small">{appVersion}</span></li>
                </ul>
            </div>
        </nav>
    );
};

export default BottomNav;
