<?php

declare(strict_types=1);

require_once __DIR__ . '/EntitySchema.php';

use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Schema\EntitySchema;

return [
  'name' => 'CiviNeoNotifyTransport',
  'table' => 'civicrm_civineonotify_transport',
  'class' => 'CRM_Civineonotify_DAO_CiviNeoNotifyTransport',
  'getInfo' => fn() => ['title' => E::ts('CiviNeo Notify Transport'), 'add' => '0.0.2'],
  'getIndices' => fn() => [
    'civineonotify_transport_active' => ['fields' => ['is_active' => TRUE, 'transport_type' => TRUE], 'add' => '0.0.2'],
  ],
  'getFields' => fn() => EntitySchema::withTitles([
    'id' => ['sql_type' => 'int unsigned', 'primary_key' => TRUE, 'auto_increment' => TRUE, 'required' => TRUE, 'add' => '0.0.2'],
    'name' => ['sql_type' => 'varchar(128)', 'required' => TRUE, 'add' => '0.0.2'],
    'description' => ['sql_type' => 'text', 'add' => '0.0.2'],
    'transport_type' => ['sql_type' => 'varchar(64)', 'required' => TRUE, 'default' => 'matrix_commander_rs', 'add' => '0.0.2'],
    'config_json' => ['sql_type' => 'text', 'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'), 'add' => '0.0.2'],
    'default_destination_id' => ['sql_type' => 'int unsigned', 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyDestination', 'key' => 'id', 'on_delete' => 'SET NULL'], 'add' => '0.0.2'],
    'is_active' => ['sql_type' => 'boolean', 'default' => TRUE, 'add' => '0.0.2'],
    'created_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP', 'add' => '0.0.2'],
    'modified_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'add' => '0.0.2'],
  ]),
];
