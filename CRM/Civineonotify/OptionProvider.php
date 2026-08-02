<?php

declare(strict_types=1);

final class CRM_Civineonotify_OptionProvider {
  public static function connections(): array {
    $options = [];
    foreach (\Civi\Api4\MatrixConnection::get(FALSE)->addWhere('is_active', '=', TRUE)->addOrderBy('name')->execute() as $row) $options[(int) $row['id']] = $row['name'];
    return $options;
  }
  public static function rooms(): array {
    $options = [];
    foreach (\Civi\Api4\MatrixRoom::get(FALSE)->addWhere('is_active', '=', TRUE)->addOrderBy('name')->execute() as $row) $options[(int) $row['id']] = $row['name'];
    return $options;
  }
}
