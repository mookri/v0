<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto;">
        <h1 style="font-size: 20px; margin: 0 0 12px;">{{ $title }}</h1>
        @if($body)
            <p style="margin: 0 0 16px;">{{ $body }}</p>
        @endif
        <p style="margin: 24px 0 0; font-size: 12px; color: #6b7280;">Navdhya &middot; transactional notification</p>
    </div>
</body>
</html>
