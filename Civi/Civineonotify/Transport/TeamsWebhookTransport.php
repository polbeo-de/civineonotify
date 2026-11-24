<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends outbound text notifications to a Microsoft Teams workflow webhook. */
final class TeamsWebhookTransport extends GenericWebhookTransport {
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $url = $this->endpoint($this->storedDestinationUrl($destination));
      $payload = json_encode(['text' => $message->body], JSON_THROW_ON_ERROR);
      $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'Idempotency-Key' => $transactionId, 'X-CiviNeo-Transaction-Id' => $transactionId];
      $response = $this->post($url, $payload, $this->timeout(), $headers);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Teams webhook rejected the notification.');
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The Teams webhook payload could not be encoded.'); }
    catch (\RuntimeException $exception) { $unknown = str_contains(strtolower($exception->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $unknown ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $unknown ? 'Teams webhook timed out.' : 'Teams webhook could not be reached.', $unknown); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Teams webhook configuration is not usable.'); }
  }

  public function capabilities(): array { return ['transport' => 'teams', 'workflow_webhook' => TRUE, 'plain_text' => TRUE]; }
}
