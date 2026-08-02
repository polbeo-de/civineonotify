<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Room extends CRM_Core_Form {
  private ?int $id = NULL;

  public function preProcess(): void {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $this->id = $id ? (int) $id : NULL;
    civineonotify_appendBreadcrumb('Rooms', 'civicrm/admin/civineonotify/rooms');
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit Matrix room' : 'Add Matrix room'));
    $this->add('select', 'connection_id', E::ts('Connection'), civineonotify_connectionOptions(), TRUE, ['class' => 'crm-select2']);
    $this->add('text', 'name', E::ts('Room name'), ['size' => 70], TRUE);
    $this->add('text', 'room_id', E::ts('Room ID'), ['size' => 70]);
    $this->add('text', 'room_alias', E::ts('Room alias'), ['size' => 70]);
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 3, 'cols' => 70]);
    $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->add('checkbox', 'is_default', E::ts('Use as default room'));
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Save room'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
  }

  public function setDefaultValues(): array {
    if (!$this->id) return ['is_active' => 1];
    return \Civi\Api4\MatrixRoom::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
  }

  public function postProcess(): void {
    $v = $this->exportValues();
    if (empty($v['room_id']) && empty($v['room_alias'])) {
      $this->setElementError('room_id', E::ts('Provide a room ID or a room alias.'));
      return;
    }
    $record = ['connection_id' => (int) $v['connection_id'], 'name' => trim((string) $v['name']), 'description' => trim((string) ($v['description'] ?? '')), 'room_id' => trim((string) ($v['room_id'] ?? '')) ?: NULL, 'room_alias' => trim((string) ($v['room_alias'] ?? '')) ?: NULL, 'is_active' => !empty($v['is_active']), 'is_default' => !empty($v['is_default'])];
    if ($this->id) \Civi\Api4\MatrixRoom::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute();
    else \Civi\Api4\MatrixRoom::create(FALSE)->setValues($record)->execute();
    CRM_Core_Session::setStatus(E::ts('Matrix room saved.'), E::ts('Saved'), 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/rooms', ['reset' => 1]));
  }
}
