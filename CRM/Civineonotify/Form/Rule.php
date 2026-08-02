<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

final class CRM_Civineonotify_Form_Rule extends CRM_Core_Form {
  private ?int $id = NULL;

  public function preProcess(): void {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $this->id = $id ? (int) $id : NULL;
    civineonotify_appendBreadcrumb('Rules', 'civicrm/admin/civineonotify/rules');
  }

  public function buildQuickForm(): void {
    CRM_Utils_System::setTitle(E::ts($this->id ? 'Edit Matrix notification rule' : 'Add Matrix notification rule'));
    $this->add('text', 'name', E::ts('Rule name'), ['size' => 70], TRUE);
    $this->add('select', 'event_pattern', E::ts('Event name'), civineonotify_eventOptions(), TRUE, [
      'class' => 'crm-select2 huge',
      'placeholder' => E::ts('- Select an event -'),
    ]);
    $this->add('select', 'connection_id', E::ts('Connection (optional)'), civineonotify_connectionOptions(TRUE));
    $this->add('select', 'room_id', E::ts('Room (optional)'), civineonotify_roomOptions(TRUE));
    $this->add('textarea', 'message_template', E::ts('Message template'), ['rows' => 5, 'cols' => 70], TRUE);
    $this->add('textarea', 'condition_json', E::ts('Conditions (JSON)'), ['rows' => 5, 'cols' => 70]);
    $this->add('text', 'priority', E::ts('Priority'), ['size' => 8]);
    $this->add('checkbox', 'is_active', E::ts('Active'));
    $this->add('checkbox', 'stop_processing', E::ts('Stop after match'));
    $this->addButtons([['type' => 'submit', 'name' => E::ts('Save rule'), 'isDefault' => TRUE], ['type' => 'cancel', 'name' => E::ts('Cancel')]]);
  }

  public function setDefaultValues(): array {
    if (!$this->id) return ['is_active' => 1, 'priority' => 0, 'condition_json' => '{}'];
    $rule = \Civi\Api4\MatrixNotificationRule::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first() ?? [];
    if (isset($rule['condition_json']) && is_array($rule['condition_json'])) $rule['condition_json'] = json_encode($rule['condition_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $rule;
  }

  public function postProcess(): void {
    $v = $this->exportValues();
    try {
      $condition = trim((string) $v['condition_json']) === '' ? [] : json_decode((string) $v['condition_json'], TRUE, 512, JSON_THROW_ON_ERROR);
      (new \Civi\Civineonotify\Rule\ConditionEngine())->validate($condition);
      (new \Civi\Civineonotify\Rule\MessageRenderer())->validate((string) $v['message_template']);
    }
    catch (\Throwable $e) {
      $this->setElementError('condition_json', E::ts('Invalid conditions or message template: %1', [1 => $e->getMessage()]));
      return;
    }
    $record = ['name' => trim((string) $v['name']), 'event_pattern' => trim((string) $v['event_pattern']), 'condition_json' => $condition, 'connection_id' => (int) ($v['connection_id'] ?? 0) ?: NULL, 'room_id' => (int) ($v['room_id'] ?? 0) ?: NULL, 'message_template' => (string) $v['message_template'], 'priority' => (int) $v['priority'], 'is_active' => !empty($v['is_active']), 'stop_processing' => !empty($v['stop_processing'])];
    if ($this->id) \Civi\Api4\MatrixNotificationRule::update(FALSE)->setWhere([['id', '=', $this->id]])->setValues($record)->execute();
    else \Civi\Api4\MatrixNotificationRule::create(FALSE)->setValues($record)->execute();
    CRM_Core_Session::setStatus(E::ts('Matrix notification rule saved.'), E::ts('Saved'), 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/civineonotify/rules', ['reset' => 1]));
  }
}
