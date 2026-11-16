<?php

/**
 * Application routes.
 *
 * @var \flight\net\Router $router
 * @var \flight\Engine<object> $app
 * @var \App\Utils\Config $config
 */

use App\Controller\CompanyController;
use App\Controller\HomeController;
use App\Controller\MySubModelController;
use App\Controller\PostController;
use App\Middleware\SecurityHeadersMiddleware;
use App\Utils\DatabaseFactory;
use flight\net\Router;

$router->group( '', function ( Router $router ) use ( $config ) {
    $router->get( '/', [HomeController::class, 'index'] );

    // Posts demo requires SimplePdo (PostController). Skip when DB is disabled.
    if ( DatabaseFactory::isEnabled( $config ) ) {

        $router->get( '/MySub/@table_name', [MySubModelController::class, 'index'] );
        $router->get( '/MySub/@table_name/@id:[0-9]+', [MySubModelController::class, 'show'] );

        $router->get( '/posts', [PostController::class, 'index'] );
        $router->get( '/posts/@id:[0-9]+', [PostController::class, 'show'] );

        $router->get( '/@table_name', [MySubModelController::class, 'index'] );
        $router->get( '/@table_name/@id:[0-9]+', [MySubModelController::class, 'show'] );

        $router->group( '/api', function ( Router $router ) {
            $router->get( '/posts', [PostController::class, 'apiIndex'] );
            $router->get( '/@table_name', [MySubModelController::class, 'apiIndex'] );

            $router->get( '/posts/@id:[0-9]+', [PostController::class, 'apiShow'] );
            $router->get( '/@table_name/@id:[0-9]+', [MySubModelController::class, 'apiShow'] );
        } );
    }
}, [SecurityHeadersMiddleware::class] );
