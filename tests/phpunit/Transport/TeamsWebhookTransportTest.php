<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Test\Transport;

use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use Civi\Civineonotify\Transport\TeamsWebhookTransport;
use PHPUnit\Framework\TestCase;

final class TeamsWebhookTransportTest extends TestCase {
  public function testPostsWorkflowTextPayload(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-teams-');
    self::assertNotFalse($path);
    try {
      (new \Civi\Civineonotify\Transport\MattermostTokenStore($path))->put('webhook', 'https://1.1.1.1/workflows/webhook');
      $request = [];
      $transport = new TeamsWebhookTransport(['token_store_file' => $path], static function (string $url, string $payload, int $timeout, array $headers) use (&$request): array { $request = compact('url', 'payload', 'timeout', 'headers'); return ['status' => 202, 'error' => '']; });
      self::assertTrue($transport->send(new NotificationMessage('Hello', 'plain_text'), new NotificationDestination(['webhook_key' => 'webhook']), 'transaction-1')->success);
      self::assertSame(['text' => 'Hello'], json_decode($request['payload'], TRUE, 512, JSON_THROW_ON_ERROR));
    }
    finally { @unlink($path); }
  }
}
