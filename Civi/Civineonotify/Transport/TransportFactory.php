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
      'generic_webhook' => new GenericWebhookTransport($config),
      'slack' => new SlackWebhookTransport($config),
      'rocketchat' => new RocketChatWebhookTransport($config),
      'discord' => new DiscordWebhookTransport($config),
      'teams' => new TeamsWebhookTransport($config),
      'ntfy' => new NtfyTransport($config),
      'gotify' => new GotifyTransport($config),
      default => throw new \InvalidArgumentException('Unsupported transport type.'),
    };
  }
}
