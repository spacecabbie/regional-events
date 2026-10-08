<?php

return [

    /*
    | How many days ahead the public page lists. The window starts at the
    | beginning of today in the display timezone and ends at the end of the
    | day this many days later. An event earlier today stays listed.
    */
    'window_days' => (int) env('EVENTS_WINDOW_DAYS', 30),

    // Display and form entry. Storage stays UTC because config app.timezone is UTC.
    'timezone' => 'Europe/Lisbon',

    'center' => [
        'lat' => 39.822,
        'lng' => -7.491,
    ],

    'zoom' => 13,

    'single_pin_zoom' => 13,

    'confirm_hours' => 72,

    'edit_hours' => 2,

    // ISO 216 A4 at the CSS reference pixel (96 px per inch): 794×1123.
    // The stored master is flyer_scale times that. Landscape swaps the sides.
    'a4_css' => [
        'width' => 794,
        'height' => 1123,
    ],

    'flyer_scale' => 2,

    'webp_quality' => 75,

    'flyer_max_bytes' => 2 * 1024 * 1024,

    'upload_max_kilobytes' => 8192,

    // 2× the list slot (80×112 CSS pixels).
    'thumb' => [
        'width' => 160,
        'height' => 224,
    ],

    'flyer_mimes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/x-ms-bmp',
        'image/tiff',
        'application/pdf',
    ],

    'open' => [
        'style' => 'https://tiles.openfreemap.org/styles/liberty',
        'satellite' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        'satellite_roads' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}',
        'satellite_places' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        'satellite_attribution' => 'Tiles © Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community.',
    ],

];
