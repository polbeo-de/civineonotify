<?php
declare(strict_types=1);
use Civi\Civineonotify\Event\NormalizedEvent;
use Civi\Civineonotify\Rule\ConditionEngine;
use PHPUnit\Framework\TestCase;
final class ConditionEngineTest extends TestCase {
  public function testMatchesAllowlistedEventField(): void { $event=new NormalizedEvent('civicrm.contact.create','test','Contact',1,'create',new DateTimeImmutable(),NULL,[],[],[],'x'); self::assertTrue((new ConditionEngine())->matches(['all'=>[['field'=>'entity.type','operator'=>'=','value'=>'Contact']]],$event)); }
  public function testMatchesChangedStatusOnlyWhenValueDiffers(): void {
    $engine = new ConditionEngine();
    $changed = new NormalizedEvent('civicrm.case.edit', 'test', 'Case', 1, 'edit', new DateTimeImmutable(), NULL, ['case_type_id' => 3, 'status_id' => 26], ['case_type_id' => 3, 'status_id' => 25], [], 'x');
    $unchanged = new NormalizedEvent('civicrm.case.edit', 'test', 'Case', 1, 'edit', new DateTimeImmutable(), NULL, ['case_type_id' => 3, 'status_id' => 25], ['case_type_id' => 3, 'status_id' => '25'], [], 'x');
    $rule = ['all' => [['field' => 'data.case_type_id', 'operator' => '=', 'value' => 3], ['field' => 'data.status_id', 'operator' => 'changed']]];
    self::assertTrue($engine->matches($rule, $changed));
    self::assertFalse($engine->matches($rule, $unchanged));
  }
}
