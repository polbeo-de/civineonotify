{if $civineonotifyView eq 'transports'}
  <p><a class="button" href="{crmURL p='civicrm/admin/civineonotify/transport' q='reset=1'}">{ts}Add transport{/ts}</a></p>
  <table class="crm-info-panel"><thead><tr><th>{ts}Name{/ts}</th><th>{ts}Provisioning{/ts}</th><th>{ts}Trust{/ts}</th><th>{ts}Default{/ts}</th><th>{ts}Actions{/ts}</th></tr></thead><tbody>
  {foreach from=$civineonotifyRows item=row}<tr><td>{$row.name|escape}</td><td>{$row.provisioning_status|escape}</td><td>{$row.trust_status|escape}</td><td>{if $row.default_destination_id}{ts}Yes{/ts}{/if}</td><td><a href="{crmURL p='civicrm/admin/civineonotify/transport' q="reset=1&id=`$row.id`"}">{ts}Edit{/ts}</a>{if $row.is_matrix_nio} · <a href="{crmURL p='civicrm/admin/civineonotify/verify-matrix-nio' q="reset=1&id=`$row.id`"}">{ts}Verify device{/ts}</a>{/if}{if $row.is_matrix_transport} · <a href="{crmURL p='civicrm/admin/civineonotify/provision-matrix' q="reset=1&id=`$row.id`"}">{ts}Provision{/ts}</a>{/if} · <a href="{crmURL p='civicrm/admin/civineonotify/transports' q="reset=1&action=test&id=`$row.id`"}">{ts}Test{/ts}</a> · <a href="{crmURL p='civicrm/admin/civineonotify/delete-transport' q="reset=1&id=`$row.id`"}">{ts}Delete{/ts}</a></td></tr>{/foreach}
  </tbody></table>
{elseif $civineonotifyView eq 'destinations'}
  <p><a class="button" href="{crmURL p='civicrm/admin/civineonotify/destination' q='reset=1'}">{ts}Add destination{/ts}</a></p>
  <table class="crm-info-panel"><thead><tr><th>{ts}Name{/ts}</th><th>{ts}Type{/ts}</th><th>{ts}Actions{/ts}</th></tr></thead><tbody>
  {foreach from=$civineonotifyRows item=row}<tr><td>{$row.name|escape}</td><td>{$row.destination_type|escape}</td><td><a href="{crmURL p='civicrm/admin/civineonotify/destination' q="reset=1&id=`$row.id`"}">{ts}Edit{/ts}</a> · <a href="{crmURL p='civicrm/admin/civineonotify/destinations' q="reset=1&action=test&id=`$row.id`"}">{ts}Test{/ts}</a></td></tr>{/foreach}
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
