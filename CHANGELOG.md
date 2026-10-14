# Changelog

## Unreleased

- Make the transport-test message a separately configurable global template.

- Add a Bluesky public-post transport with protected App Password storage,
  PDS discovery, short-lived session refresh, Unicode grapheme validation,
  automatic URL facets, and stable record-key delivery safety.

## 0.0.3-alpha.4 — 2026-10-14

- Add a Telegram Bot API transport with protected external bot-token storage,
  group/supergroup/channel delivery, per-destination chat IDs, HTML or plain
  text formatting, delivery options, and conservative unknown-outcome handling
  for timeouts.
- Add Telegram bot onboarding and chat-ID discovery documentation for groups,
  supergroups, and channels.
- Display successful destination tests as successful rather than as warnings.
- Set the default notification template to `{event.name} Hello, world!`.

## 0.0.3-alpha.3 — 2026-10-14

- Add the Mastodon transport with protected access-token storage, account
  validation, public/unlisted/followers-only text posts, instance-aware
  character-limit validation, and stable delivery idempotency keys.
- Add Mastodon language selection, retained transport-form defaults, and masked
  suffix displays for stored Mastodon and Mattermost API tokens.
- Simplify transport test posts to the rendered event name followed by
  `Hello World`.

## 0.0.3-alpha.2

- Add a shared protected transport storage directory setting for Matrix and
  Mattermost credentials, stores, and runtime files.
- Add a parallel Mattermost API transport using protected external API-token
  storage, selectable accessible channels, and channel-ID destinations; rename
  the existing UI label to Mattermost (Webhook).
- Document Mattermost personal-access-token enablement, per-bot role
  permission, token creation, and channel membership requirements.
- Add an experimental Matrix-Nio SAS flow which invites a selected member of
  the active default destination to a reusable unencrypted verification room,
  without sharing room keys during verification; retain the existing
  same-account device flow.
- Reuse one unencrypted Matrix verification room per transport and remove the
  selected user after success, cancellation, timeout, or worker failure.
- Preserve Matrix-Nio SAS cancellation codes in verification status for
  actionable diagnostics.
- Fix Mattermost transport edits so selecting a default destination does not
  require the hidden Matrix working-directory field.
- Limit destination-type choices to the type supported by the selected
  transport.
- Add a Mattermost incoming-webhook transport with configurable server URL and
  hooks path, external per-destination token storage, masked token display,
  optional server-permitted channel overrides, and per-transport delivery
  status handling.
- Add Mattermost destination management and a transport **Test** action that
  immediately posts to the configured webhook default channel and records the
  outcome in the delivery log.
- Generalize the transport message and destination contracts so Matrix and
  Mattermost can deliver the same queued CiviCRM event independently.

- Complete the Matrix-Nio/Element SAS verification flow on staging: compare
  matching emoji, persist the selected Element device as trusted, send the
  terminal Matrix verification event, and confirm encrypted delivery without
  opting in to unverified recipient devices.
- Make the Matrix-Nio transport test send the configured default destination,
  retain provisioning and verification status references when a transport is
  edited, and provide an actionable missing-default-destination state.

- Add an experimental Matrix-Nio SAS verification diagnostic that can initiate
  a request against an explicitly selected Element device, reset stale controls
  before retrying, and display available peer devices.

- Add a non-sending Matrix-Nio E2EE probe to evaluate an additional Matrix
  client implementation while retaining matrix-commander-rs for deployments
  with sufficient memory. The probe requires an explicit opt-in before sharing
  a message session with unverified recipient devices.

- Generalize the Alpha data model from Matrix connections and rooms to transports and destinations; a forward-only upgrade preserves existing Matrix configuration and delivery history.
- Move the default destination to each transport and remove global Matrix connection/room defaults.

- Rename the extension key from `de.polbeo.civicrm.matrixnotify` to
  `de.polbeo.civicrm.civineonotify`; Matrix remains the first supported transport.
- Reconcile the initial scaffolding with CiviCRM 6.16.2 Standalone and the deployed matrix-commander-rs 0.11.0 CLI.
- Expose the shared scheduled-job bridge as `CiviNeoNotifyDelivery.runoutbox`;
  transports are processed through one common outbox.
- Add administration screens for connections, rooms, rules, delivery log, editable configuration records, contextual breadcrumbs and per-room Matrix tests.
- Retire the superseded `MatrixNotification.runoutbox` API-v3 bridge.
- Add a searchable event selector for rules and deterministic JSON conditions, including a `changed` operator for state transitions.
- Add case-status change routing, with message tokens for case ID, old/new
  status and associated contact name.
- Add connections, rooms, rules, persistent outbox worker, retries, recovery and scheduled-job processing through the CiviCRM API v3 job bridge.
- Verify encrypted outbound delivery, rule matching and scheduled-job
  execution; add the PHPUnit/PHPCS baseline.
