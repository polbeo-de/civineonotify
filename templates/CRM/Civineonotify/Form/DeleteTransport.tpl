<div class="crm-block crm-form-block crm-civineonotify-delete-transport-form-block">
  <p>{ts 1=$transportName}Do you really want to delete the transport "%1"?{/ts}</p>
  <p>{ts}This permanently removes the following associated data:{/ts}</p>
  <ul>
    <li>{ts 1=$dependencies.destinations}%1 destination(s){/ts}</li>
    <li>{ts 1=$dependencies.deliveries}%1 delivery log record(s){/ts}</li>
  </ul>
  <p>{ts 1=$dependencies.rules}%1 rule(s) will remain, but no longer select this transport.{/ts}</p>
  <p>{$form.buttons.html}</p>
</div>
