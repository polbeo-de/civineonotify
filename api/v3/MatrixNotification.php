<?php

declare(strict_types=1);

/** API v3 bridge required by CiviCRM's Scheduled Jobs runner. */
function _civicrm_api3_matrix_notification_runoutbox_spec(array &$spec): void {
  $spec['batchSize'] = [
    'title' => 'Worker batch size',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 25,
  ];
}

/** Process due Matrix outbox records from the CiviCRM Scheduled Jobs runner. */
function civicrm_api3_matrix_notification_runoutbox(array $params): array {
  $batchSize = max(1, min(100, (int) ($params['batchSize'] ?? $params['batch_size'] ?? 25)));
  try {
    return civicrm_api3_create_success(\Civi::service('civineonotify.worker')->process($batchSize));
  }
  catch (\Throwable $exception) {
    return civicrm_api3_create_error('Matrix outbox worker failed: ' . $exception->getMessage());
  }
}
