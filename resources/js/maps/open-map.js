import { LngLatBounds, Map, Marker, Popup, setWorkerUrl } from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import 'maplibre-gl/dist/maplibre-gl.css';

// Vite's bundle changes import.meta.url, so the default worker path 404s.
setWorkerUrl(workerUrl);

function readMarkers(element) {
    return JSON.parse(element.dataset.markers || '[]');
}

function placeMarkers(map, markers, center, zoom, singleZoom, pins) {
    pins.forEach((pin) => pin.remove());
    pins.length = 0;

    const bounds = new LngLatBounds();

    markers.forEach((marker) => {
        const popup = new Popup({ offset: 24, maxWidth: '320px' }).setHTML(marker.popup);
        const pin = new Marker({ color: '#1e40af' })
            .setLngLat([marker.lng, marker.lat])
            .setPopup(popup)
            .addTo(map);

        pins.push(pin);
        bounds.extend([marker.lng, marker.lat]);
    });

    if (markers.length === 1) {
        map.setCenter([markers[0].lng, markers[0].lat]);
        map.setZoom(singleZoom);
    } else if (markers.length > 1) {
        map.fitBounds(bounds, { padding: 40, maxZoom: singleZoom });
    } else {
        map.setCenter([center.lng, center.lat]);
        map.setZoom(zoom);
    }
}

function setPressed(active) {
    const street = document.getElementById('map-street');
    const satellite = document.getElementById('map-satellite');

    if (street instanceof HTMLButtonElement) {
        street.setAttribute('aria-pressed', active === 'street' ? 'true' : 'false');
    }

    if (satellite instanceof HTMLButtonElement) {
        satellite.setAttribute('aria-pressed', active === 'satellite' ? 'true' : 'false');
    }

    document.querySelectorAll('#map-attribution [data-layer]').forEach((node) => {
        if (node instanceof HTMLElement) {
            node.hidden = node.dataset.layer !== active;
        }
    });
}

export function mount(element) {
    const markers = readMarkers(element);
    const center = JSON.parse(element.dataset.center || '{}');
    const zoom = Number(element.dataset.zoom || '13');
    const singleZoom = Number(element.dataset.singleZoom || '13');
    const streetStyle = element.dataset.style;
    const satelliteTiles = element.dataset.satellite;
    const satelliteRoads = element.dataset.satelliteRoads;
    const satellitePlaces = element.dataset.satellitePlaces;
    const satelliteAttribution = element.dataset.satelliteAttribution || '';

    const map = new Map({
        container: element,
        style: streetStyle,
        center: [center.lng, center.lat],
        zoom,
        attributionControl: false,
    });

    const satelliteStyle = {
        version: 8,
        sources: {
            imagery: {
                type: 'raster',
                tiles: [satelliteTiles],
                tileSize: 256,
                attribution: satelliteAttribution,
            },
            roads: {
                type: 'raster',
                tiles: [satelliteRoads],
                tileSize: 256,
            },
            places: {
                type: 'raster',
                tiles: [satellitePlaces],
                tileSize: 256,
            },
        },
        layers: [
            { id: 'imagery', type: 'raster', source: 'imagery' },
            { id: 'roads', type: 'raster', source: 'roads' },
            { id: 'places', type: 'raster', source: 'places' },
        ],
    };

    const pins = [];
    const draw = () => placeMarkers(map, markers, center, zoom, singleZoom, pins);

    map.on('load', draw);

    document.getElementById('map-street')?.addEventListener('click', () => {
        setPressed('street');
        map.setStyle(streetStyle);
        map.once('style.load', draw);
    });

    document.getElementById('map-satellite')?.addEventListener('click', () => {
        setPressed('satellite');
        map.setStyle(satelliteStyle);
        map.once('style.load', draw);
    });
}
