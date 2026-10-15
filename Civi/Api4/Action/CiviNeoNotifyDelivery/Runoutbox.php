<?php

declare(strict_types=1);

namespace Civi\Api4\Action\CiviNeoNotifyDelivery;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * CiviCRM 6.16 supports APIv4 Scheduled Jobs but predates
 * Generic\AbstractScheduledJob. Keep the scheduler properties here until the
 * extension's minimum CiviCRM version permits using that helper.
 *
 * @method int getBatchSize()
 * @method $this setBatchSize(int $batchSize)
 * @method bool getRunInNonProductionEnvironment()
 * @method $this setRunInNonProductionEnvironment(bool $runInNonProductionEnvironment)
 */
final class Runoutbox extends AbstractAction {
  protected int $batchSize = 25;
  protected bool $runInNonProductionEnvironment = FALSE;

  public function _run(Result $result): void {
    $result[] = \Civi::service('civineonotify.worker')->process(max(1, min(100, $this->batchSize)));
  }
}
