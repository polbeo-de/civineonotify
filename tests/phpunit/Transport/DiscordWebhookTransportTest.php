<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Test\Transport;

use Civi\Civineonotify\Transport\DiscordWebhookTransport;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class DiscordWebhookTransportTest extends TestCase {
  public function testPostsDiscordContentPayload(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-discord-');
    self::assertNotFalse($path);
    try {
      (new \Civi\Civineonotify\Transport\MattermostTokenStore($path))->put('webhook', 'https://1.1.1.1/api/webhooks/123/token');
      $requests = [];
      $transport = new DiscordWebhookTransport(['token_store_file' => $path], static function (string $url, string $payload, int $timeout, array $headers) use (&$requests): array { $requests[] = compact('url', 'payload', 'timeout', 'headers'); return ['status' => 204, 'error' => '']; });
      self::assertTrue($transport->send(new NotificationMessage('Hello', 'plain_text'), new NotificationDestination(['webhook_key' => 'webhook']), 'transaction-1')->success);
      self::assertSame('Hello', json_decode($requests[0]['payload'], TRUE, 512, JSON_THROW_ON_ERROR)['content']);
    }
    finally { @unlink($path); }
  }
}
