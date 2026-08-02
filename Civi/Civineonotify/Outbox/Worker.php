<?php
declare(strict_types=1);
namespace Civi\Civineonotify\Outbox;
use Civi\Civineonotify\Transport\{MatrixCommanderRsTransport,MatrixDestination,MatrixMessage};
final class Worker {
  public function __construct(private readonly ?RetryPolicy $retryPolicy = NULL) {}
  public function process(int $batchSize): array {
    if (!\Civi::settings()->get('civineonotify_enabled')) return ['processed'=>0,'skipped'=>'disabled'];
    $default = \Civi\Api4\MatrixConnection::get(FALSE)->addWhere('is_active', '=', TRUE)->addWhere('is_default', '=', TRUE)->execute()->first();
    if (!$default || empty($default['store_path'])) return ['processed'=>0,'skipped'=>'no_active_default_connection'];
    $lock = @fopen($default['store_path'] . '/.civineo-notify.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return ['processed'=>0,'skipped'=>'worker_locked'];
    try {
    \Civi\Api4\MatrixNotification::update(FALSE)->addWhere('status', '=', 'processing')->addWhere('last_attempt_at', '<=', gmdate('Y-m-d H:i:s', time() - 900))->setValues(['status' => 'delivery_unknown', 'error_code' => 'unknown_delivery_state', 'error_message' => 'Worker recovery found an unfinished delivery attempt.'])->execute();
    $rows=\Civi\Api4\MatrixNotification::get(FALSE)->addWhere('status','IN',['pending','retry'])->addWhere('next_attempt_at','<=',gmdate('Y-m-d H:i:s'))->setLimit(max(1,min(100,$batchSize)))->execute(); $sent=0;
    foreach($rows as $row){ \Civi\Api4\MatrixNotification::update(FALSE)->addWhere('id','=',$row['id'])->addWhere('status','IN',['pending','retry'])->setValues(['status'=>'processing','last_attempt_at'=>gmdate('Y-m-d H:i:s')])->execute(); $connection=\Civi\Api4\MatrixConnection::get(FALSE)->addWhere('id','=',$row['connection_id'])->execute()->first(); $room=\Civi\Api4\MatrixRoom::get(FALSE)->addWhere('id','=',$row['room_id'])->execute()->first(); if(!$connection||!$room){continue;} $res=(new MatrixCommanderRsTransport($connection))->send(new MatrixMessage($row['message_body']),new MatrixDestination($room['room_id']??NULL,$room['room_alias']??NULL),$row['transaction_id']); $attempt=(int)$row['attempt_count']+1; $policy=$this->retryPolicy ?? new RetryPolicy(); $retry=!$res->success&&!$res->deliveryUnknown&&$attempt<(int)\Civi::settings()->get('civineonotify_max_attempts')&&$policy->isRetryable($res->category); \Civi\Api4\MatrixNotification::update(FALSE)->addWhere('id','=',$row['id'])->setValues(['status'=>$res->success?'sent':($res->deliveryUnknown?'delivery_unknown':($retry?'retry':'failed')),'attempt_count'=>$attempt,'next_attempt_at'=>$retry?$policy->nextAttempt($attempt):NULL,'last_attempt_at'=>gmdate('Y-m-d H:i:s'),'sent_at'=>$res->success?gmdate('Y-m-d H:i:s'):NULL,'matrix_event_id'=>$res->matrixEventId,'exit_code'=>$res->exitCode,'error_code'=>$res->success?NULL:$res->category,'error_message'=>$res->message])->execute(); $sent+=(int)$res->success; }
    return ['processed'=>count($rows),'sent'=>$sent];
    } finally { flock($lock, LOCK_UN); fclose($lock); }
  }
}
