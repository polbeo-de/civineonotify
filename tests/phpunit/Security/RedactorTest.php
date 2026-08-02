<?php
declare(strict_types=1);
use Civi\Civineonotify\Security\Redactor;
use PHPUnit\Framework\TestCase;
final class RedactorTest extends TestCase { public function testRedactsSecret(): void { self::assertStringNotContainsString('secret-value',(new Redactor())->redact('access_token=secret-value')); } }
