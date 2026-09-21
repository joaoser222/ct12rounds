<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/js/app.ts'])
        @inertiaHead
        <style>
            [v-cloak]{display:none!important}
            #app-loading{
                position:fixed;inset:0;z-index:9999;
                display:flex;align-items:center;justify-content:center;flex-direction:column;gap:24px;
                background:#080808;
                transition:opacity .3s ease;
            }
            #app-loading.hide{opacity:0;pointer-events:none}
            #app-loading .spinner{
                width:40px;height:40px;
                border:3px solid rgba(255,255,255,.08);
                border-top-color:#0057FF;
                border-radius:50%;
                animation:spin .8s linear infinite;
            }
            #app-loading .loading-text{
                font-family:'Barlow',sans-serif;font-size:14px;color:#666;
                letter-spacing:2px;text-transform:uppercase;
            }
            @keyframes spin{to{transform:rotate(360deg)}}
        </style>
    </head>
    <body>
        <div id="app-loading">
            <div class="spinner"></div>
            <div class="loading-text">Carregando</div>
        </div>
        @inertia
    </body>
</html>
