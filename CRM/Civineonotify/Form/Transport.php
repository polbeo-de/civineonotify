<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Transport extends CRM_Core_Form {
  private ?int $id = NULL;
  public function preProcess(): void { $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); $this->id = $id ? (int) $id : NULL; civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports'); }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit transport' : 'Add transport'));
    $this->add('text', 'name', E::ts('Transport name'), ['size' => 70], TRUE);
    $this->add('select', 'transport_type', E::ts('Transport type'), ['matrix_commander_rs' => E::ts('Matrix Commander'), 'matrix_nio' => E::ts('Matrix Nio'), 'mattermost_webhook' => E::ts('Mattermost (Webhook)'), 'mattermost_api' => E::ts('Mattermost (API)'), 'mastodon' => E::ts('Mastodon'), 'telegram' => E::ts('Telegram')], TRUE);
    foreach (['binary_path' => 'Matrix Commander binary', 'credentials_path' => 'Credentials file', 'store_path' => 'Encryption store', 'working_directory' => 'Working directory', 'timeout_seconds' => 'Timeout (seconds)', 'homeserver' => 'Matrix homeserver', 'python_path' => 'Python executable', 'nio_client_path' => 'Matrix Nio client', 'session_path' => 'Protected Matrix Nio session'] as $field => $label) $this->add('text', $field, E::ts($label), ['size' => 70]);
    $this->add('text', 'server_url', E::ts('Mattermost server URL'), ['size' => 70]);
    $this->add('text', 'hooks_path', E::ts('Mattermost hooks path'), ['size' => 70]);
    $this->add('text', 'token_store_file', E::ts('Protected token store file'), ['size' => 70]);
    $this->add('password', 'api_token', E::ts('Mattermost API token'), ['size' => 70]);
    $this->add('text', 'instance_url', E::ts('Mastodon instance URL'), ['size' => 70]);
    $this->add('password', 'mastodon_access_token', E::ts('Mastodon access token'), ['size' => 70]);
    $this->add('select', 'mastodon_visibility', E::ts('Default Mastodon visibility'), ['public' => E::ts('Public'), 'unlisted' => E::ts('Unlisted'), 'private' => E::ts('Followers only')]);
    $languages = ['' => E::ts('- No language -')];
    foreach (\CRM_Contact_BAO_Contact::buildOptions('preferred_language') as $locale => $label) $languages[str_replace('_', '-', (string) $locale)] = (string) $label;
    $this->add('select', 'mastodon_language', E::ts('Default Mastodon language'), $languages, FALSE, ['class' => 'crm-select2']);
    $this->add('text', 'mastodon_spoiler_text', E::ts('Default Mastodon content warning'), ['size' => 70]);
    $this->add('password', 'telegram_bot_token', E::ts('Telegram bot token'), ['size' => 70]);
    $this->add('select', 'telegram_parse_mode', E::ts('Telegram formatting'), ['' => E::ts('Plain text'), 'HTML' => E::ts('HTML')]);
    $this->add('checkbox', 'telegram_disable_link_preview', E::ts('Disable Telegram link previews'));
    $this->add('checkbox', 'telegram_disable_notification', E::ts('Send Telegram notifications silently'));
    $this->add('checkbox', 'telegram_protect_content', E::ts('Protect Telegram content'));
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
      if (($row['transport_type'] ?? NULL) === 'mastodon') {
        $config = is_array($row['config_json'] ?? NULL) ? $row['config_json'] : [];
        try {
          $suffix = !empty($config['token_store_file']) && !empty($config['api_token_key']) ? (new \Civi\Civineonotify\Transport\MattermostTokenStore((string) $config['token_store_file']))->suffix((string) $config['api_token_key']) : '';
          $this->assign('mastodonTokenSuffix', $suffix);
        }
        catch (\Throwable) { $this->assign('mastodonTokenSuffix', ''); }
      }
      if (($row['transport_type'] ?? NULL) === 'mattermost_api') {
        $config = is_array($row['config_json'] ?? NULL) ? $row['config_json'] : [];
        try {
          $suffix = !empty($config['token_store_file']) && !empty($config['api_token_key']) ? (new \Civi\Civineonotify\Transport\MattermostTokenStore((string) $config['token_store_file']))->suffix((string) $config['api_token_key']) : '';
          $this->assign('mattermostApiTokenSuffix', $suffix);
        }
        catch (\Throwable) { $this->assign('mattermostApiTokenSuffix', ''); }
      }
      if (($row['transport_type'] ?? NULL) === 'telegram') {
        $config = is_array($row['config_json'] ?? NULL) ? $row['config_json'] : [];
        try {
          $suffix = !empty($config['token_store_file']) && !empty($config['api_token_key']) ? (new \Civi\Civineonotify\Transport\MattermostTokenStore((string) $config['token_store_file']))->suffix((string) $config['api_token_key']) : '';
          $this->assign('telegramTokenSuffix', $suffix);
        }
        catch (\Throwable) { $this->assign('telegramTokenSuffix', ''); }
      }
      if (in_array($row['transport_type'] ?? NULL, ['matrix_commander_rs', 'matrix_nio'], TRUE)) $this->assign('provisionUrl', CRM_Utils_System::url('civicrm/admin/civineonotify/provision-matrix', ['reset' => 1, 'id' => $this->id]));
      if (($row['transport_type'] ?? NULL) === 'matrix_nio') $this->assign('verifyUrl', CRM_Utils_System::url('civicrm/admin/civineonotify/verify-matrix-nio', ['reset' => 1, 'id' => $this->id]));
    }
  }
  public function setDefaultValues(): array {
    if (!$this->id) { $protected=(string)\Civi::settings()->get('civineonotify_protected_transport_storage_directory'); return ['is_active' => 1, 'transport_type' => 'matrix_commander_rs', 'timeout_seconds' => 30, 'working_directory' => $protected ?: '/tmp', 'token_store_file' => $protected ? rtrim($protected, '/') . '/mattermost-tokens.json' : '', 'nio_client_path' => dirname(__DIR__, 3) . '/bin/matrix-nio-client', 'mastodon_visibility' => 'public', 'telegram_disable_link_preview' => 1]; }
    $row = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    $config = is_array($row['config_json'] ?? NULL) ? $row['config_json'] : [];
    $defaults = array_merge($row, $config);
    if (($row['transport_type'] ?? NULL) === 'mastodon') {
      $defaults['mastodon_visibility'] = $config['visibility'] ?? 'public';
      $defaults['mastodon_language'] = $config['language'] ?? '';
      $defaults['mastodon_spoiler_text'] = $config['spoiler_text'] ?? '';
    }
    if (($row['transport_type'] ?? NULL) === 'telegram') {
      $defaults['telegram_parse_mode'] = $config['parse_mode'] ?? '';
      $defaults['telegram_disable_link_preview'] = !empty($config['disable_link_preview']);
      $defaults['telegram_disable_notification'] = !empty($config['disable_notification']);
      $defaults['telegram_protect_content'] = !empty($config['protect_content']);
    }
    return $defaults;
  }
  public function postProcess(): void {
    $v = $this->exportValues();
    $existing = $this->id ? (\Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? []) : [];
    $existingConfig = is_array($existing['config_json'] ?? NULL) ? $existing['config_json'] : [];
    $type = (string) $v['transport_type'];
    if (in_array($type, ['mattermost_webhook', 'mattermost_api', 'mastodon', 'telegram'], TRUE) && empty($v['token_store_file'])) { $protected=(string)\Civi::settings()->get('civineonotify_protected_transport_storage_directory'); if ($protected !== '') $v['token_store_file']=rtrim($protected, '/') . ($type === 'mastodon' ? 'mastodon-tokens.json' : ($type === 'telegram' ? 'telegram-tokens.json' : 'mattermost-tokens.json')); }
    if (!in_array($type, ['matrix_commander_rs', 'matrix_nio', 'mattermost_webhook', 'mattermost_api', 'mastodon', 'telegram'], TRUE)) { $this->setElementError('transport_type', E::ts('Choose a supported transport type.')); return; }
    if (in_array($type, ['matrix_commander_rs', 'matrix_nio'], TRUE)) {
      foreach (['working_directory'] as $path) if (empty($v[$path]) || !str_starts_with((string) $v[$path], '/')) { $this->setElementError($path, E::ts('Use an absolute path.')); return; }
    }
    if (in_array($type, ['mattermost_webhook', 'mattermost_api'], TRUE)) {
      if (!preg_match('#^https://[^\s]+$#', (string) $v['server_url'])) { $this->setElementError('server_url', E::ts('Use an HTTPS Mattermost server URL.')); return; }
      if (empty($v['token_store_file']) || !str_starts_with((string) $v['token_store_file'], '/') || !is_dir(dirname((string) $v['token_store_file'])) || !is_writable(dirname((string) $v['token_store_file']))) { $this->setElementError('token_store_file', E::ts('Use an absolute path in a writable protected directory for the Mattermost token store.')); return; }
      $config = array_filter(array_intersect_key($v, array_flip(['server_url', 'token_store_file', 'timeout_seconds'])), static fn($value) => $value !== '' && $value !== NULL);
      if ($type === 'mattermost_webhook') $config['hooks_path'] = trim((string) ($v['hooks_path'] ?? 'hooks'), '/');
      if ($type === 'mattermost_api') { $config['api_token_key'] = $existingConfig['api_token_key'] ?? bin2hex(random_bytes(16)); if (trim((string)($v['api_token']??'')) === '' && empty($existingConfig['api_token_key'])) { $this->setElementError('api_token', E::ts('Enter a Mattermost API token.')); return; } }
    }
    elseif ($type === 'mastodon') {
      if (!preg_match('#^https://[^\s]+$#', (string) $v['instance_url'])) { $this->setElementError('instance_url', E::ts('Use an HTTPS Mastodon instance URL.')); return; }
      if (empty($v['token_store_file']) || !str_starts_with((string) $v['token_store_file'], '/') || !is_dir(dirname((string) $v['token_store_file'])) || !is_writable(dirname((string) $v['token_store_file']))) { $this->setElementError('token_store_file', E::ts('Use an absolute path in a writable protected directory for the token store.')); return; }
      if (!in_array((string) $v['mastodon_visibility'], ['public', 'unlisted', 'private'], TRUE)) { $this->setElementError('mastodon_visibility', E::ts('Choose a supported Mastodon visibility.')); return; }
      $config = array_filter(['instance_url' => rtrim((string) $v['instance_url'], '/'), 'token_store_file' => (string) $v['token_store_file'], 'timeout_seconds' => (int) $v['timeout_seconds'], 'visibility' => (string) $v['mastodon_visibility'], 'language' => trim((string) $v['mastodon_language']), 'spoiler_text' => trim((string) $v['mastodon_spoiler_text']), 'api_token_key' => $existingConfig['api_token_key'] ?? bin2hex(random_bytes(16))], static fn($value) => $value !== '');
      if (trim((string) ($v['mastodon_access_token'] ?? '')) === '' && empty($existingConfig['api_token_key'])) { $this->setElementError('mastodon_access_token', E::ts('Enter a Mastodon access token.')); return; }
    }
    elseif ($type === 'telegram') {
      if (empty($v['token_store_file']) || !str_starts_with((string) $v['token_store_file'], '/') || !is_dir(dirname((string) $v['token_store_file'])) || !is_writable(dirname((string) $v['token_store_file']))) { $this->setElementError('token_store_file', E::ts('Use an absolute path in a writable protected directory for the token store.')); return; }
      $config = ['token_store_file' => (string)$v['token_store_file'], 'api_token_key' => $existingConfig['api_token_key'] ?? bin2hex(random_bytes(16)), 'parse_mode' => (string)$v['telegram_parse_mode'], 'disable_link_preview' => !empty($v['telegram_disable_link_preview']), 'disable_notification' => !empty($v['telegram_disable_notification']), 'protect_content' => !empty($v['telegram_protect_content']), 'timeout_seconds' => (int)$v['timeout_seconds']];
      if (trim((string)($v['telegram_bot_token'] ?? '')) === '' && empty($existingConfig['api_token_key'])) { $this->setElementError('telegram_bot_token', E::ts('Enter a Telegram bot token.')); return; }
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
    $runtimeKeys = ['provision_status_file', 'verification_status_file', 'verification_method', 'verification_device_id', 'verification_device_name', 'verification_target_user_id', 'verification_target_name', 'verification_room_id', 'provisioned_room_id', 'provisioned_user_login'];
    foreach ($runtimeKeys as $key) if (array_key_exists($key, $existingConfig)) $config[$key] = $existingConfig[$key];
    $record = ['name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'transport_type' => (string) $v['transport_type'], 'config_json' => $config, 'default_destination_id' => (int) ($v['default_destination_id'] ?? 0) ?: NULL, 'is_active' => !empty($v['is_active'])];
    if ($this->id) \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute(); else { $created=\Civi\Api4\CiviNeoNotifyTransport::create(FALSE)->setValues($record)->execute()->first(); $this->id=(int)$created['id']; }
    if ($type === 'mattermost_api' && trim((string)($v['api_token']??'')) !== '') try { (new \Civi\Civineonotify\Transport\MattermostTokenStore((string)$config['token_store_file']))->put((string)$config['api_token_key'], trim((string)$v['api_token'])); } catch (\Throwable) { throw new CRM_Core_Exception(E::ts('The Mattermost API token could not be stored.')); }
    if ($type === 'mastodon' && trim((string)($v['mastodon_access_token']??'')) !== '') try { (new \Civi\Civineonotify\Transport\MattermostTokenStore((string)$config['token_store_file']))->put((string)$config['api_token_key'], trim((string)$v['mastodon_access_token'])); } catch (\Throwable) { throw new CRM_Core_Exception(E::ts('The Mastodon access token could not be stored.')); }
    if ($type === 'telegram' && trim((string)($v['telegram_bot_token']??'')) !== '') try { (new \Civi\Civineonotify\Transport\MattermostTokenStore((string)$config['token_store_file']))->put((string)$config['api_token_key'], trim((string)$v['telegram_bot_token'])); } catch (\Throwable) { throw new CRM_Core_Exception(E::ts('The Telegram bot token could not be stored.')); }
    CRM_Core_Session::setStatus(E::ts('Transport saved.'), E::ts('Saved'), 'success'); CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/transports', ['reset' => 1]));
  }
}
