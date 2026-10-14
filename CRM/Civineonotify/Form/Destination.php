<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Transport\MattermostApiClient;
use Civi\Civineonotify\Transport\MattermostTokenStore;

final class CRM_Civineonotify_Form_Destination extends CRM_Core_Form {
  private ?int $id = NULL;
  /** @var array<string,array<string,string>> */
  private array $mattermostApiChannels = [];
  /** @var array<string,bool> */
  private array $mattermostApiChannelErrors = [];
  public function preProcess(): void { $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); $this->id = $id ? (int) $id : NULL; civineonotify_appendBreadcrumb('Destinations', 'civicrm/admin/civineonotify/destinations'); }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit destination' : 'Add destination'));
    $this->add('select', 'transport_id', E::ts('Transport'), civineonotify_transportOptions(), TRUE, ['class' => 'crm-select2']);
    $transportDestinationTypes = [];
    $channelOptions = ['' => E::ts('- select a channel -')];
    foreach (\Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('is_active', '=', TRUE)->execute() as $transport) {
      $transportDestinationTypes[(string) $transport['id']] = in_array($transport['transport_type'], ['mattermost_webhook', 'mattermost_api'], TRUE) ? 'mattermost_channel' : ($transport['transport_type'] === 'mastodon' ? 'mastodon_account' : ($transport['transport_type'] === 'bluesky' ? 'bluesky_account' : ($transport['transport_type'] === 'telegram' ? 'telegram_chat' : 'matrix_room')));
      if ($transport['transport_type'] === 'mattermost_api') {
        try {
          $config = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
          $this->mattermostApiChannels[(string) $transport['id']] = (new MattermostApiClient($config))->channels();
          $channelOptions += $this->mattermostApiChannels[(string) $transport['id']];
        }
        catch (\Throwable) { $this->mattermostApiChannelErrors[(string) $transport['id']] = TRUE; }
      }
    }
    $this->assign('transportDestinationTypesJson', json_encode($transportDestinationTypes, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    $this->assign('mattermostApiChannelsJson', json_encode($this->mattermostApiChannels, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    $this->assign('mattermostApiChannelErrorsJson', json_encode($this->mattermostApiChannelErrors, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    $this->add('text', 'name', E::ts('Destination name'), ['size' => 70], TRUE);
    $this->add('select', 'destination_type', E::ts('Destination type'), ['matrix_room' => E::ts('Matrix room'), 'mattermost_channel' => E::ts('Mattermost channel'), 'mastodon_account' => E::ts('Mastodon account'), 'bluesky_account' => E::ts('Bluesky account'), 'telegram_chat' => E::ts('Telegram chat')], TRUE);
    $this->add('text', 'room_id', E::ts('Matrix room ID'), ['size' => 70]); $this->add('text', 'room_alias', E::ts('Matrix room alias'), ['size' => 70]);
    $this->add('select', 'mattermost_channel_id', E::ts('Mattermost channel'), $channelOptions);
    $this->add('text', 'channel_override', E::ts('Mattermost channel override'), ['size' => 70]);
    $this->add('password', 'webhook_token', E::ts('Mattermost hook token'), ['size' => 70]);
    $this->add('text', 'telegram_chat_id', E::ts('Telegram chat ID'), ['size' => 30]);
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 3, 'cols' => 70]); $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Save destination'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
  }
  public function setDefaultValues(): array {
    if (!$this->id) return ['is_active' => 1, 'destination_type' => 'matrix_room'];
    $row = \Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    $config = is_array($row['config_json'] ?? NULL) ? $row['config_json'] : [];
    if (($row['destination_type'] ?? NULL) === 'mattermost_channel') {
      $transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $row['transport_id'])->execute()->first() ?? [];
      $transportConfig = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
      $suffix = !empty($config['token_key']) && !empty($transportConfig['token_store_file']) ? (new MattermostTokenStore((string) $transportConfig['token_store_file']))->suffix((string) $config['token_key']) : '';
      $this->assign('mattermostTokenSuffix', $suffix);
    }
    $defaults = array_merge($row, $config);
    if (($row['destination_type'] ?? NULL) === 'mattermost_channel') {
      $transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $row['transport_id'])->execute()->first() ?? [];
      $defaults[($transport['transport_type'] ?? '') === 'mattermost_api' ? 'mattermost_channel_id' : 'channel_override'] = $config['channel'] ?? NULL;
    }
    return $defaults;
  }
  public function postProcess(): void {
    $v = $this->exportValues();
    $transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', (int) $v['transport_id'])->execute()->first();
    $type = (string) ($v['destination_type'] ?? '');
    $transportType = (string) ($transport['transport_type'] ?? '');
    $mattermost = in_array($transportType, ['mattermost_webhook', 'mattermost_api'], TRUE);
    $supportedType = $mattermost ? 'mattermost_channel' : ($transportType === 'mastodon' ? 'mastodon_account' : ($transportType === 'bluesky' ? 'bluesky_account' : ($transportType === 'telegram' ? 'telegram_chat' : 'matrix_room')));
    if (!$transport || $type !== $supportedType) { $this->setElementError('destination_type', E::ts('Choose a destination type supported by the selected transport.')); return; }
    if ($type === 'matrix_room' && empty($v['room_id']) && empty($v['room_alias'])) { $this->setElementError('room_id', E::ts('Provide a Matrix room ID or alias.')); return; }
    if ($type === 'telegram_chat' && !preg_match('/^-[0-9]{1,20}$/', trim((string) ($v['telegram_chat_id'] ?? '')))) { $this->setElementError('telegram_chat_id', E::ts('Enter a negative numeric Telegram group, supergroup, or channel chat ID.')); return; }
    $existing = $this->id ? (\Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? []) : [];
    $existingConfig = is_array($existing['config_json'] ?? NULL) ? $existing['config_json'] : [];
    $webhook = ($transport['transport_type'] ?? '') === 'mattermost_webhook';
    $api = ($transport['transport_type'] ?? '') === 'mattermost_api';
    $channel = $webhook ? trim((string) ($v['channel_override'] ?? '')) : trim((string) ($v['mattermost_channel_id'] ?? ''));
    if ($type === 'mattermost_channel' && $api && $channel === '') { $this->setElementError('mattermost_channel_id', E::ts('Choose a Mattermost channel.')); return; }
    if ($type === 'mattermost_channel' && $api && (isset($this->mattermostApiChannelErrors[(string) $transport['id']]) || !isset($this->mattermostApiChannels[(string) $transport['id']][$channel]))) { $this->setElementError('mattermost_channel_id', E::ts('The Mattermost channels could not be loaded. Check the API token and try again.')); return; }
    if ($type === 'mattermost_channel' && $webhook && empty($existingConfig['token_key']) && trim((string) ($v['webhook_token'] ?? '')) === '') { $this->setElementError('webhook_token', E::ts('Enter a Mattermost hook token.')); return; }
    $config = $type === 'mattermost_channel' ? array_filter(['channel' => $channel ?: NULL, 'token_key' => $webhook ? ($existingConfig['token_key'] ?? bin2hex(random_bytes(16))) : NULL], static fn($value) => $value !== NULL) : ($type === 'telegram_chat' ? ['chat_id' => trim((string) $v['telegram_chat_id'])] : (in_array($type, ['mastodon_account', 'bluesky_account'], TRUE) ? [] : ['room_id' => trim((string) ($v['room_id'] ?? '')) ?: NULL, 'room_alias' => trim((string) ($v['room_alias'] ?? '')) ?: NULL]));
    $record = ['transport_id' => (int) $v['transport_id'], 'name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'destination_type' => $type, 'config_json' => $config, 'is_active' => !empty($v['is_active'])];
    if ($this->id) \Civi\Api4\CiviNeoNotifyDestination::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute(); else { $created = \Civi\Api4\CiviNeoNotifyDestination::create(FALSE)->setValues($record)->execute()->first(); $this->id = (int) $created['id']; }
    if ($type === 'mattermost_channel' && $webhook && trim((string) ($v['webhook_token'] ?? '')) !== '') {
      $transportConfig = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
      try { (new MattermostTokenStore((string) ($transportConfig['token_store_file'] ?? '')))->put((string) $config['token_key'], trim((string) $v['webhook_token'])); }
      catch (\Throwable) { $this->setElementError('webhook_token', E::ts('The Mattermost token could not be stored.')); return; }
    }
    CRM_Core_Session::setStatus(E::ts('Destination saved.'), E::ts('Saved'), 'success'); CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/destinations', ['reset' => 1]));
  }
}
