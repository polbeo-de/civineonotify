<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Outbox;

use Civi\Civineonotify\Event\NormalizedEvent;

final class OutboxRepository {
  public function enqueue(array $rule, array $resolved, NormalizedEvent $event, string $message): void {
    if (!$resolved['connection_id'] || !$resolved['room_id'] || $message === '') { throw new \LogicException('A Matrix notification needs a connection, room, and message.'); }
    \Civi\Api4\MatrixNotification::create(FALSE)->setValues([
      'rule_id' => $rule['id'], 'connection_id' => $resolved['connection_id'], 'room_id' => $resolved['room_id'],
      'event_name' => $event->eventName, 'entity_type' => $event->entityType, 'entity_id' => (string) $event->entityId,
      'correlation_id' => $event->correlationId, 'transaction_id' => bin2hex(random_bytes(16)),
      'message_body' => $message, 'payload_json' => ['origin' => $event->origin], 'status' => 'pending',
      'attempt_count' => 0, 'next_attempt_at' => gmdate('Y-m-d H:i:s'),
    ])->execute();
  }
}
