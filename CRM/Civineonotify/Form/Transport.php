<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Transport extends CRM_Core_Form {
  private ?int $id = NULL;
  public function preProcess(): void { $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); $this->id = $id ? (int) $id : NULL; civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports'); }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit transport' : 'Add transport'));
    $this->add('text', 'name', E::ts('Transport name'), ['size' => 70], TRUE);
    $this->add('select', 'transport_type', E::ts('Transport type'), ['matrix_commander_rs' => E::ts('Matrix Commander'), 'matrix_nio' => E::ts('Matrix Nio'), 'mattermost_webhook' => E::ts('Mattermost incoming webhook')], TRUE);
    foreach (['binary_path' => 'Matrix Commander binary', 'credentials_path' => 'Credentials file', 'store_path' => 'Encryption store', 'working_directory' => 'Working directory', 'timeout_seconds' => 'Timeout (seconds)', 'homeserver' => 'Matrix homeserver', 'python_path' => 'Python executable', 'nio_client_path' => 'Matrix Nio client', 'session_path' => 'Protected Matrix Nio session'] as $field => $label) $this->add('text', $field, E::ts($label), ['size' => 70]);
    $this->add('text', 'server_url', E::ts('Mattermost server URL'), ['size' => 70]);
    $this->add('text', 'hooks_path', E::ts('Mattermost hooks path'), ['size' => 70]);
    $this->add('text', 'token_store_file', E::ts('Mattermost token store file'), ['size' => 70]);
    $this->add('checkbox', 'allow_unverified_recipient_devices', E::ts('Allow unverified recipient devices'));
    $destinationOptions = $this->id
      ? civineonotify_destinationOptions(TRUE, $this->id, E::ts('- No default destination -'))
      : ['' => E::ts('- Save this transport, then add a destination -')];
    $this->add('select', 'default_destination_id', E::ts('Default destination'), $destinationOptions);
    if ($this->id && count($destinationOptions) === 1) {
      $this->assign('defaultDestinationNotice', E::ts('Create a destination (for example, a Matrix room) before selecting a default.'));
    }
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 3, 'cols' => 70]);
    $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Save transport'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
    if ($this->id) {
      $row = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
      if (in_array($row['transport_type'] ?? NULL, ['matrix_commander_rs', 'matrix_nio'], TRUE)) $this->assign('provisionUrl', CRM_Utils_System::url('civicrm/admin/civineonotify/provision-matrix', ['reset' => 1, 'id' => $this->id]));
      if (($row['transport_type'] ?? NULL) === 'matrix_nio') $this->assign('verifyUrl', CRM_Utils_System::url('civicrm/admin/civineonotify/verify-matrix-nio', ['reset' => 1, 'id' => $this->id]));
    }
  }
  public function setDefaultValues(): array {
    if (!$this->id) return ['is_active' => 1, 'transport_type' => 'matrix_commander_rs', 'timeout_seconds' => 30, 'working_directory' => '/tmp', 'nio_client_path' => dirname(__DIR__, 3) . '/bin/matrix-nio-client'];
    $row = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    return array_merge($row, is_array($row['config_json'] ?? NULL) ? $row['config_json'] : []);
  }
  public function postProcess(): void {
    $v = $this->exportValues();
    $existing = $this->id ? (\Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? []) : [];
    $existingConfig = is_array($existing['config_json'] ?? NULL) ? $existing['config_json'] : [];
    $type = (string) $v['transport_type'];
    if (!in_array($type, ['matrix_commander_rs', 'matrix_nio', 'mattermost_webhook'], TRUE)) { $this->setElementError('transport_type', E::ts('Choose a supported transport type.')); return; }
    if (in_array($type, ['matrix_commander_rs', 'matrix_nio'], TRUE)) {
      foreach (['working_directory'] as $path) if (empty($v[$path]) || !str_starts_with((string) $v[$path], '/')) { $this->setElementError($path, E::ts('Use an absolute path.')); return; }
    }
    if ($type === 'mattermost_webhook') {
      if (!preg_match('#^https://[^\s]+$#', (string) $v['server_url'])) { $this->setElementError('server_url', E::ts('Use an HTTPS Mattermost server URL.')); return; }
      if (empty($v['token_store_file']) || !str_starts_with((string) $v['token_store_file'], '/') || !is_dir(dirname((string) $v['token_store_file'])) || !is_writable(dirname((string) $v['token_store_file']))) { $this->setElementError('token_store_file', E::ts('Use an absolute path in a writable protected directory for the Mattermost token store.')); return; }
      $config = array_filter(array_intersect_key($v, array_flip(['server_url', 'hooks_path', 'token_store_file', 'timeout_seconds'])), static fn($value) => $value !== '' && $value !== NULL);
      $config['hooks_path'] = trim((string) ($config['hooks_path'] ?? 'hooks'), '/');
    }
    elseif ($type === 'matrix_commander_rs') {
      if (!is_file((string) $v['binary_path']) || !is_executable((string) $v['binary_path'])) { $this->setElementError('binary_path', E::ts('The Matrix Commander binary does not exist or is not executable.')); return; }
      $config = array_filter(array_intersect_key($v, array_flip(['binary_path', 'credentials_path', 'store_path', 'working_directory', 'timeout_seconds'])), static fn($value) => $value !== '' && $value !== NULL);
    }
    else {
      if (!preg_match('#^https://[^\s]+$#', (string) $v['homeserver'])) { $this->setElementError('homeserver', E::ts('Use an HTTPS homeserver URL.')); return; }
      foreach (['python_path', 'nio_client_path'] as $path) if (empty($v[$path]) || !str_starts_with((string) $v[$path], '/')) { $this->setElementError($path, E::ts('Use an absolute path.')); return; }
      foreach (['session_path', 'store_path'] as $path) if (!empty($v[$path]) && !str_starts_with((string) $v[$path], '/')) { $this->setElementError($path, E::ts('Use an absolute path.')); return; }
      if (!is_file((string) $v['python_path']) || !is_executable((string) $v['python_path'])) { $this->setElementError('python_path', E::ts('The Matrix Nio Python executable does not exist or is not executable.')); return; }
      if (!is_file((string) $v['nio_client_path']) || !is_readable((string) $v['nio_client_path'])) { $this->setElementError('nio_client_path', E::ts('The Matrix Nio client does not exist or is not readable.')); return; }
      $config = array_filter(array_intersect_key($v, array_flip(['homeserver', 'python_path', 'nio_client_path', 'session_path', 'store_path', 'working_directory', 'timeout_seconds', 'allow_unverified_recipient_devices'])), static fn($value) => $value !== '' && $value !== NULL && $value !== FALSE);
    }
    // These values are managed by provisioning/verification flows, not by the
    // transport editor. Retain them when an administrator changes a normal
    // transport setting (for example the default destination).
    $runtimeKeys = ['provision_status_file', 'verification_status_file', 'verification_device_id', 'verification_device_name', 'provisioned_room_id', 'provisioned_user_login'];
    foreach ($runtimeKeys as $key) if (array_key_exists($key, $existingConfig)) $config[$key] = $existingConfig[$key];
    $record = ['name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'transport_type' => (string) $v['transport_type'], 'config_json' => $config, 'default_destination_id' => (int) ($v['default_destination_id'] ?? 0) ?: NULL, 'is_active' => !empty($v['is_active'])];
    if ($this->id) \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute(); else \Civi\Api4\CiviNeoNotifyTransport::create(FALSE)->setValues($record)->execute();
    CRM_Core_Session::setStatus(E::ts('Transport saved.'), E::ts('Saved'), 'success'); CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/transports', ['reset' => 1]));
  }
}
