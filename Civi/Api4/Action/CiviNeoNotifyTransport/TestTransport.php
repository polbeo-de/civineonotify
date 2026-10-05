<?php

declare(strict_types=1);

namespace Civi\Api4\Action\CiviNeoNotifyTransport;

use Civi\Api4\CiviNeoNotifyTransport;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civineonotify\Transport\TransportFactory;

final class TestTransport extends AbstractAction {
  protected int $id;

  public function _run(Result $result): void {
    $transport = CiviNeoNotifyTransport::get(FALSE)->addWhere('id', '=', $this->id)->execute()->first();
    if (!$transport || empty($transport['is_active'])) throw new \CRM_Core_Exception('Transport not found or inactive.');
    $config = is_array($transport['config_json'] ?? NULL) ? $transport['config_json'] : [];
    try { $response = TransportFactory::create((string) $transport['transport_type'], $config)->testConnection($config); }
    catch (\Throwable) { throw new \CRM_Core_Exception('This transport type does not support connection tests.'); }
    $result[] = ['success' => $response->success, 'category' => $response->category, 'message' => $response->message];
  }
}
