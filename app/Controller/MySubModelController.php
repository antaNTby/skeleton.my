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
        $subModel = new MySubModel( $this->db, $this->tableName );

        $rows = $subModel->orderByColumn( 'id', 'ASC' )->findAll();
        // dd( $rows );

        bdump( 'Список из таблицы: ' . htmlspecialchars( $this->tableName ), 'INDEX' );

        $this->_render( 'index', ['rows' => $rows] );
    }

    /**
     * @param string|int $id
     */
    public function show(
        string $table_name,
               $id
    ): void {
        $subModel = new MySubModel( $this->db, $this->tableName );

        $row = $subModel->find( (int) $id );

        if ( !$row->isHydrated() ) {
            $this->app->halt( 404, 'MySubModel not found' );

            return;
        }

        $this->_render( 'show', ['row' => $row] );
    }

    private function _render(
        string $method = 'index',
        array  $data = []
    ): void {
        $templateDir = PROJECT_ROOT . '/templates';
        $template    = $this->tableName . '/' . $method;
        // Формируем полный физический путь к файлу шаблона.
        $templatePath = str_replace( ['/', '\\'], DIRECTORY_SEPARATOR, $templateDir . DIRECTORY_SEPARATOR . $template . '.tpl.html' );

        // Проверяем, существует ли файл шаблона на диске
        if ( !is_file( $templatePath ) ) {
            // Если индивидуального шаблона для таблицы нет, переключаемся на default
            $template = 'default/' . $method;
        }
        bdump( $template, 'template' );
        $this->app->renderSmarty( $template, ['template_data' => $data] );

    }

    public function apiIndex(): void
    {
        $subModel = new MySubModel( $this->db, $this->tableName );
        $rows     = $subModel->orderByColumn( 'id', 'ASC' )->findAll();

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
        $subModel = new MySubModel( $this->db, $this->tableName
        );
        $subModel->find( (int) $id );

        if ( !$subModel->isHydrated() ) {
            $this->app->json( ['error' => 'MySubModel not found'], 404 );

            return;
        }

        $this->app->json( [
            'id'            => $subModel->id,
            'name'          => $subModel->name,
            'legal_address' => $subModel->legal_address,
            'created_at'    => $subModel->created_at,
        ], 200, true, 'utf-8', JSON_PRETTY_PRINT );
    }
}
