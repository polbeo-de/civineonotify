# Provisioning an encrypted Matrix transport

This procedure creates the two local artifacts required by a Matrix transport:

- a `matrix-commander-rs` credentials JSON file (mode `0600`); and
- a persistent end-to-end-encryption (E2EE) store directory (mode `0700`).

Neither belongs in the database, the CiviCRM file area, the web root, a shell history, nor a ticket. The same store must remain with the same bot device for its entire life. CiviNeo Notify serializes use of that store so a scheduled worker and a manual test cannot use it concurrently.

## Before starting

1. Ask the Matrix homeserver administrator to create a dedicated bot/service account. Do not use a person's account.
2. Have the bot join the intended room. For encrypted notifications, the room must already use Matrix E2EE and the bot device must be trusted according to the homeserver's device-verification policy.
3. Obtain the password for that dedicated bot account through the homeserver's approved account-management process. Do not use a personal account or its Element device.
4. On the CiviCRM host, save the password in a temporary protected file containing only the password. Make it readable only by the account performing the provisioning. Never paste it into a CiviCRM field, command line, log, or configuration file.

The administrator needs four ordinary, non-secret facts: the HTTPS homeserver URL, the bot's target **internal room ID** (for example `!opaque:example.org`), the executable path for `matrix-commander-rs`, and a private directory outside the web root where the service account can maintain the credential file and store.

## Create the credential bundle

The protected **Provision Matrix credentials** action on an existing Matrix transport uploads a one-time password file. `matrix-commander-rs` uses it from standard input to create a dedicated Matrix device, credentials JSON, access token and persistent E2EE store together. The upload is deleted immediately. Neither password nor token is an argument to a process or a CiviCRM API, and neither is stored in the CiviCRM database.

The browser action runs as the PHP service account, which must be the same operating-system account that executes the CiviCRM worker. On the phprime8 reference host both are `phprime8`. Enter `/home/phprime8/tmp/civineonotify` as the private storage directory: the action creates this child directory as `0700`; do not use the world-readable `~/tmp` parent itself.

For controlled operational use, the extension also ships the equivalent `bin/provision-matrix-credentials` helper. Run it as that same account. Adapt all paths to the host; these are illustrative only:

```bash
umask 077
bin/provision-matrix-credentials \
  --matrix-client /path/to/matrix-commander-rs \
  --homeserver https://matrix.example.org \
  --user-login monitoring_bot \
  --room-id '!opaque:example.org' \
  --password-file /srv/civineonotify/onetime/bot-password \
  --credentials-file /srv/civineonotify/matrix-alerts/credentials.json \
  --store-path /srv/civineonotify/matrix-alerts/store
```

The script creates the parent directory and E2EE store with restrictive permissions, and prints no secret. Remove the one-time password file through the host's approved secret-handling process after successful provisioning. Do **not** remove or recreate `credentials.json` or `store/` while the transport is in use.

## Configure and verify CiviNeo Notify

Under **Administration → CiviNeo Notify → Transports**, create a Matrix transport and enter:

- **Matrix Commander binary**: the approved executable, e.g. `/home/phprime8/bin/matrix-commander-rs` on the staging reference host;
- **Credentials file**: the generated `credentials.json` path;
- **Encryption store**: the generated `store` path;
- **Working directory**: a private writable directory; and
- a conservative timeout, normally 30 seconds.

Save it, then use **Test transport**. That test uses `--whoami`; a successful result proves that HTTPS, the credentials and the Matrix identity agree without sending a message. Next create a Destination with the same internal room ID and use its explicit **Test** action. That is the only test which sends a message, and it proves room access and E2EE delivery. Review its delivery-log entry before enabling rules.

## Backup and recovery

Back up the credentials file and E2EE store together in protected storage, with their ownership and restrictive modes preserved. Restoring only one of them can create a new Matrix device or make previously encrypted history unavailable. If compromise is suspected, revoke the Matrix device at the homeserver, provision a new bot device and store, and update the transport; do not attempt to edit the credential JSON by hand.

## Browser provisioning

The provisioning page is restricted to **administer CiviNeo Notify**. It accepts only a small plain-text password file, moves it to a randomly named `0600` file inside the selected private directory, invokes the same audited helper, and removes that temporary file in all success and failure paths. It deliberately exposes neither the password, generated token nor helper output to the browser or CiviCRM logs.
