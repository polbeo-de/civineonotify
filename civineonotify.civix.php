<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
  if (str_starts_with($class, 'CiviMix\\Schema\\Civineonotify\\')) {
    pathload()->loadPackage('civimix-schema@5', TRUE);
    CiviMix\Schema\loadClass($class);
  }
}, TRUE, TRUE);
