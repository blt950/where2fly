// The umami "Interactions" event, with the interaction's name. Analytics may be blocked or not
// loaded yet; that is never an error.
export const trackInteraction = (interaction) => window.umami?.track('Interactions', { interaction });
