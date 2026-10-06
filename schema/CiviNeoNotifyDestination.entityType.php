<?php

declare(strict_types=1);

require_once __DIR__ . '/EntitySchema.php';

use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Schema\EntitySchema;

return [
  'name' => 'CiviNeoNotifyDestination',
  'table' => 'civicrm_civineonotify_destination',
  'class' => 'CRM_Civineonotify_DAO_CiviNeoNotifyDestination',
  'getInfo' => fn() => ['title' => E::ts('CiviNeo Notify Destination'), 'add' => '0.0.2'],
  'getIndices' => fn() => [
    'civineonotify_destination_transport' => ['fields' => ['transport_id' => TRUE, 'is_active' => TRUE], 'add' => '0.0.2'],
  ],
  'getFields' => fn() => EntitySchema::withTitles([
    'id' => ['sql_type' => 'int unsigned', 'primary_key' => TRUE, 'auto_increment' => TRUE, 'required' => TRUE, 'add' => '0.0.2'],
    'transport_id' => ['sql_type' => 'int unsigned', 'required' => TRUE, 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyTransport', 'key' => 'id', 'on_delete' => 'CASCADE'], 'add' => '0.0.2'],
    'name' => ['sql_type' => 'varchar(128)', 'required' => TRUE, 'add' => '0.0.2'],
    'description' => ['sql_type' => 'text', 'add' => '0.0.2'],
    'destination_type' => ['sql_type' => 'varchar(64)', 'required' => TRUE, 'default' => 'matrix_room', 'add' => '0.0.2'],
    'config_json' => ['sql_type' => 'text', 'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'), 'add' => '0.0.2'],
    'is_active' => ['sql_type' => 'boolean', 'default' => TRUE, 'add' => '0.0.2'],
    'created_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP', 'add' => '0.0.2'],
    'modified_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'add' => '0.0.2'],
  ]),
];
