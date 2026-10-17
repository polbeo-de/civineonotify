<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Rule;

use Civi\Civineonotify\Event\NormalizedEvent;
use CRM_Civineonotify_ExtensionUtil as E;

final class MessageRenderer {
  public function validate(string $template): void { preg_match_all('/\{([^}]+)\}/', $template, $m); foreach ($m[1] as $token) { if (!$this->tokenValue($token, NULL, FALSE)[0]) { throw new \InvalidArgumentException(E::ts('Unknown CiviNeo Notify token: %1', [1 => $token])); } } }
  public function render(string $template, NormalizedEvent $event): string {
    $this->validate($template); return preg_replace_callback('/\{([^}]+)\}/', fn(array $m): string => (string) $this->tokenValue($m[1], $event, TRUE)[1], $template) ?? $template;
  }
  private function tokenValue(string $token, ?NormalizedEvent $event, bool $value): array {
    $allowed = ['event.name', 'event.operation', 'entity.type', 'entity.id', 'actor.contact_id', 'site.name', 'site.url', 'data.subject', 'data.title', 'case.id', 'case.status.old', 'case.status.new', 'case.contact.first_name', 'case.contact.last_name'];
    if (!in_array($token, $allowed, TRUE)) { return [FALSE, NULL]; }
    if (!$value) { return [TRUE, NULL]; }
    $map = ['event.name' => $event->eventName, 'event.operation' => $event->operation, 'entity.type' => $event->entityType, 'entity.id' => $event->entityId, 'actor.contact_id' => $event->actorContactId, 'site.name' => defined('CIVICRM_SITE_NAME') ? CIVICRM_SITE_NAME : '', 'site.url' => defined('CIVICRM_UF_BASEURL') ? CIVICRM_UF_BASEURL : '', 'case.id' => $event->entityType === 'Case' ? $event->entityId : '', 'case.status.old' => $event->previousData['status_label'] ?? '', 'case.status.new' => $event->data['status_label'] ?? '', 'case.contact.first_name' => $event->data['contact_first_name'] ?? '', 'case.contact.last_name' => $event->data['contact_last_name'] ?? ''];
    return [TRUE, str_starts_with($token, 'data.') ? ($event->data[substr($token, 5)] ?? '') : ($map[$token] ?? '')];
  }
}
