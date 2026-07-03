const createClusterIcon = (cluster) => {

    // If url not ends with /top or /search, set style to 'inverted'
    var style = '';
    if (isDefaultView()) {
        style = 'inverted';
    }

    const count = cluster.getChildCount();

    // Scale the icon with the number of points it contains.
    const minSize = 32;
    const maxSize = 60;
    const ratio = Math.min(1, Math.max(0, (Math.log(count) - Math.log(2)) / (Math.log(100) - Math.log(2))));
    const size = Math.round(minSize + (maxSize - minSize) * ratio);
    const fontSize = Math.max(12, Math.round(size * 0.35));

    return L.divIcon({
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        html: `<div class="leaflet-marker-icon marker-cluster ${style}" style="width:${size}px;height:${size}px;line-height:${size}px;font-size:${fontSize}px">${count}</div>`
    });
};

export { createClusterIcon };
