# Provisioning an encrypted Matrix transport

This procedure creates the two local artifacts required by a Matrix transport:

- a `matrix-commander-rs` credentials JSON file (mode `0600`); and
- a persistent end-to-end-encryption (E2EE) store directory (mode `0700`).

Neither belongs in the database, the CiviCRM file area, the web root, a shell history, nor a ticket. The same store must remain with the same bot device for its entire life. CiviNeo Notify serializes use of that store so a scheduled worker and a manual test cannot use it concurrently.

## Before starting

1. Ask the Matrix homeserver administrator to create a dedicated bot/service account. Do not use a person's account.
2. Have the bot join the intended room. For encrypted notifications, the room must already use Matrix E2EE and the bot device must be trusted according to the homeserver's device-verification policy.
3. Obtain an access token for that bot account from the Matrix administrator or the homeserver's approved service-account process. Token issuance is a homeserver policy decision; CiviNeo Notify neither creates Matrix accounts nor receives a password.
4. On the CiviCRM host, save the token in a temporary protected file containing only the token. Make it readable only by the account performing the provisioning. Never paste it into a CiviCRM field, command line, log, or configuration file.

The administrator needs four ordinary, non-secret facts: the HTTPS homeserver URL, the bot's target **internal room ID** (for example `!opaque:example.org`), the executable path for `matrix-commander-rs`, and a private directory outside the web root where the service account can maintain the credential file and store.

## Create the credential bundle

The extension ships `bin/provision-matrix-credentials`. It reads the token from a file, calls Matrix `/_matrix/client/v3/account/whoami`, and writes the credentials JSON only after it has obtained the canonical user ID and device ID. The token is deliberately not an argument to `curl`, `matrix-commander-rs`, or any CiviCRM API.

Run it as the same operating-system account that executes the CiviCRM worker. Adapt all paths to the host; these are illustrative only:

```bash
umask 077
bin/provision-matrix-credentials \
  --homeserver https://matrix.example.org \
  --room-id '!opaque:example.org' \
  --access-token-file /srv/civineonotify/onetime/bot-token \
  --credentials-file /srv/civineonotify/matrix-alerts/credentials.json \
  --store-path /srv/civineonotify/matrix-alerts/store
```

The script requires `curl` and `jq`. It creates the parent directory and E2EE store with restrictive permissions, and prints no secret. Remove the one-time token file through the host's approved secret-handling process after successful provisioning. Do **not** remove or recreate `credentials.json` or `store/` while the transport is in use.

## Configure and verify CiviNeo Notify

Under **Administration → CiviNeo Notify → Transports**, create a Matrix transport and enter:

- **Matrix Commander binary**: the approved executable, e.g. `/home/phprime8/bin/matrix-commander-rs` on the staging reference host;
- **Credentials file**: the generated `credentials.json` path;
- **Encryption store**: the generated `store` path;
- **Working directory**: a private writable directory; and
- a conservative timeout, normally 30 seconds.

Save it, then use **Test transport**. That test uses `--whoami`; a successful result proves that HTTPS, the token, and the Matrix identity agree without sending a message. Next create a Destination with the same internal room ID and use its explicit **Test** action. That is the only test which sends a message, and it proves room access and E2EE delivery. Review its delivery-log entry before enabling rules.

## Backup and recovery

Back up the credentials file and E2EE store together in protected storage, with their ownership and restrictive modes preserved. Restoring only one of them can create a new Matrix device or make previously encrypted history unavailable. If compromise is suspected, revoke the Matrix token at the homeserver, provision a new bot device and store, and update the transport; do not attempt to edit the credential JSON by hand.

## Planned protected UI

The current alpha deliberately keeps the one-time secret outside the web request path and uses the audited server-side helper above. The intended next UI step is a protected provisioning page that accepts a one-time token upload, performs this exact procedure server-side, deletes the upload immediately, and never persists the token in CiviCRM. It will not replace the documented manual procedure until its filesystem ownership and upload lifecycle have been tested on the supported hosts.
