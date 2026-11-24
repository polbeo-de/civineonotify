<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\{MattermostTokenStore, NotificationDestination, NotificationMessage, RocketChatWebhookTransport, SlackWebhookTransport};
use PHPUnit\Framework\TestCase;

final class NamedWebhookTransportTest extends TestCase {
  public function testSlackAndRocketChatUseTheirStoredIncomingWebhookUrls(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-named-webhook-');
    self::assertNotFalse($path);
    unlink($path);
    try {
      $store = new MattermostTokenStore($path);
      $store->put('slack-key', 'https://1.1.1.1/slack-secret');
      $store->put('rocketchat-key', 'https://1.1.1.1/rocketchat-secret');
      foreach ([['client' => SlackWebhookTransport::class, 'key' => 'slack-key', 'url' => 'https://1.1.1.1/slack-secret'], ['client' => RocketChatWebhookTransport::class, 'key' => 'rocketchat-key', 'url' => 'https://1.1.1.1/rocketchat-secret']] as $example) {
        $captured = [];
        $class = $example['client'];
        $client = new $class(['token_store_file' => $path], static function (string $url, string $payload, int $timeout, array $headers) use (&$captured): array { $captured = compact('url', 'payload', 'timeout', 'headers'); return ['status' => 200, 'error' => '']; });
        self::assertTrue($client->send(new NotificationMessage('Hello'), new NotificationDestination(['webhook_key' => $example['key']]), 'delivery-1')->success);
        self::assertSame($example['url'], $captured['url']);
        self::assertSame(['text' => 'Hello'], json_decode($captured['payload'], TRUE, 512, JSON_THROW_ON_ERROR));
        self::assertSame('delivery-1', $captured['headers']['Idempotency-Key']);
      }
    }
    finally { @unlink($path); }
  }
}
