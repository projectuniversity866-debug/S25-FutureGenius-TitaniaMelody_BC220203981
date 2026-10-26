<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    | When HTTPS ends at a load balancer, Cloudflare or cPanel's proxy, the app
    | only sees plain HTTP from that proxy. Trusting it lets Laravel read the
    | X-Forwarded-* headers, so links, redirects and secure cookies use https.
    |
    |   TRUSTED_PROXIES=*                     trust whoever connects (behind a proxy only)
    |   TRUSTED_PROXIES=10.0.0.1,10.0.0.2     trust these addresses
    |
    | Leave it empty when the server terminates HTTPS itself (roadmap 5.1).
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
