<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Event;

final class NormalizedEvent {
  public function __construct(
    public readonly string $eventName,
    public readonly string $source,
    public readonly ?string $entityType,
    public readonly int|string|null $entityId,
    public readonly ?string $operation,
    public readonly \DateTimeImmutable $occurredAt,
    public readonly ?int $actorContactId,
    public readonly array $data,
    public readonly array $previousData,
    public readonly array $context,
    public readonly string $correlationId,
    public readonly string $origin = 'civicrm',
  ) {}
}
