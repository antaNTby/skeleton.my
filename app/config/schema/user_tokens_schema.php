<?php
// Cache for table 'user_tokens'
return array (
  'table_info' => 
  array (
    'name' => 'user_tokens',
    'primaryKey' => 'id',
    'fieldsList' => 'id,user_id,token_hash,user_agent,created_ip,expires_at,created_at,last_used_at,rotated_at',
  ),
  'fields' => 
  array (
    0 => 'id',
    1 => 'user_id',
    2 => 'token_hash',
    3 => 'user_agent',
    4 => 'created_ip',
    5 => 'expires_at',
    6 => 'created_at',
    7 => 'last_used_at',
    8 => 'rotated_at',
  ),
  'displayed_fields' => 
  array (
    0 => 'id',
    1 => 'user_id',
    2 => 'token_hash',
    3 => 'user_agent',
    4 => 'created_ip',
    5 => 'expires_at',
    6 => 'created_at',
    7 => 'last_used_at',
    8 => 'rotated_at',
  ),
  'filter_fields' => 
  array (
    0 => 'id',
    1 => 'user_id',
    2 => 'token_hash',
    3 => 'user_agent',
    4 => 'created_ip',
    5 => 'expires_at',
    6 => 'created_at',
    7 => 'last_used_at',
    8 => 'rotated_at',
  ),
  'search_fields' => 
  array (
    0 => 'id',
    1 => 'user_id',
    2 => 'token_hash',
    3 => 'user_agent',
    4 => 'created_ip',
    5 => 'expires_at',
    6 => 'created_at',
    7 => 'last_used_at',
    8 => 'rotated_at',
  ),
  'sort_fields' => 
  array (
    0 => 'id',
    1 => 'user_id',
    2 => 'token_hash',
    3 => 'user_agent',
    4 => 'created_ip',
    5 => 'expires_at',
    6 => 'created_at',
    7 => 'last_used_at',
    8 => 'rotated_at',
  ),
  'field_types' => 
  array (
    'id' => 'int',
    'user_id' => 'int',
    'token_hash' => 'string',
    'user_agent' => 'string',
    'created_ip' => 'string',
    'expires_at' => 'string',
    'created_at' => 'string',
    'last_used_at' => 'string',
    'rotated_at' => 'string',
  ),
  'sql_types' => 
  array (
    'id' => 'int',
    'user_id' => 'int',
    'token_hash' => 'char(64)',
    'user_agent' => 'varchar(255)',
    'created_ip' => 'varchar(45)',
    'expires_at' => 'datetime',
    'created_at' => 'timestamp',
    'last_used_at' => 'timestamp',
    'rotated_at' => 'datetime',
  ),
  'relations' => true,
);
