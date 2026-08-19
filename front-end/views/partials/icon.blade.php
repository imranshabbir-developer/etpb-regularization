{{--
    Inline icon set. Stroke-based, 24x24 viewBox, sized by CSS.
    Usage: @include('partials.icon', ['name' => 'home'])
--}}
@php $n = $name ?? 'dot'; @endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($n)
        @case('home')
            <path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>
            @break
        @case('file')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
            <path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>
            @break
        @case('inbox')
            <path d="M3 12h5l2 3h4l2-3h5"/>
            <path d="M5.5 5h13l2.5 7v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/>
            @break
        @case('scale')
            <path d="M12 3v18"/><path d="M7 21h10"/><path d="M5 7h14"/>
            <path d="M5 7 2.5 13h5z"/><path d="M19 7l-2.5 6h5z"/>
            @break
        @case('cash')
            <rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>
            <path d="M6 10v4"/><path d="M18 10v4"/>
            @break
        @case('gavel')
            <path d="m14 5 5 5"/><path d="m11.5 7.5 5 5"/>
            <path d="m16.5 2.5 5 5"/><path d="M12.5 9 4 17.5 6.5 20l8.5-8.5"/><path d="M3 22h8"/>
            @break
        @case('shield')
            <path d="M12 3l7.5 3v5.5c0 4.5-3 8-7.5 9.5-4.5-1.5-7.5-5-7.5-9.5V6z"/>
            <path d="m9 12 2 2 4-4"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/>
            <path d="M16.5 5.2a3.2 3.2 0 0 1 0 5.6"/><path d="M18 14.4a6.5 6.5 0 0 1 3.5 5.6"/>
            @break
        @case('chart')
            <path d="M3 3v18h18"/><path d="M7 15v-4"/><path d="M12 15V7"/><path d="M17 15v-6"/>
            @break
        @case('map')
            <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>
            @break
        @case('cog')
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 2.5v2.2M12 19.3v2.2M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6"/>
            @break
        @case('logout')
            <path d="M15 17v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v2"/>
            <path d="M19 12H9"/><path d="m16 9 3 3-3 3"/>
            @break
        @case('menu')
            <path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>
            @break
        @case('plus')
            <path d="M12 5v14"/><path d="M5 12h14"/>
            @break
        @case('check')
            <path d="m4.5 12.5 5 5 10-11"/>
            @break
        @case('alert')
            <path d="M12 8.5v5"/><circle cx="12" cy="17" r=".6" fill="currentColor"/>
            <path d="M10.3 3.9 2.6 17.4A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3.1L13.7 3.9a2 2 0 0 0-3.4 0z"/>
            @break
        @case('info')
            <circle cx="12" cy="12" r="9"/><path d="M12 11v5.5"/><circle cx="12" cy="7.8" r=".7" fill="currentColor"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5.4l3.3 2"/>
            @break
        @case('doc-search')
            <path d="M13 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5"/><path d="M13 3v5h5V9"/>
            <circle cx="17" cy="16" r="3.2"/><path d="m19.4 18.4 2.1 2.1"/>
            @break
        @case('lock')
            <rect x="4.5" y="10.5" width="15" height="10" rx="2"/>
            <path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.6"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>
            @break
        @case('empty')
            <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="M4 7.5 12 12l8-4.5"/><path d="M12 12v9"/>
            @break
        @case('sun')
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>
            @break
        @case('moon')
            <path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/>
            @break
        @case('chat')
            <path d="M21 12a8 8 0 0 1-8 8H8l-4 3v-4.6A8 8 0 1 1 21 12z"/>
            <path d="M9 10h6"/><path d="M9 14h4"/>
            @break
        @case('close')
            <path d="M6 6l12 12"/><path d="M18 6L6 18"/>
            @break
        @case('send')
            <path d="M4 12h13"/><path d="m12 5 7 7-7 7"/>
            @break
        @case('list')
            <path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/>
            <circle cx="3.6" cy="6" r=".9" fill="currentColor"/>
            <circle cx="3.6" cy="12" r=".9" fill="currentColor"/>
            <circle cx="3.6" cy="18" r=".9" fill="currentColor"/>
            @break
        @case('download')
            <path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M4 19h16"/>
            @break
        @case('arrow-right')
            <path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>
            @break
        @default
            <circle cx="12" cy="12" r="3.5"/>
    @endswitch
</svg>
