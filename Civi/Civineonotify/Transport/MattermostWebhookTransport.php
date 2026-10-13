<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends plain CiviNeo Notify messages to a Mattermost incoming webhook. */
final class MattermostWebhookTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string, string, int): array{status:int, error:string} $sender */
  public function __construct(private readonly array $connection, private readonly ?\Closure $sender = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $url = $this->webhookUrl($this->connection);
      $payload = ['text' => $message->body];
      $channel = trim((string) ($destination->config['channel'] ?? ''));
      if ($channel !== '') $payload['channel'] = $channel;
      $response = $this->post($url, json_encode($payload, JSON_THROW_ON_ERROR), $this->timeout($this->connection));
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Mattermost webhook rejected the notification.');
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The Mattermost notification payload could not be encoded.'); }
    catch (\RuntimeException $exception) {
      $unknown = str_contains(strtolower($exception->getMessage()), 'timed out');
      return new MatrixTransportResult(FALSE, $unknown ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $unknown ? 'Mattermost webhook timed out.' : 'Mattermost webhook could not be reached.', $unknown);
    }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mattermost webhook configuration is not usable.'); }
  }

  public function testConnection(array $connection): MatrixTransportResult {
    try { $this->webhookUrl($connection); return new MatrixTransportResult(TRUE, 'success'); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mattermost webhook configuration is not usable.'); }
  }

  public function capabilities(): array { return ['transport' => 'mattermost_webhook', 'incoming_webhook' => TRUE, 'api_planned' => TRUE]; }

  private function post(string $url, string $payload, int $timeout): array {
    if ($this->sender) return ($this->sender)($url, $payload, $timeout);
    if (!function_exists('curl_init')) throw new \RuntimeException('The cURL extension is unavailable.');
    $handle = curl_init($url);
    if ($handle === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try {
      curl_setopt_array($handle, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $timeout), CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
      if (curl_exec($handle) === FALSE) throw new \RuntimeException((string) curl_error($handle));
      return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'error' => ''];
    }
    finally { curl_close($handle); }
  }

  private function webhookUrl(array $connection): string {
    $path = (string) ($connection['webhook_secret_file'] ?? '');
    if ($path === '' || !str_starts_with($path, '/') || !is_file($path) || !is_readable($path)) throw new \InvalidArgumentException('Secret file unavailable.');
    $contents = trim((string) file_get_contents($path));
    if (preg_match('/^(?:export\s+)?(?:MATTERMOST_WEBHOOK_URL|WEBHOOK_URL)\s*=\s*["\']?([^"\'\s]+)["\']?\s*$/m', $contents, $match)) $contents = $match[1];
    if (!filter_var($contents, FILTER_VALIDATE_URL) || !str_starts_with($contents, 'https://')) throw new \InvalidArgumentException('Webhook URL unavailable.');
    return $contents;
  }

  private function timeout(array $connection): int { return max(1, min(300, (int) ($connection['timeout_seconds'] ?? 30))); }

  private function category(int $status): string { return match (TRUE) { $status === 401 || $status === 403 => 'authentication_error', $status === 404 => 'configuration_error', $status === 429 => 'rate_limited', $status >= 500 => 'temporary_transport_error', default => 'configuration_error' }; }
}
