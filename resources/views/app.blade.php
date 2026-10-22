<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <link rel="icon" type="image/png" href="{{ asset('img/Logo_UNET.png') }}">

        @vite('resources/js/app.ts')
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
        {{-- Mientras baja el JavaScript (5-10 s en 3G) la página quedaba en blanco. El aviso se oculta
             solo cuando Vue dibuja algo dentro de #app; no necesita JavaScript --}}
        <div id="cargando-inicial" role="status" aria-live="polite">Cargando Lockers UNET…</div>
        <style>
            #cargando-inicial { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; font: 700 16px system-ui, sans-serif; color: #213779; background: #fff; }
            #app:not(:empty) ~ #cargando-inicial { display: none; }
        </style>
    </body>
</html>