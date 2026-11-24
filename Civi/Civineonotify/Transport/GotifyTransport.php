<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends outbound notifications to a Gotify application endpoint. */
final class GotifyTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string,string,int):array{status:int,body:string,error?:string} $requester */
  public function __construct(private readonly array $connection, private readonly ?\Closure $requester = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      if ($message->body === '') throw new \InvalidArgumentException();
      $token = $this->applicationToken($destination);
      $extras = ['client::transaction_id' => $transactionId];
      $imageUrl = trim((string) ($this->connection['image_url'] ?? ''));
      if ($imageUrl !== '') {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL) || !str_starts_with($imageUrl, 'https://')) throw new \InvalidArgumentException();
        $extras['client::notification'] = ['bigImageUrl' => $imageUrl];
      }
      $payload = json_encode(['title' => 'CiviNeo Notify', 'message' => $message->body, 'priority' => 5, 'extras' => $extras], JSON_THROW_ON_ERROR);
      $response = $this->request($this->serverUrl() . '/message?token=' . rawurlencode($token), $payload);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Gotify rejected the notification.');
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The Gotify notification payload could not be encoded.'); }
    catch (\RuntimeException $exception) { $timeout = str_contains(strtolower($exception->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $timeout ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $timeout ? 'Gotify timed out; the delivery outcome is unknown.' : 'Gotify could not be reached.', $timeout); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Gotify configuration is not usable.'); }
  }

  public function testConnection(array $connection): MatrixTransportResult { try { $this->serverUrl(); return new MatrixTransportResult(TRUE, 'success'); } catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Gotify configuration is not usable.'); } }
  public function capabilities(): array { return ['transport' => 'gotify', 'destination_application' => TRUE, 'plain_text' => TRUE]; }

  /** @return array{status:int,body:string,error?:string} */
  private function request(string $url, string $payload): array {
    if ($this->requester) return ($this->requester)($url, $payload, $this->timeout());
    if (!function_exists('curl_init')) throw new \RuntimeException('The cURL extension is unavailable.');
    $handle = curl_init($url); if ($handle === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try { curl_setopt_array($handle, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout()), CURLOPT_TIMEOUT => $this->timeout(), CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]); $result = curl_exec($handle); if ($result === FALSE) throw new \RuntimeException((string) curl_error($handle)); return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => (string) $result]; } finally { curl_close($handle); }
  }

  private function serverUrl(): string { $url = rtrim((string) ($this->connection['server_url'] ?? ''), '/'); if (!preg_match('#^https://[^\s]+$#', $url)) throw new \InvalidArgumentException(); return $url; }
  private function applicationToken(NotificationDestination $destination): string { $key = (string) ($destination->config['application_token_key'] ?? ''); $store = (string) ($this->connection['token_store_file'] ?? ''); if ($key === '' || $store === '') throw new \InvalidArgumentException(); return (new MattermostTokenStore($store))->get($key); }
  private function timeout(): int { return max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30))); }
  private function category(int $status): string { return match (TRUE) { $status === 401 || $status === 403 => 'authentication_error', $status === 429 || $status >= 500 => 'temporary_transport_error', default => 'configuration_error' }; }
}
