<?php

declare ( strict_types = 1 );

namespace App\Middleware;

use flight\Engine;
use Tracy\Debugger;

/**
 * Canonical middleware example — constructor injection + before().
 */
class SecurityHeadersMiddleware
{
    /** @var Engine<object> */
    private $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct( Engine $app )
    {
        $this->app = $app;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function before( array $params ): void
    {

        $response = $this->app->response();
        $nonce    = $this->app->get( 'csp_nonce' );

        // Tracy debug bar injects inline CSS in development
        // $styleSrc = "'self' 'nonce-{$nonce}'";
        $allowedStyles = "'self' 'nonce-{$nonce}'";
        if ( class_exists( Debugger::class ) && Debugger::$showBar === true ) {
            $allowedStyles = "'self' 'unsafe-inline' https://*.googleapis.com https://*.cdnfonts.com";
            // $styleSrc = "'self' 'unsafe-inline'";
        }

        $allowedFonts  = "'self' https://*.gstatic.com https://*.cdnfonts.com";
        $allowedImages = "'self' data:"; // Разрешаем локальные картинки и встроенные Base64 / SVG

        $csp = $nonce
        ? "default-src 'self'; " .
        "script-src 'self' 'nonce-{$nonce}' 'sha256-ieoeWczDHkReVBsRBqaal5AFMlBtNjMzgwKvLqi/tSU='; " .
        "style-src {$allowedStyles}; " .
        "font-src {$allowedFonts}; " .
        "img-src {$allowedImages}"
        : "default-src 'self'; img-src {$allowedImages}";

        $response->header( 'X-Frame-Options', 'SAMEORIGIN' );
        $response->header( 'Content-Security-Policy', $csp );
        // 3. Защита от XSS (для старых браузеров)
        $response->header( 'X-XSS-Protection', '1; mode=block' );

        // 4. Защита от сниффинга MIME-типов (MIME sniffing)
        // Браузер не будет пытаться угадать тип файла и запустить его как скрипт, если сервер отдал его как текст
        $response->header( 'X-Content-Type-Options', 'nosniff' );

        // 5. Управление передачей Referrer (откуда пришел пользователь)
        // Скрывает параметры из URL при переходе на другие сайты
        // Было:
        // $response->header( 'Referrer-Policy', 'no-referrer-when-downgrade' );
        // Стало (современный стандарт безопасности):
        $response->header( 'Referrer-Policy', 'strict-origin-when-cross-origin' );

        // 6. Strict-Transport-Security (HSTS)
        // Принудительно заставляет браузер обращаться к сайту ТОЛЬКО по безопасному протоколу HTTPS
        $response->header( 'Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload' );

        // 7. Ограничение доступа к API браузера (камера, микрофон, геолокация)
        $response->header( 'Permissions-Policy', 'geolocation=(), microphone=(), camera=()' );
    }
}
