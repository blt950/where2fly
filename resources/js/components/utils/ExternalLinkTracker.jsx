import { UHTMLTabListElement } from "@u-elements/u-tabs";

const ExternalLinkTracker = () => {

    // Add metrics to card
    const cards = document.querySelectorAll('.popup-card');
    cards.forEach(card => {
        const links = card.querySelectorAll('a[href^="http"]');
        links.forEach(link => {
            // Check if the link is not pointing to your own domain
            if (link.hostname !== window.location.hostname && !link.hasAttribute('data-click-event-added')) {
                link.setAttribute('data-click-event-added', 'true');
                link.addEventListener('click', function (e) {
                    // Only get the domain of the link to avoid sending the full URL
                    let url = new URL(link.href);
                    let domain = url.hostname;

                    plausible('External Link Click', {
                        props: {
                            url: domain
                        }
                    });

                    umami.track('External Link Click', {
                        url: domain
                    });
                });
            }
        });
    })
};

export default ExternalLinkTracker;