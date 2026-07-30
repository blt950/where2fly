const createClusterIcon = (cluster) => {

    // If url not ends with /top or /search, set style to 'inverted'
    var style = '';
    if (isDefaultView()) {
        style = 'inverted';
    }

    const count = cluster.getChildCount();

    // Scale the icon with the number of points it contains. Sizes are in rem so
    // they follow the browser's font size setting.
    const minSize = 2;
    const maxSize = 3.75;
    const ratio = Math.min(1, Math.max(0, (Math.log(count) - Math.log(2)) / (Math.log(100) - Math.log(2))));
    const size = Math.round((minSize + (maxSize - minSize) * ratio) * 1000) / 1000;
    const fontSize = Math.max(0.75, Math.round(size * 0.35 * 1000) / 1000);

    // Leaflet needs pixels for the icon box, so resolve rem against the root font size.
    const rootFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
    const sizePx = size * rootFontSize;

    return L.divIcon({
        iconSize: [sizePx, sizePx],
        iconAnchor: [sizePx / 2, sizePx / 2],
        html: `<div class="leaflet-marker-icon marker-cluster ${style}" style="width:${size}rem;height:${size}rem;line-height:${size}rem;font-size:${fontSize}rem">${count}</div>`
    });
};

export { createClusterIcon };
