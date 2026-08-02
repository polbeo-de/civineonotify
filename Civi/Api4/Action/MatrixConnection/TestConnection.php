<?php
declare(strict_types=1);
namespace Civi\Api4\Action\MatrixConnection;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civineonotify\Transport\MatrixCommanderRsTransport;
final class TestConnection extends AbstractAction { protected int $id; public function _run(Result $result): void { $connection=\Civi\Api4\MatrixConnection::get(FALSE)->addWhere('id','=',$this->id)->execute()->first(); if (!$connection) throw new \CRM_Core_Exception('Matrix connection not found.'); $r=(new MatrixCommanderRsTransport($connection))->testConnection($connection); $result[]=['success'=>$r->success,'category'=>$r->category,'message'=>$r->message]; } }
