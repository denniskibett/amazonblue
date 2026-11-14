<?php

/*
 * Mobile app API (owner, 11 Nov 2026): /api/mobile/v1, Sanctum tokens, one per phone. Off by default; the test APK
 * talks to it once MOBILE_API_ENABLED=true. The APK itself is served from the download page (/app).
 */
return [
    'enabled' => (bool) env('MOBILE_API_ENABLED', false),
    // a token lasts this many days, then the app signs in again
    'token_days' => (int) env('MOBILE_TOKEN_DAYS', 30),
    // the test APK on the download page: file under storage/app/public/app/, its version and what changed
    'apk' => [
        'file' => env('MOBILE_APK_FILE', 'aimzone-test.apk'),
        'version' => env('MOBILE_APK_VERSION', '0.1.0'),
        'notes' => env('MOBILE_APK_NOTES', 'First test build: sign in, home, borrow, repay, statement.'),
    ],
];
