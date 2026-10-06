# Initial transport provisioning

Some transports need a one-time, stateful setup: creating a device, obtaining
an OAuth token, establishing encrypted keys, or discovering server metadata.
That work must never run in a CiviCRM web request.

CiviNeo Notify uses this contract for every such transport:

1. The privileged configuration form receives a secret once over HTTPS.
2. It writes that secret to a random mode-`0600` file inside a transport-private
   mode-`0700` directory. The secret is never stored in an entity, setting, log,
   process argument, or browser-visible status.
3. It starts a bounded, one-shot user-systemd service. The service is separate
   from PHP-FPM, has a startup timeout and a memory high-water limit.
4. The job removes the secret file and writes a safe stage status to a private
   status file. It may identify only `preparing`, `creating-device`, or
   `checking-identity`, plus `running`, `succeeded`, or `failed`; it never
   includes client output, credentials, tokens, room content, or server
   responses. The form may display that status.
5. Only after a successful status may an administrator run the explicit
   non-sending connection test; sending a destination test remains a separate,
   audited action.

Matrix is the first implementation. Future initial provisioners for Mastodon,
Mattermost, Telegram, or other transports must reuse these boundaries rather
than performing an OAuth exchange, device setup, or network synchronization in
PHP-FPM. A transport without an appropriate service manager must provide an
equally isolated queue worker with the same secret-lifetime and status rules.
