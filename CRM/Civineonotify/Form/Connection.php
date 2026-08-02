<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Connection extends CRM_Core_Form {
  private ?int $id = NULL;

  public function preProcess(): void {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $this->id = $id ? (int) $id : NULL;
    civineonotify_appendBreadcrumb('Connections', 'civicrm/admin/civineonotify/connections');
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit Matrix connection' : 'Add Matrix connection'));
    foreach (['name' => 'Connection name', 'binary_path' => 'Matrix Commander binary', 'credentials_path' => 'Credentials file', 'store_path' => 'Encryption store', 'working_directory' => 'Working directory', 'timeout_seconds' => 'Timeout (seconds)'] as $field => $label) {
      $this->add('text', $field, E::ts($label), ['size' => 70], $field === 'name');
    }
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 3, 'cols' => 70]);
    $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->add('checkbox', 'is_default', E::ts('Use as default connection'));
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Save connection'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
  }

  public function setDefaultValues(): array {
    if (!$this->id) {
      return ['is_active' => 1, 'timeout_seconds' => 30, 'working_directory' => '/tmp'];
    }
    return \Civi\Api4\MatrixConnection::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    foreach (['binary_path', 'credentials_path', 'store_path', 'working_directory'] as $path) {
      if (empty($values[$path]) || !str_starts_with((string) $values[$path], '/')) {
        $this->setElementError($path, E::ts('Use an absolute path.'));
        return;
      }
    }
    $record = [
      'name' => trim((string) $values['name']), 'description' => trim((string) ($values['description'] ?? '')),
      'transport' => 'matrix_commander_rs', 'binary_path' => trim((string) $values['binary_path']),
      'credentials_path' => trim((string) $values['credentials_path']), 'store_path' => trim((string) $values['store_path']),
      'working_directory' => trim((string) $values['working_directory']), 'timeout_seconds' => max(1, (int) $values['timeout_seconds']),
      'is_active' => !empty($values['is_active']), 'is_default' => !empty($values['is_default']),
    ];
    if ($this->id) \Civi\Api4\MatrixConnection::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute();
    else \Civi\Api4\MatrixConnection::create(FALSE)->setValues($record)->execute();
    CRM_Core_Session::setStatus(E::ts('Matrix connection saved.'), E::ts('Saved'), 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/connections', ['reset' => 1]));
  }
}
