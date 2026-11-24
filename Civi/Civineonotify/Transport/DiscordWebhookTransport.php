<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

/** Sends outbound text notifications through Discord incoming webhooks. */
final class DiscordWebhookTransport extends GenericWebhookTransport {
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    try {
      $url = $this->endpoint($this->storedDestinationUrl($destination));
      $payload = json_encode(['content' => $message->body], JSON_THROW_ON_ERROR);
      $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'Idempotency-Key' => $transactionId, 'X-CiviNeo-Transaction-Id' => $transactionId];
      $response = $this->post($url, $payload, $this->timeout(), $headers);
      if ($response['status'] >= 200 && $response['status'] < 300) return new MatrixTransportResult(TRUE, 'success', NULL, $response['status']);
      return new MatrixTransportResult(FALSE, $this->category($response['status']), NULL, $response['status'], 'Discord webhook rejected the notification.');
    }
    catch (\JsonException) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'The Discord webhook payload could not be encoded.'); }
    catch (\RuntimeException $exception) { $unknown = str_contains(strtolower($exception->getMessage()), 'timed out'); return new MatrixTransportResult(FALSE, $unknown ? 'unknown_delivery_state' : 'temporary_transport_error', NULL, NULL, $unknown ? 'Discord webhook timed out.' : 'Discord webhook could not be reached.', $unknown); }
    catch (\Throwable) { return new MatrixTransportResult(FALSE, 'configuration_error', NULL, NULL, 'Discord webhook configuration is not usable.'); }
  }

  public function capabilities(): array { return ['transport' => 'discord', 'incoming_webhook' => TRUE, 'plain_text' => TRUE]; }
}
