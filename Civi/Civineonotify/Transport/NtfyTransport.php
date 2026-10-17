<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends outbound notifications to an ntfy topic. */
final class NtfyTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string,string,array<string,string>,int):array{status:int,body:string,error?:string} $requester */
  public function __construct(private readonly array $connection, private readonly ?\Closure $requester = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $topic = trim((string) ($destination->config['topic'] ?? ''));
      if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $topic) || $message->body === '') throw new \InvalidArgumentException();
      $response = $this->request($this->serverUrl() . '/' . rawurlencode($topic), $message->body, ['Title' => 'CiviNeo Notify', 'X-Id' => $transactionId, 'Content-Type' => 'text/plain; charset=utf-8']);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'ntfy rejected the notification.');
    }
    catch (\RuntimeException $exception) { $timeout = str_contains(strtolower($exception->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $timeout ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $timeout ? 'ntfy timed out; the delivery outcome is unknown.' : 'ntfy could not be reached.', $timeout); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'ntfy configuration is not usable.'); }
  }

  public function testConnection(array $connection): MatrixTransportResult { try { $this->serverUrl(); $this->token(); return new MatrixTransportResult(TRUE, 'success'); } catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'ntfy configuration is not usable.'); } }
  public function capabilities(): array { return ['transport' => 'ntfy', 'destination_topic' => TRUE, 'plain_text' => TRUE]; }

  /** @return array{status:int,body:string,error?:string} */
  private function request(string $url, string $body, array $headers): array {
    $token = $this->token(); if ($token !== '') $headers['Authorization'] = 'Bearer ' . $token;
    if ($this->requester) return ($this->requester)($url, $body, $headers, $this->timeout());
    if (!function_exists('curl_init')) throw new \RuntimeException('The cURL extension is unavailable.');
    $handle = curl_init($url); if ($handle === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try { $lines = array_map(static fn(string $name, string $value): string => $name . ': ' . $value, array_keys($headers), $headers); curl_setopt_array($handle, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $lines, CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout()), CURLOPT_TIMEOUT => $this->timeout(), CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]); $result = curl_exec($handle); if ($result === FALSE) throw new \RuntimeException((string) curl_error($handle)); return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => (string) $result]; } finally { curl_close($handle); }
  }

  private function serverUrl(): string { $url = rtrim((string) ($this->connection['server_url'] ?? ''), '/'); if (!preg_match('#^https://[^\s]+$#', $url)) throw new \InvalidArgumentException(); return $url; }
  private function token(): string { $key = (string) ($this->connection['api_token_key'] ?? ''); if ($key === '') return ''; $store = (string) ($this->connection['token_store_file'] ?? ''); if ($store === '') throw new \InvalidArgumentException(); return (new MattermostTokenStore($store))->get($key); }
  private function timeout(): int { return max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30))); }
  private function category(int $status): string { return match (TRUE) { $status === 401 || $status === 403 => 'authentication_error', $status === 429 || $status >= 500 => 'temporary_transport_error', default => 'configuration_error' }; }
}
