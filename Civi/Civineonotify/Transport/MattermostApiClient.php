<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Reads channels available to the configured Mattermost API identity. */
final class MattermostApiClient {
  /** @param null|\Closure(string,array,int): array<int,array<string,mixed>> $getter */
  public function __construct(private readonly array $connection, private readonly ?\Closure $getter = NULL) {}

  /** @return array<string,string> Channel ID keyed, human-readable channel name valued. */
  public function channels(): array {
    $teams = $this->get('/users/me/teams');
    $channels = [];
    foreach ($teams as $team) {
      if (!is_array($team) || empty($team['id'])) continue;
      $teamName = trim((string) ($team['display_name'] ?? $team['name'] ?? ''));
      foreach ($this->get('/users/me/teams/' . rawurlencode((string) $team['id']) . '/channels') as $channel) {
        if (!is_array($channel) || empty($channel['id']) || !in_array($channel['type'] ?? '', ['O', 'P'], TRUE)) continue;
        $name = trim((string) ($channel['display_name'] ?? $channel['name'] ?? ''));
        if ($name !== '') $channels[(string) $channel['id']] = ($teamName !== '' ? $teamName . ' / ' : '') . $name;
      }
    }
    asort($channels, SORT_NATURAL | SORT_FLAG_CASE);
    return $channels;
  }

  /** @return array<int,array<string,mixed>> */
  private function get(string $path): array {
    if ($this->getter) return ($this->getter)($this->url($path), $this->headers(), $this->timeout());
    if (!function_exists('curl_init')) throw new \RuntimeException('The cURL extension is unavailable.');
    $handle = curl_init($this->url($path));
    if ($handle === FALSE) throw new \RuntimeException('Could not initialise HTTP client.');
    try {
      curl_setopt_array($handle, [CURLOPT_HTTPHEADER => $this->headers(), CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout()), CURLOPT_TIMEOUT => $this->timeout(), CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
      $body = curl_exec($handle);
      $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
      if ($body === FALSE) throw new \RuntimeException((string) curl_error($handle));
      if ($status < 200 || $status >= 300) throw new \RuntimeException('Mattermost API rejected channel discovery.');
      $decoded = json_decode((string) $body, TRUE, 512, JSON_THROW_ON_ERROR);
      if (!is_array($decoded)) throw new \RuntimeException('Mattermost API returned an invalid channel list.');
      return $decoded;
    }
    finally { curl_close($handle); }
  }

  private function url(string $path): string {
    $server = rtrim((string) ($this->connection['server_url'] ?? ''), '/');
    if (!filter_var($server, FILTER_VALIDATE_URL) || !str_starts_with($server, 'https://')) throw new \InvalidArgumentException('Mattermost server URL unavailable.');
    return $server . '/api/v4' . $path;
  }

  private function headers(): array { return ['Authorization: Bearer ' . $this->token()]; }
  private function token(): string {
    $store = (string) ($this->connection['token_store_file'] ?? ''); $key = (string) ($this->connection['api_token_key'] ?? '');
    if ($store === '' || $key === '') throw new \InvalidArgumentException('Mattermost token unavailable.');
    return (new MattermostTokenStore($store))->get($key);
  }
  private function timeout(): int { return max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30))); }
}
