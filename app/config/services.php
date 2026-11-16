<?php

/**
 * Services + Dice DI wiring.
 *
 * Bootstrap (web) provides:
 *   @var \flight\Engine<object> $app
 *   @var \App\Utils\Config      $config
 *   @var string                 $projectRoot
 *   @var string                 $ds
 *   @var string                 $nonce
 *
 * Runway 1.x also requires this file after loading config.php, with $config as
 * the raw array and no $app. Skip full wiring in that CLI context.
 */

use App\Utils\Config;
use App\Utils\DatabaseFactory;
use flight\Container;
use flight\database\SimplePdo;
use flight\debug\tracy\TracyExtensionLoader;
use flight\Engine;
use flight\net\Request;
use flight\net\Route;
use flight\Session;
use Smarty\Smarty;
use Tracy\Debugger;

// Runway loads config.php then services.php for config:get/set. No Engine yet.
if ( !isset( $app ) || !( $app instanceof Engine ) || !( $config instanceof Config ) ) {
    return;
}

// ---------------------------------------------------------------------------
// Session (flightphp/session)
// ---------------------------------------------------------------------------
$sessionOptions = [
    'prefix'      => (string) $config->get( 'session.prefix', 'flight_sess_' ),
    'auto_commit' => true,
];
$savePath = $config->get( 'session.save_path' );
if ( $savePath !== null && $savePath !== '' ) {
    $sessionOptions['save_path'] = (string) $savePath;
}
// Defer session start on CLI
if ( PHP_SAPI === 'cli' ) {
    $sessionOptions['start_session'] = false;
}
$session = new Session( $sessionOptions );

// ---------------------------------------------------------------------------
// Tracy
// ---------------------------------------------------------------------------
$logDir = $projectRoot . $ds . 'app' . $ds . 'log';
if ( !is_dir( $logDir ) ) {
    mkdir( $logDir, 0775, true );
}

if ( $config->isDebug() ) {
    Debugger::enable( Debugger::Development );
} else {
    Debugger::enable( Debugger::Production );
}
Debugger::$logDirectory = $logDir;
Debugger::$strictMode   = true;

// Хук для автоматического логирования критических ошибок и переноса HTML-дампов
Debugger::$onFatalError[] = static function ( \Throwable $exception ) use ( $logDir ) {
    Debugger::log( $exception, Debugger::EXCEPTION );

    $htmlDumpDir = $logDir . DIRECTORY_SEPARATOR . 'tracy_html';
    if ( !is_dir( $htmlDumpDir ) ) {
        mkdir( $htmlDumpDir, 0775, true );
    }

    $files = glob( $logDir . DIRECTORY_SEPARATOR . 'exception-*.*' );
    if ( is_array( $files ) ) {
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                rename( $file, $htmlDumpDir . DIRECTORY_SEPARATOR . basename( $file ) );
            }
        }
    }
};

//  Подключаем расширения Tracy Bar для браузера
if ( Debugger::$showBar && php_sapi_name() !== 'cli' ) {

    Flight::set( 'flight.content_length', false );

    static $isLoaderInitialized = false;
    if ( $isLoaderInitialized === false ) {
        // Передаем данные в Tracy
        $loader = new TracyExtensionLoader( Flight::app(), [
            'session_data' => $session->getAll(),
        ] );

        $isLoaderInitialized = true;
    }

}
// ---------------------------------------------------------------------------
// Database (SimplePdo) — optional if driver is empty
// ---------------------------------------------------------------------------
$db = null;
if ( DatabaseFactory::isEnabled( $config ) ) {
    $db = DatabaseFactory::create( $config );
    // Optional Flight::db() for ecosystem code; app layer should inject SimplePdo
    $app->map( 'db', function () use ( $db ) {
        return $db;
    } );
}

// ---------------------------------------------------------------------------
// Smarty
// ---------------------------------------------------------------------------

$templateDir = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . $ds . 'templates' );
$compileDir  = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . $ds . 'app/cache/smarty/compile_dir' );
$cacheDir    = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . $ds . 'app/cache/smarty/cache_dir' );
$configDir   = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot . $ds . 'app/config/smarty_config_dir' );

// Проверяем и создаём необходимые директории
$directories = [$templateDir, $compileDir, $cacheDir, $configDir];
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

$smarty = new Smarty();

// Настройка путей Smarty 5
$smarty->setTemplateDir( $templateDir );
$smarty->setCompileDir( $compileDir );
$smarty->setCacheDir( $cacheDir );
$smarty->setConfigDir( $configDir );

$smarty->compile_id = 'skeleton_';

$smarty->assign( 'csp_nonce', $nonce );
$smarty->assign( 'app_env', $config->env() );
// Always trailing slash so {{ base_url }}posts joins correctly under subpaths
$smarty->assign( 'base_url', $config->baseUrl() );

// Map Flight render → Twig (single documented view path)
$app->map( 'renderSmarty', function (
    string $template,
    array  $data = []
) use ( $app, $smarty ) {
    // Allow "welcome" or "welcome.tpl.html"
    if ( substr( $template, -9 ) !== '.tpl.html' ) {
        $template .= '.tpl.html';
    }

    $smarty->assign( $data );
    // $smarty->fetch( $template );
    $app->response()->write( $smarty->fetch( $template ) );
} );

// ---------------------------------------------------------------------------
// flight\Container + Engine dependencies
// ---------------------------------------------------------------------------
// Создаем экземпляр встроенного контейнера Flight
$container = new Container();

// Регистрируем общие экземпляры (Singletons / Shared), которые мы уже создали
$container->singleton( Engine::class, $app );
$container->singleton( Config::class, $config );
$container->singleton( Smarty::class, $smarty );
$container->singleton( Session::class, $session );

if ( $db instanceof SimplePdo ) {
    $container->singleton( SimplePdo::class, $db );
}

$container->set( Route::class, function () use ( $app ) {
    return $app->router()->route( $app->request() );
} );

// Регистрируем обработчик контейнера в Flight
$app->registerContainerHandler( function (
    $class,
    $params
) use ( $container ) {
    // Встроенный контейнер Flight принимает параметры в метод get() вторым аргументом
    return $container->get( $class, $params );
} );

// Помощник для кода вне роутов: $app->make(SomeClass::class)
$app->map( 'make', function (
    $class,
    $params = []
) use ( $container ) {
    return $container->get( $class, $params );
} );

$session->set( 'test_sess', $nonce );

bdump( $container );
