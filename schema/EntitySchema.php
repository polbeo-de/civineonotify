<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Schema;

final class EntitySchema {
  public static function withTitles(array $fields): array {
    foreach ($fields as $name => &$field) {
      $field += ['title' => ucwords(str_replace('_', ' ', (string) $name))];
    }
    unset($field);
    return $fields;
  }
}
