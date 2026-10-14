<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\MatrixCommanderRsTransport;
use Civi\Civineonotify\Transport\MatrixNioTransport;
use Civi\Civineonotify\Transport\MattermostWebhookTransport;
use Civi\Civineonotify\Transport\MattermostApiTransport;
use Civi\Civineonotify\Transport\TelegramTransport;
use Civi\Civineonotify\Transport\BlueskyTransport;
use Civi\Civineonotify\Transport\TransportFactory;
use PHPUnit\Framework\TestCase;

final class TransportFactoryTest extends TestCase {
  public function testSelectsBothSupportedMatrixClients(): void {
    self::assertInstanceOf(MatrixCommanderRsTransport::class, TransportFactory::create('matrix_commander_rs', []));
    self::assertInstanceOf(MatrixNioTransport::class, TransportFactory::create('matrix_nio', []));
    self::assertInstanceOf(MattermostWebhookTransport::class, TransportFactory::create('mattermost_webhook', []));
    self::assertInstanceOf(MattermostApiTransport::class, TransportFactory::create('mattermost_api', []));
    self::assertInstanceOf(TelegramTransport::class, TransportFactory::create('telegram', []));
    self::assertInstanceOf(BlueskyTransport::class, TransportFactory::create('bluesky', []));
  }

  public function testRejectsUnknownTransport(): void {
    $this->expectException(InvalidArgumentException::class);
    TransportFactory::create('unknown', []);
  }
}
