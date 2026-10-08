function readMarkers(element) {
    return JSON.parse(element.dataset.markers || '[]');
}

export function mount(element) {
    const key = element.dataset.googleKey;

    if (! key) {
        return;
    }

    window.initRegionalEventsGoogleMap = async () => {
        const markers = readMarkers(element);
        const center = JSON.parse(element.dataset.center || '{}');
        const zoom = Number(element.dataset.zoom || '13');
        const singleZoom = Number(element.dataset.singleZoom || '13');
        const { Map, InfoWindow } = await google.maps.importLibrary('maps');
        const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');

        const map = new Map(element, {
            center: { lat: center.lat, lng: center.lng },
            zoom,
            mapId: element.dataset.googleMapId || 'DEMO_MAP_ID',
            mapTypeControl: true,
        });

        const info = new InfoWindow();
        const bounds = new google.maps.LatLngBounds();

        markers.forEach((marker) => {
            const position = { lat: marker.lat, lng: marker.lng };
            const pin = new AdvancedMarkerElement({ map, position });

            pin.addListener('click', () => {
                info.setContent(marker.popup);
                info.open({ map, anchor: pin });
            });

            bounds.extend(position);
        });

        if (markers.length === 1) {
            map.setCenter({ lat: markers[0].lat, lng: markers[0].lng });
            map.setZoom(singleZoom);
        } else if (markers.length > 1) {
            map.fitBounds(bounds, 40);
            google.maps.event.addListenerOnce(map, 'idle', () => {
                const current = map.getZoom();

                if (typeof current === 'number' && current > singleZoom) {
                    map.setZoom(singleZoom);
                }
            });
        }
    };

    const script = document.createElement('script');
    const params = new URLSearchParams({
        key,
        loading: 'async',
        libraries: 'marker',
        callback: 'initRegionalEventsGoogleMap',
    });
    script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`;
    script.async = true;
    script.onerror = () => {
        const note = document.createElement('p');
        note.className = 'p-4';
        note.textContent = 'Google Maps did not load.';
        element.replaceChildren(note);
    };
    document.head.appendChild(script);
}
