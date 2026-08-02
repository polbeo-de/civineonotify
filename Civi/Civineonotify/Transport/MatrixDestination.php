<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class MatrixDestination { public function __construct(public readonly ?string $roomId, public readonly ?string $roomAlias) { if (!$roomId && !$roomAlias) { throw new \InvalidArgumentException('Destination has no room.'); } } }
