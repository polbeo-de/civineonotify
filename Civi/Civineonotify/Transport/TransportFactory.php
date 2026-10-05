<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class TransportFactory {
  public static function create(string $type, array $config): MatrixTransportInterface {
    return match ($type) {
      'matrix_commander_rs' => new MatrixCommanderRsTransport($config),
      'matrix_nio' => new MatrixNioTransport($config),
      default => throw new \InvalidArgumentException('Unsupported transport type.'),
    };
  }
}
