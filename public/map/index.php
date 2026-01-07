<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../php/log_visit.php';
$pid = $_SESSION['public_id'] ?? null;
log_visit($pid ?? '', $_SERVER['REQUEST_URI'] ?? '/map');
?>

<link rel="stylesheet" href="/map/map.css?v=<?= time() ?>">

<div class="map-block fade-section" id="map-block" style="height:500px;width:100vw;max-width:100%;">
    <div class="map-wrapper">
        <div id="map"></div>
        <button class="open-mapinfo-btn" onclick="toggleMapInfo()">Beacon & Aircraft Identification Plate</button>
        
        <div class="map-modal-container" id="mapInfoModal">
            <div class="map-info-content">
                <div class="map-info-group">
                    <label for="inputName" class="map-info-label">Public Name:</label>
                    <input id="inputName" type="text" value="John Smith" class="map-info-input">
                </div>

                <div class="map-info-group">
                    <label class="map-info-label">Aircraft serial number from the manufacturer:</label>
                    <div class="map-info-select" onclick="toggleNumberDropdown()">
                        <span id="numberSelectValue">Select aircraft number</span>
                        <span>▾</span>
                    </div>

                    <div class="map-info-dropdown" id="numberDropdown">
                        <div class="map-info-number-list" id="numberList"></div>
                        <div class="map-info-pagination">
                            <button class="map-info-pagination-btn" onclick="prevPage()">◀</button>
                            <span id="pageIndicator">Page 1</span>
                            <button class="map-info-pagination-btn" onclick="nextPage()">▶</button>
                        </div>
                    </div>
                </div>

                <div class="map-info-group">
                    <label for="inputInscription" class="map-info-label">Personal inscription for the Aircraft Identification Plate</label>
                    <div class="map-info-inscription-wrapper">
                        <input id="inputInscription" type="text" maxlength="50" class="map-info-input">
                        <div class="map-info-counter" id="inscriptionCounter">0 / 50</div>
                    </div>
                </div>

                <div class="map-info-group map-info-group-checkbox">
                    <label class="map-info-checkbox">
                        <input type="checkbox" id="showMarkerCheckbox" checked> Show my marker on the map
                    </label>
                </div>

                <div class="map-info-actions">
                    <button class="map-info-save-btn" onclick="saveMapInfo()">Save changes</button>
                </div>

                <div class="map-info-tooltip-icon" onclick="togglePlateTooltip(event)">?</div>
                <div class="map-info-tooltip" id="plateTooltip">
                    <div>
                        <em>Changes to the identification plate are possible until the production of your kit begins.</em><br>
                        <em>Production starts after 50% of the kit price has been paid.</em>
                    </div>
                </div>
            </div>
        </div>
</div>
</div>

<script src="/map/map.js?v=<?= time() ?>"></script>
<script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCKSh6zezbgGy-VVAvFNFaijQetKDVIDB0&callback=initMap"></script>
