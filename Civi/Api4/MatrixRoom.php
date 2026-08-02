<?php

declare(strict_types=1);

namespace Civi\Api4;

final class MatrixRoom extends Generic\DAOEntity {
  public static function testRoom(bool $checkPermissions = TRUE): Action\MatrixRoom\TestRoom {
    return (new Action\MatrixRoom\TestRoom(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
  }

  public static function permissions(): array {
    return ['meta' => ['access CiviCRM'], 'default' => ['administer matrix notify'], 'testRoom' => ['send matrix test messages']];
  }
}
