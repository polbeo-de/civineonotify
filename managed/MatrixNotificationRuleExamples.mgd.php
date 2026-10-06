<?php
declare(strict_types=1);
return array_map(static fn(array $rule): array => ['name' => 'CiviNeoNotifyRule_' . $rule['name'], 'entity' => 'CiviNeoNotifyRule', 'cleanup' => 'unused', 'update' => 'never', 'params' => $rule], [
  ['name'=>'Example new contact','event_pattern'=>'civicrm.contact.create','is_active'=>FALSE,'message_template'=>'{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.','priority'=>100],
  ['name'=>'Example new activity','event_pattern'=>'civicrm.activity.create','is_active'=>FALSE,'message_template'=>'{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.','priority'=>100],
  ['name'=>'Example case changed','event_pattern'=>'civicrm.case.edit','is_active'=>FALSE,'message_template'=>'{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.','priority'=>100],
  ['name'=>'Example contribution created','event_pattern'=>'civicrm.contribution.create','is_active'=>FALSE,'message_template'=>'{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.','priority'=>100],
  ['name'=>'Example event registration','event_pattern'=>'civicrm.participant.create','is_active'=>FALSE,'message_template'=>'{event.name}\n\n{entity.type} #{entity.id} was changed in {site.name}.','priority'=>100],
]);
