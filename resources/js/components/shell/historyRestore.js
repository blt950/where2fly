// Shared props restored from history are as old as their entry. Its flash messages have been
// read; and if the login has changed since, the navigation, lists and map hint are asked for again.
const SESSION_PROPS = ['nav', 'alerts', 'mapHint', 'csrfToken', 'listsRevision'];
const accountIn = (page) => page.props.nav?.account?.label ?? null;

export const watchHistoryRestores = (router) => {
    let account;

    router.on('navigate', (event) => {
        const { page, type } = event.detail;

        if (type !== 'history') {
            account = accountIn(page);
            return;
        }

        // umami only watches pushState/replaceState: Back and Forward are page views it never
        // sees. Sent once <Head> has set the title.
        setTimeout(() => window.umami?.track((props) => ({
            ...props, url: window.location.pathname + window.location.search, title: document.title,
        })), 300);

        if (account !== undefined && accountIn(page) !== account) {
            router.reload({ only: SESSION_PROPS });
        } else if (page.props.alerts?.messages?.length) {
            router.replace({
                props: (props) => ({ ...props, alerts: { ...props.alerts, messages: [] } }),
                preserveState: true,
                preserveScroll: true,
            });
        }
    });

    // Back onto a page the browser kept whole (bfcache), e.g. after logging out: same staleness
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            router.reload({ only: SESSION_PROPS });
        }
    });
};
