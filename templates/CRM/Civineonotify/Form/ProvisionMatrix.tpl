<div class="crm-block crm-form-block crm-civineonotify-provision-matrix-form-block">
  <p>{ts 1=$transportName}Provision a new encrypted Matrix device for %1. The uploaded password file is read once, then deleted; neither password nor access token is stored in CiviCRM.{/ts}</p>
  <p class="description">{ts}Use a dedicated bot account and an internal room ID. The private directory must be outside the web root and writable by both PHP and the CiviCRM worker.{/ts}</p>
  <table class="form-layout">
    <tr><td class="label">{$form.homeserver.label}</td><td>{$form.homeserver.html}</td></tr>
    <tr><td class="label">{$form.user_login.label}</td><td>{$form.user_login.html}</td></tr>
    <tr><td class="label">{$form.room_id.label}</td><td>{$form.room_id.html}</td></tr>
    <tr><td class="label">{$form.storage_root.label}</td><td>{$form.storage_root.html}<div class="description">{ts}On phprime8 use /home/phprime8/tmp/civineonotify, not its world-readable parent directory.{/ts}</div></td></tr>
    <tr><td class="label">{$form.bot_password_file.label}</td><td>{$form.bot_password_file.html}<div class="description">{ts}A plain-text file containing only the Matrix bot password. It creates a dedicated Matrix device and is deleted immediately.{/ts}</div></td></tr>
    <tr><td></td><td>{$form.buttons.html}</td></tr>
  </table>
</div>
