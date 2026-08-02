<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Admin extends CRM_Core_Form {
  private string $view;
  private string $entity;

  public function preProcess(): void {
    $path = (string) ($_GET['q'] ?? '');
    $this->view = str_contains($path, '/connections') ? 'connections' : (str_contains($path, '/rooms') ? 'rooms' : (str_contains($path, '/rules') ? 'rules' : 'log'));
    $this->entity = ['connections' => 'MatrixConnection', 'rooms' => 'MatrixRoom', 'rules' => 'MatrixNotificationRule', 'log' => 'MatrixNotification'][$this->view];
    civineonotify_appendBreadcrumb(ucfirst($this->view === 'log' ? 'Delivery log' : $this->view));
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('CiviNeo Notify: %1', [1 => ucfirst($this->view === 'log' ? 'Delivery log' : $this->view)]));
    $this->performAction();
    $query = call_user_func(["Civi\\Api4\\{$this->entity}", 'get'], FALSE)->setLimit(100);
    if ($this->view !== 'log') $query->addWhere('is_active', '=', TRUE);
    $this->assign('civineonotifyView', $this->view);
    $this->assign('civineonotifyRows', $query->execute());
  }

  public function setDefaultValues(): array {
    return [];
  }

  private function performAction(): void {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $action = (string) ($_GET['action'] ?? '');
    if (!$id || !$action) return;
    if ($this->entity === 'MatrixConnection' && $action === 'test' && CRM_Core_Permission::check('send matrix test messages')) {
      $test = \Civi\Api4\MatrixConnection::testConnection(FALSE)->setId($id)->execute()->first();
      CRM_Core_Session::setStatus(($test['success'] ?? FALSE) ? E::ts('Matrix connection test succeeded.') : E::ts('Matrix connection test failed: %1', [1 => $test['category'] ?? 'unknown']));
    }
    if ($this->entity === 'MatrixRoom' && $action === 'test' && CRM_Core_Permission::check('send matrix test messages')) {
      $test = \Civi\Api4\MatrixRoom::testRoom(FALSE)->setId($id)->execute()->first();
      CRM_Core_Session::setStatus(($test['success'] ?? FALSE) ? E::ts('Matrix test message sent.') : E::ts('Matrix test message failed: %1', [1 => $test['category'] ?? 'unknown']));
    }
    if ($this->entity === 'MatrixNotification' && in_array($action, ['cancel', 'retry'], TRUE) && CRM_Core_Permission::check('retry matrix notifications')) {
      call_user_func([\Civi\Api4\MatrixNotification::class, $action], FALSE)->setId($id)->execute();
    }
  }
}
