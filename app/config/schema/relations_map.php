<?php
// Strict database relations (FOREIGN KEY)
return array (
  'companies' => 
  array (
    'opf' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => '__dict_opf',
      'foreign_key' => 'opf_id',
      'owner_key' => 'id',
      'display_field' => 'short_name',
    ),
    'bank_accounts' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'company_bank_accounts',
      'foreign_key' => 'company_id',
      'owner_key' => 'id',
    ),
    'contacts' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'company_contacts',
      'foreign_key' => 'company_id',
      'owner_key' => 'id',
    ),
    'employees' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'company_employees',
      'foreign_key' => 'company_id',
      'owner_key' => 'id',
    ),
    'contracts' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'contracts',
      'foreign_key' => 'buyer_id',
      'owner_key' => 'id',
    ),
    'contractsasseller' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'contracts',
      'foreign_key' => 'seller_id',
      'owner_key' => 'id',
    ),
    'invoices' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'invoices',
      'foreign_key' => 'buyer_id',
      'owner_key' => 'id',
    ),
    'invoicesasseller' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'invoices',
      'foreign_key' => 'seller_id',
      'owner_key' => 'id',
    ),
    'orders' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'orders',
      'foreign_key' => 'buyer_id',
      'owner_key' => 'id',
    ),
    'ordersasseller' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'orders',
      'foreign_key' => 'seller_id',
      'owner_key' => 'id',
    ),
  ),
  'company_bank_accounts' => 
  array (
    'company' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'company_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
  ),
  'company_contacts' => 
  array (
    'company' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'company_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
  ),
  'company_employees' => 
  array (
    'company' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'company_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
  ),
  'invoices' => 
  array (
    'buyer' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'buyer_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
    'order' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'orders',
      'foreign_key' => 'order_id',
      'owner_key' => 'id',
      'display_field' => 'id',
    ),
    'seller' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'seller_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
    'contracts' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'contracts',
      'foreign_key' => 'invoice_id',
      'owner_key' => 'id',
    ),
  ),
  'orders' => 
  array (
    'buyer' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'buyer_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
    'seller' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'seller_id',
      'owner_key' => 'id',
      'display_field' => 'name',
    ),
    'invoices' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'invoices',
      'foreign_key' => 'order_id',
      'owner_key' => 'id',
    ),
  ),
  'user_tokens' => 
  array (
    'user' => 
    array (
      'type' => 'belongsTo',
      'source' => 'foreign_key',
      'table' => 'users',
      'foreign_key' => 'user_id',
      'owner_key' => 'id',
      'display_field' => 'username',
    ),
  ),
  '__dict_opf' => 
  array (
    'companies' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'companies',
      'foreign_key' => 'opf_id',
      'owner_key' => 'id',
    ),
  ),
  'users' => 
  array (
    'tokens' => 
    array (
      'type' => 'hasMany',
      'source' => 'foreign_key',
      'table' => 'user_tokens',
      'foreign_key' => 'user_id',
      'owner_key' => 'id',
    ),
  ),
);
