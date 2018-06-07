var OSMCZ_APP_VERSION = '0.3';

var  baseLayers, overlays;
var marker = L.marker([0, 0]);
initmap();

function initmap() {

    map = new L.Map('map', {zoomControl: false});
    map.attributionControl.setPrefix("<a href='https://openstreetmap.cz' title='osmcz'>openstreetmap.cz</a> ");
    var osmAttr = '<span>&copy;</span><a href="http://openstreetmap.org/copyright"> přispěvatelé OpenStreetMap</a>';

    var osm = L.tileLayer('http://a.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: osmAttr,
        code: 'd'
    });

    var ocm = L.tileLayer("http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png", {
        maxZoom: 18,
        attribution: osmAttr + ', <a href="http://opencyclemap.org">OpenCycleMap</a>',
        code: 'c'
    });

    var hikebike = L.tileLayer("http://toolserver.org/tiles/hikebike/{z}/{x}/{y}.png", {
        maxZoom: 18,
        attribution: osmAttr + ', <a href="http://www.hikebikemap.de">Hike &amp; Bike Map</a>',
        code: 'h'
    });

    var mtb = L.tileLayer("http://tile.mtbmap.cz/mtbmap_tiles/{z}/{x}/{y}.png", {
        maxZoom: 18,
        attribution: osmAttr + ', <a href="http://www.mtbmap.cz">mtbmap.cz</a>',
        code: 'm'
    });

    var vodovky = L.tileLayer('http://{s}.tile.stamen.com/watercolor/{z}/{x}/{y}.jpg', {
        attribution: 'Map data CC-BY-SA <a href="http://openstreetmap.org">OSM.org</a>, imagery <a href="http://maps.stamen.com">Stamen Design</a>',
        maxZoom: 18,
        code: 's'
    });

    var kct = L.tileLayer("http://tile.poloha.net/{z}/{x}/{y}.png", {
        maxZoom: 18,
        attribution: osmAttr + ', <a href="http://www.poloha.net">poloha.net</a>',
        code: 'k'
    });

    var kctOverlay = L.tileLayer("http://tile.poloha.net/kct/{z}/{x}/{y}.png", {
        maxZoom: 18,
        attribution: osmAttr + ', <a href="http://www.poloha.net">poloha.net</a>',
        opacity: 0.6,
        code: 'K'
    });

    var vrstevniceOverlay = L.tileLayer("http://tile.poloha.net/hills/{z}/{x}/{y}.png", {
        maxZoom: 18,
        attribution: osmAttr + ', <a href="http://www.poloha.net">poloha.net</a>',
        opacity: 0.6,
        code: 'V'
    });

    var ortofoto = L.tileLayer.wms('http://geoportal.cuzk.cz/WMS_ORTOFOTO_PUB/service.svc/get', {
        layers: 'GR_ORTFOTORGB',
        format: 'image/jpeg',
        transparent: false,
        crs: L.CRS.EPSG4326,
        minZoom: 7,
        maxZoom: 22,
        attribution: '<a href="http://www.cuzk.cz">ČÚZK</a>',
        code: 'o'
    });

    baseLayers = {
        "KČT trasy poloha.net": kct,
        "MTBMap.cz": mtb,
        "OpenStreetMap Mapnik": osm,
        "OpenCycleMap": ocm,
        "Hike&bike": hikebike,
        "Vodovky": vodovky,
        "Ortofoto ČÚZK": ortofoto
    };
    overlays = {
        "KČT trasy poloha.net": kctOverlay,
        "Vrstevnice": vrstevniceOverlay
    };

    // -------------------- map controls --------------------

    var layersControl = L.control.layers(baseLayers, overlays).addTo(map);
    L.control.scale({
        imperial: false
    }).addTo(map);

    L.control.zoom({
        zoomInTitle: 'Přiblížit',
        zoomOutTitle: 'Oddálit'
    }).addTo(map)

    // leaflet-locate
    L.control.locate({
        follow: true,
        locateOptions: {maxZoom: 15},
        icon: 'glyphicon glyphicon-map-marker',
        strings: {
            title: "Zobrazit moji aktuální polohu"
        }
    }).addTo(map);


    // -------------------- moduly --------------------
    new rozcestniky(map, layersControl, overlays);

    // -------------------- map state --------------------

    // nastavení polohy dle hashe nebo zapamatované v cookie nebo home
    OSM.home = {lat: 49.8, lon: 15.44, zoom: 8};
    var params = OSM.mapParams();
    updateLayersFromCode(params.layers);
    if (params.bounds) {
        map.fitBounds(params.bounds);
    } else {
        map.setView([params.lat, params.lon], params.zoom);
    }
    if (params.marker) {
        marker.setLatLng([params.mlat, params.mlon]).addTo(map);
    }
    if (params.object)
        alert('Zatím nepodporováno //TODO!');

    // updatnutí při změně hashe
    var lastHash;
    $(window).bind('hashchange', function (e) {
        if (location.hash != lastHash) {
            var hash = OSM.parseHash(location.hash);
            if (hash.center)
                map.setView([hash.lat, hash.lon], hash.zoom);
            updateLayersFromCode(hash.layers);
            lastHash = location.hash;
        }
    });

    // pamatování poslední polohy v cookie a hashi
    map.on('moveend zoomend layeradd layerremove', function () {
        lastHash = OSM.formatHash(map)
        location.hash = lastHash;
        Cookies.set("_osm_location", OSM.locationCookie(map), {expires: 31});
    });


    // pokud přepnutá baselayer je mimo zoom, rozumně odzoomovat //TODO ověřit že funguje
    map.on("baselayerchange", function (e) {
        if (map.getZoom() > e.layer.options.maxZoom) {
            map.setView(map.getCenter(), e.layer.options.maxZoom, {reset: true});
        }
    });


}

// set layers from coded string
function updateLayersFromCode(codedString) {
    var setLayer = function (key, layer) {
        for (var pos in codedString) {
            if (layer.options && layer.options.code == codedString[pos])
                map.addLayer(layer);
        }
    };
    $.each(baseLayers, setLayer);
    $.each(overlays, setLayer);
}
