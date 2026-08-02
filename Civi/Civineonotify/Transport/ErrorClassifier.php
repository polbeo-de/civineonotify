<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class ErrorClassifier {
  public function classify(?int $exitCode, string $stderr): string {
    $text = strtolower($stderr);
    if (str_contains($text, 'timed out')) return 'timeout';
    if (str_contains($text, 'rate limit') || str_contains($text, '429')) return 'rate_limited';
    if (str_contains($text, 'store') && str_contains($text, 'lock')) return 'store_locked';
    if (str_contains($text, 'unauthorized') || str_contains($text, 'authentication')) return 'authentication_error';
    if (str_contains($text, 'room') && (str_contains($text, 'not found') || str_contains($text, 'forbidden'))) return 'invalid_room';
    return $exitCode === 0 ? 'success' : 'temporary_transport_error';
  }
}
