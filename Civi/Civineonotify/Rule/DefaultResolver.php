<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Rule;

/** Version 1 exposes global defaults; this boundary reserves event/entity layers. */
final class DefaultResolver {
  public function resolve(array $rule): array {
    $settings = \Civi::settings();
    return [
      'connection_id' => $rule['connection_id'] ?? $settings->get('civineonotify_default_connection_id'),
      'room_id' => $rule['room_id'] ?? $settings->get('civineonotify_default_room_id'),
      'message_template' => $rule['message_template'] ?? $settings->get('civineonotify_default_message_template'),
      'message_format' => $rule['message_format'] ?? $settings->get('civineonotify_default_message_format') ?: 'plain',
    ];
  }
}
