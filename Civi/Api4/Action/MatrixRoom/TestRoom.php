<?php

declare(strict_types=1);

namespace Civi\Api4\Action\MatrixRoom;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civineonotify\Event\NormalizedEvent;
use Civi\Civineonotify\Rule\MessageRenderer;
use Civi\Civineonotify\Transport\{MatrixCommanderRsTransport, MatrixDestination, MatrixMessage};

/** Sends an explicitly requested, auditable test message to one configured room. */
final class TestRoom extends AbstractAction {
  protected int $id;

  public function _run(Result $result): void {
    $room = \Civi\Api4\MatrixRoom::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first();
    if (!$room || empty($room['is_active'])) throw new \CRM_Core_Exception('Matrix room not found or inactive.');
    $connection = \Civi\Api4\MatrixConnection::get(FALSE)->addWhere('id', '=', $room['connection_id'])->addWhere('is_active', '=', TRUE)->execute()->first();
    if (!$connection) throw new \CRM_Core_Exception('The room has no active Matrix connection.');

    $event = new NormalizedEvent('civicrm.civineonotify.room_test', 'ui', 'MatrixRoom', $room['id'], 'test', new \DateTimeImmutable('now', new \DateTimeZone('UTC')), NULL, ['subject' => 'CiviNeo Notify test', 'title' => 'CiviNeo Notify test'], [], [], bin2hex(random_bytes(16)), 'manual_test');
    $template = (string) \Civi::settings()->get('civineonotify_default_message_template');
    $body = (new MessageRenderer())->render($template, $event);
    $transactionId = bin2hex(random_bytes(16));
    $delivery = (new MatrixCommanderRsTransport($connection))->send(new MatrixMessage($body, (string) \Civi::settings()->get('civineonotify_default_message_format')), new MatrixDestination($room['room_id'] ?? NULL, $room['room_alias'] ?? NULL), $transactionId);
    \Civi\Api4\MatrixNotification::create(FALSE)->setValues([
      'connection_id' => $connection['id'], 'room_id' => $room['id'], 'event_name' => $event->eventName,
      'entity_type' => 'MatrixRoom', 'entity_id' => (string) $room['id'], 'correlation_id' => $event->correlationId,
      'transaction_id' => $transactionId, 'message_body' => $body, 'payload_json' => ['origin' => 'manual_test'],
      'status' => $delivery->success ? 'sent' : ($delivery->deliveryUnknown ? 'delivery_unknown' : 'failed'),
      'attempt_count' => 1, 'last_attempt_at' => gmdate('Y-m-d H:i:s'), 'sent_at' => $delivery->success ? gmdate('Y-m-d H:i:s') : NULL,
      'matrix_event_id' => $delivery->matrixEventId, 'exit_code' => $delivery->exitCode,
      'error_code' => $delivery->success ? NULL : $delivery->category, 'error_message' => $delivery->message,
    ])->execute();
    $result[] = ['success' => $delivery->success, 'category' => $delivery->category, 'message' => $delivery->message];
  }
}
