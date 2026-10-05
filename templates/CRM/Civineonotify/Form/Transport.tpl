<div class="crm-block crm-form-block crm-civineonotify-transport-form-block">
  <table class="form-layout">
    <tr><td class="label">{$form.name.label}</td><td>{$form.name.html}</td></tr>
    <tr><td class="label">{$form.transport_type.label}</td><td>{$form.transport_type.html}</td></tr>
    <tr><td class="label">{$form.description.label}</td><td>{$form.description.html}</td></tr>
    <tr><td class="label">{$form.binary_path.label}</td><td>{$form.binary_path.html}</td></tr>
    <tr><td class="label">{$form.credentials_path.label}</td><td>{$form.credentials_path.html}<div class="description">{ts}Leave empty when creating a new Matrix transport, then use Provision Matrix credentials. Do not store a token in CiviCRM.{/ts}</div></td></tr>
    <tr><td class="label">{$form.store_path.label}</td><td>{$form.store_path.html}<div class="description">{ts}Leave empty during initial setup; provisioning creates a dedicated, writable E2EE store outside the web root.{/ts}</div></td></tr>
    <tr><td class="label">{$form.working_directory.label}</td><td>{$form.working_directory.html}</td></tr>
    <tr><td class="label">{$form.timeout_seconds.label}</td><td>{$form.timeout_seconds.html}</td></tr>
    <tr><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td></tr>
    <tr><td class="label">{$form.default_destination_id.label}</td><td>{$form.default_destination_id.html}</td></tr>
    <tr><td></td><td>{$form.buttons.html}{if $provisionUrl}<a class="button" href="{$provisionUrl}">{ts}Provision Matrix credentials{/ts}</a>{/if}</td></tr>
  </table>
</div>
