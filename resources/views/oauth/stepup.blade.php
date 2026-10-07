<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify identity — Navdhya</title>
    {{-- Standalone interstitial (same shape as oauth/authorize): no admin
         chrome, inline styles — it must render even when the console JS
         cannot. No logic beyond display (doc 06 §7.5). --}}
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f4ef; color: #1c1917; margin: 0; }
        main { max-width: 28rem; margin: 8vh auto; background: #fff; border: 1px solid #e7e2d9; border-radius: 12px; padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #57534e; }
        .alert { border-radius: 8px; padding: .625rem .75rem; margin-bottom: 1rem; font-size: .875rem; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        label { display: block; font-size: .875rem; margin-bottom: .375rem; }
        input { width: 100%; box-sizing: border-box; padding: .625rem; border: 1px solid #d6d0c4; border-radius: 8px; font-size: 1rem; letter-spacing: .1em; }
        .row { display: flex; gap: .75rem; margin-top: 1.5rem; }
        button, .link { flex: 1; padding: .625rem; border-radius: 8px; font-size: 1rem; cursor: pointer; text-align: center; text-decoration: none; }
        button { background: #1c1917; color: #fff; border: 1px solid #1c1917; }
        .link { background: #fff; color: #1c1917; border: 1px solid #d6d0c4; }
    </style>
</head>
<body>
<main>
    <h1>Verify your identity</h1>

    {{-- Coded flash (success / failed code), validation $errors, all display-only. --}}
    <x-flash-alerts />

    @if (! $mfaEnabled)
        {{-- Fail-closed (SEC-004): v1 has no password re-auth — sensitive
             actions stay locked until MFA is enrolled. --}}
        <p>Multi-factor authentication is required for this action. Enroll an authenticator app in your <a href="{{ url('/account/security') }}">security settings</a> first, then come back.</p>
        <div class="row">
            <a class="link" href="{{ $returnTo }}">Back</a>
        </div>
    @else
        @if ($fresh)
            <p>You verified within the last 15 minutes — you can continue.</p>
        @endif
        <form method="POST" action="{{ url('/oauth/stepup') }}">
            @csrf
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
            <label for="stepup-code">Authenticator or recovery code</label>
            <input id="stepup-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="12" placeholder="123456" required autofocus>
            <div class="row">
                <button type="submit">Verify and continue</button>
                <a class="link" href="{{ $returnTo }}">Cancel</a>
            </div>
        </form>
    @endif
</main>
</body>
</html>
