<div class="crm-block crm-form-block crm-civineonotify-verify-matrix-nio-form-block">
  <p>{ts}Choose the Element device to verify, then start verification. Compare the displayed SAS before confirming.{/ts}</p>
  <table class="form-layout">
    <tr><td class="label">{$form.verification_device_id.label}</td><td>{$form.verification_device_id.html}</td></tr>
  </table>
  {if $verification.state}<div class="messages status"><strong>{$verification.state|escape}</strong>{if $verification.emoji}<p>{foreach from=$verification.emoji item=e}{$e[0]|escape} {$e[1]|escape}&nbsp;{/foreach}</p><p>{$verification.decimals|@implode:' · '|escape}</p>{/if}</div>{/if}
  <p>{$form.buttons.html}</p>
</div>
