<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\MastodonTransport;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class MastodonTransportTest extends TestCase {
  public function testPublishesWithStableIdempotencyAndOptions(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-mastodon-'); file_put_contents($path, json_encode(['key' => 'token'], JSON_THROW_ON_ERROR)); $requests = [];
    $transport = new MastodonTransport(['instance_url' => 'https://example.com', 'token_store_file' => $path, 'api_token_key' => 'key', 'visibility' => 'unlisted', 'language' => 'de'], static function (string $method, string $url, string $body, array $headers, int $timeout) use (&$requests): array {
      $requests[] = compact('method', 'url', 'body', 'headers', 'timeout');
      if (str_ends_with($url, '/api/v2/instance')) return ['status' => 200, 'body' => '{"configuration":{"statuses":{"max_characters":500}}}'];
      return ['status' => 200, 'body' => '{"id":"42","url":"https://example.com/@org/42"}'];
    });
    try {
      $result = $transport->send(new NotificationMessage('Hello'), new NotificationDestination(['spoiler_text' => 'CW']), 'delivery-1');
      self::assertTrue($result->success); self::assertSame('42', $result->externalId);
      self::assertSame(['status' => 'Hello', 'visibility' => 'unlisted', 'language' => 'de', 'spoiler_text' => 'CW'], json_decode($requests[1]['body'], TRUE, 512, JSON_THROW_ON_ERROR));
      self::assertContains('Idempotency-Key: civineonotify-' . hash('sha256', 'delivery-1'), $requests[1]['headers']);
    } finally { unlink($path); }
  }
  public function testRejectsDirectVisibilityAndPrivateHosts(): void {
    $transport = new MastodonTransport(['instance_url' => 'https://127.0.0.1', 'visibility' => 'direct']);
    self::assertFalse($transport->dryRun('Test')['valid']);
  }
}
