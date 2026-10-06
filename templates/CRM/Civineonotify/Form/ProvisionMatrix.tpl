<div class="crm-block crm-form-block crm-civineonotify-provision-matrix-form-block">
  <p>{ts 1=$transportName}Provision a new encrypted Matrix device for %1. The password is sent once over HTTPS and is never stored in CiviCRM.{/ts}</p>
  <p class="description">{ts}Use a dedicated bot account and an internal room ID. The private directory must be outside the web root and writable by both PHP and the CiviCRM worker.{/ts}</p>
  {if $provisioningStatus}<div class="messages status {if $provisioningStatus == 'succeeded'}alert-success{else}alert-info{/if}"><strong>{ts}Last provisioning status:{/ts}</strong> {$provisioningStatus|escape}</div>{/if}
  <table class="form-layout">
    <tr><td class="label">{$form.homeserver.label}</td><td>{$form.homeserver.html}</td></tr>
    <tr><td class="label">{$form.user_login.label}</td><td>{$form.user_login.html}</td></tr>
    <tr><td class="label">{$form.room_id.label}</td><td>{$form.room_id.html}</td></tr>
    <tr><td class="label">{$form.storage_root.label}</td><td>{$form.storage_root.html}<div class="description">{ts}Use a dedicated directory outside the web root that is readable and writable only by the CiviCRM service account.{/ts}</div></td></tr>
    <tr><td class="label">{$form.bot_password.label}</td><td>{$form.bot_password.html}<div class="description">{ts}Used once to create a dedicated Matrix device. It is passed by standard input and is not stored.{/ts}</div></td></tr>
    <tr><td></td><td>{$form.buttons.html}</td></tr>
  </table>
</div>
