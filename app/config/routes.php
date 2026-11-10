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

        $router->get( '/companies', [CompanyController::class, 'index'] );
        $router->get( '/companies/@id:[0-9]+', [CompanyController::class, 'show'] );

        $router->group( '/api', function ( Router $router ) {
            $router->get( '/posts', [PostController::class, 'apiIndex'] );
            $router->get( '/companies', [CompanyController::class, 'apiIndex'] );

            $router->get( '/posts/@id:[0-9]+', [PostController::class, 'apiShow'] );
            $router->get( '/companies/@id:[0-9]+', [CompanyController::class, 'apiShow'] );
        } );
    }
}, [SecurityHeadersMiddleware::class] );
