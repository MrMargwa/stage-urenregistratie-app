@php
    $user = auth()->user();
    $target = $user->target_hours;
    $totalMinutes = $user->totalLoggedMinutes();
    $totalHours = $totalMinutes / 60;
    $formatted = $user->totalLoggedHoursFormatted();
    $percentage = $target > 0 ? min(($totalHours / $target) * 100, 100) : 0;
    $remaining = $target > 0 ? max($target - $totalHours, 0) : 0;
@endphp

@if($target && $target > 0)
    <div style="background:var(--fi-bg);border:1px solid var(--fi-border);border-radius:12px;padding:16px 20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:600;font-size:14px;color:var(--fi-text);">Stage Voortgang</span>
            <span style="font-size:13px;color:var(--fi-text-muted);">{{ $formatted }} / {{ $target }} uren</span>
        </div>
        <div style="width:100%;height:12px;background:var(--fi-border);border-radius:9999px;overflow:hidden;">
            <div style="width:{{ $percentage }}%;height:100%;background:var(--fi-primary-500);border-radius:9999px;transition:width 0.3s;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:13px;color:var(--fi-text-muted);">
            <span>{{ number_format($percentage, 1, ',', '.') }}% voltooid</span>
            <span>{{ number_format($remaining, 1, ',', '.') }} uren resterend</span>
        </div>
    </div>
@else
    <div style="background:var(--fi-bg);border:1px solid var(--fi-border);border-radius:12px;padding:16px 20px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-weight:600;font-size:14px;color:var(--fi-text);">Stage Voortgang</span>
            <span style="font-size:13px;color:var(--fi-text-muted);">–</span>
        </div>
        <p style="margin:4px 0 0;font-size:13px;color:var(--fi-text-muted);">
            Stel je totale stage-uren in via je <a href="{{ route('filament.admin.pages.settings') }}" style="color:var(--fi-primary-500);text-decoration:underline;">instellingen</a>.
        </p>
    </div>
@endif
