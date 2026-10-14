<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class TransportFactory {
  public static function create(string $type, array $config): NotificationTransportInterface {
    return match ($type) {
      'matrix_commander_rs' => new MatrixCommanderRsTransport($config),
      'matrix_nio' => new MatrixNioTransport($config),
      'mattermost_webhook' => new MattermostWebhookTransport($config),
      'mattermost_api' => new MattermostApiTransport($config),
      'mastodon' => new MastodonTransport($config),
      'telegram' => new TelegramTransport($config),
      'bluesky' => new BlueskyTransport($config),
      default => throw new \InvalidArgumentException('Unsupported transport type.'),
    };
  }
}
