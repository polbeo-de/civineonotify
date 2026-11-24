<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class RocketChatWebhookTransport extends IncomingWebhookTransport {
  public function send(NotificationMessage $message, NotificationDestination $destination, string $transactionId): MatrixTransportResult {
    return $this->sendIncomingWebhook($message, $destination, $transactionId);
  }

  public function capabilities(): array { return ['transport' => 'rocketchat', 'incoming_webhook' => TRUE]; }
}
