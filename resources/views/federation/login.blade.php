<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — Navdhya</title>
    {{-- Unified login (ADR-0020 D1): standalone surface, no admin
         chrome, inline styles — it must render even when the console JS
         cannot. The MSG91 widget runs in custom-SDK mode: our UI drives
         everything and redeems the JWT its verifyOtp hands back; the
         runtime never sees a code. --}}
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f4ef; color: #1c1917; margin: 0; }
        main { max-width: 28rem; margin: 8vh auto; background: #fff; border: 1px solid #e7e2d9; border-radius: 12px; padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
        .sub { color: #57534e; margin: 0 0 1.25rem; font-size: .875rem; }
        .alert { border-radius: 8px; padding: .625rem .75rem; margin-bottom: 1rem; font-size: .875rem; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        label { display: block; font-size: .875rem; margin-bottom: .375rem; }
        input { width: 100%; box-sizing: border-box; padding: .625rem; border: 1px solid #d6d0c4; border-radius: 8px; font-size: 1rem; }
        input.otp { letter-spacing: .35em; text-align: center; }
        .row { display: flex; gap: .75rem; margin-top: 1.5rem; }
        .field { margin-bottom: 1rem; }
        button, .link { flex: 1; padding: .625rem; border-radius: 8px; font-size: 1rem; cursor: pointer; text-align: center; text-decoration: none; }
        button { background: #1c1917; color: #fff; border: 1px solid #1c1917; }
        button[disabled] { opacity: .5; cursor: default; }
        .link { background: #fff; color: #1c1917; border: 1px solid #d6d0c4; }
        .links { margin-top: 1.25rem; display: flex; justify-content: space-between; font-size: .875rem; }
        .links button { background: none; border: none; color: #57534e; text-decoration: underline; padding: 0; flex: 0; }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
<main>
    <h1>Sign in</h1>
    <p class="sub">One account for every Navdhya surface — no separate signup.</p>

    <div id="alert" class="alert alert-danger" hidden></div>

    @if ($user !== null)
        {{-- Already authenticated: landing state (resume target or whoami). --}}
        <div id="panel-signedin">
            <p>Signed in as <strong>{{ $user['name'] }}</strong>.</p>
            <div class="row">
                @if ($next !== null)
                    <a class="link" id="continue" href="{{ $next }}">Continue</a>
                @else
                    <a class="link" href="/auth/session">View session</a>
                @endif
                <button type="button" id="btn-signout">Sign out</button>
            </div>
        </div>
    @else
        {{-- Phone (OTP) primary path. --}}
        <section id="step-phone">
            <div class="field">
                <label for="phone">Phone number</label>
                <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="9898989898" maxlength="64" autofocus>
            </div>
            <div class="row">
                <button type="button" id="btn-send">Send code</button>
            </div>
            <div class="links">
                <button type="button" id="to-password">Use email &amp; password instead</button>
            </div>
        </section>

        {{-- OTP entry (widget-driven send/verify). --}}
        <section id="step-code" hidden>
            <div class="field">
                <label for="code">Verification code</label>
                <input id="code" class="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="——————">
            </div>
            <div class="row">
                <button type="button" id="btn-verify">Verify &amp; sign in</button>
                <button type="button" id="btn-resend" class="link" disabled>Resend</button>
            </div>
            <div class="links">
                <button type="button" id="to-phone">Use a different number</button>
            </div>
        </section>

        {{-- TOTP second factor (covers both OTP-held and password-parked flows). --}}
        <section id="step-mfa" hidden>
            <div class="field">
                <label for="totp">Authenticator code</label>
                <input id="totp" class="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="12" placeholder="——————">
            </div>
            <div class="row">
                <button type="button" id="btn-totp">Verify code</button>
            </div>
        </section>

        {{-- Email + password second path. --}}
        <section id="step-password" hidden>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" autocomplete="username" maxlength="190">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" autocomplete="current-password" maxlength="200">
            </div>
            <div class="row">
                <button type="button" id="btn-password">Sign in</button>
            </div>
            <div class="links">
                <button type="button" id="to-phone-alt">Use phone instead</button>
            </div>
        </section>
    @endif
</main>

<script>
(function () {
    'use strict';

    var next = @json($next);
    var widget = @json($widget);
    var resendDelays = @json($resend['delaysSeconds'] ?? [60, 180, 540]);
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    @if ($user === null)
    var challengeId = null;
    var lastTarget = null;   // dev-shortcut detection (mirrors @navdhya/otp/widget loader)
    var mfaMode = null;      // 'otp' (held row) | 'password' (session-parked)
    var resendCount = 0;
    var resendTimer = null;

    function alertMsg(message, kind) {
        var el = document.getElementById('alert');
        if (!message) { el.hidden = true; return; }
        el.textContent = message;
        el.className = 'alert alert-' + (kind || 'danger');
        el.hidden = false;
    }

    function showStep(name) {
        ['phone', 'code', 'mfa', 'password'].forEach(function (s) {
            document.getElementById('step-' + s).hidden = (s !== name);
        });
        alertMsg(null);
    }

    function busy(id, isBusy) {
        document.getElementById(id).disabled = !!isBusy;
    }

    async function post(url, body) {
        var res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            credentials: 'same-origin',
            body: JSON.stringify(body || {})
        });
        var json = null;
        try { json = await res.json(); } catch (e) { /* non-JSON body */ }
        return { status: res.status, json: json, retryAfter: res.headers.get('Retry-After') };
    }

    function showError(r) {
        var message = 'Something went wrong. Please try again.';
        if (r.json && r.json.error && r.json.error.message) {
            message = r.json.error.message;
        } else if (r.json && r.json.message) { // framework throttle envelope
            message = r.json.message;
        }
        if (r.status === 429 && r.retryAfter) {
            message += ' (retry in ' + r.retryAfter + 's)';
        }
        if (r.status === 422 && r.json && r.json.errors) {
            message = Object.values(r.json.errors).flat().join(' ');
        }
        alertMsg(message, 'danger');
    }

    // ---- landing ------------------------------------------------------

    function land(r) {
        var redirect = r.json && r.json.data ? r.json.data.redirect : null;
        if (redirect) {
            window.location.assign(redirect);
            return;
        }
        // No resume target: reload so the server renders the
        // signed-in state over the fresh session.
        window.location.reload();
    }

    async function handleLogin(r, mode) {
        mfaMode = mode;
        if (r.status === 200) { land(r); return; }
        if (r.status === 202) { showStep('mfa'); return; }
        showError(r);
    }

    // ---- MSG91 widget (custom-SDK mode port of @navdhya/otp/widget) --

    var WIDGET_SRC = 'https://verify.msg91.com/otp-provider.js';
    var scriptPromise = null;
    var captchaId = null;
    var widgetReady = null;

    function loadScript() {
        if (scriptPromise) { return scriptPromise; }
        scriptPromise = new Promise(function (resolve, reject) {
            if (document.querySelector('script[src="' + WIDGET_SRC + '"]')) { resolve(); return; }
            var s = document.createElement('script');
            s.src = WIDGET_SRC;
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () { scriptPromise = null; reject(new Error('The OTP service is unreachable.')); };
            document.head.appendChild(s);
        });
        return scriptPromise;
    }

    function ensureCaptcha() {
        if (captchaId && document.getElementById(captchaId)) { return captchaId; }
        captchaId = 'msg91-captcha-' + Math.random().toString(36).slice(2, 10);
        var box = document.createElement('div');
        box.id = captchaId;
        // Offscreen but rendered: the SDK measures into this node; our
        // UI drives everything, so it must never be visible.
        box.setAttribute('style', 'position:fixed;left:-10000px;top:-10000px;width:1px;height:1px;overflow:hidden;');
        document.body.appendChild(box);
        return captchaId;
    }

    function getWidget() {
        if (widgetReady) { return widgetReady; }
        widgetReady = loadScript().then(function () {
            if (typeof window.initSendOTP !== 'function') {
                throw new Error('The OTP service did not initialize.');
            }
            window.initSendOTP({
                widgetId: widget.widgetId,
                tokenAuth: widget.tokenAuth,
                exposeMethods: true,
                captchaRenderId: ensureCaptcha(),
                success: function () {},
                failure: function (r) {
                    throw new Error((r && r.message) || 'The OTP service rejected the request.');
                }
            });
            return new Promise(function (resolve, reject) {
                var waited = 0;
                (function poll() {
                    if (typeof window.sendOtp === 'function') { resolve(); return; }
                    if (waited >= 2000) { reject(new Error('The OTP service did not initialize.')); return; }
                    waited += 50;
                    setTimeout(poll, 50);
                })();
            });
        });
        widgetReady.catch(function () { widgetReady = null; });
        return widgetReady;
    }

    // Dev shortcut mirrors the TS loader: the drill phone never needs
    // the widget SDK, code 123456 mints the token Msg91Client accepts.
    function widgetSend(identifier) {
        var target = identifier.replace(/^\+/, '');
        lastTarget = target;
        if (target.endsWith('9898989898')) { return Promise.resolve(); }
        return getWidget().then(function () {
            return new Promise(function (resolve, reject) {
                window.sendOtp(target,
                    function () { resolve(); },
                    function (r) { reject(new Error((r && r.message) || 'Could not send the code.')); }
                );
            });
        });
    }

    function widgetVerify(code) {
        if (lastTarget && lastTarget.endsWith('9898989898')) {
            return code === '123456'
                ? Promise.resolve('test-token-9898989898')
                : Promise.reject(new Error('Invalid verification code.'));
        }
        return getWidget().then(function () {
            return new Promise(function (resolve, reject) {
                window.verifyOtp(code,
                    function (r) {
                        if (r && r.type === 'success' && typeof r.message === 'string' && r.message !== '') {
                            resolve(r.message);
                            return;
                        }
                        reject(new Error((r && r.message) || 'Invalid verification code.'));
                    },
                    function (r) { reject(new Error((r && r.message) || 'Invalid verification code.')); }
                );
            });
        });
    }

    function widgetRetry(channel) {
        if (lastTarget && lastTarget.endsWith('9898989898')) { return Promise.resolve(); }
        return getWidget().then(function () {
            return new Promise(function (resolve, reject) {
                window.retryOtp(channel,
                    function () { resolve(); },
                    function (r) { reject(new Error((r && r.message) || 'Could not resend the code.')); }
                );
            });
        });
    }

    // ---- ceremony wiring ----------------------------------------------

    function startResendCountdown() {
        var delay = resendDelays[resendCount] || 60;
        var btn = document.getElementById('btn-resend');
        var remaining = delay;
        btn.disabled = true;
        btn.textContent = 'Resend (' + remaining + 's)';
        clearInterval(resendTimer);
        resendTimer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(resendTimer);
                btn.disabled = false;
                btn.textContent = 'Resend code';
                return;
            }
            btn.textContent = 'Resend (' + remaining + 's)';
        }, 1000);
    }

    document.getElementById('btn-send').addEventListener('click', async function () {
        var phone = document.getElementById('phone').value.trim();
        if (!phone) { alertMsg('Enter your phone number.', 'danger'); return; }
        // Dev-shortcut phones attest in the 91… form (Msg91Client's
        // literal shortcut returns 919898989898, and the server compares
        // canonical digits), so the challenge must be requested in that
        // same form — bare 10-digit input would canonical-mismatch. Real
        // widget sends carry the country code their config declares, so
        // this mirrors the production shape.
        if (phone.replace(/^\+/, '').endsWith('9898989898') && phone.replace(/\D/g, '').length === 10) {
            phone = '91' + phone.replace(/\D/g, '');
        }
        busy('btn-send', true);
        try {
            var r = await post('/auth/login/otp', { phone: phone });
            if (r.status !== 202) { showError(r); return; }
            challengeId = r.json.data.challenge_id;
            await widgetSend(phone);
            resendCount = 0;
            showStep('code');
            startResendCountdown();
            alertMsg('Code sent. Enter it below.', 'success');
        } catch (e) {
            alertMsg(e.message, 'danger');
        } finally {
            busy('btn-send', false);
        }
    });

    document.getElementById('btn-verify').addEventListener('click', async function () {
        var code = document.getElementById('code').value.trim();
        if (!code) { alertMsg('Enter the verification code.', 'danger'); return; }
        busy('btn-verify', true);
        try {
            var token = await widgetVerify(code);
            var r = await post('/auth/login/verify', { challenge_id: challengeId, token: token, next: next });
            await handleLogin(r, 'otp');
        } catch (e) {
            alertMsg(e.message, 'danger');
        } finally {
            busy('btn-verify', false);
        }
    });

    document.getElementById('btn-resend').addEventListener('click', async function () {
        try {
            var r = await post('/auth/login/otp/resend', { challenge_id: challengeId });
            if (r.status !== 202) { showError(r); return; }
            await widgetRetry(r.json.data.channel);
            resendCount += 1;
            startResendCountdown();
            alertMsg('Code sent again.', 'success');
        } catch (e) {
            alertMsg(e.message, 'danger');
        }
    });

    document.getElementById('btn-totp').addEventListener('click', async function () {
        var totpCode = document.getElementById('totp').value.trim();
        if (!totpCode) { alertMsg('Enter your authenticator code.', 'danger'); return; }
        busy('btn-totp', true);
        try {
            var r = mfaMode === 'password'
                ? await post('/auth/login/totp', { totp_code: totpCode, next: next })
                : await post('/auth/login/verify', { challenge_id: challengeId, totp_code: totpCode, next: next });
            if (r.status === 200) { land(r); return; }
            showError(r);
        } catch (e) {
            alertMsg(e.message, 'danger');
        } finally {
            busy('btn-totp', false);
        }
    });

    document.getElementById('btn-password').addEventListener('click', async function () {
        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value;
        if (!email || !password) { alertMsg('Enter your email and password.', 'danger'); return; }
        busy('btn-password', true);
        try {
            var r = await post('/auth/login/password', { email: email, password: password, next: next });
            await handleLogin(r, 'password');
        } catch (e) {
            alertMsg(e.message, 'danger');
        } finally {
            busy('btn-password', false);
        }
    });

    document.getElementById('to-password').addEventListener('click', function () { showStep('password'); });
    document.getElementById('to-phone-alt').addEventListener('click', function () { showStep('phone'); });
    document.getElementById('to-phone').addEventListener('click', function () { showStep('phone'); });
    @else
    document.getElementById('btn-signout').addEventListener('click', async function () {
        await post('/auth/logout', {});
        window.location.reload();
    });
    @endif
})();
</script>
</body>
</html>
