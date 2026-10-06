<?php

declare(strict_types=1);

final class CRM_Civineonotify_OptionProvider {
  public static function transports(): array {
    $options = [];
    foreach (\Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('is_active', '=', TRUE)->addOrderBy('name')->execute() as $row) $options[(int) $row['id']] = $row['name'];
    return $options;
  }
  public static function destinations(): array {
    $options = [];
    foreach (\Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('is_active', '=', TRUE)->addOrderBy('name')->execute() as $row) $options[(int) $row['id']] = $row['name'];
    return $options;
  }
}
