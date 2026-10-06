<?php
declare(strict_types=1);
namespace Civi\Api4\Action\CiviNeoNotifyDelivery;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
final class Retry extends AbstractAction { protected int $id; public function _run(Result $result): void { \Civi\Api4\CiviNeoNotifyDelivery::update(FALSE)->addWhere('id','=',$this->id)->setValues(['status'=>'pending','next_attempt_at'=>gmdate('Y-m-d H:i:s'),'error_code'=>NULL,'error_message'=>NULL])->execute(); $result[]=['id'=>$this->id,'status'=>'pending']; } }
