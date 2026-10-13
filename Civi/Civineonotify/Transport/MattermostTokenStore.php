<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Stores Mattermost hook tokens outside CiviCRM in one protected JSON file. */
final class MattermostTokenStore {
  public function __construct(private readonly string $path) {}

  public function get(string $key): string {
    $tokens = $this->read();
    $token = $tokens[$key] ?? NULL;
    if (!is_string($token) || $token === '') throw new \RuntimeException('Mattermost token unavailable.');
    return $token;
  }

  public function suffix(string $key): string {
    try { return substr($this->get($key), -4); }
    catch (\Throwable) { return ''; }
  }

  public function put(string $key, string $token): void {
    if ($token === '' || preg_match('/\s/', $token)) throw new \InvalidArgumentException('Mattermost token is invalid.');
    $directory = dirname($this->path);
    if (!str_starts_with($this->path, '/') || !is_dir($directory) || !is_writable($directory)) throw new \RuntimeException('Mattermost token store is not writable.');
    $lock = fopen($this->path . '.lock', 'c');
    if ($lock === FALSE) throw new \RuntimeException('Mattermost token store lock is unavailable.');
    try {
      if (!flock($lock, LOCK_EX)) throw new \RuntimeException('Mattermost token store lock is unavailable.');
      $tokens = $this->read();
      $tokens[$key] = $token;
      $temporary = tempnam($directory, '.civineonotify-mattermost-');
      if ($temporary === FALSE) throw new \RuntimeException('Mattermost token store is not writable.');
      file_put_contents($temporary, json_encode($tokens, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
      chmod($temporary, 0600);
      if (!rename($temporary, $this->path)) { @unlink($temporary); throw new \RuntimeException('Mattermost token store could not be saved.'); }
    }
    finally { flock($lock, LOCK_UN); fclose($lock); }
  }

  private function read(): array {
    if (!is_file($this->path) || !is_readable($this->path)) return [];
    $contents = trim((string) file_get_contents($this->path));
    if ($contents === '') return [];
    $tokens = json_decode($contents, TRUE);
    if (!is_array($tokens)) throw new \RuntimeException('Mattermost token store is invalid.');
    return $tokens;
  }
}
