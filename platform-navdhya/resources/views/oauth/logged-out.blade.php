<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Signed out</title>
</head>
<body style="font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; background: #fff7ed; color: #2a2421; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0;">
    {{--
        Neutral logged-out landing (SEC-004 / A-SEC-004-5): rendered
        when the end-session endpoint has no validated redirect target
        (no/invalid id_token_hint, no registered post-logout URI).
        Deliberately static and input-free — nothing from the request
        is reflected here, so this page can never become an open
        redirect or a reflection surface.
    --}}
    <main style="text-align: center; padding: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 600; margin-bottom: .5rem;">You are signed out</h1>
        <p style="margin: 0; opacity: .75;">Your session on this device has ended.</p>
    </main>
</body>
</html>
