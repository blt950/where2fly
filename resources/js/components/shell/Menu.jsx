import { useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

import NavItem from './NavItem';

// The header; its links come from NavigationHelper (`nav`), shared with the bottom nav and the
// Blade layouts. layouts/menu.blade.php is its Blade twin.
const Menu = () => {
    const { url, props } = usePage();
    const { nav, isProduction } = props;
    const { main, feedback, account, login, community } = nav;
    const donate = community.find((item) => item.key === 'donate');
    const social = community.filter((item) => item.key !== 'donate');

    // A full load used to close the phone menu; a client-side visit has to do it itself
    useEffect(() => {
        bootstrap.Collapse.getInstance(document.getElementById('navbarMenu'))?.hide();
    }, [url]);

    const navLink = (item, icon) => (
        <li className="nav-item" key={item.key}>
            <NavItem item={item} className={`nav-link${item.active ? ' active' : ''}`}>
                <i className={`fa-sharp ${icon ?? item.icon}`}></i>&nbsp; {item.label}
            </NavItem>
        </li>
    );

    return (
        <>
            <header className="mb-auto">
                <nav className="navbar navbar-expand-xl navbar-dark">
                    <div className="d-flex d-row">
                        <Link href={main[0].href} className="d-block navbar-brand mb-0 logo">Where2Fly</Link>
                        <NavItem item={donate} className="donate btn btn-sm btn-light">
                            <img src="/img/kofi_symbol.svg" alt="Coffee cup with a heart" />
                            {donate.label}
                        </NavItem>
                    </div>
                    <button className="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                        aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                        <span className="navbar-toggler-icon"></span>
                    </button>
                    <div className="collapse navbar-collapse" id="navbarMenu">
                        <ul className="navbar-nav ms-auto">
                            {main.map((item) => navLink(item))}
                            {navLink(feedback, `${feedback.icon}${feedback.unread ? ' unread' : ''}`)}

                            {login && navLink(login)}
                            {account && (
                                <li className="nav-item dropdown">
                                    <a className={`nav-link dropdown-toggle${account.active ? ' active' : ''}`} href="#" id="navbarDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {account.notifications
                                            ? <i className="fa-sharp fa-bell text-warning"></i>
                                            : <i className="fa-sharp fa-user"></i>}
                                        &nbsp;{account.label}
                                    </a>
                                    <ul className="dropdown-menu" aria-labelledby="navbarDropdown">
                                        {account.items.map((item) => (
                                            <li key={item.key}>
                                                {item.key === 'logout' && <hr className="dropdown-divider" />}
                                                <NavItem item={item} className={`dropdown-item${item.active ? ' active' : ''}`}>
                                                    {item.label}
                                                    {item.key === 'admin' && account.notifications > 0 && (
                                                        <> <span className="badge bg-warning text-dark">{account.notifications}</span></>
                                                    )}
                                                </NavItem>
                                            </li>
                                        ))}
                                    </ul>
                                </li>
                            )}

                            {social.map((item) => (
                                <li className="nav-item" key={item.key}>
                                    <NavItem item={item} className="nav-link">
                                        <i className={item.icon}></i>
                                        <span className="d-lg-none">&nbsp;{item.label}</span>
                                    </NavItem>
                                </li>
                            ))}
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
