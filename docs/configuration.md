# Configuration

Create a transport, then named destinations, and finally rules. The Transport
list provides a **Test** action which immediately sends the configured default
message template to its default destination and records the outcome in the
delivery log.

The **Test** action uses a fixed, readable test body: the rendered test event
name followed by `Hello World`. It does not change the template used for normal
CiviCRM event notifications.

## Protected transport storage directory

Set **Protected transport storage directory** in CiviNeo Notify settings to an
absolute, protected directory outside the web root. Matrix provisioning uses it
as its default. Mattermost transports use `mattermost-tokens.json` in this
directory by default. Existing explicit transport paths remain valid.

## Mattermost (Webhook)

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

## Mattermost (API)

The API transport uses a Mattermost personal access token or bot token stored
in the protected external token store. Configure the HTTPS server URL and the
token store, then add a destination and choose one of the channels accessible
to that token. The extension reads public and private team channels through the
Mattermost v4 API; it does not store the token in CiviCRM. The channel ID is
stored internally in the destination configuration, while the administrator
sees the team and channel names.

Before creating the token, a Mattermost system administrator must enable
**Personal Access Tokens** in **System Console → Integrations → Integration
Management**. The administrator must also allow the selected bot account to
create personal access tokens through Mattermost role management. After that,
sign in as the bot account and create a token in **Profile → Security →
Personal Access Tokens**. Mattermost displays the token only once; enter it
directly in the CiviNeo Notify transport form and do not save it in CiviCRM,
source control, or documentation.

Add the bot account to the team and every channel it should notify. Reload the
destination form after saving the transport token; the channel list contains
only channels accessible to that bot account.

Incoming webhooks do not support channel discovery; their credentials must
likewise remain outside CiviCRM.

## Mastodon

The Mastodon transport publishes plain-text notifications through one
organisation account. Create a personal access token for that account with
`read:accounts` and `write:statuses` scopes, then configure an HTTPS instance
URL, the token, default visibility, and optional language or content warning.
The token is stored only in the protected external token store.
The language is selected from the CiviCRM language list and stored as a BCP 47
tag; leave it empty when the account should not declare a post language.

Create one **Mastodon account** destination for each Mastodon transport and set
it as that transport's default destination. Supported visibility values are
**Public**, **Unlisted**, and **Followers only**. Direct messages, media,
polls, threads, and automatic truncation are deliberately unsupported.

The transport validates credentials with Mastodon's account endpoint and reads
the instance character limit where available. A message over a reliably known
limit is rejected rather than shortened. Every outbox delivery supplies a
stable Mastodon idempotency key derived from its transaction ID; a timeout is
recorded as an unknown delivery outcome to avoid creating a duplicate post.
Mastodon instance URLs must use HTTPS and must not resolve to local, private,
link-local, or reserved addresses. Redirects are disabled.

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

Create rules under **Administration → CiviNeo Notify: Rules**. A rule matches one
normalised CiviCRM post-hook event and, when it matches, queues one
notification. Rules with the same event are evaluated by ascending **Priority**
(then by ID). **Stop after match** prevents later matching rules for that event
from being evaluated.

Every rule needs:

- **Rule name**: an administrator-facing name.
- **Event name**: an exact event name from the searchable list, for example
  `civicrm.case.create` or `civicrm.case.edit`. It is not a wildcard or a
  regular expression.
- **Message template**: the notification body, using only the documented
  tokens below.
- **Conditions (JSON)**: optional additional matching criteria. Leave it empty
  or use `{}` to match every occurrence of the selected event.
- **Destination** or **Transport**: select a destination to send there. If
  only a transport is selected, its active default destination is used. A rule
  without a resolved transport and destination cannot be queued.

Conditions use exactly one top-level `all` or `any` array. `all` requires every
clause to match; `any` requires at least one. Each clause has `field` and
`operator`, with `value` required where the selected operator needs one.
Supported operators are `=`, `!=`, `in`, `not_in`, `contains`, `is_empty`,
`is_not_empty`, and `changed`. `in` and `not_in` require an array value.
`changed` is valid only for `data.*` fields and compares the current value with
the matching `previous.*` value.

Fields may refer to `event.name` and `event.operation`, `entity.type` and
`entity.id`, or a key present in `data.*` (the value after a change) and
`previous.*` (the value before a change). A missing field evaluates as an empty
value; test such rules with representative records before activating them.

### Example: notify when a new case is created

Create an active rule with these form values:

- **Rule name**: `New case`
- **Event name**: `civicrm.case.create`
- **Destination**: the destination that should receive the notification
- **Message template**: `New case #{case.id}: {case.contact.first_name} {case.contact.last_name}`

The event name already limits this rule to newly created cases, so conditions
are optional. The following JSON is an explicit, equivalent filter and a useful
starting point when a rule must combine several criteria:

```json
{
  "all": [
    {"field": "entity.type", "operator": "=", "value": "Case"},
    {"field": "event.operation", "operator": "=", "value": "create"}
  ]
}
```

For comparison, this filter sends a notification only when a case status
changes and the case type is one of the listed values:

```json
{
  "all": [
    {"field": "data.case_type_id", "operator": "in", "value": [3, "3"]},
    {"field": "data.status_id", "operator": "changed"}
  ]
}
```

### Delivery path

Rules do not send messages during the CiviCRM post-hook. The path is:

`CiviCRM post-hook` → `normalised event` → `active matching rule` →
`rendered message` → `outbox delivery` → `scheduled worker` →
`transport and destination`.

The selected destination determines its transport. When only a transport is
set on the rule, CiviNeo Notify resolves that transport's default destination.
The delivery is stored as `pending` before any external transport is called.
The serial **CiviNeoNotify: Process outbox** scheduled job invokes
`CiviNeoNotifyDelivery.runoutbox`; its worker selects due pending or retry
deliveries, checks that both transport and destination are active, and then
sends through the appropriate Matrix or Mattermost implementation. Results,
retry timing, and failures are recorded in the delivery log. A failed delivery
does not block other queued deliveries.

## Message templates

Templates use a small allowlisted token language, not arbitrary Smarty or PHP. General tokens include `{event.name}`, `{event.operation}`, `{entity.type}`, `{entity.id}`, `{actor.contact_id}`, `{site.name}`, `{site.url}`, `{data.subject}`, and `{data.title}`.

For case events, these additional tokens provide readable values:

- `{case.id}`
- `{case.status.old}` and `{case.status.new}`
- `{case.contact.first_name}` and `{case.contact.last_name}`

For example: `Fall #{case.id}: {case.status.old} → {case.status.new} ({case.contact.first_name} {case.contact.last_name})`.

### Extending the token allowlist

Adding a token is a code change, not an administrator setting. Keep the token
language deliberately small: a token must expose one clearly named,
non-sensitive value that is useful in a notification. Do not add credentials,
authentication material, contact details beyond the documented case values, or
an unrestricted `{data.*}` or `{previous.*}` pattern. Event payloads can carry
fields that are not suitable for a notification destination.

To add an approved token:

1. Add its exact name to the `$allowed` list in
   `Civi/Civineonotify/Rule/MessageRenderer.php`.
2. Add the corresponding value to the `$map` in the same class, or add an
   equally narrow explicit resolver. Return an empty string when the value is
   not applicable to the current event; do not load additional CiviCRM records
   while rendering a message.
3. Add a unit test in `tests/phpunit/Rule/MessageRendererTest.php` for the
   rendered value. Keep the test that rejects an undeclared token, and add a
   rejection test for a nearby sensitive field when appropriate.
4. Add the token to the list above, with its availability and empty-value
   behaviour where that is not obvious. Record the user-visible addition in
   `CHANGELOG.md`.

The renderer validates every placeholder before rendering. Unknown tokens make
the template invalid rather than being interpreted as Smarty, PHP, a method
call, or a data lookup.
