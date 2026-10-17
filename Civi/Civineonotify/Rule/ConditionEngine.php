<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Rule;

use Civi\Civineonotify\Event\NormalizedEvent;
use CRM_Civineonotify_ExtensionUtil as E;

final class ConditionEngine {
  private const OPERATORS = ['=', '!=', 'in', 'not_in', 'contains', 'is_empty', 'is_not_empty', 'changed'];
  public function validate(array $condition): void {
    if (!$condition) { return; }
    $key = isset($condition['all']) ? 'all' : (isset($condition['any']) ? 'any' : NULL);
    if (!$key || !is_array($condition[$key])) { throw new \InvalidArgumentException(E::ts('Condition requires an all or any list.')); }
    foreach ($condition[$key] as $clause) {
      if (!is_array($clause) || !isset($clause['field'], $clause['operator']) || !in_array($clause['operator'], self::OPERATORS, TRUE)) { throw new \InvalidArgumentException(E::ts('Condition contains an unsupported clause.')); }
      if (!preg_match('/^(event|entity|data|previous)\\.[A-Za-z0-9_.:-]+$/', (string) $clause['field'])) { throw new \InvalidArgumentException(E::ts('Condition field is not allowlisted.')); }
    }
  }
  public function matches(array $condition, NormalizedEvent $event): bool {
    $this->validate($condition); if (!$condition) { return TRUE; }
    $values = array_map(fn(array $c): bool => $this->matchesClause($c, $event), $condition['all'] ?? $condition['any']);
    return isset($condition['all']) ? !in_array(FALSE, $values, TRUE) : in_array(TRUE, $values, TRUE);
  }
  private function matchesClause(array $clause, NormalizedEvent $event): bool {
    $field = (string) $clause['field']; $actual = $this->value($field, $event); $expected = $clause['value'] ?? NULL;
    return match ($clause['operator']) {
      '=' => $actual === $expected, '!=' => $actual !== $expected,
      'in' => is_array($expected) && in_array($actual, $expected, TRUE),
      'not_in' => is_array($expected) && !in_array($actual, $expected, TRUE),
      'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
      'is_empty' => $actual === NULL || $actual === '' || $actual === [],
      'is_not_empty' => !($actual === NULL || $actual === '' || $actual === []),
      'changed' => str_starts_with($field, 'data.') && $this->changed($actual, $this->value('previous.' . substr($field, 5), $event)),
    };
  }
  private function value(string $field, NormalizedEvent $event): mixed {
    $root = ['event' => ['name' => $event->eventName, 'operation' => $event->operation], 'entity' => ['type' => $event->entityType, 'id' => $event->entityId], 'data' => $event->data, 'previous' => $event->previousData];
    $value = $root; foreach (explode('.', $field) as $part) { if (!is_array($value) || !array_key_exists($part, $value)) { return NULL; } $value = $value[$part]; } return $value;
  }

  private function changed(mixed $current, mixed $previous): bool {
    if (is_scalar($current) && is_scalar($previous)) return (string) $current !== (string) $previous;
    return $current !== $previous;
  }
}
