<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\{GenericWebhookTransport, MattermostTokenStore, NotificationDestination, NotificationMessage};
use PHPUnit\Framework\TestCase;

final class GenericWebhookTransportTest extends TestCase {
  public function testSendsSignedJsonEnvelopeWithIdempotencyKey(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-webhook-');
    self::assertNotFalse($path);
    unlink($path);
    try {
      (new MattermostTokenStore($path))->put('signing-key', 'shared-secret');
      $captured = [];
      $transport = new GenericWebhookTransport(['token_store_file' => $path, 'timeout_seconds' => 15], static function (string $url, string $payload, int $timeout, array $headers) use (&$captured): array {
        $captured = compact('url', 'payload', 'timeout', 'headers');
        return ['status' => 202, 'error' => ''];
      });
      $result = $transport->send(new NotificationMessage('Hello', 'plain'), new NotificationDestination(['endpoint_url' => 'https://1.1.1.1/notify', 'signing_key' => 'signing-key']), 'delivery-1');
      self::assertTrue($result->success);
      self::assertSame('https://1.1.1.1/notify', $captured['url']);
      self::assertSame(15, $captured['timeout']);
      self::assertSame('delivery-1', $captured['headers']['Idempotency-Key']);
      self::assertSame('sha256=' . hash_hmac('sha256', $captured['payload'], 'shared-secret'), $captured['headers']['X-CiviNeo-Signature']);
      self::assertSame(['version' => 1, 'transaction_id' => 'delivery-1', 'message' => ['body' => 'Hello', 'format' => 'plain']], json_decode($captured['payload'], TRUE, 512, JSON_THROW_ON_ERROR));
    }
    finally { @unlink($path); }
  }

  public function testRejectsPrivateOrNonHttpsEndpoint(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-webhook-');
    self::assertNotFalse($path);
    unlink($path);
    try {
      $transport = new GenericWebhookTransport(['token_store_file' => $path]);
      self::assertSame('configuration_error', $transport->send(new NotificationMessage('Hello'), new NotificationDestination(['endpoint_url' => 'http://example.test']), 'delivery-1')->category);
      self::assertSame('configuration_error', $transport->send(new NotificationMessage('Hello'), new NotificationDestination(['endpoint_url' => 'https://127.0.0.1/hook']), 'delivery-1')->category);
    }
    finally { @unlink($path); }
  }
}
