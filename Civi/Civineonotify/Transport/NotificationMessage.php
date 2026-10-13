<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class NotificationMessage {
  public function __construct(public readonly string $body, public readonly string $format = 'plain') {}
}
