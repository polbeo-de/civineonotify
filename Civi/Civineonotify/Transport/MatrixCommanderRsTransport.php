<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

use Civi\Civineonotify\Security\Redactor;
use Symfony\Component\Process\Process;

/** Safe adapter for the verified matrix-commander-rs 0.11.0 command line. */
final class MatrixCommanderRsTransport implements NotificationTransportInterface {
  public function __construct(private readonly array $connection, private readonly Redactor $redactor = new Redactor(), private readonly ?ErrorClassifier $classifier = NULL) {}

  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try { return $this->withStoreLock($this->connection, fn() => $this->run($this->arguments($destination), $message->body)); }
    catch (\Throwable $exception) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, $this->redactor->redact($exception->getMessage())); }
  }

  public function testConnection(array $connection): MatrixTransportResult {
    try { return $this->withStoreLock($connection, fn() => $this->run($this->baseArguments($connection, ['--whoami', '--output', 'json']))); }
    catch (\Throwable $exception) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, $this->redactor->redact($exception->getMessage())); }
  }

  public function capabilities(): array {
    return ['transport' => 'matrix_commander_rs', 'stdin_message' => TRUE, 'json_output' => TRUE, 'transaction_id' => FALSE];
  }

  private function arguments(NotificationDestination $destination): array {
    $room = $destination->config['room_id'] ?? $destination->config['room_alias'] ?? NULL;
    if (!$room) throw new \InvalidArgumentException('Destination has no Matrix room.');
    return $this->baseArguments($this->connection, ['--message', '-', '--room', $room, '--output', 'json']);
  }

  private function baseArguments(array $connection, array $action): array {
    $binary = $this->path($connection['binary_path'] ?? '', TRUE);
    $credentials = $this->path($connection['credentials_path'] ?? '', FALSE);
    $store = $this->directory($connection['store_path'] ?? '');
    $arguments = [$binary, '--credentials', $credentials, '--store', $store];
    $timeout = max(1, min(300, (int) ($connection['timeout_seconds'] ?? 30)));
    return array_merge($arguments, ['--timeout', (string) $timeout], $action);
  }

  private function run(array $arguments, ?string $input = NULL): MatrixTransportResult {
    try {
      $process = new Process($arguments, NULL, ['HOME' => '/nonexistent', 'PATH' => '/usr/bin:/bin'], $input, 35);
      $process->run();
      if (!$process->isSuccessful()) {
        $error = $this->redactor->redact($process->getErrorOutput());
        return new MatrixTransportResult(FALSE, ($this->classifier ?? new ErrorClassifier())->classify($process->getExitCode(), $error), NULL, $process->getExitCode(), $error);
      }
      $decoded = json_decode($process->getOutput(), TRUE);
      return new MatrixTransportResult(TRUE, 'success', is_array($decoded) ? ($decoded['event_id'] ?? NULL) : NULL, 0);
    }
    catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) { return new MatrixTransportResult(FALSE, 'unknown_delivery_state', NULL, NULL, 'Matrix command timed out.', TRUE); }
    catch (\Throwable $exception) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, $this->redactor->redact($exception->getMessage())); }
  }

  /**
   * matrix-commander-rs mutates its persistent E2EE store. A non-blocking lock
   * turns a concurrent worker/test into a retryable delivery instead of risking
   * store corruption or an ambiguous send.
   */
  private function withStoreLock(array $connection, callable $operation): MatrixTransportResult {
    $store = $this->directory($connection['store_path'] ?? '');
    $lock = @fopen($store . '/.civineonotify.lock', 'c');
    if ($lock === FALSE) throw new \RuntimeException('Configured Matrix store lock is not usable.');
    try {
      if (!flock($lock, LOCK_EX | LOCK_NB)) return new MatrixTransportResult(FALSE, 'store_locked', NULL, NULL, 'Matrix encryption store is busy.');
      return $operation();
    }
    finally {
      flock($lock, LOCK_UN);
      fclose($lock);
    }
  }

  private function path(string $path, bool $executable): string {
    if ($path === '' || !is_file($path) || ($executable && !is_executable($path)) || (!$executable && !is_readable($path))) { throw new \InvalidArgumentException('Configured Matrix path is not usable.'); }
    return $path;
  }

  private function directory(string $path): string {
    if ($path === '' || !is_dir($path) || !is_readable($path) || !is_writable($path)) {
      throw new \InvalidArgumentException('Configured Matrix store is not usable.');
    }
    return $path;
  }
}
