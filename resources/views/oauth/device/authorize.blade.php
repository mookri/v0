<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorize device — Navdhya</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f4ef; color: #1c1917; margin: 0; }
        main { max-width: 28rem; margin: 8vh auto; background: #fff; border: 1px solid #e7e2d9; border-radius: 12px; padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #57534e; }
        ul { padding-left: 1.25rem; color: #57534e; }
        .row { display: flex; gap: .75rem; margin-top: 1.5rem; }
        button { flex: 1; padding: .625rem; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        .approve { background: #1c1917; color: #fff; border: 1px solid #1c1917; }
        .deny { background: #fff; color: #1c1917; border: 1px solid #d6d0c4; }
    </style>
</head>
<body>
<main>
    <h1>Authorize {{ $client->name ?? 'this device' }}</h1>
    <p>A device signed in as <strong>{{ $user->email ?? $user->name }}</strong> is requesting access to your Navdhya account.</p>
    @if(! empty($scopes ?? []))
        <p>It will be able to:</p>
        <ul>
            @foreach($scopes as $scope)
                <li>{{ $scope->description ?? $scope->id }}</li>
            @endforeach
        </ul>
    @endif
    <div class="row">
        <form method="POST" action="{{ route('passport.device.authorizations.approve') }}" style="flex: 1; display: flex;">
            @csrf
            <input type="hidden" name="auth_token" value="{{ $authToken ?? '' }}">
            <button type="submit" class="approve">Authorize device</button>
        </form>
        <form method="POST" action="{{ route('passport.device.authorizations.deny') }}" style="flex: 1; display: flex;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="auth_token" value="{{ $authToken ?? '' }}">
            <button type="submit" class="deny">Cancel</button>
        </form>
    </div>
</main>
</body>
</html>
