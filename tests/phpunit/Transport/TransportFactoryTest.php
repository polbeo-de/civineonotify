<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\MatrixCommanderRsTransport;
use Civi\Civineonotify\Transport\MatrixNioTransport;
use Civi\Civineonotify\Transport\MattermostWebhookTransport;
use Civi\Civineonotify\Transport\TransportFactory;
use PHPUnit\Framework\TestCase;

final class TransportFactoryTest extends TestCase {
  public function testSelectsBothSupportedMatrixClients(): void {
    self::assertInstanceOf(MatrixCommanderRsTransport::class, TransportFactory::create('matrix_commander_rs', []));
    self::assertInstanceOf(MatrixNioTransport::class, TransportFactory::create('matrix_nio', []));
    self::assertInstanceOf(MattermostWebhookTransport::class, TransportFactory::create('mattermost_webhook', []));
  }

  public function testRejectsUnknownTransport(): void {
    $this->expectException(InvalidArgumentException::class);
    TransportFactory::create('unknown', []);
  }
}
