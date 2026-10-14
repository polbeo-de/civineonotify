# Matrix transport provisioning model

Matrix transports need a one-time, stateful setup: creating a device,
obtaining an access token, and establishing encrypted keys. That work must
never run in a CiviCRM web request.

CiviNeo Notify uses this contract for Matrix provisioning:

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

Mastodon, Mattermost, Telegram, and Bluesky currently use administrator-created
tokens or App Passwords entered directly into their transport forms. They do
not run a provisioning job. If a future transport needs a stateful credential
exchange or device setup, it must use an equally isolated background workflow
rather than performing it in PHP-FPM.
