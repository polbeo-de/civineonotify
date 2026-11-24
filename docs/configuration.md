# Configuration

Create one or more transports, then named destinations, and finally rules. A
transport represents a sending identity or protocol configuration; a
destination represents where that transport sends. A matching event may create
multiple independent deliveries through different transports. The Transport
list provides a **Test** action which immediately sends a test message to its
default destination and records the outcome in the delivery log.

The **Transport Test** action uses the global **Transport test message
template** setting. It does not change the template used for normal CiviCRM
event notifications. The **Destination Test** action uses the global **Default
message template** setting.

## Protected transport storage directory

Set **Protected transport storage directory** in CiviNeo Notify settings to an
absolute, protected directory outside the web root. Matrix provisioning uses it
as its default. Mattermost, Slack, Rocket.Chat, Discord, Microsoft Teams,
Mastodon, Telegram, Bluesky, ntfy, Gotify, and Generic Webhook transports use separate token-store files in this directory
by default.
Existing explicit transport paths remain valid.

## Generic Webhook

The **Generic Webhook** transport sends a fixed JSON envelope with an HTTPS
`POST` request to each **Webhook endpoint** destination. One transport can
therefore serve multiple independent endpoints. Configure the transport with a
protected token-store file and timeout, then configure one destination with its
public HTTPS endpoint URL. The endpoint URL is normal addressing data; an
optional signing secret is stored outside CiviCRM in the protected token store
and is subsequently shown only by its final four characters.

The request body is deliberately not administrator-programmable:

```json
{
  "version": 1,
  "transaction_id": "…",
  "message": {
    "body": "Rendered CiviNeo message",
    "format": "plain"
  }
}
```

Every request includes `Idempotency-Key` and `X-CiviNeo-Transaction-Id` with
the durable delivery transaction ID. When a signing secret is configured, the
`X-CiviNeo-Signature` header is `sha256=` followed by the HMAC-SHA-256 of the
exact request body. Receivers should verify that signature before processing
the payload and should treat repeated idempotency keys as the same delivery.

Only public HTTPS endpoint URLs are accepted: direct private, loopback,
link-local, reserved, and `localhost`/`.local` targets are rejected; hostname
resolution must not yield such an address. Redirects are disabled. A 2xx
response is successful; 429, timeout, and 5xx responses follow the normal
retry policy. No response body or signing secret is written to the delivery
error log.

## Slack and Rocket.Chat

The **Slack** and **Rocket.Chat** transports are named outbound transports for
their respective incoming-webhook integrations. In both cases CiviCRM sends a
JSON `POST` to the target service; neither transport accepts commands from the
service or creates an inbound CiviCRM endpoint.

Create an incoming webhook in Slack or Rocket.Chat for each target channel,
then create the matching CiviNeo transport and destination. Enter the complete
incoming-webhook URL on the **Slack channel** or **Rocket.Chat channel**
destination. These URLs often contain bearer-like secrets, so CiviNeo stores
them only in the protected external token store and subsequently displays only
their last four characters. A single named transport can serve several
destinations, each with its own webhook URL.

The services receive their standard minimal incoming-webhook payload:

```json
{"text":"Rendered CiviNeo message"}
```

Each request is HTTPS-only, carries the delivery transaction ID as `Idempotency-Key` and
`X-CiviNeo-Transaction-Id`, and never follows redirects. A 2xx response is a
success; timeout, 429, and 5xx responses use the standard delivery retry
handling. Slack blocks and Rocket.Chat integrations may offer richer payload
features, but those deliberately remain out of scope for this first named
transport version.

## Discord

The **Discord** transport sends an outbound plain-text notification through a
Discord webhook. Create a Discord webhook for every channel that should receive
notifications, then create a matching **Discord channel** destination in
CiviNeo Notify. Enter the complete webhook URL in the destination form.

Discord webhook URLs contain bearer-like credentials. CiviNeo stores each URL
only in the protected external token store and later shows only its final four
characters. The message payload is deliberately the minimal Discord form:

```json
{"content":"Rendered CiviNeo message"}
```

The request is HTTPS-only, never follows redirects, and carries the durable
delivery transaction ID in `Idempotency-Key` and `X-CiviNeo-Transaction-Id`.
A 2xx response is successful; rate limits, timeouts, and 5xx responses follow
the standard retry policy. Embeds, attachments, mentions, and inbound Discord
commands are intentionally out of scope for this first version.

## Microsoft Teams

The **Microsoft Teams** transport targets the current Teams workflow-webhook
model. Do not create a legacy Microsoft 365 Connector: create a Teams workflow
that receives an HTTP webhook request and posts the received text to the
intended channel or chat. The workflow URL is the credential for one **Microsoft
Teams channel** destination.

Enter that URL in the destination form. CiviNeo stores it only in the protected
external token store and later shows only its final four characters. It sends
the documented minimal workflow payload:

```json
{"text":"Rendered CiviNeo message"}
```

Each request is HTTPS-only, does not follow redirects, and carries the durable
delivery transaction ID in `Idempotency-Key` and `X-CiviNeo-Transaction-Id`.
Teams workflows should have at least one additional owner: workflow ownership
is user-scoped and otherwise can become orphaned when its owner leaves. Adaptive
Cards, bot registration, and inbound Teams commands are deliberately outside
this transport's first version.

## ntfy

The **ntfy** transport sends outbound plain-text notifications to a topic on an
HTTPS ntfy server. Configure the server URL and protected token-store file on
the transport. An access token is optional: it is needed only when the server
protects publishing to the selected topic, and is stored outside CiviCRM.

Create one **ntfy topic** destination per topic. Topics are deliberately
limited to letters, digits, underscores, and hyphens so that the destination
can safely form one URL path segment. CiviNeo posts the rendered message as
plain text, its durable transaction ID in ntfy's `X-Id` header, and a CiviNeo
notification icon. The bundled icon is the default; an administrator may set a
different public HTTPS PNG or JPEG URL on the transport. The CiviCRM site must
therefore make the bundled static extension asset reachable from the ntfy
server and clients. The first named version keeps priorities, tags,
attachments, actions, and scheduled publishing out of scope.

## Gotify

The **Gotify** transport sends outbound plain-text notifications to a Gotify
application. Configure the HTTPS Gotify server URL and protected token-store
file on the transport, then create a **Gotify application** destination.
Create an application in Gotify for each independently targeted audience and
enter its application token in that destination. Application tokens identify
the destination, so CiviNeo keeps each token in the protected external store
and subsequently shows only its final four characters.

CiviNeo submits Gotify's standard JSON message with a fixed title, normal
priority, and the durable delivery transaction ID as metadata. The optional
transport-level **Gotify notification image URL** sets Gotify's `bigImageUrl`
extra; it is a large image supported by the Gotify Android client, not a
replacement for that client's fixed small Gotify icon. It does not use the
Gotify client API, nor does it receive messages from Gotify.

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

1. Add an active transport of type **Mattermost (Webhook)**, enter its
   server URL, and leave the hooks path as `hooks` unless the server uses a
   different path. Provide a protected, writable external token-store path.
2. Add an active **Mattermost channel** destination for that transport and
   enter its hook token. The form never shows the token again; it displays only
   the last four characters. Leave **Mattermost channel override** empty to use
   the incoming webhook's default channel, or enter an override only where the
   Mattermost server allows it.
3. Select that destination as the transport's **Default destination**.
4. Use the transport's **Test** action. It sends the global **Transport test
   message template**, records the result in the delivery log, and does not
   enqueue a normal CiviCRM event.

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

When creating a Mastodon transport, CiviNeo Notify offers to create and select
its neutral **Mastodon account** destination automatically. Keep the suggested
option or give the destination a meaningful name. You can also add a destination
manually. Supported visibility values are
**Public**, **Unlisted**, and **Followers only**. Direct messages, media,
polls, threads, and automatic truncation are deliberately unsupported.

The transport validates credentials with Mastodon's account endpoint and reads
the instance character limit where available. A message over a reliably known
limit is rejected rather than shortened. Every outbox delivery supplies a
stable Mastodon idempotency key derived from its transaction ID; a timeout is
recorded as an unknown delivery outcome to avoid creating a duplicate post.
Mastodon instance URLs must use HTTPS and must not resolve to local, private,
link-local, or reserved addresses. Redirects are disabled.

## Telegram

The Telegram transport sends outbound text notifications through the official
Telegram Bot API. Create the bot and its token with BotFather, then enter the
token directly in the transport form. CiviNeo Notify stores it only in the
protected external token store and subsequently displays only its last four
characters.

### Bot onboarding and chat IDs

1. Open **@BotFather** in Telegram and use `/newbot` to create a dedicated
   notification bot. Telegram displays its bot token only once. Treat it as a
   password: enter it directly in the CiviNeo Notify transport form and never
   add it to source control, a ticket, a screenshot, or a chat message.
2. Create the **Telegram** transport with that token and save it. The transport
   holds one bot identity and may serve multiple destinations.
3. Add the bot to every target group or supergroup. For a channel, add it as an
   administrator with permission to post messages. Direct-message destinations
   are not supported.
4. Obtain each target chat ID with one manual `getUpdates` request. First send
   a fresh command such as `/start@your_bot_username` in a group after adding
   the bot. For a channel, publish a fresh channel post after adding the bot as
   an administrator. Then run this locally, replacing the placeholder without
   exposing the real token:

   ```bash
   read -rs TELEGRAM_BOT_TOKEN
   curl -s "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/getUpdates"
   unset TELEGRAM_BOT_TOKEN
   ```

   In the JSON response, use `message.chat.id` for a group or
   `channel_post.chat.id` for a channel. CiviNeo Notify accepts the negative
   numeric IDs used by groups, supergroups, and channels; supergroups and
   channels commonly start with `-100`. An empty `result` means the bot has no
   pending update: confirm that it was added before the fresh command or post,
   then repeat the request. In groups, privacy mode still permits explicit
   commands addressed to the bot.
5. Create one **Telegram chat** destination per chat ID, then set one of those
   destinations as the transport's default. Rules may select any of the other
   Telegram destinations directly.

Use the transport's **Test** action only after selecting an active default
destination. It renders the global **Transport test message template**, sends
it to that destination, and records the result in the delivery log.

Plain text is the default format. HTML may be selected when the message
template contains only Telegram-supported markup. Link previews are disabled
by default; silent delivery and content protection are optional. The transport
does not receive updates, configure webhooks, poll Telegram, send media, or
split messages. Messages longer than 4,096 characters are rejected rather than
truncated.

Telegram does not provide a request idempotency mechanism for `sendMessage`.
If a request times out, CiviNeo Notify records an unknown delivery outcome and
does not automatically retry it, avoiding a possible duplicate post. Requests
use HTTPS only and do not follow redirects.

## Bluesky

The Bluesky transport publishes public text posts through one configured AT
Protocol account. Create an **App Password** in the Bluesky account settings;
never use the main account password. Configure the account handle and App
Password in CiviNeo Notify. The password is held only in the protected external
token store and is subsequently displayed only by its final four characters.

When creating a Bluesky transport, CiviNeo Notify offers to create and select
its neutral **Bluesky account** destination automatically. You can also add it
manually. Each transport represents one public account;
use additional transports for additional accounts. Posts are public only:
direct messages, visibility controls, media, threads, replies, editing, and
timeline polling are deliberately unsupported.

The transport resolves the handle to a DID and discovers the account PDS from
the DID document, so accounts hosted outside the default service are supported.
It creates a short-lived session for each send and refreshes its in-memory
access token once on an authentication expiry. App Passwords and session JWTs
are never written to CiviCRM, delivery logs, or dry-run output.

Posts may contain at most 300 Unicode grapheme clusters. Empty or longer posts
are rejected rather than truncated or split. An optional BCP 47 language tag
and automatic HTTP/HTTPS link facets are supported; link facet offsets use UTF-8
bytes as required by AT Protocol. The transport is outbound-only and does not
register a webhook or poll Bluesky.

Every delivery derives a stable protocol-valid record key from its transaction
identifier. On a record-key conflict, CiviNeo Notify reads the record and only
treats an identical post as delivered. A timeout remains an unknown delivery
outcome and is not retried automatically, avoiding duplicate public posts.

## Message templates and images

Message templates render text. A URL in a template remains a URL; CiviNeo does
not download, upload, or attach that URL as media. A receiving client may
unfurl it, but that is controlled by the receiving platform and must not be
relied on for delivery.

This distinction matters for publishing transports: Mastodon needs a separate
media-upload request followed by attachment IDs on the status, Bluesky needs
an uploaded blob embedded in the post record, and Telegram needs a media
method such as `sendPhoto`. Those workflows are intentionally not part of the
current text transports. Telegram link previews may be enabled by the
transport setting, but they are previews rather than sent media. ntfy is the
exception for a small per-notification icon, and Gotify can optionally show a
large Android notification image as documented above.

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

This is nevertheless an internal alpha integration. The verified runtime
contains a pinned Matrix-Nio version plus narrowly scoped interoperability
patches, but no reproducible runtime bundle is currently published. It is not
installable by external operators from the CiviNeo Notify ZIP; use Matrix
Commander for external installations. The rationale, upstreaming plan, and
remaining release criteria are documented in [Matrix-Nio E2EE and device-verification status](matrix-nio-limitations.md).

In particular, **allow unverified recipient devices** is a separate, explicit
risk decision. It permits message-key sharing with unverified recipient
devices; it does not verify the bot device and must not be used to mask a
failed or incomplete Matrix-Nio verification.

Set global defaults, create inactive example rules, then enable both a specific
rule and the extension. Configure the `CiviNeoNotifyDelivery.runOutbox`
scheduled job so executions do not overlap. It invokes the extension's
CiviCRM 6.16-compatible API v4 scheduled-job Action, which delegates to the
worker. Back up credentials and the Matrix store together, separately from
the database, using protected backup storage.

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

This example announces each newly created case to an internal operations
channel and a separate on-call channel. It illustrates the broker model: one
CiviCRM event can create independent deliveries for more than one destination
and transport.

First, configure two active destinations. For example, an **Operations cases**
destination can use a Mattermost transport while an **On-call cases**
destination uses Telegram. The destinations may use any supported transports;
the useful distinction here is the audience, not the provider.

Then create two active rules. Both rules use the same event and message
template, but each selects one of the two destinations:

- **Rule name**: `New case — operations`
- **Event name**: `civicrm.case.create`
- **Destination**: `Operations cases`
- **Message template**: `New case #{case.id}: {case.contact.first_name} {case.contact.last_name}`

For the second rule, use **Rule name** `New case — on-call` and select
`On-call cases` as its destination. When CiviCRM creates a case, CiviNeo Notify
queues one delivery for each matching rule. A failure or retry on one channel
does not prevent the other notification from being sent.

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
The **CiviNeoNotify: Process outbox** scheduled job invokes
`CiviNeoNotifyDelivery.runOutbox`; its worker selects due pending or retry
deliveries, checks that both transport and destination are active, and then
sends through the appropriate configured transport. Results, retry timing, and
failures are recorded in the delivery log. A failed delivery does not block
other queued deliveries.

## Message templates

Templates use a small allowlisted token language, not arbitrary Smarty or PHP. General tokens include `{event.name}`, `{event.operation}`, `{entity.type}`, `{entity.id}`, `{actor.contact_id}`, `{site.name}`, `{site.url}`, `{data.subject}`, and `{data.title}`.

For case events, these additional tokens provide readable values:

- `{case.id}`
- `{case.status.old}` and `{case.status.new}`
- `{case.contact.first_name}` and `{case.contact.last_name}`

For example: `Case #{case.id}: {case.status.old} → {case.status.new} ({case.contact.first_name} {case.contact.last_name})`.

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
