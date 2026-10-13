<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

interface NotificationTransportInterface {
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult;

  public function testConnection(array $connection): MatrixTransportResult;

  public function capabilities(): array;
}
