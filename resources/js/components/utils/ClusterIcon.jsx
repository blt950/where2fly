const createClusterIcon = (cluster) => {
    
    // if url not ends with /top or /search, set style to 'inverted'
    var style = '';
    if (isDefaultView()) {
        style = 'inverted';
    }
    
    return L.divIcon({ 
        iconSize: [40, 40], 
        html: `<div class="leaflet-marker-icon marker-cluster ${style}">${cluster.getChildCount()}</div>` 
    });
};

export { createClusterIcon };