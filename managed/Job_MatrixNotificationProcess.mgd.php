<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

return [[
  'name' => 'Job_MatrixNotificationProcess',
  'entity' => 'Job',
  'cleanup' => 'unused',
  'update' => 'never',
  'params' => [
    'version' => 3,
    'name' => E::ts('CiviNeo Notify: Process Matrix outbox'),
    'description' => E::ts('Processes pending Matrix notifications serially.'),
    'run_frequency' => 'Always',
    'api_entity' => 'MatrixNotification',
    'api_action' => 'runOutbox',
    'parameters' => 'batchSize=25',
    'is_active' => TRUE,
  ],
]];
