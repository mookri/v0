@extends('layouts.vertical', ['title' => 'Security', 'subTitle' => 'Account'])

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Coded flash for the shared profile/MFA/session actions — the
             one-time setup key and recovery codes ride it and die with
             this single render (doc 06 §7.2, §7.5). --}}
        <x-flash-alerts />
        <div class="alert alert-danger d-none" id="security-alert" role="alert"></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">Profile</h5>
            </div>
            <div class="card-body">
                {{-- Posts the shared updateProfile action (doc 06 §7.1);
                     name is prefilled server-side so the form works
                     without JS. Email is the sign-in identity and is not
                     editable on this surface. --}}
                <form method="POST" action="{{ url('/account/security/profile') }}" id="profile-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="profile-name">Name</label>
                        <input class="form-control" id="profile-name" name="name" value="{{ old('name', auth()->user()?->name) }}" maxlength="255" required>
                    </div>
                    <p class="text-muted mb-3">Email: <span class="font-monospace">{{ auth()->user()?->email }}</span> — sign-in identity, not editable here.</p>
                    <button class="btn btn-primary" type="submit">Save profile</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">Two-factor authentication</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" id="mfa-status">Loading status…</p>

                {{-- Not enabled: enroll is two posts since I2 — an emailed
                     step-up code proves possession of the account's email
                     before a TOTP secret can be attached (a hijacked session
                     must not be able to lock the owner out). The setup key
                     arrives on the flash after the verify redirect, exactly as
                     the enroll one used to. --}}
                <form method="POST" action="{{ url('/account/security/mfa/enroll/step-up') }}" id="mfa-stepup-form" class="mb-3 d-none">
                    @csrf
                    <button class="btn btn-primary" type="submit">Send verification code to my email</button>
                </form>

                @if (filled(session('mfa_enroll_stepup_challenge')))
                    <form method="POST" action="{{ url('/account/security/mfa/enroll/verify') }}" id="mfa-stepup-verify-form" class="mb-3 d-none">
                        @csrf
                        <input type="hidden" name="challenge_id" value="{{ session('mfa_enroll_stepup_challenge') }}">
                        <label class="form-label" for="mfa-stepup-code">Verification code (from your email)</label>
                        <div class="input-group">
                            <input class="form-control font-monospace" id="mfa-stepup-code" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
                            <button class="btn btn-primary" type="submit">Get setup key</button>
                        </div>
                    </form>
                @endif

                <form method="POST" action="{{ url('/account/security/mfa/confirm') }}" id="mfa-confirm-form" class="mb-3 d-none">
                    @csrf
                    <label class="form-label" for="mfa-confirm-code">Confirm code (from your app)</label>
                    <div class="input-group">
                        <input class="form-control font-monospace" id="mfa-confirm-code" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required>
                        <button class="btn btn-primary" type="submit">Confirm &amp; enable</button>
                    </div>
                </form>

                <form method="POST" action="{{ url('/account/security/mfa/recovery-codes') }}" id="mfa-regenerate-form" class="mb-3 d-none">
                    @csrf
                    <label class="form-label" for="mfa-regenerate-code">New recovery codes (code from your app)</label>
                    <div class="input-group">
                        <input class="form-control font-monospace" id="mfa-regenerate-code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
                        <button class="btn btn-outline-primary" type="submit" data-confirm="Discard your current recovery codes and generate new ones?">Regenerate</button>
                    </div>
                </form>

                <form method="POST" action="{{ url('/account/security/mfa/revoke') }}" id="mfa-revoke-form" class="mb-3 d-none">
                    @csrf
                    <label class="form-label" for="mfa-revoke-code">Disable two-factor (code from your app)</label>
                    <div class="input-group">
                        <input class="form-control font-monospace" id="mfa-revoke-code" name="code" inputmode="numeric" autocomplete="one-time-code" required>
                        <button class="btn btn-outline-danger" type="submit" data-confirm="Disable two-factor authentication? Sensitive actions will refuse until you enroll again.">Disable</button>
                    </div>
                </form>

                <noscript>
                    <div class="alert alert-warning mb-0">
                        Two-factor status loads live data and needs JavaScript.
                    </div>
                </noscript>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">Web sessions</h5>
                <form method="POST" action="{{ url('/account/security/sessions/revoke-others') }}" id="sessions-revoke-form">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm" type="submit" data-confirm="Sign out every other browser and device?">Sign out other sessions</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>IP address</th>
                            <th>Device</th>
                            <th>Label</th>
                            <th>Last active</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody id="session-rows"></tbody>
                    </table>
                </div>

                <noscript>
                    <div class="alert alert-warning mt-3 mb-0">
                        This list loads live data from the platform API and needs JavaScript.
                    </div>
                </noscript>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-bottom')
    @vite('resources/js/pages/account-security.js')
@endsection
