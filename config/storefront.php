<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Browser theme colour
    |--------------------------------------------------------------------------
    |
    | The address-bar tint on mobile. It has to be a literal hex because a
    | <meta> tag cannot read a CSS custom property, so it lives here rather
    | than in a view — the design tokens themselves are in
    | resources/css/storefront.css and this must be kept equal to --surface-0.
    |
    */
    'theme_color' => '#0F0F11',

    /*
    |--------------------------------------------------------------------------
    | Ignore the Vite hot file
    |--------------------------------------------------------------------------
    |
    | Laravel serves assets from the Vite dev server whenever public/hot
    | exists. That file belongs to whichever `npm run dev` the developer left
    | running, so the browser suite would otherwise load the HMR client on
    | every page -- and HMR answers a file change with a full page reload,
    | which cancels whatever navigation happens to be in flight. Turning this
    | on points the hot file at a path that never exists, so the suite reads
    | the build manifest and tests the assets that ship.
    |
    */
    'ignore_vite_hot_file' => (bool) env('STOREFRONT_IGNORE_VITE_HOT', false),
];
