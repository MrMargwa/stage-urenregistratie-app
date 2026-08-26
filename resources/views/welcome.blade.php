<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Stage Urenregistratie') }}</title>
    <meta http-equiv="refresh" content="0;url={{ route('filament.admin.pages.dashboard') }}">
    <style>
        body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #0a0a0a; color: #EDEDEC; }
        a { color: #06b6d4; }
    </style>
</head>
<body>
    <p>Je wordt doorgestuurd... <a href="{{ route('filament.admin.pages.dashboard') }}">Klik hier als je niet automatisch doorgestuurd wordt.</a></p>
</body>
</html>
