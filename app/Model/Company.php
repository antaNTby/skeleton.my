<?php

declare ( strict_types = 1 );

namespace App\Model;

use App\Model\MySubModel;
use flight\ActiveRecord;

/**
 * Example ActiveRecord model for the posts table.
 *
 * @property int         $id
 * @property string      $title
 * @property string|null $content
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @link https://docs.flightphp.com/awesome-plugins/active-record
 */
class Company extends MySubModel
{
    /**
     * @param mixed $databaseConnection PDO / SimplePdo / mysqli connection
     */
    public string $table_name
    = 'companies';
    public function __construct( $databaseConnection )
    {
        parent::__construct( $databaseConnection, $this->table_name
        );
    }
}
