<?php
declare(strict_types=1);
use Civi\Civineonotify\Transport\MatrixCommanderRsTransport;
use PHPUnit\Framework\TestCase;
final class ProcessArgumentsTest extends TestCase {
  public function testRejectsRelativeBinaryPathWithoutRunningProcess(): void {
    $transport = new MatrixCommanderRsTransport(['binary_path'=>'relative','credentials_path'=>'/tmp/nope','store_path'=>'/tmp']);
    $result = $transport->testConnection(['binary_path'=>'relative','credentials_path'=>'/tmp/nope','store_path'=>'/tmp']);
    self::assertFalse($result->success); self::assertSame('configuration_error',$result->category);
  }

  public function testReturnsRetryableResultWhenEncryptionStoreIsBusy(): void {
    $store = sys_get_temp_dir() . '/civineonotify-store-' . bin2hex(random_bytes(8));
    mkdir($store, 0700);
    $credentials = $store . '/credentials.json';
    file_put_contents($credentials, '{}');
    $lock = fopen($store . '/.civineonotify.lock', 'c');
    flock($lock, LOCK_EX);
    try {
      $config = ['binary_path' => '/bin/true', 'credentials_path' => $credentials, 'store_path' => $store];
      $result = (new MatrixCommanderRsTransport($config))->testConnection($config);
      self::assertFalse($result->success);
      self::assertSame('store_locked', $result->category);
    }
    finally {
      flock($lock, LOCK_UN);
      fclose($lock);
      unlink($store . '/.civineonotify.lock');
      unlink($credentials);
      rmdir($store);
    }
  }
}
