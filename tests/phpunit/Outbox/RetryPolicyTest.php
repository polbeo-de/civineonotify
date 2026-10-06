<?php
declare(strict_types=1);
use Civi\Civineonotify\Outbox\RetryPolicy;
use PHPUnit\Framework\TestCase;
final class RetryPolicyTest extends TestCase { public function testRetryableCategories(): void { self::assertTrue((new RetryPolicy())->isRetryable('timeout')); self::assertFalse((new RetryPolicy())->isRetryable('authentication_error')); } }
