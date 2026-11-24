<div class="crm-block crm-form-block crm-civineonotify-destination-form-block">
  <table class="form-layout">
    <tr><td class="label">{$form.transport_id.label}</td><td>{$form.transport_id.html}</td></tr>
    <tr><td class="label">{$form.name.label}</td><td>{$form.name.html}</td></tr>
    <tr><td class="label">{$form.destination_type.label}</td><td>{$form.destination_type.html}</td></tr>
    <tr><td class="label">{$form.description.label}</td><td>{$form.description.html}</td></tr>
    <tr class="matrix-field"><td class="label">{$form.room_id.label}</td><td>{$form.room_id.html}</td></tr>
    <tr class="matrix-field"><td class="label">{$form.room_alias.label}</td><td>{$form.room_alias.html}</td></tr>
    <tr class="mattermost-webhook-field"><td class="label">{$form.webhook_token.label}</td><td>{$form.webhook_token.html}{if $mattermostTokenSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$mattermostTokenSuffix|escape}</div>{/if}</td></tr>
    <tr class="mattermost-api-field"><td class="label">{$form.mattermost_channel_id.label}</td><td>{$form.mattermost_channel_id.html}<div class="description mattermost-api-channel-description">{ts}Choose a channel the configured Mattermost account can access.{/ts}</div><div class="description mattermost-api-channel-error">{ts}The Mattermost channels could not be loaded. Check the API token and reload this page.{/ts}</div></td></tr>
    <tr class="mattermost-webhook-field"><td class="label">{$form.channel_override.label}</td><td>{$form.channel_override.html}<div class="description">{ts}Optional; leave empty to use the webhook's default channel.{/ts}</div></td></tr>
    <tr class="telegram-field"><td class="label">{$form.telegram_chat_id.label}</td><td>{$form.telegram_chat_id.html}<div class="description">{ts}Group, supergroup, or channel chat ID. Direct messages are not supported.{/ts}</div></td></tr>
    <tr class="webhook-field"><td class="label">{$form.webhook_endpoint_url.label}</td><td>{$form.webhook_endpoint_url.html}<div class="description">{ts}Public HTTPS URL. Requests are JSON POSTs and never follow redirects.{/ts}</div></td></tr>
    <tr class="webhook-field"><td class="label">{$form.webhook_signing_secret.label}</td><td>{$form.webhook_signing_secret.html}<div class="description">{ts}Optional. When set, CiviNeo sends an HMAC-SHA-256 signature in X-CiviNeo-Signature.{/ts}</div>{if $webhookSigningSecretSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$webhookSigningSecretSuffix|escape}</div>{/if}</td></tr>
    <tr class="slack-field"><td class="label">{$form.slack_webhook_url.label}</td><td>{$form.slack_webhook_url.html}<div class="description">{ts}Stored outside CiviCRM in the protected token store.{/ts}</div>{if $slackWebhookSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$slackWebhookSuffix|escape}</div>{/if}</td></tr>
    <tr class="rocketchat-field"><td class="label">{$form.rocketchat_webhook_url.label}</td><td>{$form.rocketchat_webhook_url.html}<div class="description">{ts}Stored outside CiviCRM in the protected token store.{/ts}</div>{if $rocketchatWebhookSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$rocketchatWebhookSuffix|escape}</div>{/if}</td></tr>
    <tr class="discord-field"><td class="label">{$form.discord_webhook_url.label}</td><td>{$form.discord_webhook_url.html}<div class="description">{ts}Stored outside CiviCRM in the protected token store.{/ts}</div>{if $discordWebhookSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$discordWebhookSuffix|escape}</div>{/if}</td></tr>
    <tr class="teams-field"><td class="label">{$form.teams_webhook_url.label}</td><td>{$form.teams_webhook_url.html}<div class="description">{ts}Create a Teams workflow that receives webhook requests, then paste its URL here. Stored outside CiviCRM in the protected token store.{/ts}</div>{if $teamsWebhookSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$teamsWebhookSuffix|escape}</div>{/if}</td></tr>
    <tr class="ntfy-field"><td class="label">{$form.ntfy_topic.label}</td><td>{$form.ntfy_topic.html}<div class="description">{ts}A topic is the destination on the configured ntfy server.{/ts}</div></td></tr>
    <tr class="gotify-field"><td class="label">{$form.gotify_application_token.label}</td><td>{$form.gotify_application_token.html}<div class="description">{ts}Create an application in Gotify and enter its token here. It is stored outside CiviCRM in the protected token store.{/ts}</div>{if $gotifyApplicationTokenSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$gotifyApplicationTokenSuffix|escape}</div>{/if}</td></tr>
    <tr><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td></tr>
    <tr><td></td><td>{$form.buttons.html}</td></tr>
  </table>
</div>
<script>
var civineonotifyTransportDestinationTypes = {$transportDestinationTypesJson nofilter};
var civineonotifyMattermostApiChannels = {$mattermostApiChannelsJson nofilter};
var civineonotifyMattermostApiChannelErrors = {$mattermostApiChannelErrorsJson nofilter};
</script>
{literal}
<script>
CRM.$(function($) {
  function chooseDestination() {
    var mattermost = $('#destination_type').val() === 'mattermost_channel';
    var mastodon = $('#destination_type').val() === 'mastodon_account';
    var telegram = $('#destination_type').val() === 'telegram_chat';
    var bluesky = $('#destination_type').val() === 'bluesky_account';
    var genericWebhook = $('#destination_type').val() === 'webhook_endpoint';
    var slack = $('#destination_type').val() === 'slack_channel';
    var rocketchat = $('#destination_type').val() === 'rocketchat_channel';
    var discord = $('#destination_type').val() === 'discord_channel';
    var teams = $('#destination_type').val() === 'teams_channel';
    var ntfy = $('#destination_type').val() === 'ntfy_topic';
    var gotify = $('#destination_type').val() === 'gotify_application';
    var transportId = $('#transport_id').val();
    var api = Object.prototype.hasOwnProperty.call(civineonotifyMattermostApiChannels, transportId) || Object.prototype.hasOwnProperty.call(civineonotifyMattermostApiChannelErrors, transportId);
    $('.matrix-field').toggle(!mattermost && !mastodon && !telegram && !bluesky && !genericWebhook && !slack && !rocketchat && !discord && !teams && !ntfy && !gotify);
    $('.mattermost-webhook-field').toggle(mattermost && !api);
    $('.mattermost-api-field').toggle(mattermost && api);
    $('.telegram-field').toggle(telegram);
    $('.webhook-field').toggle(genericWebhook);
    $('.slack-field').toggle(slack);
    $('.rocketchat-field').toggle(rocketchat);
    $('.discord-field').toggle(discord);
    $('.teams-field').toggle(teams);
    $('.ntfy-field').toggle(ntfy);
    $('.gotify-field').toggle(gotify);
    if (mattermost && api) {
      $('.mattermost-api-channel-description').toggle(!civineonotifyMattermostApiChannelErrors[transportId]);
      $('.mattermost-api-channel-error').toggle(!!civineonotifyMattermostApiChannelErrors[transportId]);
    }
  }
  function chooseTransport() {
    var supported = civineonotifyTransportDestinationTypes[$('#transport_id').val()];
    var destinationType = $('#destination_type');
    destinationType.find('option').each(function() {
      var permitted = !supported || this.value === supported;
      $(this).prop('disabled', !permitted).toggle(permitted);
    });
    if (supported && destinationType.val() !== supported) {
      destinationType.val(supported).trigger('change');
    }
    var transportId = $('#transport_id').val();
    var selected = $('#mattermost_channel_id').val();
    $('#mattermost_channel_id option').each(function() {
      var permitted = !this.value || !!(civineonotifyMattermostApiChannels[transportId] && civineonotifyMattermostApiChannels[transportId][this.value]);
      $(this).prop('disabled', !permitted).toggle(permitted || this.value === selected);
    });
  }
  $('#destination_type').on('change', chooseDestination);
  $('#transport_id').on('change', chooseTransport);
  chooseTransport();
  chooseDestination();
});
</script>
{/literal}
