<?php

declare(strict_types=1);

require_once __DIR__ . '/EntitySchema.php';

use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Schema\EntitySchema;

return [
  'name' => 'CiviNeoNotifyRule',
  'table' => 'civicrm_civineonotify_rule',
  'class' => 'CRM_Civineonotify_DAO_CiviNeoNotifyRule',
  'getInfo' => fn() => ['title' => E::ts('CiviNeo Notify Rule'), 'add' => '0.0.2'],
  'getIndices' => fn() => ['civineonotify_rule_match' => ['fields' => ['is_active' => TRUE, 'event_pattern' => TRUE, 'priority' => TRUE], 'add' => '0.0.2']],
  'getFields' => fn() => EntitySchema::withTitles([
    'id' => ['sql_type' => 'int unsigned', 'primary_key' => TRUE, 'auto_increment' => TRUE, 'required' => TRUE, 'add' => '0.0.2'],
    'name' => ['sql_type' => 'varchar(128)', 'required' => TRUE, 'add' => '0.0.2'], 'description' => ['sql_type' => 'text', 'add' => '0.0.2'],
    'is_active' => ['sql_type' => 'boolean', 'default' => FALSE, 'add' => '0.0.2'], 'event_pattern' => ['sql_type' => 'varchar(128)', 'required' => TRUE, 'add' => '0.0.2'],
    'entity_type' => ['sql_type' => 'varchar(64)', 'add' => '0.0.2'], 'operation' => ['sql_type' => 'varchar(32)', 'add' => '0.0.2'],
    'condition_json' => ['sql_type' => 'text', 'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'), 'add' => '0.0.2'],
    'transport_id' => ['sql_type' => 'int unsigned', 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyTransport', 'key' => 'id', 'on_delete' => 'SET NULL'], 'add' => '0.0.2'],
    'destination_id' => ['sql_type' => 'int unsigned', 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyDestination', 'key' => 'id', 'on_delete' => 'SET NULL'], 'add' => '0.0.2'],
    'message_template' => ['sql_type' => 'text', 'add' => '0.0.2'], 'message_format' => ['sql_type' => 'varchar(16)', 'default' => 'plain', 'add' => '0.0.2'],
    'priority' => ['sql_type' => 'int', 'default' => 0, 'add' => '0.0.2'], 'stop_processing' => ['sql_type' => 'boolean', 'default' => FALSE, 'add' => '0.0.2'],
    'created_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP', 'add' => '0.0.2'], 'modified_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'add' => '0.0.2'],
  ]),
];
