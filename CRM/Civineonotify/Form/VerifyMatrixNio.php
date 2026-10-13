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
    $this->persistReusableRoom();
    civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports'); civineonotify_appendBreadcrumb('Verify Matrix Nio device');
  }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Verify Matrix Nio device'));
    $verification = $this->readStatus();
    $this->assign('verification', $verification);
    $devices = $this->deviceOptions(); $members = $this->memberOptions();
    $this->add('select', 'verification_method', E::ts('Verification method'), ['device' => E::ts('Another device of this account'), 'room' => E::ts('A member of the default destination')]);
    $this->add('select', 'verification_device_id', E::ts('Element device'), $devices);
    $this->add('select', 'verification_target_user_id', E::ts('Room member'), $members);
    $deviceIds = array_values(array_filter(array_keys($devices)));
    $defaults = ['verification_method' => (string) ($this->config['verification_method'] ?? 'device')];
    if (count($deviceIds) === 1) $defaults['verification_device_id'] = $deviceIds[0];
    $this->setDefaults($defaults);
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
      $method = (string) $this->exportValue('verification_method');
      $device = (string) $this->exportValue('verification_device_id'); $target = (string) $this->exportValue('verification_target_user_id');
      $options = $this->deviceOptions(); $members = $this->memberOptions();
      if ($method === 'device' && ($device === '' || !isset($options[$device]))) { $this->setElementError('verification_device_id', E::ts('Choose an Element device.')); return; }
      if ($method === 'room' && ($target === '' || !isset($members[$target]))) { $this->setElementError('verification_target_user_id', E::ts('Choose a room member.')); return; }
      if (!in_array($method, ['device', 'room'], TRUE)) { $this->setElementError('verification_method', E::ts('Choose a verification method.')); return; }
      $uid = posix_geteuid(); $runtime = '/run/user/' . $uid;
      (new Process(['/usr/bin/systemctl','--user','stop','civineonotify-verify-' . $this->id], NULL, ['PATH'=>'/usr/bin:/bin','XDG_RUNTIME_DIR'=>$runtime,'DBUS_SESSION_BUS_ADDRESS'=>'unix:path=' . $runtime . '/bus'], NULL, 10))->run();
      foreach ([$status, $confirm, $cancel] as $file) if (is_file($file) && !unlink($file)) throw new CRM_Core_Exception(E::ts('Could not reset the previous verification.'));
      file_put_contents($status, json_encode(['state' => 'waiting', 'phase' => 'starting'], JSON_THROW_ON_ERROR) . "\n", LOCK_EX); chmod($status, 0600);
      $agent = dirname(__DIR__, 3) . '/bin/' . ($method === 'room' ? 'matrix-nio-room-verify' : 'matrix-nio-verify');
      $args = ['/usr/bin/systemd-run','--user','--no-block','--collect','--unit','civineonotify-verify-' . $this->id,'--property=RuntimeMaxSec=5min','--property=MemoryMax=350M','--',(string) $this->config['python_path'],$agent,'--homeserver',(string) $this->config['homeserver'],'--session-file',(string) $this->config['session_path'],'--store-path',(string) $this->config['store_path'],'--status-file',$status,'--confirm-file',$confirm,'--cancel-file',$cancel];
      if ($method === 'room') {
        array_push($args, '--target-user-id', $target);
        if (is_string($this->config['verification_room_id'] ?? NULL) && $this->config['verification_room_id'] !== '') array_push($args, '--verification-room-id', $this->config['verification_room_id']);
      }
      else array_push($args, '--target-device-id', $device);
      $launch = new Process($args, NULL, ['PATH'=>'/usr/bin:/bin','XDG_RUNTIME_DIR'=>$runtime,'DBUS_SESSION_BUS_ADDRESS'=>'unix:path=' . $runtime . '/bus'], NULL, 10); $launch->run();
      if (!$launch->isSuccessful()) { file_put_contents($status, json_encode(['state' => 'failed'], JSON_THROW_ON_ERROR) . "\n", LOCK_EX); chmod($status, 0600); }
      $this->config['verification_method'] = $method;
      if ($method === 'room') { $this->config['verification_target_user_id'] = $target; $this->config['verification_target_name'] = $members[$target]; }
      else { $this->config['verification_device_id'] = $device; $this->config['verification_device_name'] = $options[$device]; }
    }
    $this->config['verification_status_file'] = $status; \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id','=',$this->id]])->setValues(['config_json'=>$this->config])->execute();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/verify-matrix-nio',['reset'=>1,'id'=>$this->id]));
  }
  private function deviceOptions(): array {
    $verification = $this->readStatus(); $active = in_array($verification['state'] ?? NULL, ['waiting', 'compare', 'confirming'], TRUE);
    if ($active && is_string($this->config['verification_device_id'] ?? NULL)) return [$this->config['verification_device_id'] => (string) ($this->config['verification_device_name'] ?? $this->config['verification_device_id'])];
    $agent = dirname(__DIR__, 3) . '/bin/matrix-nio-devices';
    if (!is_file($agent) || !is_readable($agent)) return ['' => E::ts('No Element devices are available.')];
    $args = [(string) ($this->config['python_path'] ?? ''), $agent, '--homeserver', (string) ($this->config['homeserver'] ?? ''), '--session-file', (string) ($this->config['session_path'] ?? ''), '--store-path', (string) ($this->config['store_path'] ?? '')];
    $process = new Process($args, NULL, ['PATH'=>'/usr/bin:/bin'], NULL, 30); $process->run();
    $payload = json_decode($process->getOutput(), TRUE);
    $options = ['' => E::ts('- choose an Element device -')];
    foreach (is_array($payload['devices'] ?? NULL) ? $payload['devices'] : [] as $device) if (is_array($device) && is_string($device['id'] ?? NULL)) $options[$device['id']] = (string) ($device['name'] ?? $device['id']);
    return $options;
  }
  private function memberOptions(): array {
    $verification = $this->readStatus(); $active = in_array($verification['state'] ?? NULL, ['waiting', 'compare', 'confirming'], TRUE);
    if ($active && is_string($this->config['verification_target_user_id'] ?? NULL)) return [$this->config['verification_target_user_id'] => (string) ($this->config['verification_target_name'] ?? $this->config['verification_target_user_id'])];
    $room = $this->defaultRoomId(); if ($room === NULL) return ['' => E::ts('No active default Matrix room is configured.')];
    $agent = dirname(__DIR__, 3) . '/bin/matrix-nio-room-members';
    $args = [(string) ($this->config['python_path'] ?? ''), $agent, '--homeserver', (string) ($this->config['homeserver'] ?? ''), '--session-file', (string) ($this->config['session_path'] ?? ''), '--store-path', (string) ($this->config['store_path'] ?? ''), '--room-id', $room];
    $process = new Process($args, NULL, ['PATH'=>'/usr/bin:/bin'], NULL, 30); $process->run(); $payload = json_decode($process->getOutput(), TRUE);
    $options = ['' => E::ts('- choose a room member -')];
    foreach (is_array($payload['members'] ?? NULL) ? $payload['members'] : [] as $member) if (is_array($member) && is_string($member['id'] ?? NULL)) $options[$member['id']] = (string) ($member['name'] ?? $member['id']);
    return $options;
  }
  private function defaultRoomId(): ?string {
    $destinationId = (int) ($this->transport['default_destination_id'] ?? 0); if (!$destinationId) return NULL;
    $destination = \Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $destinationId)->addWhere('transport_id', '=', $this->id)->addWhere('is_active', '=', TRUE)->execute()->first() ?? [];
    $config = is_array($destination['config_json'] ?? NULL) ? $destination['config_json'] : []; $room = (string) ($config['room_id'] ?? '');
    return $room !== '' ? $room : NULL;
  }
  private function persistReusableRoom(): void {
    $status = $this->readStatus(); $room = $status['verification_room_id'] ?? NULL;
    if (!is_string($room) || !str_starts_with($room, '!') || ($this->config['verification_room_id'] ?? NULL) === $room) return;
    $this->config['verification_room_id'] = $room;
    \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id','=',$this->id]])->setValues(['config_json'=>$this->config])->execute();
  }
  private function readStatus(): array { $f=(string)($this->config['verification_status_file']??''); if (!$f || !is_readable($f)) return []; $v=json_decode((string)file_get_contents($f),TRUE); return is_array($v)?$v:[]; }
}
