<?php

declare(strict_types=1);

require_once __DIR__ . '/civineonotify.civix.php';
if (!in_array(__DIR__, explode(PATH_SEPARATOR, get_include_path()), TRUE)) {
  set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());
}
require_once __DIR__ . '/CRM/Civineonotify/ExtensionUtil.php';
require_once __DIR__ . '/CRM/Civineonotify/OptionProvider.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/Admin.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/Transport.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/ProvisionMatrix.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/VerifyMatrixNio.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/DeleteTransport.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/Destination.php';
require_once __DIR__ . '/CRM/Civineonotify/Form/Rule.php';

use Civi\Civineonotify\Event\PostHookSubscriber;
use Civi\Civineonotify\Outbox\OutboxRepository;
use Civi\Civineonotify\Outbox\Worker;
use Civi\Civineonotify\Outbox\RetryPolicy;
use Civi\Civineonotify\Rule\ConditionEngine;
use Civi\Civineonotify\Rule\DefaultResolver;
use Civi\Civineonotify\Rule\MessageRenderer;
use Civi\Civineonotify\Rule\RuleEngine;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

function civineonotify_civicrm_container(ContainerBuilder $container): void {
  $container->register('civineonotify.condition_engine', ConditionEngine::class);
  $container->register('civineonotify.message_renderer', MessageRenderer::class);
  $container->register('civineonotify.default_resolver', DefaultResolver::class);
  $container->register('civineonotify.outbox_repository', OutboxRepository::class);
  $container->register('civineonotify.retry_policy', RetryPolicy::class);
  $container->register('civineonotify.worker', Worker::class)->setArguments([new Reference('civineonotify.retry_policy')])->setPublic(TRUE);
  $container->register('civineonotify.rule_engine', RuleEngine::class)->setArguments([
    new Reference('civineonotify.condition_engine'), new Reference('civineonotify.message_renderer'),
    new Reference('civineonotify.default_resolver'), new Reference('civineonotify.outbox_repository'),
  ]);
  $container->register('civineonotify.post_hook_subscriber', PostHookSubscriber::class)->setArguments([
    new Reference('civineonotify.rule_engine'),
  ])->setPublic(TRUE);
}

/** Keeps the synchronous hook deliberately local and side-effect bounded. */
function civineonotify_civicrm_pre(string $op, string $objectName, int|string|null $objectId, &$params): void {
  if (strtolower($op) !== 'edit' || $objectName !== 'Case' || !$objectId) return;
  $dao = \CRM_Core_DAO::executeQuery('SELECT status_id, case_type_id FROM civicrm_case WHERE id = %1', [1 => [(int) $objectId, 'Integer']]);
  if ($dao->fetch()) $GLOBALS['civineonotify_case_before'][(int) $objectId] = ['status_id' => $dao->status_id, 'case_type_id' => $dao->case_type_id];
}

function civineonotify_civicrm_post(string $op, string $objectName, int|string $objectId, &$objectRef): void {
  if (!\Civi::settings()->get('civineonotify_enabled')) { return; }
  $previous = $objectName === 'Case' ? ($GLOBALS['civineonotify_case_before'][(int) $objectId] ?? []) : [];
  unset($GLOBALS['civineonotify_case_before'][(int) $objectId]);
  try { \Civi::service('civineonotify.post_hook_subscriber')->onPost($op, $objectName, $objectId, $objectRef, $previous); }
  catch (\Throwable $exception) { \Civi::log()->warning('CiviNeo Notify could not enqueue an event.', ['exception' => get_class($exception)]); }
}

function civineonotify_civicrm_permission(array &$permissions): void {
  foreach ([
    'administer civineo notify' => 'Administer CiviNeo Notify configuration.',
    'view civineo notify delivery log' => 'View CiviNeo Notify delivery history.',
    'retry civineo notify deliveries' => 'Retry or cancel Matrix notifications.',
    'send civineo notify test messages' => 'Send explicitly requested Matrix test messages.',
  ] as $permission => $description) {
    $permissions[$permission] = ['label' => CRM_Civineonotify_ExtensionUtil::ts(ucwords(str_replace(' matrix ', ' Matrix ', $permission))), 'description' => CRM_Civineonotify_ExtensionUtil::ts($description)];
  }
}

/** Exposes the global settings page under CiviCRM administration. */
function civineonotify_civicrm_navigationMenu(array &$menu): void {
  $admin = (int) \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Administer', 'id', 'name');
  if (!$admin || empty($menu[$admin])) return;
  foreach ($menu[$admin]['child'] ?? [] as $item) if (($item['attributes']['name'] ?? NULL) === 'CiviNeoNotify') return;
  $id = civineonotify_nextNavigationId($menu);
  $children = [];
  foreach ([
    ['Settings', 'CiviNeoNotifySettings', 'civicrm/admin/setting/civineonotify?reset=1', 'administer civineo notify'],
    ['Transports', 'CiviNeoNotifyTransports', 'civicrm/admin/civineonotify/transports?reset=1', 'administer civineo notify'],
    ['Destinations', 'CiviNeoNotifyDestinations', 'civicrm/admin/civineonotify/destinations?reset=1', 'administer civineo notify'],
    ['Rules', 'CiviNeoNotifyRules', 'civicrm/admin/civineonotify/rules?reset=1', 'administer civineo notify'],
    ['Delivery log', 'CiviNeoNotifyLog', 'civicrm/admin/civineonotify/log?reset=1', 'view civineo notify delivery log'],
  ] as $offset => [$label, $name, $url, $permission]) $children[$id + $offset + 1] = ['attributes' => ['label' => CRM_Civineonotify_ExtensionUtil::ts($label), 'name' => $name, 'url' => $url, 'permission' => $permission, 'operator' => 'OR', 'parentID' => $id, 'navID' => $id + $offset + 1, 'active' => 1]];
  $menu[$admin]['child'][$id] = ['attributes' => ['label' => CRM_Civineonotify_ExtensionUtil::ts('CiviNeo Notify'), 'name' => 'CiviNeoNotify', 'url' => NULL, 'permission' => 'administer civineo notify', 'operator' => 'OR', 'parentID' => $admin, 'navID' => $id, 'active' => 1], 'child' => $children];
}

function civineonotify_nextNavigationId(array $menu): int { $ids = []; $collect = function (array $items) use (&$collect, &$ids): void { foreach ($items as $key => $item) { $ids[] = (int) ($item['attributes']['navID'] ?? $key); if (!empty($item['child'])) $collect($item['child']); } }; $collect($menu); return max(array_merge([0], $ids)) + 1; }

/** Add the extension level that CiviCRM's generic admin pages cannot infer. */
function civineonotify_appendBreadcrumb(?string $currentTitle = NULL, ?string $currentPath = NULL): void {
  $crumbs = [[
    'title' => CRM_Civineonotify_ExtensionUtil::ts('CiviNeo Notify'),
    'url' => CRM_Utils_System::url('civicrm/admin/setting/civineonotify', ['reset' => 1]),
  ]];
  if ($currentTitle !== NULL) {
    $crumb = ['title' => CRM_Civineonotify_ExtensionUtil::ts($currentTitle)];
    if ($currentPath !== NULL) $crumb['url'] = CRM_Utils_System::url($currentPath, ['reset' => 1]);
    $crumbs[] = $crumb;
  }
  CRM_Utils_System::appendBreadCrumb($crumbs);
}

function civineonotify_transportOptions(bool $optional = FALSE): array {
  $options = $optional ? ['' => CRM_Civineonotify_ExtensionUtil::ts('- Use destination transport -')] : ['' => CRM_Civineonotify_ExtensionUtil::ts('- Select transport -')];
  foreach (\Civi\Api4\CiviNeoNotifyTransport::get(FALSE)->addWhere('is_active', '=', TRUE)->addOrderBy('name', 'ASC')->execute() as $transport) $options[(string) $transport['id']] = $transport['name'];
  return $options;
}

function civineonotify_destinationOptions(bool $optional = FALSE, ?int $transportId = NULL, ?string $emptyLabel = NULL): array {
  $options = ['' => $emptyLabel ?? CRM_Civineonotify_ExtensionUtil::ts($optional ? '- Use transport default destination -' : '- Select destination -')];
  $query = \Civi\Api4\CiviNeoNotifyDestination::get(FALSE)->addWhere('is_active', '=', TRUE)->addOrderBy('name', 'ASC');
  if ($transportId) $query->addWhere('transport_id', '=', $transportId);
  foreach ($query->execute() as $destination) $options[(string) $destination['id']] = $destination['name'];
  return $options;
}

/**
 * Events emitted by this extension's civicrm_post subscriber.
 * A select2 control keeps the full list searchable even on installations with
 * many enabled API entities.
 */
function civineonotify_eventOptions(): array {
  $options = ['' => CRM_Civineonotify_ExtensionUtil::ts('- Select an event -')];
  $entities = \Civi\Api4\Entity::get(FALSE)->addOrderBy('title', 'ASC')->execute();
  foreach ($entities as $entity) {
    if (!in_array('DAOEntity', (array) ($entity['type'] ?? []), TRUE)) continue;
    $name = (string) ($entity['name'] ?? '');
    if ($name === '' || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) continue;
    $eventEntity = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '.$0', $name));
    $label = (string) ($entity['title'] ?? $name);
    foreach (['create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete'] as $operation => $operationLabel) {
      $options["civicrm.{$eventEntity}.{$operation}"] = "{$label} — " . CRM_Civineonotify_ExtensionUtil::ts($operationLabel);
    }
  }
  return $options;
}
