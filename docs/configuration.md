# Configuration

Create a Matrix connection, then named rooms, and finally rules. Configure an absolute executable path, credentials path, store path, working directory and timeout. Only `matrix_commander_rs` is intended for production in 0.0.1. The Rooms list provides a **Test** action which immediately sends the configured default message template to that room and records the outcome in the delivery log.

The verified `phprime8` binary is `/home/phprime8/bin/matrix-commander-rs` version 0.11.0. It supports `--credentials`, `--store`, `--timeout`, `--message -` for stdin, `--room`, and `--output json`. Do not enable arbitrary extra options. Credential files should be owned by the service account and readable only by it (normally mode 0600). No explicit transaction-ID option was found, so delivery idempotency cannot be guaranteed after an ambiguous timeout.

For a supplied access token, provisioning must first call the homeserver's
`/_matrix/client/v3/account/whoami` endpoint using an authorization header,
then create the CLI credentials JSON with homeserver, user ID, device ID, token,
and room ID. The token is read from a protected file and must never become a
process argument, log value, or database value. This follows the established
operational pattern in the polbeo POC Matrix client.

Set global defaults, create inactive example rules, then enable both a specific rule and the extension. Configure the `CiviNeoNotifyDelivery.runoutbox` scheduled job to run serially. This is a CiviCRM API v3 bridge to the extension worker; scheduled jobs do not invoke custom API v4 actions directly. Back up credentials and the Matrix store separately from the database, using protected backup storage.

## Rules and conditions

Choose an event from the searchable **Event name** list. The list represents the CiviCRM post-hook events emitted by this extension, for example `civicrm.case.edit`. A rule can optionally also restrict entity type and operation.

Conditions are stored as JSON. `all` requires every clause to match; `any` requires one or more clauses to match. Supported operators are `=`, `!=`, `in`, `not_in`, `contains`, `is_empty`, `is_not_empty`, and `changed`. Fields may refer to `event.*`, `entity.*`, `data.*` (the value after a change), or `previous.*` (the value before a change).

Example: notify only when the status of a `polishubinstanz` case changes:

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
