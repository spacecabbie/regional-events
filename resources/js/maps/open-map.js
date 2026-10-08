import { LngLatBounds, Map, Marker, Popup } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

function readMarkers(element) {
    return JSON.parse(element.dataset.markers || '[]');
}

function placeMarkers(map, markers, center, zoom, singleZoom, pins) {
    pins.forEach((pin) => pin.remove());
    pins.length = 0;

    const bounds = new LngLatBounds();

    markers.forEach((marker) => {
        const popup = new Popup({ offset: 24, maxWidth: '280px' }).setHTML(marker.popup);
        const pin = new Marker({ color: '#44403c' })
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
        street.classList.toggle('bg-stone-900', active === 'street');
        street.classList.toggle('text-white', active === 'street');
        street.classList.toggle('bg-white', active !== 'street');
    }

    if (satellite instanceof HTMLButtonElement) {
        satellite.setAttribute('aria-pressed', active === 'satellite' ? 'true' : 'false');
        satellite.classList.toggle('bg-stone-900', active === 'satellite');
        satellite.classList.toggle('text-white', active === 'satellite');
        satellite.classList.toggle('bg-white', active !== 'satellite');
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
    const singleZoom = Number(element.dataset.singleZoom || '15');
    const streetStyle = element.dataset.style;
    const satelliteTiles = element.dataset.satellite;
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
            esri: {
                type: 'raster',
                tiles: [satelliteTiles],
                tileSize: 256,
                attribution: satelliteAttribution,
            },
        },
        layers: [{ id: 'esri', type: 'raster', source: 'esri' }],
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
