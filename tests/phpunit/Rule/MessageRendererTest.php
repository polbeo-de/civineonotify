<?php
declare(strict_types=1);
use Civi\Civineonotify\Event\NormalizedEvent;
use Civi\Civineonotify\Rule\MessageRenderer;
use PHPUnit\Framework\TestCase;
final class MessageRendererTest extends TestCase {
  public function testRendersAllowedTokens(): void { $e=new NormalizedEvent('civicrm.contact.create','test','Contact',7,'create',new DateTimeImmutable(),NULL,['subject'=>'Hello'],[],[],'x'); self::assertSame('Contact #7: Hello',(new MessageRenderer())->render('{entity.type} #{entity.id}: {data.subject}',$e)); }
  public function testRejectsUnknownToken(): void { $this->expectException(InvalidArgumentException::class); (new MessageRenderer())->validate('{data.password}'); }
  public function testRendersCaseStatusAndContactTokens(): void {
    $event = new NormalizedEvent('civicrm.case.edit', 'test', 'Case', 2, 'edit', new DateTimeImmutable(), NULL, ['status_label' => 'Closed', 'contact_first_name' => 'Ada', 'contact_last_name' => 'Lovelace'], ['status_label' => 'Open'], [], 'x');
    self::assertSame('Case 2: Open → Closed (Ada Lovelace)', (new MessageRenderer())->render('Case {case.id}: {case.status.old} → {case.status.new} ({case.contact.first_name} {case.contact.last_name})', $event));
  }
}
