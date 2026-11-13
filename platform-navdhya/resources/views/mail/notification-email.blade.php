<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #FFF7ED; color: #2C2033; line-height: 1.6; margin: 0; padding: 32px 16px;">
    <div style="max-width: 580px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #EFE3D3; border-radius: 8px; padding: 32px; box-shadow: 0 2px 4px rgba(44, 32, 51, 0.04);">
        <div style="border-bottom: 2px solid #EFE3D3; padding-bottom: 16px; margin-bottom: 24px;">
            <span style="font-size: 20px; font-weight: bold; color: #2C2033; letter-spacing: 0.5px;">Navdhya</span>
            <span style="display: inline-block; width: 6px; height: 6px; background-color: #F16A24; border-radius: 50%; margin-left: 4px; vertical-align: middle;"></span>
        </div>
        <h1 style="font-size: 22px; font-weight: 600; color: #2C2033; margin: 0 0 16px; line-height: 1.3;">{{ $title }}</h1>
        @if($body)
            <div style="margin: 0 0 24px; color: #2C2033; font-size: 15px; line-height: 1.6;">
                {{ $body }}
            </div>
        @endif
        <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #EFE3D3; font-size: 12px; color: #2A2421; line-height: 1.5;">
            <p style="margin: 0;">Navdhya Sacred Ritual Services &middot; Transactional Notification</p>
            <p style="margin: 4px 0 0; color: #2A2421;">Preserving sacred traditions with authenticity and devotion.</p>
        </div>
    </div>
</body>
</html>
