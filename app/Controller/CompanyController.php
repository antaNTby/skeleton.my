<?php

declare ( strict_types = 1 );

namespace App\Controller;

use App\Model\Company;
use flight\database\SimplePdo;
use flight\Engine;

/**
 * Example CRUD-ish controller using ActiveRecord + SimplePdo.
 * Run `php runway migrate` first so the posts table exists.
 */
class CompanyController
{
    /** @var Engine<object> */
    private $app;

    /** @var SimplePdo */
    private $db;

    /**
     * @param Engine<object> $app
     */
    public function __construct(
        Engine    $app,
        SimplePdo $db
    ) {
        $this->app = $app;
        $this->db  = $db;
    }

    public function index(): void
    {
        $post  = new Company( $this->db );
        $posts = $post->orderByColumn( 'id', 'DESC' )->findAll();

        $this->app->render( 'posts/index', [
            'posts' => $posts,
        ] );
    }

    /**
     * @param string|int $id
     */
    public function show( $id ): void
    {
        $post = new Company( $this->db );
        $post->find( (int) $id );

        if ( !$post->isHydrated() ) {
            $this->app->halt( 404, 'Company not found' );

            return;
        }

        $this->app->render( 'posts/show', [
            'post' => $post,
        ] );
    }

    public function apiIndex(): void
    {
        $post  = new Company( $this->db );
        $posts = $post->orderByColumn( 'id', 'DESC' )->findAll();

        $data = [];
        foreach ( $posts as $row ) {
            // findAll() is typed as ActiveRecord[]; narrow to Company for @property fields
            if ( !$row instanceof Company ) {
                continue;
            }
            $data[] = [
                'id'         => $row->id,
                'title'      => $row->title,
                'content'    => $row->content,
                'created_at' => $row->created_at,
            ];
        }

        $this->app->json( $data, 200, true, 'utf-8', JSON_PRETTY_PRINT );
    }

    /**
     * @param string|int $id
     */
    public function apiShow( $id ): void
    {
        $post = new Company( $this->db );
        $post->find( (int) $id );

        if ( !$post->isHydrated() ) {
            $this->app->json( ['error' => 'Company not found'], 404 );

            return;
        }

        $this->app->json( [
            'id'         => $post->id,
            'title'      => $post->title,
            'content'    => $post->content,
            'created_at' => $post->created_at,
        ], 200, true, 'utf-8', JSON_PRETTY_PRINT );
    }
}
