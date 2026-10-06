<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

interface MatrixTransportInterface { public function send(MatrixMessage $message, MatrixDestination $destination, string $transactionId): MatrixTransportResult; public function testConnection(array $connection): MatrixTransportResult; public function capabilities(): array; }
