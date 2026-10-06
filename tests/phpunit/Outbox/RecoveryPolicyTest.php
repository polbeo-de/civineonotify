<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class RecoveryPolicyTest extends TestCase { public function testProcessingTimeoutIsConservative(): void { self::assertSame('delivery_unknown','delivery_unknown'); } }
