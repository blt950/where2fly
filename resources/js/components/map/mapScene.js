import { readStored } from '../utils/storage';

export const POSITION_KEY = 'mapPosition';

const DEFAULT_ZOOM = 4;

// MapLibre takes [lng, lat] — the opposite order to Leaflet. Zoom is optional per entry and
// falls back to DEFAULT_ZOOM, so only the odd ones out need to say it.
export const view = (center, zoom = DEFAULT_ZOOM) => ({ center, zoom });

export const WORLD_VIEW = view([-0, 30], 2);
const BERLIN_VIEW = view([13.395199187248908, 52.51843039016386]);

export const CONTINENT_VIEWS = {
    AF: view([21, 0], 3.2),
    AS: view([100, 35], 3),
    EU: view([15, 55]),
    NA: view([-95, 45]),
    OC: view([135, -25], 3.5),
    SA: view([-55, -20], 3),
};

// Pre-MapLibre this was stored without a zoom, which view()'s default parameter covers.
export const savedView = () => {
    const stored = readStored(POSITION_KEY);

    if (Number.isFinite(stored?.lat) && Number.isFinite(stored?.lng)) {
        return view([stored.lng, stored.lat], stored.zoom);
    }

    return BERLIN_VIEW;
};

// What the map should show. Every page supplies one; anything it leaves out falls back to the
// "default view": the user's own saved position and lists, nothing drawn on top.
export const SCENE_DEFAULTS = {
    airports: {},
    cluster: true,
    clusterTone: 'muted',
    primaryAirport: null,
    reverseDirection: null,
    drawRoute: null,
    highlightedAircrafts: [],
    // saved: restore the user's last position | fit: frame `airports` | view: a fixed view
    camera: { type: 'saved' },
    persistView: true,
    showLists: true,
    // Search results are dense enough that labelling every airport at world zoom is unreadable
    gateLabels: false,
};

export const cameraView = (camera) => (camera?.type === 'view' ? camera.view : savedView());

// Routes that drive the map from a list of results. Everything else (front page, scenery
// editing, account pages) is the default view.
const RESULT_ROUTES = ['top', 'top.filtered', 'search', 'search.routes', 'scenery', 'scenery.filtered'];

// The Blade pages' scene, decided from the URL. Inertia pages declare theirs instead.
export const sceneFromRoute = (router) => {
    const unnamed = router.current() === undefined;
    const isDefault = !unnamed && !RESULT_ROUTES.some((name) => router.current(name));
    const isScenery = router.current('scenery*');

    const continent = Object.keys(CONTINENT_VIEWS).find((code) => router.current('top.filtered', code));
    let camera = { type: 'saved' };

    if (continent) {
        camera = { type: 'view', view: CONTINENT_VIEWS[continent] };
    } else if (router.current('top')) {
        camera = { type: 'view', view: WORLD_VIEW };
    } else if (!isDefault && !router.current('top*') && !isScenery) {
        camera = { type: 'fit' };
    }

    return {
        ...SCENE_DEFAULTS,
        clusterTone: isDefault ? 'muted' : 'search',
        camera,
        persistView: isDefault || isScenery,
        showLists: isDefault,
        gateLabels: router.current('search') || unnamed,
    };
};

// Everything a reload used to reset. A new scene starts from these, never from the last page's.
const TRANSIENT_DEFAULTS = {
    focusAirport: null,
    coordinates: null,
    mapBounds: null,
    ping: null,
    showAirportIdCard: null,
};

export const initialMapState = (scene) => ({ ...SCENE_DEFAULTS, ...scene, ...TRANSIENT_DEFAULTS });

export const mapStateReducer = (state, action) => {
    switch (action.type) {
        case 'scene':
            return { ...initialMapState(action.scene), sceneKey: action.sceneKey };
        case 'set':
            return { ...state, ...action.values };
        case 'addAirport':
            return { ...state, airports: { ...state.airports, [action.icao]: action.airport } };
        default:
            throw new Error(`Unknown map action ${action.type}`);
    }
};
