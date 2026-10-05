<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Transport extends CRM_Core_Form {
  private ?int $id = NULL;
  public function preProcess(): void { $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); $this->id = $id ? (int) $id : NULL; civineonotify_appendBreadcrumb('Transports', 'civicrm/admin/civineonotify/transports'); }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit transport' : 'Add transport'));
    $this->add('text', 'name', E::ts('Transport name'), ['size' => 70], TRUE);
    $this->add('select', 'transport_type', E::ts('Transport type'), ['matrix_commander_rs' => E::ts('Matrix')], TRUE);
    foreach (['binary_path' => 'Matrix Commander binary', 'credentials_path' => 'Credentials file', 'store_path' => 'Encryption store', 'working_directory' => 'Working directory', 'timeout_seconds' => 'Timeout (seconds)'] as $field => $label) $this->add('text', $field, E::ts($label), ['size' => 70], $field === 'binary_path');
    $this->add('select', 'default_destination_id', E::ts('Default destination'), civineonotify_destinationOptions(TRUE));
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 3, 'cols' => 70]);
    $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Save transport'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
  }
  public function setDefaultValues(): array {
    if (!$this->id) return ['is_active' => 1, 'transport_type' => 'matrix_commander_rs', 'timeout_seconds' => 30, 'working_directory' => '/tmp'];
    $row = \Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    return array_merge($row, is_array($row['config_json'] ?? NULL) ? $row['config_json'] : []);
  }
  public function postProcess(): void {
    $v = $this->exportValues();
    foreach (['binary_path', 'credentials_path', 'store_path', 'working_directory'] as $path) if (empty($v[$path]) || !str_starts_with((string) $v[$path], '/')) { $this->setElementError($path, E::ts('Use an absolute path.')); return; }
    $config = array_intersect_key($v, array_flip(['binary_path', 'credentials_path', 'store_path', 'working_directory', 'timeout_seconds']));
    $record = ['name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'transport_type' => (string) $v['transport_type'], 'config_json' => $config, 'default_destination_id' => (int) ($v['default_destination_id'] ?? 0) ?: NULL, 'is_active' => !empty($v['is_active'])];
    if ($this->id) \Civi\Api4\CiviNeoNotifyTransport::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute(); else \Civi\Api4\CiviNeoNotifyTransport::create(FALSE)->setValues($record)->execute();
    CRM_Core_Session::setStatus(E::ts('Transport saved.'), E::ts('Saved'), 'success'); CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/transports', ['reset' => 1]));
  }
}
