<?php
declare(strict_types=1);
namespace Civi\Api4\Action\CiviNeoNotifyDelivery;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
final class Cancel extends AbstractAction { protected int $id; public function _run(Result $result): void { \Civi\Api4\CiviNeoNotifyDelivery::update(FALSE)->addWhere('id','=',$this->id)->addWhere('status','IN',['pending','retry'])->setValues(['status'=>'cancelled'])->execute(); $result[]=['id'=>$this->id,'status'=>'cancelled']; } }
