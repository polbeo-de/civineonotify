<?php

declare(strict_types=1);

/**
 * Generic scheduled-job bridge for CiviNeo Notify deliveries.
 *
 * Transport-specific adapters are deliberately not exposed as scheduled jobs.
 */
function _civicrm_api3_civi_neo_notify_delivery_runoutbox_spec(array &$spec): void {
  $spec['batchSize'] = [
    'title' => 'Worker batch size',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 25,
  ];
}

function civicrm_api3_civi_neo_notify_delivery_runoutbox(array $params): array {
  $batchSize = max(1, min(100, (int) ($params['batchSize'] ?? $params['batch_size'] ?? 25)));
  try {
    return civicrm_api3_create_success(\Civi::service('civineonotify.worker')->process($batchSize));
  }
  catch (\Throwable $exception) {
    return civicrm_api3_create_error('CiviNeo Notify delivery worker failed: ' . $exception->getMessage());
  }
}
