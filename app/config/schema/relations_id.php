<?php
// Implicit database relations (*_id convention)
return array (
  'audit_logs' => 
  array (
    'user' => 
    array (
      'type' => 'belongsTo',
      'source' => 'implicit_id',
      'table' => 'users',
      'foreign_key' => 'user_id',
      'owner_key' => 'id',
      'display_field' => 'username',
    ),
  ),
  'users' => 
  array (
    'audit_logs' => 
    array (
      'type' => 'hasMany',
      'source' => 'implicit_id',
      'table' => 'audit_logs',
      'foreign_key' => 'user_id',
      'owner_key' => 'id',
    ),
  ),
);
