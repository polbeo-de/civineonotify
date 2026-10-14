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
    <tr class="mattermost-field"><td class="label">{$form.server_url.label}</td><td>{$form.server_url.html}</td></tr>
    <tr class="mattermost-field"><td class="label">{$form.hooks_path.label}</td><td>{$form.hooks_path.html}<div class="description">{ts}Defaults to hooks.{/ts}</div></td></tr>
    <tr class="external-token-field"><td class="label">{$form.token_store_file.label}</td><td>{$form.token_store_file.html}<div class="description">{ts}Protected external JSON token store. Tokens are never stored in CiviCRM.{/ts}</div></td></tr>
    <tr class="mattermost-api-field"><td class="label">{$form.api_token.label}</td><td>{$form.api_token.html}<div class="description">{ts}Stored outside CiviCRM in the protected token store.{/ts}</div>{if $mattermostApiTokenSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$mattermostApiTokenSuffix|escape}</div>{/if}</td></tr>
    <tr class="mastodon-field"><td class="label">{$form.instance_url.label}</td><td>{$form.instance_url.html}</td></tr>
    <tr class="mastodon-field"><td class="label">{$form.mastodon_access_token.label}</td><td>{$form.mastodon_access_token.html}<div class="description">{ts}Stored outside CiviCRM in the protected token store.{/ts}</div>{if $mastodonTokenSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$mastodonTokenSuffix|escape}</div>{/if}</td></tr>
    <tr class="mastodon-field"><td class="label">{$form.mastodon_visibility.label}</td><td>{$form.mastodon_visibility.html}</td></tr>
    <tr class="mastodon-field"><td class="label">{$form.mastodon_language.label}</td><td>{$form.mastodon_language.html}</td></tr>
    <tr class="mastodon-field"><td class="label">{$form.mastodon_spoiler_text.label}</td><td>{$form.mastodon_spoiler_text.html}</td></tr>
    <tr class="telegram-field"><td class="label">{$form.telegram_bot_token.label}</td><td>{$form.telegram_bot_token.html}<div class="description">{ts}Stored outside CiviCRM in the protected token store.{/ts}</div>{if $telegramTokenSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$telegramTokenSuffix|escape}</div>{/if}</td></tr>
    <tr class="telegram-field"><td class="label">{$form.telegram_parse_mode.label}</td><td>{$form.telegram_parse_mode.html}</td></tr>
    <tr class="telegram-field"><td class="label">{$form.telegram_disable_link_preview.label}</td><td>{$form.telegram_disable_link_preview.html}</td></tr>
    <tr class="telegram-field"><td class="label">{$form.telegram_disable_notification.label}</td><td>{$form.telegram_disable_notification.html}</td></tr>
    <tr class="telegram-field"><td class="label">{$form.telegram_protect_content.label}</td><td>{$form.telegram_protect_content.html}</td></tr>
    <tr class="nio-field"><td class="label">{$form.homeserver.label}</td><td>{$form.homeserver.html}</td></tr>
    <tr class="nio-field"><td class="label">{$form.python_path.label}</td><td>{$form.python_path.html}</td></tr>
    <tr class="nio-field"><td class="label">{$form.nio_client_path.label}</td><td>{$form.nio_client_path.html}<div class="description">{ts}The bundled client script must run in a Python environment with matrix-nio[e2e] installed.{/ts}</div></td></tr>
    <tr class="nio-field"><td class="label">{$form.session_path.label}</td><td>{$form.session_path.html}<div class="description">{ts}Private session JSON outside the web root. It contains an access token and must be mode 0600.{/ts}</div></td></tr>
    <tr class="nio-field"><td class="label">{$form.allow_unverified_recipient_devices.label}</td><td>{$form.allow_unverified_recipient_devices.html}<div class="description">{ts}Off by default. Enabling it still encrypts messages but shares their session with recipient devices which have not been verified.{/ts}</div></td></tr>
    <tr><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td></tr>
    <tr><td class="label">{$form.default_destination_id.label}</td><td>{$form.default_destination_id.html}{if $defaultDestinationNotice}<div class="description">{$defaultDestinationNotice}</div>{/if}</td></tr>
    <tr><td></td><td>{$form.buttons.html}{if $provisionUrl}<a class="button" href="{$provisionUrl}">{ts}Provision Matrix credentials{/ts}</a>{/if}{if $verifyUrl}<a class="button" href="{$verifyUrl}">{ts}Verify Matrix device{/ts}</a>{/if}</td></tr>
  </table>
</div>
{literal}
<script>
CRM.$(function($) {
  function chooseTransport() {
    var type = $('#transport_type').val();
    var nio = type === 'matrix_nio';
    var mattermost = type === 'mattermost_webhook' || type === 'mattermost_api';
    var mattermostApi = type === 'mattermost_api';
    var mastodon = type === 'mastodon';
    var telegram = type === 'telegram';
    $('.nio-field').toggle(nio);
    $('.mattermost-field').toggle(mattermost);
    $('.external-token-field').toggle(mattermost || mastodon || telegram);
    $('.mattermost-api-field').toggle(mattermostApi);
    $('.mastodon-field').toggle(mastodon);
    $('.telegram-field').toggle(telegram);
    $('#hooks_path').closest('tr').toggle(type === 'mattermost_webhook');
    $('#binary_path, #credentials_path, #store_path, #working_directory').closest('tr').toggle(!nio && !mattermost && !mastodon && !telegram);
  }
  $('#transport_type').on('change', chooseTransport);
  chooseTransport();
});
</script>
{/literal}
