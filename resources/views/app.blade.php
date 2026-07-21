<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="navy"
    @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? 'system' }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }

        {{-- Public marketing theme background. Guarded with :not(.dark) so it
                 never overrides a signed-in user's personal dark-mode dashboard. --}} html[data-theme='navy']:not(.dark) {
            background-color: #090d16;
        }

        html[data-theme='fairway']:not(.dark) {
            background-color: #faf9f6;
        }

        html[data-theme='electric']:not(.dark) {
            background-color: #0d1117;
        }
    </style>

    @php($siteLogo = \App\Models\SiteSetting::get('site_logo') ? asset('storage/'.\App\Models\SiteSetting::get('site_logo')) : asset('logo.jpg'))
    <link rel="icon" href="{{ $siteLogo }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ $siteLogo }}">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    <x-inertia::head>
        <title>{{ config('app.name', 'PickleHub') }}</title>
    </x-inertia::head>
</head>

<body class="font-sans antialiased">
    <x-inertia::app />
</body>

</html>
