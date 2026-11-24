<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class SlackWebhookTransport extends IncomingWebhookTransport {
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    return $this->sendIncomingWebhook($message, $destination, $transactionId);
  }

  public function capabilities(): array { return ['transport' => 'slack', 'incoming_webhook' => TRUE]; }
}
