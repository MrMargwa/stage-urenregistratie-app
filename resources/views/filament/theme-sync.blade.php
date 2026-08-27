@php
    $user = auth()->user();

    $theme = ($user !== null && in_array($user->theme_mode, ['dark', 'light', 'system'], true))
        ? $user->theme_mode
        : null;
@endphp

<style>
    .fi-btn.fi-color-primary {
        --text: #000 !important;
        --hover-text: #000 !important;
        --dark-text: #000 !important;
        --dark-hover-text: #000 !important;
    }
</style>

<script>
    @if ($theme !== null)
        localStorage.setItem('theme', @js($theme));
    @endif

    document.addEventListener('settings-applied', (event) => {
        const theme = event.detail?.theme ?? 'dark';

        localStorage.setItem('theme', theme);
        window.theme = theme;

        const isDark = theme === 'dark'
            || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', isDark);
    });
</script>
