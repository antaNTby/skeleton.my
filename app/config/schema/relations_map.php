<?php
// Strict database relations (FOREIGN KEY)

return [
    'companies'             => [
        'opf'              => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => '__dict_opf',
            'foreign_key'   => 'opf_id',
            'owner_key'     => 'id',
            'display_field' => 'short_name',
        ],
        'bank_accounts'    => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'company_bank_accounts',
            'foreign_key' => 'company_id',
            'owner_key'   => 'id',
        ],
        'contacts'         => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'company_contacts',
            'foreign_key' => 'company_id',
            'owner_key'   => 'id',
        ],
        'employees'        => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'company_employees',
            'foreign_key' => 'company_id',
            'owner_key'   => 'id',
        ],
        'invoices'         => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'invoices',
            'foreign_key' => 'buyer_id',
            'owner_key'   => 'id',
        ],
        'invoicesasseller' => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'invoices',
            'foreign_key' => 'seller_id',
            'owner_key'   => 'id',
        ],
        'orders'           => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'orders',
            'foreign_key' => 'buyer_id',
            'owner_key'   => 'id',
        ],
        'ordersasseller'   => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'orders',
            'foreign_key' => 'seller_id',
            'owner_key'   => 'id',
        ],
    ],
    'company_bank_accounts' => [
        'company' => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'company_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'company_contacts'      => [
        'company' => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'company_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'company_employees'     => [
        'company' => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'company_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'invoices'              => [
        'buyer'  => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'buyer_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
        'order'  => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'orders',
            'foreign_key'   => 'order_id',
            'owner_key'     => 'id',
            'display_field' => 'id',
        ],
        'seller' => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'seller_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'orders'                => [
        'buyer'    => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'buyer_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
        'seller'   => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'companies',
            'foreign_key'   => 'seller_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
        'invoices' => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'invoices',
            'foreign_key' => 'order_id',
            'owner_key'   => 'id',
        ],
    ],
    'user_tokens'           => [
        'user' => [
            'type'          => 'belongsTo',
            'source'        => 'foreign_key',
            'table'         => 'users',
            'foreign_key'   => 'user_id',
            'owner_key'     => 'id',
            'display_field' => 'username',
        ],
    ],
    '__dict_opf'            => [
        'companies' => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'companies',
            'foreign_key' => 'opf_id',
            'owner_key'   => 'id',
        ],
    ],
    'users'                 => [
        'tokens' => [
            'type'        => 'hasMany',
            'source'      => 'foreign_key',
            'table'       => 'user_tokens',
            'foreign_key' => 'user_id',
            'owner_key'   => 'id',
        ],
    ],
];
