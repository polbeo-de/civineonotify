<?php
declare(strict_types=1);
namespace Civi\Api4;
final class MatrixConnection extends Generic\DAOEntity { public static function testConnection(bool $checkPermissions = TRUE): Action\MatrixConnection\TestConnection { return (new Action\MatrixConnection\TestConnection(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions); } public static function permissions(): array { return ['meta'=>['access CiviCRM'],'default'=>['administer matrix notify'],'testConnection'=>['send matrix test messages']]; } }
