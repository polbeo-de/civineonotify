<?php

declare(strict_types=1);

/** Forward-only migration from the Matrix-alpha schema to generic transports. */
final class CRM_Civineonotify_Upgrader extends CRM_Extension_Upgrader_Base {
  public function upgrade_1000(): bool {
    $this->renameTable('civicrm_matrix_connection', 'civicrm_civineonotify_transport');
    $this->renameTable('civicrm_matrix_room', 'civicrm_civineonotify_destination');
    $this->renameTable('civicrm_matrix_notification_rule', 'civicrm_civineonotify_rule');
    $this->renameTable('civicrm_matrix_notification', 'civicrm_civineonotify_delivery');
    if (!$this->tableExists('civicrm_civineonotify_transport')) return TRUE;
    $this->renameColumn('civicrm_civineonotify_transport', 'transport', 'transport_type', "varchar(64) NOT NULL DEFAULT 'matrix_commander_rs'");
    $this->addSchemaColumn('civicrm_civineonotify_transport', 'config_json', 'TEXT NULL');
    $this->addSchemaColumn('civicrm_civineonotify_transport', 'default_destination_id', 'INT UNSIGNED NULL');
    $this->renameColumn('civicrm_civineonotify_destination', 'connection_id', 'transport_id', 'INT UNSIGNED NOT NULL');
    $this->addSchemaColumn('civicrm_civineonotify_destination', 'destination_type', "varchar(64) NOT NULL DEFAULT 'matrix_room'");
    $this->addSchemaColumn('civicrm_civineonotify_destination', 'config_json', 'TEXT NULL');
    $this->renameColumn('civicrm_civineonotify_rule', 'connection_id', 'transport_id', 'INT UNSIGNED NULL');
    $this->renameColumn('civicrm_civineonotify_rule', 'room_id', 'destination_id', 'INT UNSIGNED NULL');
    $this->renameColumn('civicrm_civineonotify_delivery', 'connection_id', 'transport_id', 'INT UNSIGNED NOT NULL');
    $this->renameColumn('civicrm_civineonotify_delivery', 'room_id', 'destination_id', 'INT UNSIGNED NOT NULL');
    $this->renameColumn('civicrm_civineonotify_delivery', 'matrix_event_id', 'external_id', 'varchar(255) NULL');
    $this->migrateMatrixConfiguration();
    return TRUE;
  }

  /** Move the managed outbox job to its APIv4 action without discarding settings. */
  public function upgrade_1001(): bool {
    $jobs = CRM_Core_DAO::executeQuery('SELECT id, parameters FROM civicrm_job WHERE api_entity = %1 AND api_action = %2', [
      1 => ['CiviNeoNotifyDelivery', 'String'],
      2 => ['runOutbox', 'String'],
    ]);
    while ($jobs->fetch()) {
      $parameters = $this->withApiV4Version((string) ($jobs->parameters ?? ''));
      CRM_Core_DAO::executeQuery('UPDATE civicrm_job SET parameters = %1 WHERE id = %2', [
        1 => [$parameters, 'String'],
        2 => [(int) $jobs->id, 'Integer'],
      ]);
    }
    return TRUE;
  }

  private function migrateMatrixConfiguration(): void {
    $transport = CRM_Core_DAO::executeQuery('SELECT id, binary_path, credentials_path, store_path, config_path, working_directory, timeout_seconds, options, config_json FROM civicrm_civineonotify_transport');
    while ($transport->fetch()) {
      if ($transport->config_json) continue;
      $config = ['binary_path' => $transport->binary_path, 'credentials_path' => $transport->credentials_path, 'store_path' => $transport->store_path, 'config_path' => $transport->config_path, 'working_directory' => $transport->working_directory, 'timeout_seconds' => (int) $transport->timeout_seconds, 'options' => $transport->options ? json_decode((string) $transport->options, TRUE) : NULL];
      CRM_Core_DAO::executeQuery('UPDATE civicrm_civineonotify_transport SET config_json = %1 WHERE id = %2', [1 => [json_encode($config, JSON_UNESCAPED_SLASHES), 'String'], 2 => [(int) $transport->id, 'Integer']]);
    }
    $destination = CRM_Core_DAO::executeQuery('SELECT id, transport_id, room_id, room_alias, is_default, config_json FROM civicrm_civineonotify_destination');
    while ($destination->fetch()) {
      if (!$destination->config_json) CRM_Core_DAO::executeQuery('UPDATE civicrm_civineonotify_destination SET config_json = %1 WHERE id = %2', [1 => [json_encode(['room_id' => $destination->room_id, 'room_alias' => $destination->room_alias], JSON_UNESCAPED_SLASHES), 'String'], 2 => [(int) $destination->id, 'Integer']]);
      if ($destination->is_default) CRM_Core_DAO::executeQuery('UPDATE civicrm_civineonotify_transport SET default_destination_id = COALESCE(default_destination_id, %1) WHERE id = %2', [1 => [(int) $destination->id, 'Integer'], 2 => [(int) $destination->transport_id, 'Integer']]);
    }
  }
  private function withApiV4Version(string $parameters): string {
    $lines = preg_split('/\r?\n/', trim($parameters)) ?: [];
    $withoutVersion = array_values(array_filter($lines, static fn(string $line): bool => preg_match('/^version\s*=/i', trim($line)) !== 1));
    if ($withoutVersion === []) $withoutVersion[] = 'batchSize=25';
    array_unshift($withoutVersion, 'version=4');
    return implode("\n", $withoutVersion);
  }
  private function renameTable(string $old, string $new): void { if ($this->tableExists($old) && !$this->tableExists($new)) CRM_Core_DAO::executeQuery("RENAME TABLE `$old` TO `$new`"); }
  private function tableExists(string $table): bool { return (bool) CRM_Core_DAO::singleValueQuery('SHOW TABLES LIKE %1', [1 => [$table, 'String']]); }
  private function columnExists(string $table, string $column): bool { return (bool) CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `$table` LIKE %1", [1 => [$column, 'String']]); }
  private function addSchemaColumn(string $table, string $column, string $definition): void { if ($this->tableExists($table) && !$this->columnExists($table, $column)) CRM_Core_DAO::executeQuery("ALTER TABLE `$table` ADD COLUMN `$column` $definition"); }
  private function renameColumn(string $table, string $old, string $new, string $definition): void { if ($this->tableExists($table) && $this->columnExists($table, $old) && !$this->columnExists($table, $new)) CRM_Core_DAO::executeQuery("ALTER TABLE `$table` CHANGE COLUMN `$old` `$new` $definition"); }
}
