<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Admin extends CRM_Core_Form {
  private string $view;
  private string $entity;

  public function preProcess(): void {
    $path = (string) ($_GET['q'] ?? '');
    $this->view = str_contains($path, '/transports') ? 'transports' : (str_contains($path, '/destinations') ? 'destinations' : (str_contains($path, '/rules') ? 'rules' : 'log'));
    $this->entity = ['transports' => 'CiviNeoNotifyTransport', 'destinations' => 'CiviNeoNotifyDestination', 'rules' => 'CiviNeoNotifyRule', 'log' => 'CiviNeoNotifyDelivery'][$this->view];
    civineonotify_appendBreadcrumb(ucfirst($this->view === 'log' ? 'Delivery log' : $this->view));
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('CiviNeo Notify: %1', [1 => ucfirst($this->view === 'log' ? 'Delivery log' : $this->view)]));
    $this->performAction();
    $query = call_user_func(["Civi\\Api4\\{$this->entity}", 'get'], FALSE)->setLimit(100);
    if ($this->view !== 'log') $query->addWhere('is_active', '=', TRUE);
    $this->assign('civineonotifyView', $this->view);
    $rows = [];
    foreach ($query->execute() as $row) {
      $row = (array) $row;
      if ($this->view === 'transports') $row += $this->transportStatus($row);
      $rows[] = $row;
    }
    $this->assign('civineonotifyRows', $rows);
  }

  public function setDefaultValues(): array {
    return [];
  }

  private function performAction(): void {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $action = (string) ($_GET['action'] ?? '');
    if (!$id || !$action) return;
    if ($this->entity === 'CiviNeoNotifyTransport' && $action === 'test' && CRM_Core_Permission::check('send civineo notify test messages')) {
      $test = \Civi\Api4\CiviNeoNotifyTransport::testTransport(FALSE)->setId($id)->execute()->first();
      if (($test['success'] ?? FALSE)) {
        CRM_Core_Session::setStatus(E::ts('Transport test message sent to its default destination.'), E::ts('Test sent'), 'success');
      }
      elseif (($test['category'] ?? '') === 'default_destination_missing') {
        CRM_Core_Session::setStatus(E::ts('Set an active default destination for this transport before sending a test.'), E::ts('No default destination'), 'error');
      }
      else {
        CRM_Core_Session::setStatus(E::ts('Transport test failed: %1', [1 => $test['category'] ?? 'unknown']), E::ts('Test failed'), 'error');
      }
    }
    if ($this->entity === 'CiviNeoNotifyDestination' && $action === 'test' && CRM_Core_Permission::check('send civineo notify test messages')) {
      $test = \Civi\Api4\CiviNeoNotifyDestination::testDestination(FALSE)->setId($id)->execute()->first();
      CRM_Core_Session::setStatus(($test['success'] ?? FALSE) ? E::ts('Destination test message sent.') : E::ts('Destination test message failed: %1', [1 => $test['category'] ?? 'unknown']));
    }
    if ($this->entity === 'CiviNeoNotifyDelivery' && in_array($action, ['cancel', 'retry'], TRUE) && CRM_Core_Permission::check('retry civineo notify deliveries')) {
      call_user_func([\Civi\Api4\CiviNeoNotifyDelivery::class, $action], FALSE)->setId($id)->execute();
    }
  }

  /** Return only operator-facing state; private paths and credential details never leave this method. */
  private function transportStatus(array $transport): array {
    $type = (string) ($transport['transport_type'] ?? '');
    if (!in_array($type, ['matrix_commander_rs', 'matrix_nio'], TRUE)) {
      return ['provisioning_status' => E::ts('Not applicable'), 'trust_status' => E::ts('Not applicable'), 'is_matrix_nio' => FALSE];
    }
    $config = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
    $provisioning = $this->readProvisioningStatus((string) ($config['provision_status_file'] ?? ''));
    if ($provisioning === '') $provisioning = E::ts('Not provisioned');
    if ($type !== 'matrix_nio') {
      return ['provisioning_status' => $provisioning, 'trust_status' => E::ts('Not applicable'), 'is_matrix_nio' => FALSE];
    }
    $trust = $this->readVerificationStatus((string) ($config['verification_status_file'] ?? ''));
    return ['provisioning_status' => $provisioning, 'trust_status' => $trust, 'is_matrix_nio' => TRUE];
  }

  private function readProvisioningStatus(string $file): string {
    if ($file === '' || !is_readable($file)) return '';
    $status = trim((string) file_get_contents($file));
    $labels = [
      'succeeded' => E::ts('Provisioned'),
      'running' => E::ts('Provisioning in progress'),
      'running:preparing' => E::ts('Provisioning in progress'),
      'running:creating-device' => E::ts('Provisioning in progress'),
      'running:checking-identity' => E::ts('Provisioning in progress'),
      'failed' => E::ts('Provisioning failed'),
      'failed:preparing' => E::ts('Provisioning failed'),
      'failed:creating-device' => E::ts('Provisioning failed'),
      'failed:checking-identity' => E::ts('Provisioning failed'),
    ];
    return $labels[$status] ?? E::ts('Provisioning status unavailable');
  }

  private function readVerificationStatus(string $file): string {
    if ($file === '' || !is_readable($file)) return E::ts('Not verified');
    $status = json_decode((string) file_get_contents($file), TRUE);
    $state = is_array($status) ? (string) ($status['state'] ?? '') : '';
    return match ($state) {
      'verified' => E::ts('Trusted'),
      'waiting', 'compare', 'confirming' => E::ts('Verification in progress'),
      'failed', 'cancelled', 'timed_out' => E::ts('Not trusted'),
      default => E::ts('Not verified'),
    };
  }
}
