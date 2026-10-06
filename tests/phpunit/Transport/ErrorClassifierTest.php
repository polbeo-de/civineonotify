<?php
declare(strict_types=1);
use Civi\Civineonotify\Transport\ErrorClassifier;
use PHPUnit\Framework\TestCase;
final class ErrorClassifierTest extends TestCase {
  public function testClassifiesTemporaryFailure(): void { self::assertSame('temporary_transport_error', (new ErrorClassifier())->classify(1, '')); }
  public function testClassifiesTimeout(): void { self::assertSame('timeout', (new ErrorClassifier())->classify(1, 'Process timed out')); }
  public function testClassifiesRateLimit(): void { self::assertSame('rate_limited', (new ErrorClassifier())->classify(1, 'HTTP 429 rate limit')); }
}
