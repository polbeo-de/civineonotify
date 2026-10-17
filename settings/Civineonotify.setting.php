<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

$settings = [
  'civineonotify_enabled' => ['type' => 'Boolean', 'html_type' => 'checkbox', 'default' => FALSE, 'title' => E::ts('Enable CiviNeo Notify')],
  'civineonotify_default_message_template' => ['type' => 'String', 'default' => E::ts('{event.name} Hello, world!'), 'html_type' => 'textarea', 'title' => E::ts('Default message template')],
  'civineonotify_transport_test_message_template' => ['type' => 'String', 'default' => E::ts('{event.name} Hello, world. Testing! Testing! Do you read me?'), 'html_type' => 'textarea', 'title' => E::ts('Transport test message template')],
  'civineonotify_default_message_format' => ['type' => 'String', 'html_type' => 'text', 'default' => 'plain', 'title' => E::ts('Default message format')],
  'civineonotify_batch_size' => ['type' => 'Integer', 'html_type' => 'text', 'default' => 25, 'title' => E::ts('Worker batch size')],
  'civineonotify_max_attempts' => ['type' => 'Integer', 'html_type' => 'text', 'default' => 5, 'title' => E::ts('Maximum delivery attempts')],
  'civineonotify_retry_delays' => ['type' => 'Array', 'html_type' => 'textarea', 'serialize' => CRM_Core_DAO::SERIALIZE_JSON, 'default' => [60, 300, 1800, 7200], 'title' => E::ts('Retry delays (seconds)')],
  'civineonotify_retention_days' => ['type' => 'Integer', 'html_type' => 'text', 'default' => 90, 'title' => E::ts('Delivery log retention (days)')],
  'civineonotify_protected_transport_storage_directory' => ['type' => 'String', 'html_type' => 'text', 'default' => '', 'title' => E::ts('Protected transport storage directory'), 'description' => E::ts('Absolute path outside the web root for transport credentials, encrypted stores, and protected runtime files. The directory must be writable only by the CiviCRM service account.')],
];
foreach ($settings as $name => &$setting) { $setting += ['name' => $name, 'group' => 'civineonotify', 'is_domain' => 1, 'is_contact' => 0, 'settings_pages' => ['civineonotify' => ['weight' => 1000]]]; }
unset($setting);
$settings['civineonotify_retry_delays']['settings_pages'] = [];
return $settings;
