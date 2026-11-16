import { Link } from '@inertiajs/react';

// One NavigationHelper item: a client-side visit when the target is in the shell (keeps the map),
// fetched on hover so it opens at once; an ordinary link otherwise (logout, admin, external links).
const NavItem = ({ item, className, onClick, children, ...rest }) => {
    const props = { className, href: item.href, 'aria-current': item.active ? 'page' : undefined, ...rest };
    const content = children ?? item.label;

    if (item.shell) {
        return <Link {...props} prefetch onClick={onClick}>{content}</Link>;
    }

    return <a {...props} {...(item.external ? { target: '_blank', rel: 'noopener' } : {})}>{content}</a>;
};

export default NavItem;
