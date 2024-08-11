<!-- Leaflet -->
<div id="map" class="map"></div>
<div class="popup-container">
    <div class="popup-card">

        <div>
            <img class="flag border-0" src="/img/flags/no.svg" height="16">
            ENGM
        </div>
        <h2>Oslo Gardermoen</h2>

        <dl class="font-kanit">
            <dt>Airlines</dt>
            <dd>
                <button type="button" class="airline-button " data-bs-toggle="modal" data-bs-target="#EDDP-EYVI-BCS-Modal">
                    <img data-bs-toggle="tooltip" data-bs-title="See all European Air Transport flights" class="airline-logo" width="35" src="https://where2fly.today/img/airlines/QY.png">
                </button>
            
                <button type="button" class="airline-button " data-bs-toggle="modal" data-bs-target="#EDDP-EYVI-SWT-Modal">
                    <img data-bs-toggle="tooltip" data-bs-title="See all Swiftair flights" class="airline-logo" width="35" src="https://where2fly.today/img/airlines/WT.png">
                </button>
            </dd>

            <dt>Runways</dt>
            <dd>01L: 11.811ft / 3.600m</dd>
            <dd>01R: 11.811ft / 3.600m</dd>

            <dt>Weather</dt>
            <dd>
                <td>
                    <div class="d-flex justify-content-between mb-3 nav nav-pills" style="font-size: 0.75rem" role="tablist">
                        <div class="d-flex">
                            <div>
                                <button class="nav-link active" id="home-tab-26371" data-bs-toggle="tab" data-bs-target="#metar-pane-26371" type="button" role="tab" aria-selected="true">METAR</button>
                            </div>
                            <div>
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#taf-pane-26371" data-taf-button="true" data-airport-icao="USCM" type="button" role="tab" aria-selected="false" tabindex="-1">TAF</button>
                            </div>
                        </div>

                        <div class="d-flex hover-show-group">
                            <div class="hover-show secondary">
                                <a class="btn btn-sm float-end font-work-sans text-muted" href="https://windy.com/USCM" target="_blank">
                                    <span class="d-none d-lg-inline d-xl-inline">Windy</span> <i class="fas fa-up-right-from-square"></i>
                                </a>
                            </div>
                            <div class="hover-show">
                                                                                <a class="btn btn-sm float-end font-work-sans text-muted" href="https://dispatch.simbrief.com/options/custom?orig=EDDP&amp;dest=USCM" target="_blank">
                                    <span class="d-none d-lg-inline d-xl-inline">SimBrief</span> <i class="fas fa-up-right-from-square"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content">
                        
                        <div class="tab-pane fade show active" id="metar-pane-26371" role="tabpanel" aria-labelledby="home-tab-26371" tabindex="0">111200Z 10003MPS 0800 R36/0800N FG VV001 12/12 Q1007 R36/290255 TEMPO 12013MPS 0300 +TSRA FG RMK QBB040 QFE717</div>
                        
                        
                                                                    <div class="tab-pane fade" id="taf-pane-26371" role="tabpanel" tabindex="0">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                                                            </div>
                </td>
            </dd>
        </dl>

        <a href="#" class="btn btn-outline-primary"><i class="fas fa-up-right-from-square"></i> Windy</a>
        <a href="#" class="btn btn-outline-primary"><i class="fas fa-up-right-from-square"></i> Simbrief</a>

        

    </div>
</div>

@vite('resources/js/leaflet.js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var map = L.map('map', {
            attributionControl: false,
            zoomControl: false
        }).setView([52.51843039016386, 13.395199187248908], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
            minZoom: 2,
            maxZoom: 17,
        }).addTo(map);
    });
</script>