<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Outbox;

use Civi\Api4\CiviNeoNotifyDelivery;
use Civi\Api4\CiviNeoNotifyDestination;
use Civi\Api4\CiviNeoNotifyTransport;
use Civi\Civineonotify\Transport\{MatrixDestination, MatrixMessage, TransportFactory};

final class Worker {
  public function __construct(private readonly ?RetryPolicy $retryPolicy = NULL) {}
  public function process(int $batchSize): array {
    if (!\Civi::settings()->get('civineonotify_enabled')) return ['processed' => 0, 'skipped' => 'disabled'];
    CiviNeoNotifyDelivery::update(FALSE)->addWhere('status', '=', 'processing')->addWhere('last_attempt_at', '<=', gmdate('Y-m-d H:i:s', time() - 900))->setValues(['status' => 'delivery_unknown', 'error_code' => 'unknown_delivery_state', 'error_message' => 'Worker recovery found an unfinished delivery attempt.'])->execute();
    $rows = CiviNeoNotifyDelivery::get(FALSE)->addWhere('status', 'IN', ['pending', 'retry'])->addWhere('next_attempt_at', '<=', gmdate('Y-m-d H:i:s'))->setLimit(max(1, min(100, $batchSize)))->execute();
    $sent = 0;
    foreach ($rows as $row) {
      CiviNeoNotifyDelivery::update(FALSE)->addWhere('id', '=', $row['id'])->addWhere('status', 'IN', ['pending', 'retry'])->setValues(['status' => 'processing', 'last_attempt_at' => gmdate('Y-m-d H:i:s')])->execute();
      $transport = CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $row['transport_id'])->addWhere('is_active', '=', TRUE)->execute()->first();
      $destination = CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $row['destination_id'])->addWhere('is_active', '=', TRUE)->execute()->first();
      if (!$transport || !$destination) { $this->failConfiguration($row['id']); continue; }
      $transportConfig = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
      $destinationConfig = is_array($destination['config_json'] ?? NULL) ? $destination['config_json'] : [];
      try { $client = TransportFactory::create((string) $transport['transport_type'], $transportConfig); }
      catch (\Throwable) { $this->failConfiguration($row['id']); continue; }
      $result = $client->send(new MatrixMessage($row['message_body']), new MatrixDestination($destinationConfig['room_id'] ?? NULL, $destinationConfig['room_alias'] ?? NULL), $row['transaction_id']);
      $attempt = (int) $row['attempt_count'] + 1; $policy = $this->retryPolicy ?? new RetryPolicy();
      $retry = !$result->success && !$result->deliveryUnknown && $attempt < (int) \Civi::settings()->get('civineonotify_max_attempts') && $policy->isRetryable($result->category);
      CiviNeoNotifyDelivery::update(FALSE)->addWhere('id', '=', $row['id'])->setValues(['status' => $result->success ? 'sent' : ($result->deliveryUnknown ? 'delivery_unknown' : ($retry ? 'retry' : 'failed')), 'attempt_count' => $attempt, 'next_attempt_at' => $retry ? $policy->nextAttempt($attempt) : NULL, 'last_attempt_at' => gmdate('Y-m-d H:i:s'), 'sent_at' => $result->success ? gmdate('Y-m-d H:i:s') : NULL, 'external_id' => $result->matrixEventId, 'exit_code' => $result->exitCode, 'error_code' => $result->success ? NULL : $result->category, 'error_message' => $result->message])->execute();
      $sent += (int) $result->success;
    }
    return ['processed' => count($rows), 'sent' => $sent];
  }
  private function failConfiguration(int $id): void { CiviNeoNotifyDelivery::update(FALSE)->addWhere('id', '=', $id)->setValues(['status' => 'failed', 'error_code' => 'configuration_error', 'error_message' => 'The transport or destination is unavailable or unsupported.'])->execute(); }
}
