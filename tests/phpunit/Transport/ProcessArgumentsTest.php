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
}
