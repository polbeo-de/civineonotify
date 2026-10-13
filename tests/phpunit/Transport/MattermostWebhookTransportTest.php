<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\MattermostWebhookTransport;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class MattermostWebhookTransportTest extends TestCase {
  public function testPostsMessageToWebhookDefaultChannel(): void {
    $secret = tempnam(sys_get_temp_dir(), 'civineonotify-hook-');
    file_put_contents($secret, json_encode(['destination-key' => 'a-test-secret']));
    $request = NULL;
    $transport = new MattermostWebhookTransport(['server_url' => 'https://mattermost.example', 'hooks_path' => 'hooks', 'token_store_file' => $secret, 'timeout_seconds' => 12], static function (string $url, string $payload, int $timeout) use (&$request): array {
      $request = compact('url', 'payload', 'timeout');
      return ['status' => 200, 'error' => ''];
    });
    try {
      $result = $transport->send(new NotificationMessage('Test notification'), new NotificationDestination(['token_key' => 'destination-key', 'channel' => NULL]), 'tx');
      self::assertTrue($result->success);
      self::assertSame('https://mattermost.example/hooks/a-test-secret', $request['url']);
      self::assertSame(['text' => 'Test notification'], json_decode($request['payload'], TRUE, 512, JSON_THROW_ON_ERROR));
      self::assertSame(12, $request['timeout']);
    }
    finally { unlink($secret); }
  }

  public function testMarksTimeoutAsUnknownDelivery(): void {
    $secret = tempnam(sys_get_temp_dir(), 'civineonotify-hook-');
    file_put_contents($secret, json_encode(['destination-key' => 'a-test-secret']));
    $transport = new MattermostWebhookTransport(['server_url' => 'https://mattermost.example', 'token_store_file' => $secret], static fn() => throw new RuntimeException('Operation timed out'));
    try {
      $result = $transport->send(new NotificationMessage('Test'), new NotificationDestination(['token_key' => 'destination-key']), 'tx');
      self::assertFalse($result->success);
      self::assertTrue($result->deliveryUnknown);
      self::assertSame('unknown_delivery_state', $result->category);
    }
    finally { unlink($secret); }
  }
}
