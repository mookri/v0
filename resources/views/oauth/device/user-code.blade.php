<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connect a device — Navdhya</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f4ef; color: #1c1917; margin: 0; }
        main { max-width: 24rem; margin: 8vh auto; background: #fff; border: 1px solid #e7e2d9; border-radius: 12px; padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #57534e; }
        .error { color: #b91c1c; }
        input { width: 100%; box-sizing: border-box; padding: .625rem; font-size: 1.125rem; letter-spacing: .125em; text-align: center; border: 1px solid #d6d0c4; border-radius: 8px; margin-top: 1rem; }
        button { width: 100%; padding: .625rem; margin-top: 1rem; border-radius: 8px; font-size: 1rem; cursor: pointer; background: #1c1917; color: #fff; border: 1px solid #1c1917; }
    </style>
</head>
<body>
<main>
    <h1>Connect a device</h1>
    <p>Enter the code shown on your device.</p>
    @if($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif
    <form method="GET" action="{{ route('passport.device.authorizations.authorize') }}">
        <input type="text" name="user_code" value="{{ old('user_code', request('user_code')) }}" placeholder="ABCD-1234" autocomplete="off" autofocus>
        <button type="submit">Continue</button>
    </form>
</main>
</body>
</html>
