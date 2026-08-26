@php
    $colors = [
        'red' => '#EF4444',
        'orange' => '#F97316',
        'amber' => '#F59E0B',
        'yellow' => '#EAB308',
        'lime' => '#84CC16',
        'green' => '#22C55E',
        'emerald' => '#10B981',
        'teal' => '#14B8A6',
        'cyan' => '#06B6D4',
        'sky' => '#0EA5E9',
        'blue' => '#3B82F6',
        'indigo' => '#6366F1',
        'violet' => '#8B5CF6',
        'purple' => '#A855F7',
        'fuchsia' => '#D946EF',
        'pink' => '#EC4899',
    ];
    $active = $accentColor ?? 'amber';
@endphp

<div style="display:flex;flex-wrap:wrap;gap:8px;padding:4px 0;">
    @foreach($colors as $name => $hex)
        <button
            type="button"
            wire:click="$set('data.accent_color', '{{ $name }}')"
            title="{{ ucfirst($name) }}"
            style="
                width:32px;height:32px;border-radius:9999px;border:none;cursor:pointer;padding:0;
                background-color:{{ $hex }};
                @if($active === $name)
                    box-shadow:0 0 0 3px var(--fi-bg), 0 0 0 5px {{ $hex }};
                    transform:scale(1.15);
                @else
                    opacity:0.7;
                    transform:scale(1);
                @endif
                transition:all 0.15s ease;
            "
        ></button>
    @endforeach
</div>
