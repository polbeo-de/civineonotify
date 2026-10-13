<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

final class MatrixTransportResult { public function __construct(public readonly bool $success, public readonly string $category = 'process_error', public readonly ?string $externalId = NULL, public readonly ?int $exitCode = NULL, public readonly ?string $message = NULL, public readonly bool $deliveryUnknown = FALSE) {} }
