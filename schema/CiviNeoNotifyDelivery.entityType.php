<?php

declare(strict_types=1);

require_once __DIR__ . '/EntitySchema.php';

use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Schema\EntitySchema;

return [
  'name' => 'CiviNeoNotifyDelivery',
  'table' => 'civicrm_civineonotify_delivery',
  'class' => 'CRM_Civineonotify_DAO_CiviNeoNotifyDelivery',
  'getInfo' => fn() => ['title' => E::ts('CiviNeo Notify Delivery'), 'add' => '0.0.2'],
  'getIndices' => fn() => [
    'civineonotify_delivery_due' => ['fields' => ['status' => TRUE, 'next_attempt_at' => TRUE], 'add' => '0.0.2'],
    'civineonotify_delivery_transaction' => ['fields' => ['transaction_id' => TRUE], 'unique' => TRUE, 'add' => '0.0.2'],
    'civineonotify_delivery_external' => ['fields' => ['external_id' => TRUE], 'add' => '0.0.2'],
    'civineonotify_delivery_correlation' => ['fields' => ['correlation_id' => TRUE], 'add' => '0.0.2'],
  ],
  'getFields' => fn() => EntitySchema::withTitles([
    'id' => ['sql_type' => 'int unsigned', 'primary_key' => TRUE, 'auto_increment' => TRUE, 'required' => TRUE, 'add' => '0.0.2'],
    'rule_id' => ['sql_type' => 'int unsigned', 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyRule', 'key' => 'id', 'on_delete' => 'SET NULL'], 'add' => '0.0.2'],
    'transport_id' => ['sql_type' => 'int unsigned', 'required' => TRUE, 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyTransport', 'key' => 'id', 'on_delete' => 'RESTRICT'], 'add' => '0.0.2'],
    'destination_id' => ['sql_type' => 'int unsigned', 'required' => TRUE, 'input_type' => 'EntityRef', 'entity_reference' => ['entity' => 'CiviNeoNotifyDestination', 'key' => 'id', 'on_delete' => 'RESTRICT'], 'add' => '0.0.2'],
    'event_name' => ['sql_type' => 'varchar(128)', 'required' => TRUE, 'add' => '0.0.2'], 'entity_type' => ['sql_type' => 'varchar(64)', 'add' => '0.0.2'], 'entity_id' => ['sql_type' => 'varchar(64)', 'add' => '0.0.2'],
    'correlation_id' => ['sql_type' => 'char(32)', 'required' => TRUE, 'add' => '0.0.2'], 'transaction_id' => ['sql_type' => 'char(32)', 'required' => TRUE, 'add' => '0.0.2'],
    'message_body' => ['sql_type' => 'text', 'required' => TRUE, 'add' => '0.0.2'], 'formatted_body' => ['sql_type' => 'text', 'add' => '0.0.2'],
    'payload_json' => ['sql_type' => 'text', 'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'), 'add' => '0.0.2'],
    'status' => ['sql_type' => 'varchar(32)', 'required' => TRUE, 'default' => 'pending', 'add' => '0.0.2'], 'attempt_count' => ['sql_type' => 'int unsigned', 'default' => 0, 'add' => '0.0.2'],
    'next_attempt_at' => ['sql_type' => 'datetime', 'add' => '0.0.2'], 'last_attempt_at' => ['sql_type' => 'datetime', 'add' => '0.0.2'], 'sent_at' => ['sql_type' => 'datetime', 'add' => '0.0.2'],
    'external_id' => ['sql_type' => 'varchar(255)', 'add' => '0.0.2'], 'exit_code' => ['sql_type' => 'int', 'add' => '0.0.2'], 'error_code' => ['sql_type' => 'varchar(64)', 'add' => '0.0.2'], 'error_message' => ['sql_type' => 'text', 'add' => '0.0.2'],
    'created_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP', 'add' => '0.0.2'], 'modified_date' => ['sql_type' => 'timestamp', 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'add' => '0.0.2'],
  ]),
];
