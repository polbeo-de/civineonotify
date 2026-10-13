<div class="crm-block crm-form-block crm-civineonotify-verify-matrix-nio-form-block">
  <p>{ts}Verify another device of this account, or invite a member of the default Matrix destination to the reusable unencrypted verification room. Compare the displayed SAS before confirming.{/ts}</p>
  <table class="form-layout">
    <tr><td class="label">{$form.verification_method.label}</td><td>{$form.verification_method.html}</td></tr>
    <tr><td class="label">{$form.verification_device_id.label}</td><td>{$form.verification_device_id.html}</td></tr>
    <tr><td class="label">{$form.verification_target_user_id.label}</td><td>{$form.verification_target_user_id.html}</td></tr>
  </table>
  {if $verification.state}<div class="messages status"><strong>{$verification.state|escape}</strong>{if $verification.phase}<p>{ts 1=$verification.phase|escape}Verifier phase: %1{/ts}</p>{/if}{if $verification.events}<p>{ts 1=$verification.events|@implode:', '|escape}Observed verification events: %1{/ts}</p>{/if}{if $verification.emoji}<p>{foreach from=$verification.emoji item=e}{$e[0]|escape} {$e[1]|escape}&nbsp;{/foreach}</p><p>{$verification.decimals|@implode:' · '|escape}</p>{/if}</div>{/if}
  <p>{$form.buttons.html}</p>
</div>
{literal}<script>
  (function () {
    var method = document.getElementById('verification_method');
    var device = document.getElementById('verification_device_id');
    var member = document.getElementById('verification_target_user_id');
    if (!method || !device || !member) return;
    function toggle() {
      device.closest('tr').style.display = method.value === 'device' ? '' : 'none';
      member.closest('tr').style.display = method.value === 'room' ? '' : 'none';
    }
    method.addEventListener('change', toggle); toggle();
  }());
</script>{/literal}
{if $verification.state eq 'waiting' || $verification.state eq 'confirming'}
{literal}<script>
  window.setTimeout(function () { window.location.reload(); }, 3000);
</script>{/literal}
{/if}
