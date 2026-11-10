<?php

declare ( strict_types = 1 );

namespace App\Controller;

use App\Model\MySubModel;
use flight\database\SimplePdo;
use flight\Engine;
use flight\net\Request;
use flight\net\Route;

/**
 * Example CRUD-ish controller using ActiveRecord + SimplePdo.
 * Run `php runway migrate` first so the companies table exists.
 */
class MySubModelController
{
    /** @var Engine<object> */
    private $app;

    /** @var SimplePdo */
    private $db;

    public string $tableName = 'unknown_table_name';
    /**
     * @param Engine<object> $app
     */
    public function __construct(
        Engine    $app,
        SimplePdo $db,
        Route     $route // <-- Добавили получение роута из контейнера

    ) {
        $this->app       = $app;
        $this->db        = $db;
        $this->tableName = $route->params['table_name'] ?? '';

        // Здесь ваш код
        bdump( 'Вы запросили таблицу: ' . htmlspecialchars( $this->tableName ) );
    }

    public function index(): void
    {
        $mySub = new MySubModel( $this->db, $this->tableName );

        $rows = $mySub->orderByColumn( 'id', 'ASC' )->findAll();
        dd( $rows );

        bdump( 'Список из таблицы: ' . htmlspecialchars( $this->tableName ), 'INDEX' );

        $this->app->renderSmarty( $this->tableName . '/index', ['rows' => $rows] );
    }

    /**
     * @param string|int $id
     */
    public function show( $id ): void
    {
        $mySub = new MySubModel( $this->db, $this->tableName );
        $mySub->find( (int) $id );

        if ( !$mySub->isHydrated() ) {
            $this->app->halt( 404, 'MySubModel not found' );

            return;
        }

        $this->app->renderSmarty( $this->tableName . '/show', ['mySub' => $mySub] );
    }

    public function apiIndex(): void
    {
        $mySub = new MySubModel( $this->db, $this->tableName );
        $rows  = $mySub->orderByColumn( 'id', 'ASC' )->findAll();

        $data = [];
        foreach ( $rows as $row ) {
            // findAll() is typed as ActiveRecord[]; narrow to MySubModel for @property fields
            if ( !$row instanceof MySubModel ) {
                continue;
            }
            $data[] = [
                'id'            => $row->id,
                'name'          => $row->name,
                'legal_address' => $row->legal_address,
                'created_at'    => $row->created_at,
            ];
        }

        $this->app->json( $data, 200, true, 'utf-8', JSON_PRETTY_PRINT );
    }

    /**
     * @param string|int $id
     */
    public function apiShow( $id ): void
    {
        $mySub = new MySubModel( $this->db, $this->tableName
        );
        $mySub->find( (int) $id );

        if ( !$mySub->isHydrated() ) {
            $this->app->json( ['error' => 'MySubModel not found'], 404 );

            return;
        }

        $this->app->json( [
            'id'            => $mySub->id,
            'name'          => $mySub->name,
            'legal_address' => $mySub->legal_address,
            'created_at'    => $mySub->created_at,
        ], 200, true, 'utf-8', JSON_PRETTY_PRINT );
    }
}
