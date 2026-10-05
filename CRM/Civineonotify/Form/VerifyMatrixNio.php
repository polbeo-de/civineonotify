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
    $this->assign('verification', $this->readStatus());
    $this->addButtons([['type'=>'submit','name'=>E::ts('Start verification'),'subName'=>'start'],['type'=>'submit','name'=>E::ts('Confirm matching SAS'),'subName'=>'confirm'],['type'=>'submit','name'=>E::ts('Cancel verification'),'subName'=>'cancel'],['type'=>'cancel','name'=>E::ts('Close')]]);
  }
  public function postProcess(): void {
    $action = (string) ($this->controller->getButtonName() ?? ''); $root = dirname((string) ($this->config['session_path'] ?? ''));
    $status = $root . '/.civineonotify-nio-verify.status'; $confirm = $root . '/.civineonotify-nio-verify.confirm'; $cancel = $root . '/.civineonotify-nio-verify.cancel';
    if (str_ends_with($action, 'confirm') || str_ends_with($action, 'cancel')) { file_put_contents(str_ends_with($action, 'confirm') ? $confirm : $cancel, '1', LOCK_EX); chmod(str_ends_with($action, 'confirm') ? $confirm : $cancel, 0600); }
    elseif (str_ends_with($action, 'start')) {
      $uid = posix_geteuid(); $runtime = '/run/user/' . $uid; $agent = dirname(__DIR__, 3) . '/bin/matrix-nio-verify';
      $args = ['/usr/bin/systemd-run','--user','--no-block','--collect','--unit','civineonotify-verify-' . $this->id,'--property=RuntimeMaxSec=5min','--property=MemoryMax=350M','--',(string) $this->config['python_path'],$agent,'--homeserver',(string) $this->config['homeserver'],'--session-file',(string) $this->config['session_path'],'--store-path',(string) $this->config['store_path'],'--status-file',$status,'--confirm-file',$confirm,'--cancel-file',$cancel];
      (new Process($args, NULL, ['PATH'=>'/usr/bin:/bin','XDG_RUNTIME_DIR'=>$runtime,'DBUS_SESSION_BUS_ADDRESS'=>'unix:path=' . $runtime . '/bus'], NULL, 10))->run();
    }
    $this->config['verification_status_file'] = $status; \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id','=',$this->id]])->setValues(['config_json'=>$this->config])->execute();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/verify-matrix-nio',['reset'=>1,'id'=>$this->id]));
  }
  private function readStatus(): array { $f=(string)($this->config['verification_status_file']??''); if (!$f || !is_readable($f)) return []; $v=json_decode((string)file_get_contents($f),TRUE); return is_array($v)?$v:[]; }
}
