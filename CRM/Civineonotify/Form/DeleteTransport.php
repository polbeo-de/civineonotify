<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

/** Explicit, CSRF-protected destructive action for a transport and its delivery history. */
final class CRM_Civineonotify_Form_DeleteTransport extends CRM_Core_Form {
  private int $id;
  private array $transport;

  public function preProcess(): void {
    $this->id = (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->transport = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    if (!$this->transport) throw new CRM_Core_Exception(E::ts('Transport not found.'));
    civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports');
    civineonotify_appendBreadcrumb('Delete transport');
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts('Delete transport'));
    $this->assign('transportName', (string) $this->transport['name']);
    $this->assign('dependencies', $this->dependencyCounts());
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Delete transport'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
  }

  public function postProcess(): void {
    try {
      $transaction = new CRM_Core_Transaction();
      // Delivery records deliberately use RESTRICT foreign keys. Delete them
      // explicitly, after the confirmation screen has disclosed the count.
      \Civi\Api4\CiviNeoNotifyDelivery::delete(FALSE)->addWhere('transport_id', '=', $this->id)->execute();
      \Civi\Api4\CiviNeoNotifyTransport::delete(FALSE)->addWhere('id', '=', $this->id)->execute();
      $transaction->commit();
      CRM_Core_Session::setStatus(E::ts('Transport deleted.'), E::ts('Deleted'), 'success');
    }
    catch (\Throwable $exception) {
      if (isset($transaction)) $transaction->rollback();
      \Civi::log()->warning('CiviNeo Notify transport deletion failed.', ['transport_id' => $this->id, 'error_class' => get_class($exception)]);
      CRM_Core_Session::setStatus(E::ts('The transport could not be deleted.'), E::ts('Delete failed'), 'error');
    }
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/transports', ['reset' => 1]));
  }

  private function dependencyCounts(): array {
    $counts = [];
    foreach ([
      'destinations' => 'civicrm_civineonotify_destination',
      'rules' => 'civicrm_civineonotify_rule',
      'deliveries' => 'civicrm_civineonotify_delivery',
    ] as $key => $table) {
      $dao = CRM_Core_DAO::executeQuery('SELECT COUNT(*) AS count FROM ' . $table . ' WHERE transport_id = %1', [1 => [$this->id, 'Integer']]);
      $counts[$key] = $dao->fetch() ? (int) $dao->count : 0;
    }
    return $counts;
  }
}
