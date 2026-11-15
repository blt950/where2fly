import { useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

// Pages already in the shell link with <Link> and keep the map; the rest are ordinary loads.
const NavLink = ({ inShell, href, active, children, ...rest }) => {
    const className = `nav-link${active ? ' active' : ''}`;

    return inShell
        ? <Link className={className} href={href} {...rest}>{children}</Link>
        : <a className={className} href={href} {...rest}>{children}</a>;
};

// Port of layouts/menu.blade.php.
const Menu = () => {
    const { url, props } = usePage();
    const { auth, unreadFeedback, hasNotifications, isProduction } = props;
    const user = auth?.user;
    const is = (name) => route().current(name);

    // A full load used to close the phone menu; a client-side visit has to do it itself.
    useEffect(() => {
        bootstrap.Collapse.getInstance(document.getElementById('navbarMenu'))?.hide();
    }, [url]);

    return (
        <>
            <header className="mb-auto">
                <nav className="navbar navbar-expand-xl navbar-dark">
                    <div className="d-flex d-row">
                        <a href={route('front')} className="d-block navbar-brand mb-0 logo">Where2Fly</a>
                        <a className="donate btn btn-sm btn-light" href="https://ko-fi.com/X7X21V3MEX" target="_blank">
                            <img src="/img/kofi_symbol.svg" alt="Coffee cup with a heart" />
                            Buy me a coffee
                        </a>
                    </div>
                    <button className="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                        aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                        <span className="navbar-toggler-icon"></span>
                    </button>
                    <div className="collapse navbar-collapse" id="navbarMenu">
                        <ul className="navbar-nav ms-auto">
                            <li className="nav-item">
                                <NavLink href={route('front')} active={is('front*') || is('search*')}>
                                    <i className="fa-sharp fa-search"></i>&nbsp; Search
                                </NavLink>
                            </li>
                            <li className="nav-item">
                                <NavLink inShell href={route('top')} active={is('top*')}>
                                    <i className="fa-sharp fa-list"></i>&nbsp; Top List
                                </NavLink>
                            </li>
                            <li className="nav-item">
                                <NavLink href={route('scenery')} active={is('scenery*')}>
                                    <i className="fa-sharp fa-map"></i>&nbsp; Scenery
                                </NavLink>
                            </li>
                            <li className="nav-item">
                                <NavLink href={route('feedback')} active={is('feedback*')}>
                                    <i className={`fa-sharp fa-message${unreadFeedback ? ' unread' : ''}`}></i>&nbsp; Feedback
                                </NavLink>
                            </li>

                            {!user && (
                                <li className="nav-item">
                                    <NavLink href={route('login')} active={is('login') || is('register')}>
                                        <i className="fa-sharp fa-lock"></i>&nbsp; Login
                                    </NavLink>
                                </li>
                            )}
                            {user && (
                                <li className="nav-item dropdown">
                                    <a className={`nav-link dropdown-toggle${is('user.account') ? ' active' : ''}`} href="#" id="navbarDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {hasNotifications
                                            ? <i className="fa-sharp fa-bell text-warning"></i>
                                            : <i className="fa-sharp fa-user"></i>}
                                        &nbsp;{user.username}
                                    </a>
                                    <ul className="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <li><a className="dropdown-item" href={route('user.account')}>Settings</a></li>
                                        <li><a className="dropdown-item" href={route('list.index')}>My Lists</a></li>
                                        {user.isAdmin && (
                                            <li>
                                                <a className="dropdown-item" href={route('admin')}>
                                                    Admin
                                                    {hasNotifications > 0 && <> <span className="badge bg-warning text-dark">{hasNotifications}</span></>}
                                                </a>
                                            </li>
                                        )}
                                        <li><hr className="dropdown-divider" /></li>
                                        <li><a className="dropdown-item" href={route('user.logout')}>Logout</a></li>
                                    </ul>
                                </li>
                            )}

                            <li className="nav-item">
                                <a className="nav-link" href="https://discord.gg/UkFg9Yy4gP" target="_blank">
                                    <i className="fab fa-discord"></i>
                                    <span className="d-lg-none">&nbsp;Discord</span>
                                </a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="https://github.com/blt950/where2fly" target="_blank">
                                    <i className="fab fa-github"></i>
                                    <span className="d-lg-none">&nbsp;GitHub</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div id="menu-overlay" className="menu-overlay d-md-none"></div>
                </nav>
            </header>
            {!isProduction && <span className="testbadge badge bg-danger  ms-2 mt-2" role="alert">TEST</span>}
        </>
    );
};

export default Menu;
