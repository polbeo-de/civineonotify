<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends outbound text notifications through the official Telegram Bot API. */
final class TelegramTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string,string,array,int):array{status:int,body:string,error?:string} $requester */
  public function __construct(private readonly array $connection, private readonly ?\Closure $requester = NULL) {}
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $payload = $this->payload($message->body, $destination->config); $response = $this->request('sendMessage', $payload);
      $body = json_decode($response['body'], TRUE, 512, JSON_THROW_ON_ERROR);
      if (($response['status'] ?? 0) === 200 && is_array($body) && !empty($body['ok']) && !empty($body['result']['message_id'])) return new MatrixTransportResult(TRUE, 'success', (string) $body['result']['message_id'], 200);
      return new MatrixTransportResult(FALSE, $this->category((int) ($response['status'] ?? 0), is_array($body) ? $body : []), NULL, (int) ($response['status'] ?? 0), $this->message(is_array($body) ? $body : []));
    } catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Telegram returned an invalid response.'); }
    catch (\RuntimeException $e) { $timeout = str_contains(strtolower($e->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $timeout ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $timeout ? 'Telegram timed out; the delivery outcome is unknown.' : 'Telegram could not be reached.', $timeout); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Telegram configuration is not usable.'); }
  }
  public function testConnection(array $connection): MatrixTransportResult { try { $response = $this->request('getMe', []); $body = json_decode($response['body'], TRUE, 512, JSON_THROW_ON_ERROR); return (($response['status'] ?? 0) === 200 && !empty($body['ok'])) ? new MatrixTransportResult(TRUE, 'success') : new MatrixTransportResult(FALSE, 'authentication_error', NULL, (int) $response['status'], 'Telegram rejected the bot token.'); } catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Telegram configuration is not usable.'); } }
  public function capabilities(): array { return ['transport' => 'telegram', 'plain_text' => TRUE, 'html' => TRUE, 'dry_run' => TRUE]; }
  /** @return array{valid:bool,payload:array<string,mixed>,message:?string} */
  public function dryRun(string $body, array $destination = []): array { try { return ['valid' => TRUE, 'payload' => $this->payload($body, $destination), 'message' => NULL]; } catch (\Throwable) { return ['valid' => FALSE, 'payload' => [], 'message' => 'Telegram configuration is not usable.']; } }
  /** @return array<string,mixed> */
  private function payload(string $body, array $destination): array {
    $chat = trim((string) ($destination['chat_id'] ?? '')); if (!preg_match('/^-[0-9]{1,20}$/', $chat) || $body === '' || mb_strlen($body) > 4096) throw new \InvalidArgumentException();
    $format = (string) ($this->connection['parse_mode'] ?? ''); if (!in_array($format, ['', 'HTML'], TRUE)) throw new \InvalidArgumentException();
    $payload = ['chat_id' => $chat, 'text' => $body, 'disable_web_page_preview' => !empty($this->connection['disable_link_preview']), 'disable_notification' => !empty($this->connection['disable_notification']), 'protect_content' => !empty($this->connection['protect_content'])];
    if ($format !== '') $payload['parse_mode'] = $format;
    return $payload;
  }
  /** @return array{status:int,body:string,error?:string} */
  private function request(string $method, array $payload): array {
    $url = 'https://api.telegram.org/bot' . rawurlencode($this->token()) . '/' . $method;
    if ($this->requester) return ($this->requester)('POST', $url, $payload, $this->timeout());
    $h = curl_init($url); if ($h === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try { curl_setopt_array($h, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout()), CURLOPT_TIMEOUT => $this->timeout(), CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]); $body = curl_exec($h); if ($body === FALSE) throw new \RuntimeException((string) curl_error($h)); return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => (string) $body]; } finally { curl_close($h); }
  }
  private function token(): string { $store=(string)($this->connection['token_store_file']??''); $key=(string)($this->connection['api_token_key']??''); if ($store===''||$key==='') throw new \InvalidArgumentException(); return (new MattermostTokenStore($store))->get($key); }
  private function timeout(): int { return max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30))); }
  private function category(int $status, array $body): string { return match (TRUE) { $status === 401 || $status === 403 => 'authentication_error', $status === 429 => 'rate_limited', $status >= 500 => 'temporary_transport_error', default => 'configuration_error' }; }
  private function message(array $body): string { return !empty($body['error_code']) && (int)$body['error_code'] === 429 ? 'Telegram is rate limiting this bot.' : 'Telegram rejected the message.'; }
}
