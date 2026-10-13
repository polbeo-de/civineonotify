<div class="crm-block crm-form-block crm-civineonotify-verify-matrix-nio-form-block">
  <p>{ts}Choose a recipient device from the default destination, then start verification. Compare the displayed SAS before confirming.{/ts}</p>
  <table class="form-layout">
    <tr><td class="label">{$form.verification_device.label}</td><td>{$form.verification_device.html}</td></tr>
  </table>
  {if $verification.state}<div class="messages status"><strong>{$verification.state|escape}</strong>{if $verification.phase}<p>{ts 1=$verification.phase|escape}Verifier phase: %1{/ts}</p>{/if}{if $verification.events}<p>{ts 1=$verification.events|@implode:', '|escape}Observed verification events: %1{/ts}</p>{/if}{if $verification.emoji}<p>{foreach from=$verification.emoji item=e}{$e[0]|escape} {$e[1]|escape}&nbsp;{/foreach}</p><p>{$verification.decimals|@implode:' · '|escape}</p>{/if}</div>{/if}
  <p>{$form.buttons.html}</p>
</div>
{if $verification.state eq 'waiting' || $verification.state eq 'confirming'}
{literal}<script>
  window.setTimeout(function () { window.location.reload(); }, 3000);
</script>{/literal}
{/if}
