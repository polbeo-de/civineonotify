<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends a fixed, signed JSON envelope to a destination-specific HTTPS endpoint. */
final class GenericWebhookTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string, string, int, array<string,string>):array{status:int,error:string} $sender */
  public function __construct(private readonly array $connection, private readonly ?\Closure $sender = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $url = $this->endpoint((string) ($destination->config['endpoint_url'] ?? ''));
      $payload = json_encode(['version' => 1, 'transaction_id' => $transactionId, 'message' => ['body' => $message->body, 'format' => $message->format]], JSON_THROW_ON_ERROR);
      $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'Idempotency-Key' => $transactionId, 'X-CiviNeo-Transaction-Id' => $transactionId];
      $secret = $this->signingSecret($destination);
      if ($secret !== '') $headers['X-CiviNeo-Signature'] = 'sha256=' . hash_hmac('sha256', $payload, $secret);
      $response = $this->post($url, $payload, $this->timeout(), $headers);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Webhook endpoint rejected the notification.');
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The webhook notification payload could not be encoded.'); }
    catch (\RuntimeException $exception) {
      $unknown = str_contains(strtolower($exception->getMessage()), 'timed out');
      return new MatrixTransportResult(FALSE, $unknown ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $unknown ? 'Webhook endpoint timed out.' : 'Webhook endpoint could not be reached.', $unknown);
    }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Webhook configuration is not usable.'); }
  }

  public function testConnection(array $connection): MatrixTransportResult {
    try { $this->storePath(); return new MatrixTransportResult(TRUE, 'success'); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Webhook configuration is not usable.'); }
  }

  public function capabilities(): array { return ['transport' => 'generic_webhook', 'destination_endpoint' => TRUE, 'hmac_sha256' => TRUE]; }

  /** @return array{status:int,error:string} */
  private function post(string $url, string $payload, int $timeout, array $headers): array {
    if ($this->sender) return ($this->sender)($url, $payload, $timeout, $headers);
    if (!function_exists('curl_init')) throw new \RuntimeException('The cURL extension is unavailable.');
    $handle = curl_init($url);
    if ($handle === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try {
      $headerLines = array_map(static fn(string $name, string $value): string => $name . ': ' . $value, array_keys($headers), $headers);
      curl_setopt_array($handle, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => $headerLines, CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $timeout), CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
      if (curl_exec($handle) === FALSE) throw new \RuntimeException((string) curl_error($handle));
      return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'error' => ''];
    }
    finally { curl_close($handle); }
  }

  private function endpoint(string $url): string {
    $parts = parse_url($url);
    $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass']) || $this->isPrivateHost($host)) throw new \InvalidArgumentException('Webhook endpoint must be a public HTTPS URL.');
    return $url;
  }

  private function isPrivateHost(string $host): bool {
    if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) return TRUE;
    if (filter_var($host, FILTER_VALIDATE_IP) !== FALSE) return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === FALSE;
    $records = @dns_get_record($host, DNS_A | DNS_AAAA);
    if (!is_array($records) || $records === []) return TRUE;
    foreach ($records as $record) {
      $address = (string) ($record['ip'] ?? $record['ipv6'] ?? '');
      if ($address === '' || filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === FALSE) return TRUE;
    }
    return FALSE;
  }

  private function signingSecret(NotificationDestination $destination): string {
    $key = (string) ($destination->config['signing_key'] ?? '');
    return $key === '' ? '' : (new MattermostTokenStore($this->storePath()))->get($key);
  }

  private function storePath(): string {
    $path = (string) ($this->connection['token_store_file'] ?? '');
    if ($path === '' || !str_starts_with($path, '/') || !is_dir(dirname($path)) || !is_writable(dirname($path))) throw new \InvalidArgumentException('Webhook token store is not writable.');
    return $path;
  }

  private function timeout(): int { return max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30))); }

  private function category(int $status): string { return match (TRUE) { $status === 401 || $status === 403 => 'authentication_error', $status === 404 => 'configuration_error', $status === 408 || $status === 429 || $status >= 500 => 'temporary_transport_error', default => 'configuration_error' }; }
}
