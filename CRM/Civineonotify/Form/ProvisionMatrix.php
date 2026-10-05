<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;
use Symfony\Component\Process\Process;

/** One-time, privileged provisioning of a Matrix credential bundle. */
final class CRM_Civineonotify_Form_ProvisionMatrix extends CRM_Core_Form {
  private int $id;
  private array $transport;

  public function preProcess(): void {
    $id = (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $id)->execute()->first();
    if (!$transport || ($transport['transport_type'] ?? NULL) !== 'matrix_commander_rs') throw new CRM_Core_Exception(E::ts('Matrix transport not found.'));
    $this->id = $id;
    $this->transport = $transport;
    civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports');
    civineonotify_appendBreadcrumb('Provision Matrix transport');
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Provision Matrix transport'));
    $this->assign('transportName', $this->transport['name']);
    $this->assign('provisioningStatus', $this->provisioningStatus());
    $this->add('text', 'homeserver', E::ts('Matrix homeserver'), ['size' => 70], TRUE);
    $this->add('text', 'user_login', E::ts('Matrix bot user'), ['size' => 70], TRUE);
    $this->add('text', 'room_id', E::ts('Matrix internal room ID'), ['size' => 70], TRUE);
    $this->add('text', 'storage_root', E::ts('Private storage directory'), ['size' => 70], TRUE);
    $this->add('password', 'bot_password', E::ts('One-time bot password'), ['size' => 70], TRUE);
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Provision securely'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
  }

  public function setDefaultValues(): array {
    $config = is_array($this->transport['config_json'] ?? NULL) ? $this->transport['config_json'] : [];
    $credentials = (string) ($config['credentials_path'] ?? '');
    return ['homeserver' => $config['homeserver'] ?? '', 'user_login' => $config['provisioned_user_login'] ?? 'monitoring_bot', 'room_id' => $config['provisioned_room_id'] ?? '', 'storage_root' => $credentials !== '' ? dirname($credentials) : ''];
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    $homeserver = rtrim(trim((string) $values['homeserver']), '/');
    $userLogin = trim((string) $values['user_login']);
    $roomId = trim((string) $values['room_id']);
    $storageRoot = rtrim(trim((string) $values['storage_root']), '/');
    if (!preg_match('#^https://[^\s]+$#', $homeserver)) { $this->setElementError('homeserver', E::ts('Use an HTTPS homeserver URL.')); return; }
    if (!preg_match('/^![^\/\s]+:[^\/\s]+$/', $roomId)) { $this->setElementError('room_id', E::ts('Use an internal Matrix room ID beginning with !.')); return; }
    if (!str_starts_with($storageRoot, '/')) { $this->setElementError('storage_root', E::ts('Use an absolute path outside the web root.')); return; }
    if ($userLogin === '') { $this->setElementError('user_login', E::ts('Provide the Matrix bot user.')); return; }
    $password = (string) ($values['bot_password'] ?? '');
    if ($password === '' || strlen($password) > 4096) { $this->setElementError('bot_password', E::ts('Provide the bot password.')); return; }

    $passwordFile = '';
    try {
      if (!is_dir($storageRoot) && !mkdir($storageRoot, 0700, TRUE) && !is_dir($storageRoot)) throw new RuntimeException('Private storage directory cannot be created.');
      $mode = fileperms($storageRoot);
      if ($mode === FALSE || ($mode & 0077) !== 0 || !is_writable($storageRoot)) throw new RuntimeException('Private storage directory is not private and writable.');
      $job = bin2hex(random_bytes(8));
      $passwordFile = $storageRoot . '/.civineonotify-password-' . $job;
      $statusFile = $storageRoot . '/.civineonotify-provision-' . $job . '.status';
      if (file_put_contents($passwordFile, $password, LOCK_EX) === FALSE || !chmod($passwordFile, 0600)) throw new RuntimeException('Bot password cannot be secured.');
      $password = '';
      $credentials = $storageRoot . '/credentials.json';
      $store = $storageRoot . '/store';
      $helper = dirname(__DIR__, 3) . '/bin/provision-matrix-credentials';
      $config = is_array($this->transport['config_json'] ?? NULL) ? $this->transport['config_json'] : [];
      $config['credentials_path'] = $credentials;
      $config['store_path'] = $store;
      $config['working_directory'] = $storageRoot;
      $config['homeserver'] = $homeserver;
      $config['provisioned_room_id'] = $roomId;
      $config['provisioned_user_login'] = $userLogin;
      $config['provision_status_file'] = $statusFile;
      \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues(['config_json' => $config])->execute();
      $process = new Process(['/usr/bin/systemd-run', '--user', '--no-block', '--collect', '--unit', 'civineonotify-provision-' . $this->id . '-' . $job, '--property=TimeoutStartSec=5min', '--property=MemoryHigh=900M', '--', $helper, '--matrix-client', (string) ($config['binary_path'] ?? ''), '--homeserver', $homeserver, '--user-login', $userLogin, '--room-id', $roomId, '--password-file', $passwordFile, '--remove-password-file', '--status-file', $statusFile, '--credentials-file', $credentials, '--store-path', $store, '--timeout', (string) ($config['timeout_seconds'] ?? 30), '--device-name', 'CiviNeo Notify phprime8'], NULL, ['PATH' => '/usr/bin:/bin'], NULL, 10);
      $process->run();
      if (!$process->isSuccessful()) throw new RuntimeException('Matrix provisioning job cannot be started.');
      $passwordFile = '';
      CRM_Core_Session::setStatus(E::ts('Matrix provisioning has started in a protected background job. Reopen this page shortly to view its status before testing the transport.'), E::ts('Provisioning started'), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/transport', ['reset' => 1, 'id' => $this->id]));
    }
    catch (\Throwable $exception) {
      \Civi::log()->warning('CiviNeo Notify Matrix provisioning failed.', ['transport_id' => $this->id, 'error_class' => get_class($exception)]);
      $this->setElementError('bot_password', E::ts('Provisioning failed. Verify the homeserver, bot password, room ID and private directory.'));
    }
    finally {
      $password = '';
      if ($passwordFile !== '' && is_file($passwordFile)) unlink($passwordFile);
    }
  }

  private function provisioningStatus(): string {
    $config = is_array($this->transport['config_json'] ?? NULL) ? $this->transport['config_json'] : [];
    $file = (string) ($config['provision_status_file'] ?? '');
    if ($file === '' || !is_file($file) || !is_readable($file)) return '';
    $status = trim((string) file_get_contents($file));
    return in_array($status, ['running', 'succeeded', 'failed'], TRUE) ? $status : '';
  }
}
