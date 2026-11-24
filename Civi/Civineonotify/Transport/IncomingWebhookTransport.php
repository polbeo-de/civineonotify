<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Shared safe delivery path for named incoming-webhook transports. */
abstract class IncomingWebhookTransport extends GenericWebhookTransport {
  protected function sendIncomingWebhook(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $url = $this->endpoint($this->storedDestinationUrl($destination));
      $payload = json_encode(['text' => $message->body], JSON_THROW_ON_ERROR);
      $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'Idempotency-Key' => $transactionId, 'X-CiviNeo-Transaction-Id' => $transactionId];
      $response = $this->post($url, $payload, $this->timeout(), $headers);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Incoming webhook endpoint rejected the notification.');
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The incoming webhook payload could not be encoded.'); }
    catch (\RuntimeException $exception) {
      $unknown = str_contains(strtolower($exception->getMessage()), 'timed out');
      return new MatrixTransportResult(FALSE, $unknown ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $unknown ? 'Incoming webhook endpoint timed out.' : 'Incoming webhook endpoint could not be reached.', $unknown);
    }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Incoming webhook configuration is not usable.'); }
  }
}
