<?php
// Cache for table 'users'
return array (
  'table_info' => 
  array (
    'name' => 'users',
    'primaryKey' => 'id',
    'fieldsList' => 'id,username,email,password_hash,password_raw,is_active,login_count,role,created_at,last_login',
  ),
  'fields' => 
  array (
    0 => 'id',
    1 => 'username',
    2 => 'email',
    3 => 'password_hash',
    4 => 'password_raw',
    5 => 'is_active',
    6 => 'login_count',
    7 => 'role',
    8 => 'created_at',
    9 => 'last_login',
  ),
  'displayed_fields' => 
  array (
    0 => 'id',
    1 => 'username',
    2 => 'email',
    3 => 'password_hash',
    4 => 'password_raw',
    5 => 'is_active',
    6 => 'login_count',
    7 => 'role',
    8 => 'created_at',
    9 => 'last_login',
  ),
  'filter_fields' => 
  array (
    0 => 'id',
    1 => 'username',
    2 => 'email',
    3 => 'password_hash',
    4 => 'password_raw',
    5 => 'is_active',
    6 => 'login_count',
    7 => 'role',
    8 => 'created_at',
    9 => 'last_login',
  ),
  'search_fields' => 
  array (
    0 => 'id',
    1 => 'username',
    2 => 'email',
    3 => 'password_hash',
    4 => 'password_raw',
    5 => 'is_active',
    6 => 'login_count',
    7 => 'role',
    8 => 'created_at',
    9 => 'last_login',
  ),
  'sort_fields' => 
  array (
    0 => 'id',
    1 => 'username',
    2 => 'email',
    3 => 'password_hash',
    4 => 'password_raw',
    5 => 'is_active',
    6 => 'login_count',
    7 => 'role',
    8 => 'created_at',
    9 => 'last_login',
  ),
  'field_types' => 
  array (
    'id' => 'int',
    'username' => 'string',
    'email' => 'string',
    'password_hash' => 'string',
    'password_raw' => 'string',
    'is_active' => 'bool',
    'login_count' => 'int',
    'role' => 'string',
    'created_at' => 'string',
    'last_login' => 'string',
  ),
  'sql_types' => 
  array (
    'id' => 'int',
    'username' => 'varchar(50)',
    'email' => 'varchar(255)',
    'password_hash' => 'varchar(255)',
    'password_raw' => 'varchar(255)',
    'is_active' => 'tinyint(1)',
    'login_count' => 'int unsigned',
    'role' => 'enum(\'administrator\',\'user\',\'guest\')',
    'created_at' => 'timestamp',
    'last_login' => 'datetime',
  ),
  'relations' => 
  array (
  ),
);
