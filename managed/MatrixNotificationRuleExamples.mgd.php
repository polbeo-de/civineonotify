<?php

declare(strict_types=1);

use CRM_Civineonotify_ExtensionUtil as E;

return array_map(static fn(array $rule): array => ['name' => 'CiviNeoNotifyRule_' . $rule['name'], 'entity' => 'CiviNeoNotifyRule', 'cleanup' => 'unused', 'update' => 'never', 'params' => $rule], [
  ['name'=>E::ts('Example new contact'),'event_pattern'=>'civicrm.contact.create','is_active'=>FALSE,'message_template'=>E::ts('{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.'),'priority'=>100],
  ['name'=>E::ts('Example new activity'),'event_pattern'=>'civicrm.activity.create','is_active'=>FALSE,'message_template'=>E::ts('{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.'),'priority'=>100],
  ['name'=>E::ts('Example case changed'),'event_pattern'=>'civicrm.case.edit','is_active'=>FALSE,'message_template'=>E::ts('{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.'),'priority'=>100],
  ['name'=>E::ts('Example contribution created'),'event_pattern'=>'civicrm.contribution.create','is_active'=>FALSE,'message_template'=>E::ts('{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.'),'priority'=>100],
  ['name'=>E::ts('Example event registration'),'event_pattern'=>'civicrm.participant.create','is_active'=>FALSE,'message_template'=>E::ts('{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.'),'priority'=>100],
]);
