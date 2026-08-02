{if $civineonotifyView eq 'connections'}
  <p><a class="button" href="{crmURL p='civicrm/admin/civineonotify/connection' q='reset=1'}">{ts}Add connection{/ts}</a></p>
  <table class="crm-info-panel"><thead><tr><th>{ts}Name{/ts}</th><th>{ts}Default{/ts}</th><th>{ts}Actions{/ts}</th></tr></thead><tbody>
  {foreach from=$civineonotifyRows item=row}<tr><td>{$row.name|escape}</td><td>{if $row.is_default}{ts}Yes{/ts}{/if}</td><td><a href="{crmURL p='civicrm/admin/civineonotify/connection' q="reset=1&id=`$row.id`"}">{ts}Edit{/ts}</a> · <a href="{crmURL p='civicrm/admin/civineonotify/connections' q="reset=1&action=test&id=`$row.id`"}">{ts}Test{/ts}</a></td></tr>{/foreach}
  </tbody></table>
{elseif $civineonotifyView eq 'rooms'}
  <p><a class="button" href="{crmURL p='civicrm/admin/civineonotify/room' q='reset=1'}">{ts}Add room{/ts}</a></p>
  <table class="crm-info-panel"><thead><tr><th>{ts}Name{/ts}</th><th>{ts}Room ID / alias{/ts}</th><th>{ts}Default{/ts}</th><th>{ts}Actions{/ts}</th></tr></thead><tbody>
  {foreach from=$civineonotifyRows item=row}<tr><td>{$row.name|escape}</td><td>{$row.room_id|default:$row.room_alias|escape}</td><td>{if $row.is_default}{ts}Yes{/ts}{/if}</td><td><a href="{crmURL p='civicrm/admin/civineonotify/room' q="reset=1&id=`$row.id`"}">{ts}Edit{/ts}</a> · <a href="{crmURL p='civicrm/admin/civineonotify/rooms' q="reset=1&action=test&id=`$row.id`"}">{ts}Test{/ts}</a></td></tr>{/foreach}
  </tbody></table>
{elseif $civineonotifyView eq 'rules'}
  <p><a class="button" href="{crmURL p='civicrm/admin/civineonotify/rule' q='reset=1'}">{ts}Add rule{/ts}</a></p>
  <table class="crm-info-panel"><thead><tr><th>{ts}Name{/ts}</th><th>{ts}Event{/ts}</th><th>{ts}Priority{/ts}</th><th>{ts}Actions{/ts}</th></tr></thead><tbody>
  {foreach from=$civineonotifyRows item=row}<tr><td>{$row.name|escape}</td><td>{$row.event_pattern|escape}</td><td>{$row.priority|escape}</td><td><a href="{crmURL p='civicrm/admin/civineonotify/rule' q="reset=1&id=`$row.id`"}">{ts}Edit{/ts}</a></td></tr>{/foreach}
  </tbody></table>
{else}
  <table class="crm-info-panel"><thead><tr><th>{ts}Event{/ts}</th><th>{ts}Status{/ts}</th><th>{ts}Attempts{/ts}</th><th>{ts}Actions{/ts}</th></tr></thead><tbody>
  {foreach from=$civineonotifyRows item=row}<tr><td>{$row.event_name|escape}</td><td>{$row.status|escape}</td><td>{$row.attempt_count|default:0|escape}</td><td>{if $row.status eq 'pending' or $row.status eq 'retry'}<a href="{crmURL p='civicrm/admin/civineonotify/log' q="reset=1&action=cancel&id=`$row.id`"}">{ts}Cancel{/ts}</a>{/if}{if $row.status neq 'sent'}{if $row.status eq 'pending' or $row.status eq 'retry'} · {/if}<a href="{crmURL p='civicrm/admin/civineonotify/log' q="reset=1&action=retry&id=`$row.id`"}">{ts}Retry{/ts}</a>{/if}</td></tr>{/foreach}
  </tbody></table>
{/if}
