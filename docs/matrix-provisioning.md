# Provisioning an encrypted Matrix transport

This procedure creates the two local artifacts required by a Matrix transport:

- a `matrix-commander-rs` credentials JSON file (mode `0600`); and
- a persistent end-to-end-encryption (E2EE) store directory (mode `0700`).

Neither belongs in the database, the CiviCRM file area, the web root, a shell history, nor a ticket. The same store must remain with the same bot device for its entire life. CiviNeo Notify serializes use of that store so a scheduled worker and a manual test cannot use it concurrently.

## Before starting

1. Ask the Matrix homeserver administrator to create a dedicated bot/service account. Do not use a person's account.
2. Have the bot join the intended room. For encrypted notifications, the room must already use Matrix E2EE and the bot device must be trusted according to the homeserver's device-verification policy.
3. Obtain the password for that dedicated bot account through the homeserver's approved account-management process. Do not use a personal account or its Element device.
4. Obtain the password only through the homeserver's approved account-management process. The protected **Provision Matrix credentials** form transmits it once over HTTPS to its isolated provisioning job; it is not stored in CiviCRM, a command line, a log, or configuration file. For the optional command-line procedure below, use a temporary protected file containing only the password and make it readable only by the provisioning account.

The administrator needs four ordinary, non-secret facts: the HTTPS homeserver URL, the bot's target **internal room ID** (for example `!opaque:example.org`), the executable path for `matrix-commander-rs`, and a private directory outside the web root where the service account can maintain the credential file and store.

## Create the credential bundle

The protected **Provision Matrix credentials** action on an existing Matrix transport accepts the bot password once over HTTPS. `matrix-commander-rs` receives it only on standard input to create a dedicated Matrix device, credentials JSON, access token and persistent E2EE store together. Neither password nor token is an argument to a process or a CiviCRM API, and neither is stored in the CiviCRM database.

The browser action runs as the PHP service account, which must be the same
operating-system account that executes the CiviCRM worker. Choose a dedicated
directory outside the web root that is not readable by other users; the action
creates it as `0700`. The page reports only safe provisioning stages and never
exposes Matrix client output.

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

- **Matrix Commander binary**: the approved `matrix-commander-rs` executable;
- **Credentials file**: the generated `credentials.json` path;
- **Encryption store**: the generated `store` path;
- **Working directory**: a private writable directory; and
- a conservative timeout, normally 30 seconds.

Create a Destination with the intended internal room ID and select it as the
transport's default destination. **Test transport** then sends the configured
default message template to that destination and records the outcome in the
delivery log. Review the entry before enabling rules.

## Backup and recovery

Back up the credentials file and E2EE store together in protected storage, with their ownership and restrictive modes preserved. Restoring only one of them can create a new Matrix device or make previously encrypted history unavailable. If compromise is suspected, revoke the Matrix device at the homeserver, provision a new bot device and store, and update the transport; do not attempt to edit the credential JSON by hand.

## Browser provisioning

The provisioning page is restricted to **administer CiviNeo Notify**. It passes the password directly to the audited helper through standard input. It deliberately exposes neither the password, generated token nor helper output to the browser or CiviCRM logs.
