// Routes that drive the map from a list of results. Everything else — the front page, scenery
// editing, account pages — is the "default view": the user's own saved position and lists.
const RESULT_ROUTES = ['top', 'top.filtered', 'search', 'search.routes', 'scenery', 'scenery.filtered'];

export const isDefaultView = () =>
    route().current() !== undefined && !RESULT_ROUTES.some((name) => route().current(name));
