<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Test\Transport;

use Civi\Civineonotify\Transport\GotifyTransport;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class GotifyTransportTest extends TestCase {
  public function testPostsJsonToApplicationTokenEndpoint(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-gotify-');
    self::assertNotFalse($path);
    try {
      (new \Civi\Civineonotify\Transport\MattermostTokenStore($path))->put('application', 'secret');
      $requests = [];
      $transport = new GotifyTransport(['server_url' => 'https://gotify.example.test', 'token_store_file' => $path, 'image_url' => 'https://civi.example.test/civineo.png'], static function (string $url, string $payload, int $timeout) use (&$requests): array { $requests[] = compact('url', 'payload', 'timeout'); return ['status' => 200, 'body' => '{}']; });
      self::assertTrue($transport->send(new NotificationMessage('Hello', 'plain_text'), new NotificationDestination(['application_token_key' => 'application']), 'transaction-1')->success);
      self::assertSame('https://gotify.example.test/message?token=secret', $requests[0]['url']);
      $payload = json_decode($requests[0]['payload'], TRUE, 512, JSON_THROW_ON_ERROR);
      self::assertSame('Hello', $payload['message']);
      self::assertSame('https://civi.example.test/civineo.png', $payload['extras']['client::notification']['bigImageUrl']);
    }
    finally { @unlink($path); }
  }
}
