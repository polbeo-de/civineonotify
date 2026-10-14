# CiviNeo Notify

`de.polbeo.civicrm.civineonotify` is an AGPL-3.0-or-later CiviCRM extension for
rule-based outbound notifications. It turns CiviCRM events into durable delivery
records and can send the same information independently through Matrix,
Mattermost (Webhook or API), Mastodon, Telegram, and Bluesky. It is currently
an alpha and is disabled by default.

## Why “CiviNeo Notify”?

The first working transport was Matrix, and “Neo” is a nod to that origin.
Today the name represents a general CiviCRM event broker: one event can be
routed to several independently configured destinations and channels. Matrix
is a supported transport, not the product boundary.

## What it does

- Normalizes `hook_civicrm_post` events into stable names such as
  `civicrm.contact.create`.
- Matches deterministic, JSON-structured rules; no PHP, SQL, Twig, or general
  expression evaluator is accepted.
- Renders an explicit, small token set and writes materialized delivery records
  to a persistent outbox.
- Provides a searchable event selector, JSON conditions, editable transport and
  destination records, delivery log, and an explicitly requested transport
  test.
- Separates hooks from transports. Sending is reserved for a scheduled worker
  and isolated transport implementations.

No external notification is sent at installation or activation. Inbound
commands are documented as a future, transport-neutral capability but are not
implemented.

## Requirements and installation

Target runtime is CiviCRM 6.16+ and PHP 8.1+; development reference is Civix
format 25.10.2. Install the extension normally into CiviCRM's extension
directory, install Composer development dependencies when running tests, enable
it in CiviCRM, then configure it under **Administer → System Settings → CiviNeo
Notify**. Keep it disabled until at least one transport, destination, and
explicit rule are reviewed.

Most transports use CiviCRM's PHP and cURL runtime only. A Matrix Commander
transport additionally needs the external `matrix-commander-rs` client. Its
supported credentials/store, stdin-message, room, timeout, and JSON-output
options have been verified against version 0.11.0. See
[configuration](docs/configuration.md) for all transport-specific setup.

For encrypted Matrix transports, follow the dedicated
[provisioning guide](docs/matrix-provisioning.md). It documents the bot account,
one-time token, credentials bundle, persistent E2EE store, and safe test order.

The optional Matrix-Nio E2EE/SAS path has completed an end-to-end staging
verification with Element: it provisions a dedicated device, compares the same
SAS on both sides, persists device trust, and sends an encrypted test message.
For a distinct Matrix user, the verification dialog also provides an
experimental SAS path which invites the selected member to a reusable
unencrypted verification room; it does not share room keys during verification.
It remains an **internal alpha integration**: its pinned Python runtime and
narrowly scoped interoperability patches are not bundled or published as an
installable artifact. External installations should use Matrix Commander until
a reproducible Matrix-Nio runtime bundle is released. Read the [Matrix-Nio
status and design rationale](docs/matrix-nio-limitations.md) before selecting
it.

Deployment responsibilities and the staging/release-artifact workflow are
documented in [deployment](docs/deployment.md). Production installations must
use the validated release ZIP, not a source checkout.

## Documentation and support

- [Architecture](docs/architecture.md)
- [Configuration](docs/configuration.md)
- [Initial transport provisioning](docs/provisioning.md)
- [Matrix-Nio E2EE and device-verification status](docs/matrix-nio-limitations.md)
- [Security and privacy](docs/security.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Inbound roadmap](docs/inbound-roadmap.md)

Report security vulnerabilities privately to the maintainer; do not include
credentials or personal data in tickets.
