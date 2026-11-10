<?php

declare ( strict_types = 1 );

namespace App\Controller;

use App\Model\MySubModel;
use flight\database\SimplePdo;
use flight\Engine;

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

    public string $table_name = 'unknown_table_name';
    /**
     * @param Engine<object> $app
     */
    public function __construct(
        Engine    $app,
        SimplePdo $db,
        string    $table_name

    ) {
        $this->app        = $app;
        $this->db         = $db;
        $this->table_name = $table_name;

        // Здесь ваш код
        bdump( 'Вы запросили таблицу: ' . htmlspecialchars( $table_name ) );
    }

    public function index(): void
    {
        $mySub = new MySubModel( $this->db, $this->table_name
        );
        $rows = $mySub->orderByColumn( 'id', 'ASC' )->findAll();

        $this->app->renderSmarty( $this->table_name . '/index', ['rows' => $rows] );
    }

    /**
     * @param string|int $id
     */
    public function show( $id ): void
    {
        $mySub = new MySubModel( $this->db, $this->table_name );
        $mySub->find( (int) $id );

        if ( !$mySub->isHydrated() ) {
            $this->app->halt( 404, 'MySubModel not found' );

            return;
        }

        $this->app->renderSmarty( $this->table_name . '/show', ['mySub' => $mySub] );
    }

    public function apiIndex(): void
    {
        $mySub = new MySubModel( $this->db, $this->table_name );
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
        $mySub = new MySubModel( $this->db, $this->table_name
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
