<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\MattermostApiClient;
use PHPUnit\Framework\TestCase;

final class MattermostApiClientTest extends TestCase {
  public function testListsAccessiblePublicAndPrivateChannels(): void {
    $secret = tempnam(sys_get_temp_dir(), 'civineonotify-api-');
    file_put_contents($secret, json_encode(['api-key' => 'api-secret'], JSON_THROW_ON_ERROR));
    $requests = [];
    $client = new MattermostApiClient(['server_url' => 'https://mattermost.example', 'token_store_file' => $secret, 'api_token_key' => 'api-key'], static function (string $url, array $headers, int $timeout) use (&$requests): array {
      $requests[] = compact('url', 'headers', 'timeout');
      return str_ends_with($url, '/teams') ? [['id' => 'team-1', 'display_name' => 'Team']] : [
        ['id' => 'public', 'display_name' => 'Public', 'type' => 'O'],
        ['id' => 'private', 'display_name' => 'Private', 'type' => 'P'],
        ['id' => 'direct', 'display_name' => 'Direct', 'type' => 'D'],
      ];
    });
    try {
      self::assertSame(['private' => 'Team / Private', 'public' => 'Team / Public'], $client->channels());
      self::assertSame(['Authorization: Bearer api-secret'], $requests[0]['headers']);
      self::assertSame(30, $requests[0]['timeout']);
    }
    finally { unlink($secret); }
  }
}
