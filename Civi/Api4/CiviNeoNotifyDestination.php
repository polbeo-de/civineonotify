<?php

declare(strict_types=1);

namespace Civi\Api4;

final class CiviNeoNotifyDestination extends Generic\DAOEntity {
  public static function testDestination(bool $checkPermissions = TRUE): Action\CiviNeoNotifyDestination\TestDestination {
    return (new Action\CiviNeoNotifyDestination\TestDestination(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    return ['meta' => ['access CiviCRM'], 'default' => ['administer civineo notify'], 'testDestination' => ['send civineo notify test messages']];
  }
}
