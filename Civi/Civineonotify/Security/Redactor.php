<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Security;

final class Redactor {
  private const SENSITIVE = '/(access[_-]?token|password|secret|authorization|api[_-]?key|security[_-]?key|credentials)\s*([:=])\s*[^\s,]+/i';
  public function redact(string $value, int $maximumLength = 1000): string { return substr((string) preg_replace(self::SENSITIVE, '$1$2[REDACTED]', $value), 0, $maximumLength); }
}
