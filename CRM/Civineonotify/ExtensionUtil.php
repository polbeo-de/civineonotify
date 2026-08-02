<?php

declare(strict_types=1);

class CRM_Civineonotify_ExtensionUtil {
  public static function ts(string $text, array $params = []): string {
    return ts($text, $params, 'de.polbeo.civicrm.civineonotify');
  }
}
