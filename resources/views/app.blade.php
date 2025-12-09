<!DOCTYPE html>
<!--
    resources/views/app.blade.php

    Centrale Blade layout voor Inertia/Vue pages. Deze template laadt
    Vite-assets en initialiseert Inertia. Wijzigen van deze file kan
    invloed hebben op iedere frontend pagina; wees voorzichtig met CDN's.
-->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!-- Er staan HIER GEEN externe CDN scripts meer! Alles gebeurt via Vite/NPM. -->
    </body>
</html>