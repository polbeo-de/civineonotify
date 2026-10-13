<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Transport\MattermostTokenStore;

final class CRM_Civineonotify_Form_Destination extends CRM_Core_Form {
  private ?int $id = NULL;
  public function preProcess(): void { $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); $this->id = $id ? (int) $id : NULL; civineonotify_appendBreadcrumb('Destinations', 'civicrm/admin/civineonotify/destinations'); }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit destination' : 'Add destination'));
    $this->add('select', 'transport_id', E::ts('Transport'), civineonotify_transportOptions(), TRUE, ['class' => 'crm-select2']);
    $transportDestinationTypes = [];
    foreach (\Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('is_active', '=', TRUE)->execute() as $transport) {
      $transportDestinationTypes[(string) $transport['id']] = $transport['transport_type'] === 'mattermost_webhook' ? 'mattermost_channel' : 'matrix_room';
    }
    $this->assign('transportDestinationTypesJson', json_encode($transportDestinationTypes, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    $this->add('text', 'name', E::ts('Destination name'), ['size' => 70], TRUE);
    $this->add('select', 'destination_type', E::ts('Destination type'), ['matrix_room' => E::ts('Matrix room'), 'mattermost_channel' => E::ts('Mattermost channel')], TRUE);
    $this->add('text', 'room_id', E::ts('Matrix room ID'), ['size' => 70]); $this->add('text', 'room_alias', E::ts('Matrix room alias'), ['size' => 70]);
    $this->add('text', 'channel', E::ts('Mattermost channel override'), ['size' => 70]);
    $this->add('password', 'webhook_token', E::ts('Mattermost hook token'), ['size' => 70]);
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
    return array_merge($row, $config);
  }
  public function postProcess(): void {
    $v = $this->exportValues();
    $transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', (int) $v['transport_id'])->execute()->first();
    $type = (string) ($v['destination_type'] ?? '');
    if (!$transport || (($transport['transport_type'] === 'mattermost_webhook') !== ($type === 'mattermost_channel'))) { $this->setElementError('destination_type', E::ts('Choose a destination type supported by the selected transport.')); return; }
    if ($type === 'matrix_room' && empty($v['room_id']) && empty($v['room_alias'])) { $this->setElementError('room_id', E::ts('Provide a Matrix room ID or alias.')); return; }
    $existing = $this->id ? (\Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? []) : [];
    $existingConfig = is_array($existing['config_json'] ?? NULL) ? $existing['config_json'] : [];
    if ($type === 'mattermost_channel' && empty($existingConfig['token_key']) && trim((string) ($v['webhook_token'] ?? '')) === '') { $this->setElementError('webhook_token', E::ts('Enter a Mattermost hook token.')); return; }
    $config = $type === 'mattermost_channel' ? ['channel' => trim((string) ($v['channel'] ?? '')) ?: NULL, 'token_key' => $existingConfig['token_key'] ?? bin2hex(random_bytes(16))] : ['room_id' => trim((string) ($v['room_id'] ?? '')) ?: NULL, 'room_alias' => trim((string) ($v['room_alias'] ?? '')) ?: NULL];
    $record = ['transport_id' => (int) $v['transport_id'], 'name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'destination_type' => $type, 'config_json' => $config, 'is_active' => !empty($v['is_active'])];
    if ($this->id) \Civi\Api4\CiviNeoNotifyDestination::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute(); else { $created = \Civi\Api4\CiviNeoNotifyDestination::create(FALSE)->setValues($record)->execute()->first(); $this->id = (int) $created['id']; }
    if ($type === 'mattermost_channel' && trim((string) ($v['webhook_token'] ?? '')) !== '') {
      $transportConfig = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
      try { (new MattermostTokenStore((string) ($transportConfig['token_store_file'] ?? '')))->put((string) $config['token_key'], trim((string) $v['webhook_token'])); }
      catch (\Throwable) { $this->setElementError('webhook_token', E::ts('The Mattermost token could not be stored.')); return; }
    }
    CRM_Core_Session::setStatus(E::ts('Destination saved.'), E::ts('Saved'), 'success'); CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/destinations', ['reset' => 1]));
  }
}
