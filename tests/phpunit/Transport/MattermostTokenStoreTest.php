<?php

declare(strict_types=1);

use Civi\Civineonotify\Transport\MattermostTokenStore;
use PHPUnit\Framework\TestCase;

final class MattermostTokenStoreTest extends TestCase {
  public function testStoresAndMasksTokenOutsideCiviConfiguration(): void {
    $path = tempnam(sys_get_temp_dir(), 'civineonotify-tokens-');
    unlink($path);
    $store = new MattermostTokenStore($path);
    try {
      $store->put('destination-key', 'secret-token-1234');
      self::assertSame('secret-token-1234', $store->get('destination-key'));
      self::assertSame('1234', $store->suffix('destination-key'));
      self::assertSame('600', substr(sprintf('%o', fileperms($path)), -3));
    }
    finally { @unlink($path); @unlink($path . '.lock'); }
  }
}
