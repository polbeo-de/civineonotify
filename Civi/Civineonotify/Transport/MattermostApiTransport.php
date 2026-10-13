<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends messages through the Mattermost v4 posts API. */
final class MattermostApiTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string,string,int,array): array{status:int,error:string} $sender */
  public function __construct(private readonly array $connection, private readonly ?\Closure $sender = NULL) {}
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $channel = trim((string) ($destination->config['channel'] ?? ''));
      if ($channel === '') throw new \InvalidArgumentException('Mattermost channel unavailable.');
      $payload = json_encode(['channel_id' => $channel, 'message' => $message->body], JSON_THROW_ON_ERROR);
      $response = $this->post($this->url(), $payload, $this->timeout(), ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token()]);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Mattermost API rejected the notification.');
    } catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The Mattermost notification payload could not be encoded.'); }
    catch (\RuntimeException $exception) { $unknown=str_contains(strtolower($exception->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $unknown ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $unknown ? 'Mattermost API timed out.' : 'Mattermost API could not be reached.', $unknown); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mattermost API configuration is not usable.'); }
  }
  public function testConnection(array $connection): MatrixTransportResult { try { $this->url(); $this->token(); return new MatrixTransportResult(TRUE, 'success'); } catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Mattermost API configuration is not usable.'); } }
  public function capabilities(): array { return ['transport' => 'mattermost_api', 'posts_api' => TRUE]; }
  private function url(): string { $server=rtrim((string)($this->connection['server_url']??''), '/'); if (!filter_var($server,FILTER_VALIDATE_URL)||!str_starts_with($server,'https://')) throw new \InvalidArgumentException(); return $server . '/api/v4/posts'; }
  private function token(): string { $store=(string)($this->connection['token_store_file']??''); $key=(string)($this->connection['api_token_key']??''); if ($store===''||$key==='') throw new \InvalidArgumentException(); return (new MattermostTokenStore($store))->get($key); }
  private function timeout(): int { return max(1,min(300,(int)($this->connection['timeout_seconds']??30))); }
  private function post(string $url,string $payload,int $timeout,array $headers): array { if($this->sender)return ($this->sender)($url,$payload,$timeout,$headers); if(!function_exists('curl_init'))throw new \RuntimeException('The cURL extension is unavailable.'); $h=curl_init($url); if($h===FALSE)throw new \RuntimeException('Could not initialise HTTP client.'); try { curl_setopt_array($h,[CURLOPT_POST=>TRUE,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_CONNECTTIMEOUT=>min(10,$timeout),CURLOPT_TIMEOUT=>$timeout,CURLOPT_FOLLOWLOCATION=>FALSE,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]); if(curl_exec($h)===FALSE)throw new \RuntimeException((string)curl_error($h)); return ['status'=>(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE),'error'=>'']; } finally { curl_close($h); } }
  private function category(int $status): string { return match(TRUE){$status===401||$status===403=>'authentication_error',$status===404=>'configuration_error',$status===429=>'rate_limited',$status>=500=>'temporary_transport_error',default=>'configuration_error'}; }
}
