# Configuration

Create a transport, then named destinations, and finally rules. The Transport
list provides a **Test** action which immediately sends the configured default
message template to its default destination and records the outcome in the
delivery log.

## Mattermost incoming webhook

Create a Mattermost incoming-webhook transport with its HTTPS server URL, an
optional hooks path (default: `hooks`), and an absolute path to a protected
external token-store file. The server URL is normal configuration; webhook
tokens are not stored in CiviCRM, included in an extension archive, or written
to a delivery log. An incoming webhook is bound to its configured Mattermost
channel; create a destination for every independently targeted channel. A
Mattermost channel override may be configured only where the Mattermost server
permits it; leaving it empty uses the webhook's configured channel.

To configure it in CiviCRM:

1. Add an active transport of type **Mattermost incoming webhook**, enter its
   server URL, and leave the hooks path as `hooks` unless the server uses a
   different path. Provide a protected, writable external token-store path.
2. Add an active **Mattermost channel** destination for that transport and
   enter its hook token. The form never shows the token again; it displays only
   the last four characters. Leave **Mattermost channel override** empty to use
   the incoming webhook's default channel, or enter an override only where the
   Mattermost server allows it.
3. Select that destination as the transport's **Default destination**.
4. Use the transport's **Test** action. It sends the configured default message
   immediately, records the result in the delivery log, and does not enqueue a
   normal CiviCRM event.

Each transport has an independent delivery status and retry history. Therefore,
a Mattermost failure does not prevent delivery through a parallel Matrix or
other configured transport.

The Mattermost REST API is planned as a future transport type. It is not
implemented by the incoming-webhook transport and its credentials must likewise
remain outside CiviCRM.

## Matrix

The verified `matrix-commander-rs` version is 0.11.0. It supports
`--credentials`, `--store`, `--timeout`, `--message -` for stdin, `--room`,
and `--output json`. Do not enable arbitrary extra options. Credential files
should be owned by the service account and readable only by it (normally mode
0600). No explicit transaction-ID option was found, so delivery idempotency
cannot be guaranteed after an ambiguous timeout.

Follow the complete [encrypted Matrix provisioning procedure](matrix-provisioning.md) before creating the transport. It explains how the Matrix administrator creates the bot account and how the supplied helper creates its dedicated device, credentials file and persistent E2EE store without putting the bot password or generated token in a process argument, log, or database field.

## Matrix-Nio alpha integration

Matrix-Nio is the lower-memory E2EE option where `matrix-commander-rs` cannot
be provisioned within the host's memory limit. On the staging reference system
it has provisioned a device, completed Element SAS verification, persisted the
selected Element device as trusted, and sent an encrypted test message without
allowing unverified recipient devices.

This is nevertheless an alpha integration. The verified runtime contains a
pinned Matrix-Nio version plus narrowly scoped interoperability patches; it is
an external deployment prerequisite and is not included in the CiviNeo Notify
ZIP. Operators must use the documented runtime bundle for the release they
install. The rationale, upstreaming plan, and remaining release criteria are
documented in [Matrix-Nio E2EE and device-verification status](matrix-nio-limitations.md).

In particular, **allow unverified recipient devices** is a separate, explicit
risk decision. It permits message-key sharing with unverified recipient
devices; it does not verify the bot device and must not be used to mask a
failed or incomplete Matrix-Nio verification.

Set global defaults, create inactive example rules, then enable both a specific rule and the extension. Configure the `CiviNeoNotifyDelivery.runoutbox` scheduled job to run serially. This is a CiviCRM API v3 bridge to the extension worker; scheduled jobs do not invoke custom API v4 actions directly. Back up credentials and the Matrix store together, separately from the database, using protected backup storage.

## Rules and conditions

Choose an event from the searchable **Event name** list. The list represents the CiviCRM post-hook events emitted by this extension, for example `civicrm.case.edit`. A rule can optionally also restrict entity type and operation.

Conditions are stored as JSON. `all` requires every clause to match; `any` requires one or more clauses to match. Supported operators are `=`, `!=`, `in`, `not_in`, `contains`, `is_empty`, `is_not_empty`, and `changed`. Fields may refer to `event.*`, `entity.*`, `data.*` (the value after a change), or `previous.*` (the value before a change).

Example: notify only when a case status changes:

```json
{
  "all": [
    {"field": "data.case_type_id", "operator": "in", "value": [3, "3"]},
    {"field": "data.status_id", "operator": "changed"}
  ]
}
```

## Message templates

Templates use a small allowlisted token language, not arbitrary Smarty or PHP. General tokens include `{event.name}`, `{event.operation}`, `{entity.type}`, `{entity.id}`, `{actor.contact_id}`, `{site.name}`, `{site.url}`, `{data.subject}`, and `{data.title}`.

For case events, these additional tokens provide readable values:

- `{case.id}`
- `{case.status.old}` and `{case.status.new}`
- `{case.contact.first_name}` and `{case.contact.last_name}`

For example: `Fall #{case.id}: {case.status.old} → {case.status.new} ({case.contact.first_name} {case.contact.last_name})`.
