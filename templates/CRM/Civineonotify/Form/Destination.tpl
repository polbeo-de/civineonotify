<div class="crm-block crm-form-block crm-civineonotify-destination-form-block">
  <table class="form-layout">
    <tr><td class="label">{$form.transport_id.label}</td><td>{$form.transport_id.html}</td></tr>
    <tr><td class="label">{$form.name.label}</td><td>{$form.name.html}</td></tr>
    <tr><td class="label">{$form.destination_type.label}</td><td>{$form.destination_type.html}</td></tr>
    <tr><td class="label">{$form.description.label}</td><td>{$form.description.html}</td></tr>
    <tr class="matrix-field"><td class="label">{$form.room_id.label}</td><td>{$form.room_id.html}</td></tr>
    <tr class="matrix-field"><td class="label">{$form.room_alias.label}</td><td>{$form.room_alias.html}</td></tr>
    <tr class="mattermost-field"><td class="label">{$form.webhook_token.label}</td><td>{$form.webhook_token.html}{if $mattermostTokenSuffix}<div class="description">{ts}Stored token:{/ts} ••••{$mattermostTokenSuffix|escape}</div>{/if}</td></tr>
    <tr class="mattermost-field"><td class="label">{$form.channel.label}</td><td>{$form.channel.html}<div class="description">{ts}Optional. Leave empty to use the channel configured on the incoming webhook.{/ts}</div></td></tr>
    <tr><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td></tr>
    <tr><td></td><td>{$form.buttons.html}</td></tr>
  </table>
</div>
<script>
var civineonotifyTransportDestinationTypes = {$transportDestinationTypesJson nofilter};
</script>
{literal}
<script>
CRM.$(function($) {
  function chooseDestination() {
    var mattermost = $('#destination_type').val() === 'mattermost_channel';
    $('.matrix-field').toggle(!mattermost);
    $('.mattermost-field').toggle(mattermost);
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
  }
  $('#destination_type').on('change', chooseDestination);
  $('#transport_id').on('change', chooseTransport);
  chooseTransport();
  chooseDestination();
});
</script>
{/literal}
