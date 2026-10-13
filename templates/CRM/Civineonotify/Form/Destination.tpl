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
    var transportId = $('#transport_id').val();
    var api = Object.prototype.hasOwnProperty.call(civineonotifyMattermostApiChannels, transportId) || Object.prototype.hasOwnProperty.call(civineonotifyMattermostApiChannelErrors, transportId);
    $('.matrix-field').toggle(!mattermost);
    $('.mattermost-webhook-field').toggle(mattermost && !api);
    $('.mattermost-api-field').toggle(mattermost && api);
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
