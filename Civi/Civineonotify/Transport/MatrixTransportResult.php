<?php

declare(strict_types=1);

namespace Civi\Civineonotify\Transport;

use CRM_Civineonotify_ExtensionUtil as E;

final class MatrixTransportResult {
  public readonly bool $success;
  public readonly string $category;
  public readonly ?string $externalId;
  public readonly ?int $exitCode;
  public readonly ?string $message;
  public readonly bool $deliveryUnknown;

  public function __construct(
    bool $success,
    string $category = 'process_error',
    ?string $externalId = NULL,
    ?int $exitCode = NULL,
    ?string $message = NULL,
    bool $deliveryUnknown = FALSE,
  ) {
    $this->success = $success;
    $this->category = $category;
    $this->externalId = $externalId;
    $this->exitCode = $exitCode;
    // Transport implementations supply stable, non-secret diagnostic keys.
    // Translate only at this presentation boundary, never external responses.
    $this->message = $message === NULL ? NULL : E::ts($message);
    $this->deliveryUnknown = $deliveryUnknown;
  }
}
