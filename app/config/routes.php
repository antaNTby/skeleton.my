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
use App\Controller\SchemaVisualizerController;
use App\Middleware\SecurityHeadersMiddleware;
use App\Utils\DatabaseFactory;
use App\Utils\Schema\RelationManager;

##### // 1. Страница визуализации (можно ограничить только для dev-окружения)
##### Flight::route( 'GET /dev/schema-map', function () use ( $container ) {
#####     /** @var SchemaManager $schemaManager */
#####     $schemaManager = $container->get( SchemaManager::class );
#####     /** @var RelationManager $relationManager */
#####     $relationManager = $container->get( RelationManager::class );
#####
#####     $mermaidCode = buildMermaidErDiagram( $schemaManager, $relationManager );
#####     $tablesData  = [];
#####
#####     foreach ( $schemaManager->all() as $tableName => $schema ) {
#####         $tablesData[$tableName] = [
#####             'schema'    => $schema->toArray(),
#####             'relations' => $relationManager->forTable( $tableName ),
#####         ];
#####     }
#####
#####     // Отдаём автономную HTML-страницу (можно вынести в Latte / PHP-шаблон)
#####     renderSchemaVisualizerView( $mermaidCode, $tablesData );
##### } );

use App\Utils\Schema\SchemaManager;
use flight\net\Router;

Flight::route( 'GET /dev/schema-map', [SchemaVisualizerController::class, 'index'] );
Flight::route( 'GET /dev/schema-diagram', [SchemaVisualizerController::class, 'diagram'] );
Flight::route( 'POST /dev/schema-map/sync', [SchemaVisualizerController::class, 'sync'] );
Flight::route( 'POST /dev/schema-map/update', [SchemaVisualizerController::class, 'update'] );

$router->group( '', function ( Router $router ) use ( $config ) {
    $router->get( '/', [HomeController::class, 'index'] );

    // Posts demo requires SimplePdo (PostController). Skip when DB is disabled.
    if ( DatabaseFactory::isEnabled( $config ) ) {

        $router->get( '/MySubs/@table_name', [MySubModelController::class, 'index'] );
        $router->get( '/MySubs/@table_name/@id:[0-9]+', [MySubModelController::class, 'show'] );

/*        $router->get( '/posts', [PostController::class, 'index'] );
        $router->get( '/posts/@id:[0-9]+', [PostController::class, 'show'] );

        $router->get( '/@table_name', [MySubModelController::class, 'index'] );
        $router->get( '/@table_name/@id:[0-9]+', [MySubModelController::class, 'show'] );*/

        $router->group( '/api', function ( Router $router ) {
            $router->get( '/posts', [PostController::class, 'apiIndex'] );
            $router->get( '/@table_name', [MySubModelController::class, 'apiIndex'] );

            $router->get( '/posts/@id:[0-9]+', [PostController::class, 'apiShow'] );
            $router->get( '/@table_name/@id:[0-9]+', [MySubModelController::class, 'apiShow'] );
        } );
    }
}, [SecurityHeadersMiddleware::class] );

Flight::route( 'GET /debug/relations', function () use ( $container ) {
    $relManager    = $container->get( \App\Utils\Schema\RelationManager::class );
    $schemaManager = $container->get( \App\Utils\Schema\SchemaManager::class );

    $report = [];
    foreach ( $schemaManager->all() as $table => $schema ) {
        $relations      = $relManager->forTable( $table );
        $report[$table] = [
            'flag_in_schema' => $schema->hasRelations,
            'actual_count'   => count( $relations ),
            'strict_keys'    => array_keys( $relManager->strictForTable( $table ) ),
            'implicit_keys'  => array_keys( $relManager->implicitForTable( $table ) ),
            'display_fields' => array_map(
                fn( $r ) => $r['display_field'] ?? '(hasMany)',
                $relations
            ),
        ];
    }

    Flight::json( $report );
} );
