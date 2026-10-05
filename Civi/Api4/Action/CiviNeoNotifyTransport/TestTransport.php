<?php

declare(strict_types=1);

namespace Civi\Api4\Action\CiviNeoNotifyTransport;

use Civi\Api4\CiviNeoNotifyTransport;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civineonotify\Transport\MatrixCommanderRsTransport;

final class TestTransport extends AbstractAction {
  protected int $id;

  public function _run(Result $result): void {
    $transport = CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first();
    if (!$transport || empty($transport['is_active'])) throw new \CRM_Core_Exception('Transport not found or inactive.');
    if (($transport['transport_type'] ?? NULL) !== 'matrix_commander_rs') throw new \CRM_Core_Exception('This transport type does not yet support connection tests.');
    $config = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
    $response = (new MatrixCommanderRsTransport($config))->testConnection($config);
    $result[] = ['success' => $response->success, 'category' => $response->category, 'message' => $response->message];
  }
}
