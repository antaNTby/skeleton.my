<?php
// Cache for table 'orders'
return array (
  'table_info' => 
  array (
    'name' => 'orders',
    'primaryKey' => 'id',
    'fieldsList' => 'id,seller_id,buyer_id,status,total_amount,currency,description,created_at,updated_at',
  ),
  'fields' => 
  array (
    0 => 'id',
    1 => 'seller_id',
    2 => 'buyer_id',
    3 => 'status',
    4 => 'total_amount',
    5 => 'currency',
    6 => 'description',
    7 => 'created_at',
    8 => 'updated_at',
  ),
  'displayed_fields' => 
  array (
    0 => 'id',
    1 => 'seller_id',
    2 => 'total_amount',
    3 => 'currency',
    4 => 'description',
    5 => 'created_at',
    6 => 'updated_at',
  ),
  'filter_fields' => 
  array (
    0 => 'seller_id',
    1 => 'buyer_id',
    2 => 'status',
    3 => 'total_amount',
    4 => 'currency',
    5 => 'description',
    6 => 'created_at',
    7 => 'updated_at',
  ),
  'search_fields' => 
  array (
    0 => 'id',
    1 => 'seller_id',
    2 => 'buyer_id',
    3 => 'currency',
    4 => 'description',
    5 => 'created_at',
    6 => 'updated_at',
  ),
  'sort_fields' => 
  array (
    0 => 'id',
    1 => 'seller_id',
    2 => 'buyer_id',
    3 => 'currency',
    4 => 'description',
    5 => 'created_at',
    6 => 'updated_at',
  ),
  'field_types' => 
  array (
    'id' => 'int',
    'seller_id' => 'int',
    'buyer_id' => 'int',
    'status' => 'string',
    'total_amount' => 'float',
    'currency' => 'string',
    'description' => 'string',
    'created_at' => 'string',
    'updated_at' => 'string',
  ),
  'sql_types' => 
  array (
    'id' => 'int unsigned',
    'seller_id' => 'int unsigned',
    'buyer_id' => 'int unsigned',
    'status' => 'enum(\'draft\',\'confirmed\',\'processing\',\'completed\',\'cancelled\')',
    'total_amount' => 'decimal(12,2)',
    'currency' => 'char(3)',
    'description' => 'text',
    'created_at' => 'timestamp',
    'updated_at' => 'timestamp',
  ),
  'relations' => true,
);
