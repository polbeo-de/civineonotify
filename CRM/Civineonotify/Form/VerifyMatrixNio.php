<?php
declare(strict_types=1);
use CRM_Civineonotify_ExtensionUtil as E;
use Symfony\Component\Process\Process;

final class CRM_Civineonotify_Form_VerifyMatrixNio extends CRM_Core_Form {
  private int $id; private array $transport; private array $config;
  public function preProcess(): void {
    $this->id = (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    if (($this->transport['transport_type'] ?? NULL) !== 'matrix_nio') throw new CRM_Core_Exception(E::ts('Matrix Nio transport not found.'));
    $this->config = is_array($this->transport['config_json'] ?? NULL) ? $this->transport['config_json'] : [];
    civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports'); civineonotify_appendBreadcrumb('Verify Matrix Nio device');
  }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Verify Matrix Nio device'));
    $verification = $this->readStatus();
    $this->assign('verification', $verification);
    $devices = $this->deviceOptions();
    $this->add('select', 'verification_device', E::ts('Recipient device'), $devices);
    $deviceIds = array_values(array_filter(array_keys($devices)));
    if (count($deviceIds) === 1) $this->setDefaults(['verification_device' => $deviceIds[0]]);
    $state = (string) ($verification['state'] ?? '');
    $active = in_array($state, ['waiting', 'compare', 'confirming'], TRUE);
    $buttons = [];
    if (!$active) $buttons[] = ['type'=>'submit','name'=>E::ts('Start verification'),'subName'=>'start'];
    if ($state === 'compare') $buttons[] = ['type'=>'submit','name'=>E::ts('Confirm matching SAS'),'subName'=>'confirm'];
    if ($active) $buttons[] = ['type'=>'submit','name'=>E::ts('Cancel verification'),'subName'=>'cancel'];
    $buttons[] = ['type'=>'cancel','name'=>E::ts('Close')];
    $this->addButtons($buttons);
  }
  public function postProcess(): void {
    $action = (string) ($this->controller->getButtonName() ?? ''); $root = dirname((string) ($this->config['session_path'] ?? ''));
    $status = $root . '/.civineonotify-nio-verify.status'; $confirm = $root . '/.civineonotify-nio-verify.confirm'; $cancel = $root . '/.civineonotify-nio-verify.cancel';
    if (str_ends_with($action, 'confirm')) { file_put_contents($confirm, '1', LOCK_EX); chmod($confirm, 0600); file_put_contents($status, json_encode(['state' => 'confirming'], JSON_THROW_ON_ERROR) . "\n", LOCK_EX); chmod($status, 0600); }
    elseif (str_ends_with($action, 'cancel')) {
      file_put_contents($cancel, '1', LOCK_EX); chmod($cancel, 0600);
      $uid = posix_geteuid(); $runtime = '/run/user/' . $uid;
      (new Process(['/usr/bin/systemctl','--user','stop','civineonotify-verify-' . $this->id], NULL, ['PATH'=>'/usr/bin:/bin','XDG_RUNTIME_DIR'=>$runtime,'DBUS_SESSION_BUS_ADDRESS'=>'unix:path=' . $runtime . '/bus'], NULL, 10))->run();
      file_put_contents($status, json_encode(['state' => 'cancelled'], JSON_THROW_ON_ERROR) . "\n", LOCK_EX); chmod($status, 0600);
    }
    elseif (str_ends_with($action, 'start')) {
      $device = (string) $this->exportValue('verification_device');
      $options = $this->deviceOptions();
      if ($device === '' || !isset($options[$device])) { $this->setElementError('verification_device', E::ts('Choose a recipient device.')); return; }
      [$targetUserId, $targetDeviceId] = $this->decodeDeviceKey($device);
      $targetDeviceName = (string) $options[$device];
      $uid = posix_geteuid(); $runtime = '/run/user/' . $uid;
      (new Process(['/usr/bin/systemctl','--user','stop','civineonotify-verify-' . $this->id], NULL, ['PATH'=>'/usr/bin:/bin','XDG_RUNTIME_DIR'=>$runtime,'DBUS_SESSION_BUS_ADDRESS'=>'unix:path=' . $runtime . '/bus'], NULL, 10))->run();
      foreach ([$status, $confirm, $cancel] as $file) if (is_file($file) && !unlink($file)) throw new CRM_Core_Exception(E::ts('Could not reset the previous verification.'));
      file_put_contents($status, json_encode(['state' => 'waiting', 'phase' => 'starting'], JSON_THROW_ON_ERROR) . "\n", LOCK_EX); chmod($status, 0600);
      $agent = dirname(__DIR__, 3) . '/bin/matrix-nio-verify';
      $args = ['/usr/bin/systemd-run','--user','--no-block','--collect','--unit','civineonotify-verify-' . $this->id,'--property=RuntimeMaxSec=5min','--property=MemoryMax=350M','--',(string) $this->config['python_path'],$agent,'--homeserver',(string) $this->config['homeserver'],'--session-file',(string) $this->config['session_path'],'--store-path',(string) $this->config['store_path'],'--target-user-id',$targetUserId,'--target-device-id',$targetDeviceId,'--status-file',$status,'--confirm-file',$confirm,'--cancel-file',$cancel];
      $launch = new Process($args, NULL, ['PATH'=>'/usr/bin:/bin','XDG_RUNTIME_DIR'=>$runtime,'DBUS_SESSION_BUS_ADDRESS'=>'unix:path=' . $runtime . '/bus'], NULL, 10); $launch->run();
      if (!$launch->isSuccessful()) { file_put_contents($status, json_encode(['state' => 'failed'], JSON_THROW_ON_ERROR) . "\n", LOCK_EX); chmod($status, 0600); }
      $this->config['verification_user_id'] = $targetUserId; $this->config['verification_device_id'] = $targetDeviceId; $this->config['verification_device_name'] = $targetDeviceName;
    }
    $this->config['verification_status_file'] = $status; \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id','=',$this->id]])->setValues(['config_json'=>$this->config])->execute();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/verify-matrix-nio',['reset'=>1,'id'=>$this->id]));
  }
  /** @return array<string, string> */
  private function deviceOptions(): array {
    $verification = $this->readStatus(); $active = in_array($verification['state'] ?? NULL, ['waiting', 'compare', 'confirming'], TRUE);
    if ($active && is_string($this->config['verification_device_id'] ?? NULL) && is_string($this->config['verification_user_id'] ?? NULL)) {
      $key = $this->deviceKey($this->config['verification_user_id'], $this->config['verification_device_id']);
      return [$key => (string) ($this->config['verification_device_name'] ?? $this->config['verification_device_id'])];
    }
    $agent = dirname(__DIR__, 3) . '/bin/matrix-nio-devices';
    $roomId = $this->defaultRoomId();
    if (!is_file($agent) || !is_readable($agent) || $roomId === '') return ['' => E::ts('No recipient devices are available.')];
    $args = [(string) ($this->config['python_path'] ?? ''), $agent, '--homeserver', (string) ($this->config['homeserver'] ?? ''), '--session-file', (string) ($this->config['session_path'] ?? ''), '--store-path', (string) ($this->config['store_path'] ?? ''), '--room-id', $roomId];
    $process = new Process($args, NULL, ['PATH'=>'/usr/bin:/bin'], NULL, 30); $process->run();
    $payload = json_decode($process->getOutput(), TRUE);
    $options = ['' => E::ts('- choose a recipient device -')];
    foreach (is_array($payload['devices'] ?? NULL) ? $payload['devices'] : [] as $device) {
      if (!is_array($device) || !is_string($device['user_id'] ?? NULL) || !is_string($device['id'] ?? NULL)) continue;
      $name = (string) ($device['name'] ?? $device['id']);
      $options[$this->deviceKey($device['user_id'], $device['id'])] = $device['user_id'] . ' — ' . $name;
    }
    return $options;
  }
  private function defaultRoomId(): string {
    $destinationId = (int) ($this->transport['default_destination_id'] ?? 0);
    if (!$destinationId) return '';
    $destination = \Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $destinationId)->addWhere('transport_id', '=', $this->id)->execute()->first() ?? [];
    $config = is_array($destination['config_json'] ?? NULL) ? $destination['config_json'] : [];
    return (string) ($config['room_id'] ?? '');
  }
  private function deviceKey(string $userId, string $deviceId): string { return rtrim(strtr(base64_encode(json_encode([$userId, $deviceId], JSON_THROW_ON_ERROR)), '+/', '-_'), '='); }
  /** @return array{string, string} */
  private function decodeDeviceKey(string $value): array {
    $json = base64_decode(strtr($value . str_repeat('=', (4 - strlen($value) % 4) % 4), '-_', '+/'), TRUE);
    $parts = is_string($json) ? json_decode($json, TRUE) : NULL;
    if (!is_array($parts) || !is_string($parts[0] ?? NULL) || !is_string($parts[1] ?? NULL)) throw new \InvalidArgumentException('Invalid Matrix recipient device.');
    return [$parts[0], $parts[1]];
  }
  private function readStatus(): array { $f=(string)($this->config['verification_status_file']??''); if (!$f || !is_readable($f)) return []; $v=json_decode((string)file_get_contents($f),TRUE); return is_array($v)?$v:[]; }
}
