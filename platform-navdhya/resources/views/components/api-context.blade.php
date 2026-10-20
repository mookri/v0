{{-- API context for interactive admin-shell fetches (doc 06 §8.4):
     safe methods authenticate with the session cookie, the workspace
     header scopes the call, memberships are JSON for pickers, and the
     CSRF token feeds JS-built mutation forms (web form posts only —
     API mutations stay bearer/CSRF-exempt by design). --}}
<meta name="api-base" content="/api/v1">
<meta name="api-tenant" content="{{ $defaultTenantId }}">
<meta name="api-memberships" content="{{ json_encode($memberships, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
