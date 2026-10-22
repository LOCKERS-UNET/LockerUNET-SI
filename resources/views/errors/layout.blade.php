{{-- Páginas de error en español (la de Laravel salía en inglés y sin enlace para volver). HTML simple: no depende de Vite. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') - Lockers UNET</title>
    <link rel="icon" type="image/png" href="/img/Logo_UNET.png">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, sans-serif; background: #fff; color: #404040; padding: 16px; box-sizing: border-box; }
        main { max-width: 420px; text-align: center; }
        img { height: 72px; margin-bottom: 24px; }
        .codigo { font-size: 56px; font-weight: 800; color: #213779; margin: 0; }
        h1 { font-size: 22px; color: #1C2F5E; margin: 8px 0 12px; }
        p { line-height: 1.5; margin: 0 0 24px; }
        a { display: inline-block; min-height: 44px; line-height: 44px; padding: 0 28px; background: #213779; color: #fff; font-weight: 700; border-radius: 12px; text-decoration: none; }
        a:hover { background: #1a2b5f; }
    </style>
</head>
<body>
    <main>
        <img src="/img/Logo_Lockers_UNET.webp" alt="Lockers UNET">
        <p class="codigo">@yield('codigo')</p>
        <h1>@yield('titulo')</h1>
        <p>@yield('mensaje')</p>
        <a href="/">Volver al inicio</a>
    </main>
</body>
</html>
