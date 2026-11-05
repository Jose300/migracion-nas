@php
    $type = $type ?? 'navbar';
    $customLogo = $type === 'login' 
        ? (setting('logo_login') ?: setting('logo_navbar')) 
        : setting('logo_navbar');
    $appName = setting('app_name', config('app.name', 'Sistema Base'));

    // Dimensiones condicionadas estrictas para garantizar proporciones perfectas
    $isLogin = ($type === 'login');
    $maxHeight = $isLogin ? '65px' : '42px';
    $maxWidth  = $isLogin ? '280px' : '185px';
    $iconSize  = $isLogin ? '44px' : '36px';
    $svgSize   = $isLogin ? '30' : '24';
    $fontSize  = $isLogin ? 'h1' : 'h2';
    $textLimit = $isLogin ? '260px' : '140px';
@endphp

@if($customLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($customLogo))
    <div class="logo-conditioned-wrapper d-inline-flex align-items-center justify-content-center" 
         style="max-width: {{ $maxWidth }}; max-height: {{ $maxHeight }}; width: auto; height: auto; overflow: hidden;">
        <img src="{{ asset('storage/' . $customLogo) }}" 
             alt="{{ $appName }}" 
             class="img-fluid" 
             style="max-height: {{ $maxHeight }}; max-width: {{ $maxWidth }}; width: auto; height: auto; object-fit: contain; display: block;"
             loading="eager">
    </div>
@else
    <div class="d-inline-flex align-items-center justify-content-center" style="max-width: 100%;">
        <div class="bg-primary-lt p-2 rounded-3 me-2 d-flex align-items-center justify-content-center flex-shrink-0" 
             style="width: {{ $iconSize }}; height: {{ $iconSize }};">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building-store text-primary m-0" 
                 width="{{ $svgSize }}" height="{{ $svgSize }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 21l18 0" />
                <path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4" />
                <path d="M5 21l0 -10.15" />
                <path d="M19 21l0 -10.15" />
                <path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4" />
            </svg>
        </div>
        @if(strtolower(trim($appName)) === 'sistema base')
            <span class="{{ $fontSize }} mb-0 fw-bold text-nowrap" style="letter-spacing: -0.02em;">Sistema<span class="text-primary">Base</span></span>
        @else
            <span class="{{ $fontSize }} mb-0 fw-bold text-dark text-truncate" style="max-width: {{ $textLimit }};" title="{{ $appName }}">{{ $appName }}</span>
        @endif
    </div>
@endif
