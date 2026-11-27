<?php
// Cache for table '__dict_banks'
return array (
  'table_info' => 
  array (
    'name' => '__dict_banks',
    'primaryKey' => 'id',
    'fieldsList' => 'id,bank_name,bic_code,unp,address,phone_fax,website,is_deprecated,note',
  ),
  'fields' => 
  array (
    0 => 'id',
    1 => 'bank_name',
    2 => 'bic_code',
    3 => 'unp',
    4 => 'address',
    5 => 'phone_fax',
    6 => 'website',
    7 => 'is_deprecated',
    8 => 'note',
  ),
  'displayed_fields' => 
  array (
    0 => 'id',
    1 => 'bank_name',
    2 => 'bic_code',
    3 => 'unp',
    4 => 'address',
    5 => 'phone_fax',
    6 => 'website',
    7 => 'is_deprecated',
    8 => 'note',
  ),
  'filter_fields' => 
  array (
  ),
  'search_fields' => 
  array (
    0 => 'id',
    1 => 'bank_name',
    2 => 'unp',
    3 => 'address',
    4 => 'phone_fax',
    5 => 'website',
    6 => 'is_deprecated',
  ),
  'sort_fields' => 
  array (
    0 => 'id',
    1 => 'bank_name',
    2 => 'bic_code',
    3 => 'unp',
    4 => 'address',
    5 => 'website',
    6 => 'is_deprecated',
    7 => 'note',
  ),
  'field_types' => 
  array (
    'id' => 'int',
    'bank_name' => 'string',
    'bic_code' => 'string',
    'unp' => 'string',
    'address' => 'string',
    'phone_fax' => 'string',
    'website' => 'string',
    'is_deprecated' => 'bool',
    'note' => 'string',
  ),
  'sql_types' => 
  array (
    'id' => 'int unsigned',
    'bank_name' => 'varchar(500)',
    'bic_code' => 'char(8)',
    'unp' => 'char(9)',
    'address' => 'varchar(1000)',
    'phone_fax' => 'varchar(255)',
    'website' => 'varchar(255)',
    'is_deprecated' => 'tinyint(1)',
    'note' => 'text',
  ),
  'relations' => false,
);
