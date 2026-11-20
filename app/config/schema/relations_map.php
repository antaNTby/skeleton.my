<?php
// Global database relations map

return [
    'companies'             => [
        'opf'                => [
            'type'          => 'belongsTo',
            'table'         => '__dict_opf',
            'foreign_key'   => 'opf_id',
            'owner_key'     => 'id',
            'display_field' => 'short_name',
        ],
        'bank_accounts'      => [
            'type'        => 'hasMany',
            'table'       => 'company_bank_accounts',
            'foreign_key' => 'company_id',
            'owner_key'   => 'id',
        ],
        'contacts'           => [
            'type'        => 'hasMany',
            'table'       => 'company_contacts',
            'foreign_key' => 'company_id',
            'owner_key'   => 'id',
        ],
        'employees'          => [
            'type'        => 'hasMany',
            'table'       => 'company_employees',
            'foreign_key' => 'company_id',
            'owner_key'   => 'id',
        ],
        'invoices'           => [
            'type'        => 'hasMany',
            'table'       => 'invoices',
            'foreign_key' => 'buyer_id',
            'owner_key'   => 'id',
        ],
        'invoices_as_seller' => [
            'type'        => 'hasMany',
            'table'       => 'invoices',
            'foreign_key' => 'seller_id',
            'owner_key'   => 'id',
        ],
        'orders'             => [
            'type'        => 'hasMany',
            'table'       => 'orders',
            'foreign_key' => 'buyer_id',
            'owner_key'   => 'id',
        ],
        'orders_as_seller'   => [
            'type'        => 'hasMany',
            'table'       => 'orders',
            'foreign_key' => 'seller_id',
            'owner_key'   => 'id',
        ],
    ],
    'company_bank_accounts' => [
        'company' => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'company_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'company_contacts'      => [
        'company' => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'company_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'company_employees'     => [
        'company' => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'company_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'invoices'              => [
        'buyer'  => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'buyer_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
        'order'  => [
            'type'          => 'belongsTo',
            'table'         => 'orders',
            'foreign_key'   => 'order_id',
            'owner_key'     => 'id',
            'display_field' => 'id',
        ],
        'seller' => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'seller_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
    ],
    'orders'                => [
        'buyer'    => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'buyer_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
        'seller'   => [
            'type'          => 'belongsTo',
            'table'         => 'companies',
            'foreign_key'   => 'seller_id',
            'owner_key'     => 'id',
            'display_field' => 'name',
        ],
        'invoices' => [
            'type'        => 'hasMany',
            'table'       => 'invoices',
            'foreign_key' => 'order_id',
            'owner_key'   => 'id',
        ],
    ],
    'user_tokens'           => [
        'user' => [
            'type'          => 'belongsTo',
            'table'         => 'users',
            'foreign_key'   => 'user_id',
            'owner_key'     => 'id',
            'display_field' => 'username',
        ],
    ],
    '__dict_opf'            => [
        'companies' => [
            'type'        => 'hasMany',
            'table'       => 'companies',
            'foreign_key' => 'opf_id',
            'owner_key'   => 'id',
        ],
    ],
    'users'                 => [
        'tokens' => [
            'type'        => 'hasMany',
            'table'       => 'user_tokens',
            'foreign_key' => 'user_id',
            'owner_key'   => 'id',
        ],
    ],
];
