<?php

declare(strict_types=1);

namespace Civi\Api4\Action\CiviNeoNotifyTransport;

use Civi\Api4\CiviNeoNotifyTransport;
use Civi\Api4\CiviNeoNotifyDestination;
use Civi\Api4\CiviNeoNotifyDelivery;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civineonotify\Event\NormalizedEvent;
use Civi\Civineonotify\Rule\MessageRenderer;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use Civi\Civineonotify\Transport\TransportFactory;
use CRM_Civineonotify_ExtensionUtil as E;

final class TestTransport extends AbstractAction {
  protected int $id;

  public function _run(Result $result): void {
    $transport = CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first();
    if (!$transport || empty($transport['is_active'])) throw new \CRM_Core_Exception(E::ts('Transport not found or inactive.'));
    $destinationId = (int) ($transport['default_destination_id'] ?? 0);
    if (!$destinationId) {
      $result[] = ['success' => FALSE, 'category' => 'default_destination_missing', 'message' => E::ts('Set an active default destination before sending a test.')];
      return;
    }
    $destination = CiviNeoNotifyDestination::get(FALSE)
      ->addWhere('id', '=', $destinationId)
      ->addWhere('transport_id', '=', $this->id)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first();
    if (!$destination) {
      $result[] = ['success' => FALSE, 'category' => 'default_destination_missing', 'message' => E::ts('The configured default destination is unavailable.')];
      return;
    }

    $event = new NormalizedEvent('civicrm.civineonotify.transport_test', 'ui', 'CiviNeoNotifyTransport', $this->id, 'test', new \DateTimeImmutable('now', new \DateTimeZone('UTC')), NULL, ['subject' => 'CiviNeo Notify test', 'title' => 'CiviNeo Notify test'], [], [], bin2hex(random_bytes(16)), 'manual_test');
    $body = (new MessageRenderer())->render((string) \Civi::settings()->get('civineonotify_transport_test_message_template'), $event);
    $transactionId = bin2hex(random_bytes(16));
    $config = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
    $destinationConfig = is_array($destination['config_json'] ?? NULL) ? $destination['config_json'] : [];
    try { $client = TransportFactory::create((string) $transport['transport_type'], $config); }
    catch (\Throwable) { throw new \CRM_Core_Exception(E::ts('The transport type is unsupported.')); }
    $response = $client->send(
      new NotificationMessage($body, (string) \Civi::settings()->get('civineonotify_default_message_format')),
      new NotificationDestination($destinationConfig),
      $transactionId,
    );
    CiviNeoNotifyDelivery::create(FALSE)->setValues([
      'transport_id' => $transport['id'], 'destination_id' => $destination['id'], 'event_name' => $event->eventName,
      'entity_type' => 'CiviNeoNotifyTransport', 'entity_id' => (string) $this->id, 'correlation_id' => $event->correlationId,
      'transaction_id' => $transactionId, 'message_body' => $body, 'payload_json' => ['origin' => 'manual_transport_test'],
      'status' => $response->success ? 'sent' : ($response->deliveryUnknown ? 'delivery_unknown' : 'failed'),
      'attempt_count' => 1, 'last_attempt_at' => gmdate('Y-m-d H:i:s'), 'sent_at' => $response->success ? gmdate('Y-m-d H:i:s') : NULL,
      'external_id' => $response->externalId, 'exit_code' => $response->exitCode,
      'error_code' => $response->success ? NULL : $response->category, 'error_message' => $response->message,
    ])->execute();
    $result[] = ['success' => $response->success, 'category' => $response->category, 'message' => $response->message];
  }
}
