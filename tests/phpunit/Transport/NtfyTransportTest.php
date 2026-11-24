<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Test\Transport;

use Civi\Civineonotify\Transport\NtfyTransport;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class NtfyTransportTest extends TestCase {
  public function testPostsPlainTextToTopicWithOptionalToken(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-ntfy-');
    self::assertNotFalse($path);
    try {
      (new \Civi\Civineonotify\Transport\MattermostTokenStore($path))->put('token', 'secret');
      $requests = [];
      $transport = new NtfyTransport(['server_url' => 'https://ntfy.example.test', 'token_store_file' => $path, 'api_token_key' => 'token', 'icon_url' => 'https://civi.example.test/civineo.png'], static function (string $url, string $body, array $headers, int $timeout) use (&$requests): array { $requests[] = compact('url', 'body', 'headers', 'timeout'); return ['status' => 200, 'body' => '']; });
      self::assertTrue($transport->send(new NotificationMessage('Hello', 'plain_text'), new NotificationDestination(['topic' => 'operations']), 'transaction-1')->success);
      self::assertSame('https://ntfy.example.test/operations', $requests[0]['url']);
      self::assertSame('Hello', $requests[0]['body']);
      self::assertSame('Bearer secret', $requests[0]['headers']['Authorization']);
      self::assertSame('https://civi.example.test/civineo.png', $requests[0]['headers']['Icon']);
    }
    finally { @unlink($path); }
  }
}
