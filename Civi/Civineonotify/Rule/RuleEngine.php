<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Rule;

use Civi\Civineonotify\Event\NormalizedEvent;
use Civi\Civineonotify\Outbox\OutboxRepository;

final class RuleEngine {
  public function __construct(private readonly ConditionEngine $conditions, private readonly MessageRenderer $renderer, private readonly DefaultResolver $defaults, private readonly OutboxRepository $outbox) {}
  public function route(NormalizedEvent $event): void {
    $rules = \Civi\Api4\CiviNeoNotifyRule::get(FALSE)->addWhere('is_active', '=', TRUE)->addWhere('event_pattern', '=', $event->eventName)->addOrderBy('priority', 'ASC')->addOrderBy('id', 'ASC')->execute();
    foreach ($rules as $rule) {
      if (($rule['entity_type'] ?? NULL) && $rule['entity_type'] !== $event->entityType) { continue; }
      if (($rule['operation'] ?? NULL) && strtolower($rule['operation']) !== $event->operation) { continue; }
      $condition = $rule['condition_json'] ?? []; if (!$this->conditions->matches(is_array($condition) ? $condition : [], $event)) { continue; }
      $resolved = $this->defaults->resolve($rule); $template = (string) ($resolved['message_template'] ?? '');
      $this->outbox->enqueue($rule, $resolved, $event, $this->renderer->render($template, $event));
      if (!empty($rule['stop_processing'])) { break; }
    }
  }
}
