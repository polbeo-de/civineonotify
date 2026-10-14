<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use Civi\Civineonotify\Transport\TelegramTransport;
use PHPUnit\Framework\TestCase;

final class TelegramTransportTest extends TestCase {
  public function testSendsConfiguredGroupMessageAndReturnsMessageId(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-telegram-');
    file_put_contents($path, json_encode(['key' => 'test-token'], JSON_THROW_ON_ERROR));
    $requests = [];
    $transport = new TelegramTransport(['token_store_file' => $path, 'api_token_key' => 'key', 'parse_mode' => 'HTML', 'disable_link_preview' => TRUE], static function (string $method, string $url, array $payload, int $timeout) use (&$requests): array {
      $requests[] = compact('method', 'url', 'payload', 'timeout');
      return ['status' => 200, 'body' => '{"ok":true,"result":{"message_id":42}}'];
    });
    try {
      $result = $transport->send(new NotificationMessage('Hello'), new NotificationDestination(['chat_id' => '-100123']), 'delivery-1');
      self::assertTrue($result->success);
      self::assertSame('42', $result->externalId);
      self::assertSame('POST', $requests[0]['method']);
      self::assertSame('https://api.telegram.org/bottest-token/sendMessage', $requests[0]['url']);
      self::assertSame(['chat_id' => '-100123', 'text' => 'Hello', 'disable_web_page_preview' => TRUE, 'disable_notification' => FALSE, 'protect_content' => FALSE, 'parse_mode' => 'HTML'], $requests[0]['payload']);
    }
    finally { unlink($path); }
  }

  public function testRejectsDirectChatsAndDoesNotMakeRequest(): void {
    $transport = new TelegramTransport([]);
    self::assertFalse($transport->dryRun('Hello', ['chat_id' => '123456'])['valid']);
  }

  public function testReportsRateLimitAndTimeoutWithoutClaimingDelivery(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-telegram-');
    file_put_contents($path, json_encode(['key' => 'test-token'], JSON_THROW_ON_ERROR));
    try {
      $rateLimited = new TelegramTransport(['token_store_file' => $path, 'api_token_key' => 'key'], static fn(): array => ['status' => 429, 'body' => '{"ok":false,"error_code":429}']);
      self::assertSame('rate_limited', $rateLimited->send(new NotificationMessage('Hello'), new NotificationDestination(['chat_id' => '-100123']), 'delivery-1')->category);
      $timedOut = new TelegramTransport(['token_store_file' => $path, 'api_token_key' => 'key'], static function (): array { throw new RuntimeException('Operation timed out'); });
      $result = $timedOut->send(new NotificationMessage('Hello'), new NotificationDestination(['chat_id' => '-100123']), 'delivery-1');
      self::assertSame('unknown_delivery_state', $result->category);
      self::assertTrue($result->deliveryUnknown);
    }
    finally { unlink($path); }
  }
}
