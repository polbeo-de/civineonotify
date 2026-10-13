<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

use Civi\Civineonotify\Security\Redactor;
use Symfony\Component\Process\Process;

/** Adapter for the bundled Matrix-Nio client helper and a protected Nio store. */
final class MatrixNioTransport implements NotificationTransportInterface {
  public function __construct(private readonly array $connection, private readonly Redactor $redactor = new Redactor()) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    $roomId = (string) ($destination->config['room_id'] ?? '');
    if ($roomId === '') return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Matrix-Nio requires an internal room ID.');
    return $this->withStoreLock(fn() => $this->run($this->arguments('send', $roomId), $message->body));
  }

  public function testConnection(array $connection): MatrixTransportResult {
    return $this->withStoreLock(fn() => $this->run($this->arguments('test')));
  }

  public function capabilities(): array {
    return ['transport' => 'matrix_nio', 'stdin_message' => TRUE, 'json_output' => TRUE, 'transaction_id' => FALSE, 'requires_explicit_unverified_recipient_opt_in' => TRUE];
  }

  private function arguments(string $action, ?string $roomId = NULL): array {
    $python = $this->executable($this->connection['python_path'] ?? '');
    // ``nio_client_path`` is the persisted UI field. Accept the former key as
    // well so that configurations created by earlier development builds keep
    // working after upgrade.
    $client = $this->readable($this->connection['nio_client_path'] ?? $this->connection['client_path'] ?? '');
    $session = $this->readable($this->connection['session_path'] ?? '');
    $store = $this->directory($this->connection['store_path'] ?? '');
    $homeserver = (string) ($this->connection['homeserver'] ?? '');
    if (!preg_match('#^https://[^\s]+$#', $homeserver)) throw new \InvalidArgumentException('Configured Matrix homeserver is not usable.');
    $timeout = max(1, min(300, (int) ($this->connection['timeout_seconds'] ?? 30)));
    $arguments = [$python, $client, $action, '--homeserver', rtrim($homeserver, '/'), '--session-file', $session, '--store-path', $store, '--timeout', (string) $timeout];
    if ($roomId !== NULL) {
      $arguments = array_merge($arguments, ['--room-id', $roomId]);
      if (!empty($this->connection['allow_unverified_recipient_devices'])) $arguments[] = '--allow-unverified-devices';
    }
    return $arguments;
  }

  private function run(array $arguments, ?string $input = NULL): MatrixTransportResult {
    try {
      $process = new Process($arguments, NULL, ['HOME' => '/nonexistent', 'PATH' => '/usr/bin:/bin'], $input, 35);
      $process->run();
      $payload = json_decode($process->getOutput(), TRUE);
      if (!is_array($payload)) return new MatrixTransportResult(FALSE, 'configuration_error', NULL, $process->getExitCode(), 'Matrix-Nio returned no usable result.');
      if (!empty($payload['success'])) return new MatrixTransportResult(TRUE, 'success', $payload['event_id'] ?? NULL, 0);
      return new MatrixTransportResult(FALSE, (string) ($payload['category'] ?? 'connection_error'), NULL, $process->getExitCode(), 'Matrix-Nio operation failed.');
    }
    catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) { return new MatrixTransportResult(FALSE, 'unknown_delivery_state', NULL, NULL, 'Matrix-Nio timed out.', TRUE); }
    catch (\Throwable $exception) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, $this->redactor->redact($exception->getMessage())); }
  }

  private function withStoreLock(callable $operation): MatrixTransportResult {
    try {
      $lock = @fopen($this->directory($this->connection['store_path'] ?? '') . '/.civineonotify.lock', 'c');
      if ($lock === FALSE) throw new \RuntimeException('Configured Matrix-Nio store lock is not usable.');
      try {
        if (!flock($lock, LOCK_EX | LOCK_NB)) return new MatrixTransportResult(FALSE, 'store_locked', NULL, NULL, 'Matrix encryption store is busy.');
        return $operation();
      }
      finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    catch (\Throwable $exception) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, $this->redactor->redact($exception->getMessage())); }
  }

  private function executable(string $path): string { if ($path === '' || !is_file($path) || !is_executable($path)) throw new \InvalidArgumentException('Configured Matrix-Nio Python executable is not usable.'); return $path; }
  private function readable(string $path): string { if ($path === '' || !is_file($path) || !is_readable($path)) throw new \InvalidArgumentException('Configured Matrix-Nio path is not usable.'); return $path; }
  private function directory(string $path): string { if ($path === '' || !is_dir($path) || !is_readable($path) || !is_writable($path)) throw new \InvalidArgumentException('Configured Matrix-Nio store is not usable.'); return $path; }
}
