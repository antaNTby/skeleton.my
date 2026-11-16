<?php

/**
 * Application bootstrap — one robust layout only.
 *
 * Flow:
 *   public/index.php
 *     → vendor/autoload
 *     → load .env → $_ENV
 *     → Flight::app()
 *     → config.php (literals) + Config::mergeEnv
 *     → apply flight.* / timezone / CSP nonce
 *     → services.php (Tracy, SimplePdo, Twig, Session, Dice)
 *     → routes.php
 *     → $app->start()
 */
use App\Utils\Config;
use App\Utils\Env;

$ds          = DIRECTORY_SEPARATOR;
$projectRoot = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, dirname( __DIR__, 2 ) );
$appDir      = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'app' );
$configDir   = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'app/config' );
$logsDir     = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'app/logs' );

$templateDir     = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'templates' );
$compileDir      = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'app/cache/smarty/compile_dir' );
$cacheDir        = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'app/cache/smarty/cache_dir' );
$configDirSmarty = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . DIRECTORY_SEPARATOR . 'app/config/smarty_config_dir' );

// Проверяем и создаём необходимые директории
$directories = [$appDir, $configDir, $templateDir, $compileDir, $cacheDir, $configDirSmarty, $logsDir];
foreach ( $directories as $directory ) {
    if ( !is_dir( $directory ) ) {
        if (
            !mkdir( $directory, 0775, true )
            && !is_dir( $directory )
        ) {
            throw new RuntimeException(
                "Unable to create directory: {$directory}"
            );
        }
    }

}

define( 'PROJECT_ROOT', $projectRoot );
define( 'APP_DIR', $appDir );
define( 'CONFIG_DIR', $configDir );

define( 'TEMPLATE_DIR', $templateDir );
define( 'COMPILE_DIR', $compileDir );
define( 'CACHE_DIR', $cacheDir );
define( 'CONFIG_DIR_SMARTY', $configDirSmarty );
define( 'LOGS_DIR', $logsDir );

require PROJECT_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

dump( __DIR__ );
dump( PROJECT_ROOT );
dump( APP_DIR );

// Layer 1: .env / real environment (secrets + deploy overrides)
Env::load( PROJECT_ROOT . DIRECTORY_SEPARATOR . '.env' );

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
if ( file_exists( $configPath ) === false ) {
    Flight::halt(
        500,
        'Config file not found. Copy app/config/config_sample.php to app/config/config.php'
    );
}

// Prefer instance over static facade for testability
$app = Flight::app();

// Layer 2: literal config.php (Runway config:set / config:get safe)
$fileConfig = require $configPath;

// Layer 3: merge — env wins for mapped keys when set
$merged = Config::mergeEnv( $fileConfig, $_ENV );
$config = new Config( $merged );

// Side effects from config (not inside the return array)
$timezone = (string) $config->get( 'app.timezone', 'UTC+3' );
if ( $timezone !== '' ) {
    date_default_timezone_set( $timezone );
}

if ( function_exists( 'mb_internal_encoding' ) === true ) {
    mb_internal_encoding( 'UTF-8' );
}

error_reporting( E_ALL );

// Flight core settings from merged Config
$app->set( 'flight.base_url', $config->baseUrl() );
$app->set( 'flight.case_sensitive', false );
$app->set( 'flight.log_errors', true );
// Let Tracy handle errors when debug is on
$app->set( 'flight.handle_errors', $config->isDebug() === false );
$app->set( 'flight.views.path', TEMPLATE_DIR );
$app->set( 'flight.views.extension', '.tpl.html' );
$app->set( 'flight.content_length', false );
// Harden: do not allow X-HTTP-Method-Override / _method spoofing unless you need it
$app->set( 'flight.allow_method_override', false );
// Harden: keep native View templates inside flight.views.path (Twig has its own loader root).
// Harmless on older core that ignores the key; applied when flightphp/core supports it.
$app->set( 'flight.views.restrict_to_path', true );

// CSP nonce per request (used by SecurityHeadersMiddleware + Twig globals)
$nonce = bin2hex( random_bytes( 16 ) );
$app->set( 'csp_nonce', $nonce );

// Expose project root for commands/services that need filesystem paths
$app->set( 'app.project_root', PROJECT_ROOT );

// Services: Tracy, DB, Twig, Session, Dice + container handler
require __DIR__ . DIRECTORY_SEPARATOR . 'services.php';

$router = $app->router();
require __DIR__ . DIRECTORY_SEPARATOR . 'routes.php';

$app->start();
