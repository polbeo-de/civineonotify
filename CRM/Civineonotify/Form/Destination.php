<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Destination extends CRM_Core_Form {
  private ?int $id = NULL;
  public function preProcess(): void { $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE); $this->id = $id ? (int) $id : NULL; civineonotify_appendBreadcrumb('Destinations', 'civicrm/admin/civineonotify/destinations'); }
  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit destination' : 'Add destination'));
    $this->add('select', 'transport_id', E::ts('Transport'), civineonotify_transportOptions(), TRUE, ['class' => 'crm-select2']);
    $this->add('text', 'name', E::ts('Destination name'), ['size' => 70], TRUE); $this->add('text', 'room_id', E::ts('Matrix room ID'), ['size' => 70]); $this->add('text', 'room_alias', E::ts('Matrix room alias'), ['size' => 70]);
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 3, 'cols' => 70]); $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Save destination'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
  }
  public function setDefaultValues(): array { if (!$this->id) return ['is_active' => 1]; $row = \Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? []; return array_merge($row, is_array($row['config_json'] ?? NULL) ? $row['config_json'] : []); }
  public function postProcess(): void {
    $v = $this->exportValues(); if (empty($v['room_id']) && empty($v['room_alias'])) { $this->setElementError('room_id', E::ts('Provide a Matrix room ID or alias.')); return; }
    $record = ['transport_id' => (int) $v['transport_id'], 'name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'destination_type' => 'matrix_room', 'config_json' => ['room_id' => trim((string) ($v['room_id'] ?? '')) ?: NULL, 'room_alias' => trim((string) ($v['room_alias'] ?? '')) ?: NULL], 'is_active' => !empty($v['is_active'])];
    if ($this->id) \Civi\Api4\CiviNeoNotifyDestination::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute(); else \Civi\Api4\CiviNeoNotifyDestination::create(FALSE)->setValues($record)->execute();
    CRM_Core_Session::setStatus(E::ts('Destination saved.'), E::ts('Saved'), 'success'); CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/destinations', ['reset' => 1]));
  }
}
