<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\BlueskyTransport;
use Civi\Civineonotify\Transport\NotificationDestination;
use Civi\Civineonotify\Transport\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class BlueskyTransportTest extends TestCase {
  public function testPublishesWithUtf8ByteFacetsAndStableRecordKey(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-bluesky-'); file_put_contents($path, json_encode(['key' => 'app-password'], JSON_THROW_ON_ERROR)); $requests=[];
    $client = new BlueskyTransport(['handle' => '@org.example', 'token_store_file' => $path, 'api_token_key' => 'key', 'language' => 'de', 'detect_links' => TRUE], static function (string $method, string $url, ?array $payload, array $headers) use (&$requests): array {
      $requests[] = compact('method','url','payload','headers');
      if (str_contains($url, 'resolveHandle')) return ['status'=>200,'body'=>'{"did":"did:plc:abc"}'];
      if (str_contains($url, 'plc.directory')) return ['status'=>200,'body'=>'{"id":"did:plc:abc","service":[{"id":"#atproto_pds","type":"AtprotoPersonalDataServer","serviceEndpoint":"https://pds.example"}]}'];
      if (str_contains($url, 'createSession')) return ['status'=>200,'body'=>'{"did":"did:plc:abc","accessJwt":"access","refreshJwt":"refresh"}'];
      return ['status'=>200,'body'=>'{"uri":"at://did:plc:abc/app.bsky.feed.post/abc","cid":"cid"}'];
    });
    try { $result=$client->send(new NotificationMessage('😀 https://example.com'),new NotificationDestination([]),'delivery-1'); self::assertTrue($result->success); self::assertSame('at://did:plc:abc/app.bsky.feed.post/abc',$result->externalId); self::assertStringContainsString('handle=org.example',$requests[0]['url']); $record=$requests[count($requests)-1]['payload']['record']; self::assertSame('de',$record['langs'][0]); self::assertSame(5,$record['facets'][0]['index']['byteStart']); self::assertSame(24,$record['facets'][0]['index']['byteEnd']); self::assertMatchesRegularExpression('/^[234567abcdefghijklmnopqrstuvwxyz]{13}$/',$requests[count($requests)-1]['payload']['rkey']); } finally { unlink($path); }
  }
  public function testDryRunCountsGraphemesAndNeverCallsNetwork(): void { $client=new BlueskyTransport(['detect_links'=>TRUE], static function (): array { self::fail('Dry run must not request the network.'); }); self::assertTrue($client->dryRun(str_repeat('👨‍👩‍👧‍👧',300))['valid']); self::assertFalse($client->dryRun(str_repeat('a',301))['valid']); }
}
