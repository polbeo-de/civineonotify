<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Outbox;

use Civi\Api4\CiviNeoNotifyDelivery;
use Civi\Civineonotify\Event\NormalizedEvent;

final class OutboxRepository {
  public function enqueue(array $rule, array $resolved, NormalizedEvent $event, string $message): void {
    if (empty($resolved['transport_id']) || empty($resolved['destination_id']) || $message === '') throw new \LogicException('A delivery needs a transport, destination, and message.');
    CiviNeoNotifyDelivery::create(FALSE)->setValues(['rule_id' => $rule['id'], 'transport_id' => $resolved['transport_id'], 'destination_id' => $resolved['destination_id'], 'event_name' => $event->eventName, 'entity_type' => $event->entityType, 'entity_id' => (string) $event->entityId, 'correlation_id' => $event->correlationId, 'transaction_id' => bin2hex(random_bytes(16)), 'message_body' => $message, 'payload_json' => ['origin' => $event->origin], 'status' => 'pending', 'attempt_count' => 0, 'next_attempt_at' => gmdate('Y-m-d H:i:s')])->execute();
  }
}
