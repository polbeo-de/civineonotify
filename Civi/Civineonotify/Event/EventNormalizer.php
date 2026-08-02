<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Event;

final class EventNormalizer {
  public function fromPost(string $operation, string $entityType, int|string $entityId, mixed $object, array $previousData = []): NormalizedEvent {
    $operation = strtolower($operation);
    $type = strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $entityType) ?: $entityType);
    $data = is_object($object) ? get_object_vars($object) : (is_array($object) ? $object : []);
    if ($entityType === 'Case') {
      [$caseData, $previousData] = $this->enrichCase((int) $entityId, $previousData);
      $data = array_merge($caseData, $data);
    }
    return new NormalizedEvent(
      "civicrm.{$type}.{$operation}", 'hook_civicrm_post', $entityType, $entityId, $operation,
      new \DateTimeImmutable('now', new \DateTimeZone('UTC')), $this->actorId(), $data, $previousData,
      ['hook' => 'civicrm_post'], bin2hex(random_bytes(16)), 'civicrm'
    );
  }
  private function actorId(): ?int { return defined('CRM_Core_Session::USER_ID') ? (int) CRM_Core_Session::getLoggedInContactID() : NULL; }

  /** Adds presentation-safe case data without putting contact data in the outbox payload. */
  private function enrichCase(int $caseId, array $previousData): array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT c.status_id, cc.contact_id, contact.first_name, contact.last_name
       FROM civicrm_case c
       LEFT JOIN civicrm_case_contact cc ON cc.case_id = c.id
       LEFT JOIN civicrm_contact contact ON contact.id = cc.contact_id
       WHERE c.id = %1 ORDER BY cc.contact_id ASC LIMIT 1',
      [1 => [$caseId, 'Integer']]
    );
    $data = [];
    if ($dao->fetch()) {
      $data = [
        'case_id' => $caseId, 'status_id' => $dao->status_id,
        'status_label' => $this->statusLabel($dao->status_id), 'contact_id' => $dao->contact_id,
        'contact_first_name' => $dao->first_name, 'contact_last_name' => $dao->last_name,
      ];
    }
    if (array_key_exists('status_id', $previousData)) $previousData['status_label'] = $this->statusLabel($previousData['status_id']);
    return [$data, $previousData];
  }

  private function statusLabel(mixed $statusId): string {
    return (string) (\CRM_Case_PseudoConstant::caseStatus('label', FALSE)[$statusId] ?? $statusId ?? '');
  }
}
