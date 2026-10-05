<?php

declare(strict_types=1);

namespace Civi\Api4;

final class CiviNeoNotifyDelivery extends Generic\DAOEntity {
  public static function process(bool $checkPermissions = TRUE): Action\CiviNeoNotifyDelivery\Process { return (new Action\CiviNeoNotifyDelivery\Process(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function runOutbox(bool $checkPermissions = TRUE): Action\CiviNeoNotifyDelivery\Runoutbox { return (new Action\CiviNeoNotifyDelivery\Runoutbox(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function retry(bool $checkPermissions = TRUE): Action\CiviNeoNotifyDelivery\Retry { return (new Action\CiviNeoNotifyDelivery\Retry(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function cancel(bool $checkPermissions = TRUE): Action\CiviNeoNotifyDelivery\Cancel { return (new Action\CiviNeoNotifyDelivery\Cancel(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function permissions(): array {
    return ['meta' => ['access CiviCRM'], 'default' => ['administer civineo notify'], 'get' => ['view civineo notify delivery log'], 'process' => ['administer civineo notify'], 'runOutbox' => ['administer civineo notify'], 'retry' => ['retry civineo notify deliveries'], 'cancel' => ['retry civineo notify deliveries']];
  }
}
