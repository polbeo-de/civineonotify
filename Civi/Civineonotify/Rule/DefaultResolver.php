<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Rule;

use Civi\Api4\CiviNeoNotifyDestination;
use Civi\Api4\CiviNeoNotifyTransport;

final class DefaultResolver {
  public function resolve(array $rule): array {
    $destinationId = (int) ($rule['destination_id'] ?? 0) ?: NULL;
    $transportId = (int) ($rule['transport_id'] ?? 0) ?: NULL;
    if ($destinationId) {
      $destination = CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $destinationId)->execute()->first();
      $transportId ??= (int) ($destination['transport_id'] ?? 0) ?: NULL;
    }
    if ($transportId && !$destinationId) {
      $transport = CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $transportId)->execute()->first();
      $destinationId = (int) ($transport['default_destination_id'] ?? 0) ?: NULL;
    }
    return ['transport_id' => $transportId, 'destination_id' => $destinationId, 'message_template' => $rule['message_template'] ?? \Civi::settings()->get('civineonotify_default_message_template'), 'message_format' => $rule['message_format'] ?? \Civi::settings()->get('civineonotify_default_message_format') ?: 'plain'];
  }
}
