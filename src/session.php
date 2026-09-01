<?php
if (session_status() === PHP_SESSION_NONE) {
    // Behind the reverse proxy PHP only sees plain HTTP, so trust the proxy's header
    // to decide whether the cookie may be marked Secure.
    $isHttps = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,   // not readable from JavaScript
        'samesite' => 'Lax',  // not sent on cross-site requests
        'secure' => $isHttps, // HTTPS-only once a certificate is in place
    ]);
    session_start();
}
