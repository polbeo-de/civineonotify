<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Publishes plain-text notifications through one configured Mastodon account. */
final class MastodonTransport implements NotificationTransportInterface {
  /** @var array<string,int> */
  private static array $characterLimits = [];

  /** @param null|\Closure(string,string,array,int):array{status:int,body:string,error?:string,headers?:array<string,string>} $requester */
  public function __construct(private readonly array $connection, private readonly ?\Closure $requester = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $payload = $this->payload($message->body, $destination->config);
      $limit = $this->characterLimit();
      if ($limit !== NULL && mb_strlen($message->body) > $limit) return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The Mastodon post exceeds this instance\'s character limit.');
      $response = $this->request('POST', '/api/v1/statuses', $payload, ['Content-Type: application/json', 'Idempotency-Key: civineonotify-' . hash('sha256', $transactionId)]);
      if ($response['status'] >= 200 && $response['status'] < 300) {
        $body = json_decode($response['body'], TRUE, 512, JSON_THROW_ON_ERROR);
        if (!is_array($body) || empty($body['id'])) throw new \RuntimeException('Mastodon returned an invalid status response.');
        return new MatrixTransportResult(TRUE, 'success', (string) $body['id'], $response['status']);
      }
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], $this->message($response['status']));
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mastodon returned an invalid response.'); }
    catch (\RuntimeException $exception) { $timeout = str_contains(strtolower($exception->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $timeout ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $timeout ? 'Mastodon timed out; the publication outcome is unknown.' : 'Mastodon could not be reached.', $timeout); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mastodon configuration is not usable.'); }
  }

  public function testConnection(array $connection): MatrixTransportResult {
    try {
      $response = $this->request('GET', '/api/v1/accounts/verify_credentials');
      return $response['status'] >= 200 && $response['status'] < 300 ? new MatrixTransportResult(TRUE, 'success') : new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], $this->message($response['status']));
    }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mastodon configuration is not usable.'); }
  }

  public function capabilities(): array { return ['transport' => 'mastodon', 'plain_text_statuses' => TRUE, 'dry_run' => TRUE]; }

  /** @return array{valid:bool,payload:array<string,string>,character_limit:?int,message:?string} */
  public function dryRun(string $body, array $overrides = []): array {
    try {
      $payload = $this->payload($body, $overrides); $limit = $this->characterLimit();
      if ($limit !== NULL && mb_strlen($body) > $limit) return ['valid' => FALSE, 'payload' => $payload, 'character_limit' => $limit, 'message' => 'The Mastodon post exceeds this instance\'s character limit.'];
      return ['valid' => TRUE, 'payload' => $payload, 'character_limit' => $limit, 'message' => NULL];
    }
    catch (\Throwable) { return ['valid' => FALSE, 'payload' => [], 'character_limit' => NULL, 'message' => 'Mastodon configuration is not usable.']; }
  }

  /** @return array<string,string> */
  private function payload(string $body, array $overrides): array {
    if ($body === '') throw new \InvalidArgumentException('Empty Mastodon post.');
    $visibility = (string) ($overrides['visibility'] ?? $this->connection['visibility'] ?? 'public');
    if (!in_array($visibility, ['public', 'unlisted', 'private'], TRUE)) throw new \InvalidArgumentException('Invalid Mastodon visibility.');
    $payload = ['status' => $body, 'visibility' => $visibility];
    foreach (['language', 'spoiler_text'] as $field) {
      $value = trim((string) ($overrides[$field] ?? $this->connection[$field] ?? ''));
      if ($value !== '') $payload[$field] = $value;
    }
    if (isset($payload['language']) && !preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/', $payload['language'])) throw new \InvalidArgumentException('Invalid Mastodon language.');
    return $payload;
  }

  private function characterLimit(): ?int {
    $base = $this->baseUrl();
    if (isset(self::$characterLimits[$base])) return self::$characterLimits[$base];
    try {
      $response = $this->request('GET', '/api/v2/instance');
      $body = $response['status'] >= 200 && $response['status'] < 300 ? json_decode($response['body'], TRUE, 512, JSON_THROW_ON_ERROR) : [];
      $limit = $body['configuration']['statuses']['max_characters'] ?? NULL;
      return is_int($limit) && $limit > 0 ? self::$characterLimits[$base] = $limit : NULL;
    }
    catch (\Throwable) { return NULL; }
  }

  /** @return array{status:int,body:string,error?:string,headers?:array<string,string>} */
  private function request(string $method, string $path, ?array $payload = NULL, array $headers = []): array {
    $url = $this->baseUrl() . $path;
    $headers[] = 'Authorization: Bearer ' . $this->token();
    if ($this->requester) return ($this->requester)($method, $url, $payload === NULL ? '' : json_encode($payload, JSON_THROW_ON_ERROR), $headers, $this->timeout());
    if (!function_exists('curl_init')) throw new \RuntimeException('The cURL extension is unavailable.');
    $handle = curl_init($url); if ($handle === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try {
      curl_setopt_array($handle, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout()), CURLOPT_TIMEOUT => $this->timeout(), CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
      if ($payload !== NULL) curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
      $body = curl_exec($handle); if ($body === FALSE) throw new \RuntimeException((string) curl_error($handle));
      return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => (string) $body];
    }
    finally { curl_close($handle); }
  }

  private function baseUrl(): string {
    $url = rtrim((string) ($this->connection['instance_url'] ?? ''), '/'); $host = parse_url($url, PHP_URL_HOST);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://') || !is_string($host) || !$this->safeHost($host)) throw new \InvalidArgumentException('Unsafe Mastodon instance URL.');
    return $url;
  }
  private function safeHost(string $host): bool {
    if (filter_var($host, FILTER_VALIDATE_IP)) return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE;
    if (strtolower($host) === 'localhost') return FALSE;
    // Test requesters never open a socket; permit DNS-free fixture hostnames.
    if ($this->requester) return TRUE;
    $addresses = gethostbynamel($host); if (!$addresses) return FALSE;
    foreach ($addresses as $address) if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return FALSE;
    return TRUE;
  }
  private function token(): string { $store = (string) ($this->connection['token_store_file'] ?? ''); $key = (string) ($this->connection['api_token_key'] ?? ''); if ($store === '' || $key === '') throw new \InvalidArgumentException(); return (new MattermostTokenStore($store))->get($key); }
  private function timeout(): int { return max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30))); }
  private function category(int $status): string { return match (TRUE) { $status === 401 || $status === 403 => 'authentication_error', $status === 429 => 'rate_limited', $status >= 500 => 'temporary_transport_error', default => 'configuration_error' }; }
  private function message(int $status): string { return match ($status) { 401, 403 => 'Mastodon rejected the access token or its publishing scope.', 429 => 'Mastodon is rate limiting this account.', default => 'Mastodon rejected the publication.' }; }
}
