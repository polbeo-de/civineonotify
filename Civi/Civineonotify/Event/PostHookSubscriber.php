<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Event;

use Civi\Civineonotify\Rule\RuleEngine;

final class PostHookSubscriber {
  public function __construct(private readonly RuleEngine $ruleEngine, private readonly ?EventNormalizer $normalizer = NULL) {}
  public function onPost(string $operation, string $entityType, int|string $entityId, mixed $object, array $previousData = []): void {
    $event = ($this->normalizer ?? new EventNormalizer())->fromPost($operation, $entityType, $entityId, $object, $previousData);
    $this->ruleEngine->route($event);
  }
}
