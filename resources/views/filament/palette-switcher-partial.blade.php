@php
    $manager = app('filament.palette');
    $active = $manager->get();
    $palettes = config('filament-palette.palette', []);
    $hidden = $manager->isHidden();
@endphp

@if(! $hidden && count($palettes))
    <div style="display:grid;grid-template-columns:repeat(9,1fr);gap:4px;padding:8px 12px;align-items:center;">
        @foreach($palettes as $name => $palette)
            <form method="POST" action="{{ route('palette.apply', $name) }}" style="display:contents;">
                @csrf
                <button
                    type="submit"
                    title="{{ strtoupper($name) }}"
                    style="display:block;width:16px;height:16px;border-radius:9999px;cursor:pointer;border:none;padding:0;background-color:{{ $palette['primary'][400] }};{{ $active === $name ? 'box-shadow:0 0 0 2px ' . $palette['primary'][700] . ';transform:scale(1.2);' : '' }}"
                ></button>
            </form>
        @endforeach
    </div>
@endif
