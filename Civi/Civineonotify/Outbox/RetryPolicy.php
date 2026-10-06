<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Outbox;

final class RetryPolicy {
  public function isRetryable(string $category): bool {
    return in_array($category, ['temporary_transport_error', 'rate_limited', 'timeout', 'store_locked'], TRUE);
  }
  public function nextAttempt(int $attempt): string {
    $delays = \Civi::settings()->get('civineonotify_retry_delays') ?: [60, 300, 1800, 7200];
    $delay = (int) ($delays[min(max(0, $attempt - 1), count($delays) - 1)] ?? 7200);
    return gmdate('Y-m-d H:i:s', time() + max(1, $delay));
  }
}
