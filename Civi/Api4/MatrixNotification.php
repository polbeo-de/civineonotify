<?php
declare(strict_types=1);
namespace Civi\Api4;
final class MatrixNotification extends Generic\DAOEntity {
  public static function process(bool $checkPermissions = TRUE): Action\MatrixNotification\Process { return (new Action\MatrixNotification\Process(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function runOutbox(bool $checkPermissions = TRUE): Action\MatrixNotification\Runoutbox { return (new Action\MatrixNotification\Runoutbox(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function retry(bool $checkPermissions = TRUE): Action\MatrixNotification\Retry { return (new Action\MatrixNotification\Retry(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function cancel(bool $checkPermissions = TRUE): Action\MatrixNotification\Cancel { return (new Action\MatrixNotification\Cancel(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); }
  public static function permissions(): array { return ['meta'=>['access CiviCRM'],'default'=>['administer matrix notify'],'get'=>['view matrix notification log'],'process'=>['administer matrix notify'],'runOutbox'=>['administer matrix notify'],'retry'=>['retry matrix notifications'],'cancel'=>['retry matrix notifications']]; }
}
