<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Owner account
    |--------------------------------------------------------------------------
    |
    | The address the seeder creates the administrator account with, and where
    | contact-form notifications are sent when the site settings have no email
    | of their own yet. Everything else about the profile — name, location,
    | social links, portrait — is edited from the admin panel.
    |
    */

    'owner_email' => env('PORTFOLIO_OWNER_EMAIL', 'hola@hectorzamorano.dev'),

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The languages the public site can be switched between, in the order the
    | ES / EN toggle renders them.
    |
    */

    'locales' => ['es', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Presentation
    |--------------------------------------------------------------------------
    |
    | The design exposes these as editable properties. They are forwarded to the
    | front-end runtime as a JSON payload on the page root.
    |
    */

    'presentation' => [
        // Animated dot-lattice behind the public site.
        'background' => true,
        // Seconds the intro splash runs for (the design caps this at 2.5s).
        'intro_duration' => 2.5,
        // Replay the intro on every visit, or only until it has been seen once.
        'intro_always' => true,
        // Seconds each canvas scene holds before cross-fading to the next.
        'scene_duration' => 8,
        // Theme applied when a visitor has no stored preference: light | dark.
        'default_theme' => 'light',
    ],

];
