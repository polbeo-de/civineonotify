<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Publishes public text posts through one AT Protocol account. */
final class BlueskyTransport implements NotificationTransportInterface {
  /** @param null|\Closure(string,string,?array,array,int):array{status:int,body:string,headers?:array<string,string>} $requester */
  public function __construct(private readonly array $connection, private readonly ?\Closure $requester = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $prepared = $this->prepare($message->body, $transactionId);
      $session = $this->session();
      $response = $this->publish($session, $prepared);
      if ($response['status'] === 401) { $session = $this->refresh($session); $response = $this->publish($session, $prepared); }
      $body = $this->json($response);
      if ($response['status'] >= 200 && $response['status'] < 300 && !empty($body['uri']) && !empty($body['cid'])) return new MatrixTransportResult(TRUE, 'success', (string) $body['uri'], $response['status'], $this->webUrl((string) $session['did'], (string) $body['uri']));
      if ($response['status'] === 409 && $this->sameRecord($session, $prepared)) { $uri = 'at://' . $session['did'] . '/app.bsky.feed.post/' . $prepared['rkey']; return new MatrixTransportResult(TRUE, 'success', $uri, 409, $this->webUrl((string) $session['did'], $uri)); }
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], $this->errorMessage($response['status']));
    }
    catch (\RuntimeException $e) { $timeout = str_contains(strtolower($e->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $timeout ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $timeout ? 'Bluesky timed out; the publication outcome is unknown.' : 'Bluesky could not be reached.', $timeout); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Bluesky configuration is not usable.'); }
  }

  public function testConnection(array $connection): MatrixTransportResult { try { $this->session(); return new MatrixTransportResult(TRUE, 'success'); } catch (\Throwable) { return new MatrixTransportResult(FALSE, 'authentication_error', NULL, NULL, 'Bluesky rejected the account handle or app password.'); } }
  public function capabilities(): array { return ['transport' => 'bluesky', 'public_posts' => TRUE, 'link_facets' => TRUE, 'dry_run' => TRUE]; }
  /** @return array{valid:bool,payload:array<string,mixed>,message:?string} */
  public function dryRun(string $body, string $transactionId = 'dry-run'): array { try { return ['valid' => TRUE, 'payload' => $this->prepare($body, $transactionId)['record'], 'message' => NULL]; } catch (\Throwable) { return ['valid' => FALSE, 'payload' => [], 'message' => 'Bluesky configuration or post content is not usable.']; } }

  /** @return array{record:array<string,mixed>,rkey:string} */
  private function prepare(string $text, string $transactionId): array {
    if ($text === '' || $this->graphemes($text) > 300) throw new \InvalidArgumentException();
    $language = trim((string) ($this->connection['language'] ?? ''));
    if ($language !== '' && !preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/', $language)) throw new \InvalidArgumentException();
    $record = ['$type' => 'app.bsky.feed.post', 'text' => $text, 'createdAt' => gmdate('c')];
    if ($language !== '') $record['langs'] = [$language];
    if (!empty($this->connection['detect_links'])) { $facets = $this->facets($text); if ($facets) $record['facets'] = $facets; }
    $rkey = $this->rkey($transactionId);
    return ['record' => $record, 'rkey' => $rkey];
  }

  /** @return array<string,mixed> */
  private function session(): array {
    $pds = $this->pds();
    $body = $this->json($this->request('POST', $pds . '/xrpc/com.atproto.server.createSession', ['identifier' => $this->handle(), 'password' => $this->password()]));
    if (empty($body['accessJwt']) || empty($body['refreshJwt']) || empty($body['did'])) throw new \RuntimeException('Invalid session.');
    $body['pds'] = $pds; return $body;
  }
  /** @return array<string,mixed> */
  private function refresh(array $session): array {
    $body = $this->json($this->request('POST', (string) $session['pds'] . '/xrpc/com.atproto.server.refreshSession', NULL, ['Authorization: Bearer ' . $session['refreshJwt']]));
    if (empty($body['accessJwt']) || empty($body['refreshJwt']) || empty($body['did'])) throw new \RuntimeException('Invalid refresh.');
    $body['pds'] = $session['pds']; return $body;
  }
  /** @return array{status:int,body:string,headers?:array<string,string>} */
  private function publish(array $session, array $prepared): array { return $this->request('POST', (string) $session['pds'] . '/xrpc/com.atproto.repo.createRecord', ['repo' => $session['did'], 'collection' => 'app.bsky.feed.post', 'rkey' => $prepared['rkey'], 'record' => $prepared['record']], ['Authorization: Bearer ' . $session['accessJwt']]); }
  private function sameRecord(array $session, array $prepared): bool { try { $response = $this->request('GET', (string) $session['pds'] . '/xrpc/com.atproto.repo.getRecord?repo=' . rawurlencode((string) $session['did']) . '&collection=app.bsky.feed.post&rkey=' . rawurlencode($prepared['rkey']), NULL, ['Authorization: Bearer ' . $session['accessJwt']]); $body = $this->json($response); $uri = 'at://' . $session['did'] . '/app.bsky.feed.post/' . $prepared['rkey']; return $response['status'] === 200 && (($body['uri'] ?? '') === $uri) && (($body['value']['text'] ?? '') === ($prepared['record']['text'] ?? '')); } catch (\Throwable) { return FALSE; } }
  private function pds(): string { $did = $this->did(); $url = str_starts_with($did, 'did:plc:') ? 'https://plc.directory/' . rawurlencode($did) : 'https://' . substr($did, 8) . '/.well-known/did.json'; $doc = $this->json($this->request('GET', $url)); if (($doc['id'] ?? '') !== $did) throw new \InvalidArgumentException(); foreach (($doc['service'] ?? []) as $service) if (($service['type'] ?? '') === 'AtprotoPersonalDataServer' && str_ends_with((string) ($service['id'] ?? ''), '#atproto_pds')) { $endpoint = rtrim((string) ($service['serviceEndpoint'] ?? ''), '/'); if ($this->safeUrl($endpoint)) return $endpoint; } throw new \InvalidArgumentException(); }
  private function did(): string { $body = $this->json($this->request('GET', 'https://public.api.bsky.app/xrpc/com.atproto.identity.resolveHandle?handle=' . rawurlencode($this->handle()))); $did = (string) ($body['did'] ?? ''); if (!preg_match('/^did:(plc|web):[A-Za-z0-9._:%-]+$/', $did)) throw new \InvalidArgumentException(); return $did; }
  private function handle(): string { $value = strtolower(ltrim(trim((string) ($this->connection['handle'] ?? '')), '@')); if (!preg_match('/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/', $value) || !str_contains($value, '.')) throw new \InvalidArgumentException(); return $value; }
  private function password(): string { $store = (string) ($this->connection['token_store_file'] ?? ''); $key = (string) ($this->connection['api_token_key'] ?? ''); if ($store === '' || $key === '') throw new \InvalidArgumentException(); return (new MattermostTokenStore($store))->get($key); }
  private function safeUrl(string $url): bool { $host = parse_url($url, PHP_URL_HOST); $path = parse_url($url, PHP_URL_PATH); if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://') || !is_string($host) || !in_array($path, [NULL, ''], TRUE) || parse_url($url, PHP_URL_QUERY) !== NULL || parse_url($url, PHP_URL_USER) !== NULL) return FALSE; if ($this->requester) return TRUE; $addresses = gethostbynamel($host); if (!$addresses) return FALSE; foreach ($addresses as $address) if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return FALSE; return TRUE; }
  /** @return list<array<string,mixed>> */
  private function facets(string $text): array { preg_match_all('~https?://[^\s<>()]+~u', $text, $matches, PREG_OFFSET_CAPTURE); $result=[]; foreach ($matches[0] as [$url,$start]) { $url=rtrim($url, '.,;:!?)]}'); if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http','https'], TRUE)) continue; $result[]=['index'=>['byteStart'=>$start,'byteEnd'=>$start+strlen($url)],'features'=>[['$type'=>'app.bsky.richtext.facet#link','uri'=>$url]]]; } return $result; }
  private function graphemes(string $text): int { if (function_exists('grapheme_strlen')) return (int) grapheme_strlen($text); return preg_match_all('/\X/u', $text); }
  private function rkey(string $transactionId): string { $alphabet='234567abcdefghijklmnopqrstuvwxyz'; $hash=hash('sha256', $transactionId, TRUE); $out=''; for ($i=0;$i<13;$i++) $out.=$alphabet[ord($hash[$i]) & 31]; return $out; }
  private function webUrl(string $did, string $uri): string { return 'https://bsky.app/profile/' . rawurlencode($did) . '/post/' . rawurlencode((string) basename($uri)); }
  /** @return array<string,mixed> */
  private function json(array $response): array { $body=json_decode((string)($response['body'] ?? ''), TRUE, 512, JSON_THROW_ON_ERROR); if (!is_array($body)) throw new \RuntimeException('Invalid response.'); return $body; }
  /** @return array{status:int,body:string,headers?:array<string,string>} */
  private function request(string $method, string $url, ?array $payload = NULL, array $headers = []): array { if ($this->requester) return ($this->requester)($method, $url, $payload, $headers, $this->timeout()); if (!function_exists('curl_init')) throw new \RuntimeException('HTTP client unavailable.'); $h=curl_init($url); if ($h===FALSE) throw new \RuntimeException('HTTP client unavailable.'); try { $headers[]='Content-Type: application/json'; curl_setopt_array($h,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_CONNECTTIMEOUT=>min(10,$this->timeout()),CURLOPT_TIMEOUT=>$this->timeout(),CURLOPT_FOLLOWLOCATION=>FALSE,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]); if ($payload!==NULL) curl_setopt($h,CURLOPT_POSTFIELDS,json_encode($payload, JSON_THROW_ON_ERROR)); $body=curl_exec($h); if ($body===FALSE) throw new \RuntimeException((string)curl_error($h)); return ['status'=>(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE),'body'=>(string)$body]; } finally { curl_close($h); } }
  private function timeout(): int { return max(1,min(300,(int)($this->connection['timeout_seconds']??30))); }
  private function category(int $status): string { return match(TRUE) { $status===401||$status===403=>'authentication_error',$status===429=>'rate_limited',$status>=500=>'temporary_transport_error',default=>'configuration_error' }; }
  private function errorMessage(int $status): string { return $status===429 ? 'Bluesky is rate limiting this account.' : 'Bluesky rejected the publication.'; }
}
