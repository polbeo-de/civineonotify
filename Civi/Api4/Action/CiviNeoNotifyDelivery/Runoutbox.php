<?php

declare(strict_types=1);

namespace Civi\Api4\Action\CiviNeoNotifyDelivery;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/** Name follows CiviCRM's lower-cased scheduled-job action resolver. */
final class Runoutbox extends AbstractAction {
  protected int $batchSize = 25;

  public function _run(Result $result): void {
    $result[] = \Civi::service('civineonotify.worker')->process($this->batchSize);
  }
}
