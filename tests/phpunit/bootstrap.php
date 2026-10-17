<?php
declare(strict_types=1);

// Unit tests intentionally run without a CiviCRM bootstrap. This mirrors the
// ExtensionUtil substitution behaviour closely enough for translated errors.
if (!class_exists('CRM_Civineonotify_ExtensionUtil')) {
  final class CRM_Civineonotify_ExtensionUtil {
    public static function ts(string $message, array $params = []): string {
      foreach ($params as $key => $value) {
        $message = str_replace('%' . $key, (string) $value, $message);
      }
      return $message;
    }
  }
}

require_once __DIR__ . '/../../vendor/autoload.php';
