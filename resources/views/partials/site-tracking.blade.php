{{-- Rastreamento opcional (Google Analytics 4 & Meta Pixel) — configure GA4_MEASUREMENT_ID / META_PIXEL_ID no .env --}}
@php
    $ga4Id = config('services.analytics.ga4_id');
    $metaPixelId = config('services.analytics.meta_pixel_id');
@endphp

@if(!empty($ga4Id))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', @json($ga4Id));
</script>
@endif

@if(!empty($metaPixelId))
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', @json($metaPixelId));
    fbq('track', 'PageView');
</script>
@endif
