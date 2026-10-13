<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class NotificationDestination {
  public function __construct(public readonly array $config) {}
}
