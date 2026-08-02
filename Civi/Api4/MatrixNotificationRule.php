<?php
declare(strict_types=1);
namespace Civi\Api4;
final class MatrixNotificationRule extends Generic\DAOEntity { public static function permissions(): array { return ['meta'=>['access CiviCRM'],'default'=>['administer matrix notify']]; } }
