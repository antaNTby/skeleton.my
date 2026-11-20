<?php
// Cache for table 'company_contacts'
return array (
  'table_info' => 
  array (
    'name' => 'company_contacts',
    'primaryKey' => 'id',
    'fieldsList' => 'id,company_id,address_type,address_text,phone,email,other,contact_person,is_primary,note',
  ),
  'fields' => 
  array (
    0 => 'id',
    1 => 'company_id',
    2 => 'address_type',
    3 => 'address_text',
    4 => 'phone',
    5 => 'email',
    6 => 'other',
    7 => 'contact_person',
    8 => 'is_primary',
    9 => 'note',
  ),
  'displayed_fields' => 
  array (
    0 => 'id',
    1 => 'company_id',
    2 => 'address_type',
    3 => 'address_text',
    4 => 'phone',
    5 => 'email',
    6 => 'other',
    7 => 'contact_person',
    8 => 'is_primary',
    9 => 'note',
  ),
  'filter_fields' => 
  array (
    0 => 'id',
    1 => 'company_id',
    2 => 'address_type',
    3 => 'address_text',
    4 => 'phone',
    5 => 'email',
    6 => 'other',
    7 => 'contact_person',
    8 => 'is_primary',
    9 => 'note',
  ),
  'search_fields' => 
  array (
    0 => 'id',
    1 => 'company_id',
    2 => 'address_type',
    3 => 'address_text',
    4 => 'phone',
    5 => 'email',
    6 => 'other',
    7 => 'contact_person',
    8 => 'is_primary',
    9 => 'note',
  ),
  'sort_fields' => 
  array (
    0 => 'id',
    1 => 'company_id',
    2 => 'address_type',
    3 => 'address_text',
    4 => 'phone',
    5 => 'email',
    6 => 'other',
    7 => 'contact_person',
    8 => 'is_primary',
    9 => 'note',
  ),
  'field_types' => 
  array (
    'id' => 'int',
    'company_id' => 'int',
    'address_type' => 'string',
    'address_text' => 'string',
    'phone' => 'string',
    'email' => 'string',
    'other' => 'string',
    'contact_person' => 'string',
    'is_primary' => 'bool',
    'note' => 'string',
  ),
  'sql_types' => 
  array (
    'id' => 'int unsigned',
    'company_id' => 'int unsigned',
    'address_type' => 'enum(\'legal\',\'postal\',\'delivery\',\'other\')',
    'address_text' => 'text',
    'phone' => 'varchar(50)',
    'email' => 'varchar(100)',
    'other' => 'varchar(1000)',
    'contact_person' => 'varchar(255)',
    'is_primary' => 'tinyint(1)',
    'note' => 'varchar(255)',
  ),
  'relations' => true,
);
