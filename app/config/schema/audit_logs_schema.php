<?php
// Cache for table 'audit_logs'
return array (
  'table_info' => 
  array (
    'name' => 'audit_logs',
    'primaryKey' => 'id',
    'fieldsList' => 'id,table_name,record_id,action,user_id,payload,created_at',
  ),
  'fields' => 
  array (
    0 => 'id',
    1 => 'table_name',
    2 => 'record_id',
    3 => 'action',
    4 => 'user_id',
    5 => 'payload',
    6 => 'created_at',
  ),
  'displayed_fields' => 
  array (
    0 => 'id',
    1 => 'table_name',
    2 => 'record_id',
    3 => 'action',
    4 => 'user_id',
    5 => 'payload',
    6 => 'created_at',
  ),
  'filter_fields' => 
  array (
    0 => 'id',
    1 => 'table_name',
    2 => 'record_id',
    3 => 'action',
    4 => 'user_id',
    5 => 'payload',
    6 => 'created_at',
  ),
  'search_fields' => 
  array (
    0 => 'id',
    1 => 'table_name',
    2 => 'record_id',
    3 => 'action',
    4 => 'user_id',
    5 => 'payload',
    6 => 'created_at',
  ),
  'sort_fields' => 
  array (
    0 => 'id',
    1 => 'table_name',
    2 => 'record_id',
    3 => 'action',
    4 => 'user_id',
    5 => 'payload',
    6 => 'created_at',
  ),
  'field_types' => 
  array (
    'id' => 'int',
    'table_name' => 'string',
    'record_id' => 'string',
    'action' => 'string',
    'user_id' => 'int',
    'payload' => 'json',
    'created_at' => 'string',
  ),
  'sql_types' => 
  array (
    'id' => 'bigint unsigned',
    'table_name' => 'varchar(64)',
    'record_id' => 'varchar(64)',
    'action' => 'varchar(20)',
    'user_id' => 'bigint unsigned',
    'payload' => 'json',
    'created_at' => 'datetime',
  ),
  'relations' => false,
);
