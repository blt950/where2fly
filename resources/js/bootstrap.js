import 'bootstrap';

import _ from 'lodash';
window._ = _;

// Metrics
document.querySelectorAll('a[href^="http"]').forEach(link => {
    // Check if the link is not pointing to your own domain
    if (link.hostname !== window.location.hostname) {
        link.addEventListener('click', function (e) {
            // Only get the domain of the link to avoid sending the full URL
            let url = new URL(link.href);
            let domain = url.hostname;

            plausible('External Link Click', {
                props: {
                    url: domain
                }
            });
        });
    }
});

document.addEventListener('cardOpened', function(event) {
    var type = event.detail.type;
    if (type == 'flights' || type == 'scenery') {
        plausible('Interactions', {props: {interaction: `Open ${type} card`}});
    }
});