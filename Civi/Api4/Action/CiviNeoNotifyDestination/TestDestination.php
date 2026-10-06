<?php

declare(strict_types=1);

namespace Civi\Api4\Action\CiviNeoNotifyDestination;

use Civi\Api4\CiviNeoNotifyDelivery;
use Civi\Api4\CiviNeoNotifyDestination;
use Civi\Api4\CiviNeoNotifyTransport;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civineonotify\Event\NormalizedEvent;
use Civi\Civineonotify\Rule\MessageRenderer;
use Civi\Civineonotify\Transport\{MatrixDestination, MatrixMessage, TransportFactory};

final class TestDestination extends AbstractAction {
  protected int $id;

  public function _run(Result $result): void {
    $destination = CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first();
    if (!$destination || empty($destination['is_active'])) throw new \CRM_Core_Exception('Destination not found or inactive.');
    $transport = CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $destination['transport_id'])->addWhere('is_active', '=', TRUE)->execute()->first();
    if (!$transport) throw new \CRM_Core_Exception('The destination has no active Matrix transport.');
    $event = new NormalizedEvent('civicrm.civineonotify.destination_test', 'ui', 'CiviNeoNotifyDestination', $destination['id'], 'test', new \DateTimeImmutable('now', new \DateTimeZone('UTC')), NULL, ['subject' => 'CiviNeo Notify test', 'title' => 'CiviNeo Notify test'], [], [], bin2hex(random_bytes(16)), 'manual_test');
    $body = (new MessageRenderer())->render((string) \Civi::settings()->get('civineonotify_default_message_template'), $event);
    $transactionId = bin2hex(random_bytes(16));
    $config = is_array($destination['config_json'] ?? NULL) ? $destination['config_json'] : [];
    try { $client = TransportFactory::create((string) $transport['transport_type'], (array) ($transport['config_json'] ?? [])); }
    catch (\Throwable) { throw new \CRM_Core_Exception('The destination transport is unsupported.'); }
    $delivery = $client->send(new MatrixMessage($body, (string) \Civi::settings()->get('civineonotify_default_message_format')), new MatrixDestination($config['room_id'] ?? NULL, $config['room_alias'] ?? NULL), $transactionId);
    CiviNeoNotifyDelivery::create(FALSE)->setValues([
      'transport_id' => $transport['id'], 'destination_id' => $destination['id'], 'event_name' => $event->eventName, 'entity_type' => 'CiviNeoNotifyDestination', 'entity_id' => (string) $destination['id'], 'correlation_id' => $event->correlationId, 'transaction_id' => $transactionId, 'message_body' => $body, 'payload_json' => ['origin' => 'manual_test'], 'status' => $delivery->success ? 'sent' : ($delivery->deliveryUnknown ? 'delivery_unknown' : 'failed'), 'attempt_count' => 1, 'last_attempt_at' => gmdate('Y-m-d H:i:s'), 'sent_at' => $delivery->success ? gmdate('Y-m-d H:i:s') : NULL, 'external_id' => $delivery->matrixEventId, 'exit_code' => $delivery->exitCode, 'error_code' => $delivery->success ? NULL : $delivery->category, 'error_message' => $delivery->message,
    ])->execute();
    $result[] = ['success' => $delivery->success, 'category' => $delivery->category, 'message' => $delivery->message];
  }
}
