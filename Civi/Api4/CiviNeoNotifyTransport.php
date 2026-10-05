<?php

declare(strict_types=1);

namespace Civi\Api4;

final class CiviNeoNotifyTransport extends Generic\DAOEntity {
  public static function testTransport(bool $checkPermissions = TRUE): Action\CiviNeoNotifyTransport\TestTransport {
    return (new Action\CiviNeoNotifyTransport\TestTransport(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    return ['meta' => ['access CiviCRM'], 'default' => ['administer civineo notify'], 'testTransport' => ['send civineo notify test messages']];
  }
}
