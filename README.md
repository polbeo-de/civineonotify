# CiviNeo Notify

`de.polbeo.civicrm.civineonotify` is an AGPL-3.0-or-later CiviCRM extension for
rule-based outbound notifications. Matrix is its first transport. It is
currently an alpha and is disabled by default.

## Why “CiviNeo Notify”?

The first working transport was Matrix, and “Neo” is a small nod to that
origin. It became clear very quickly, however, that CiviCRM needs a general
outbound-notification capability rather than a Matrix-only feature. CiviNeo
Notify therefore starts with Matrix as its reference transport, while its
product direction is to support further destinations and messenger services.

## What it does

- Normalizes `hook_civicrm_post` events into stable names such as
  `civicrm.contact.create`.
- Matches deterministic, JSON-structured rules; no PHP, SQL, Twig, or general
  expression evaluator is accepted.
- Renders an explicit, small token set and writes materialized delivery records
  to a persistent outbox.
- Provides a searchable event selector, JSON conditions, editable transport and
  destination records, delivery log, and an explicitly requested Matrix test.
- Separates hooks from transport. Sending is reserved for a serial scheduled
  worker and is designed for `matrix-commander-rs` through a safe transport
  boundary.

No Matrix message is sent at installation or activation. Stage 2 inbound Matrix
commands are documented but intentionally not present.

## Requirements and installation

Target runtime is CiviCRM 6.15+ and PHP 8.1+; development reference is Civix
format 25.10.2. Install the extension normally into CiviCRM's extension
directory, install Composer development dependencies when running tests, enable
it in CiviCRM, then configure it under **Administer → System Settings → CiviNeo
Notify**. Keep it disabled until a transport, destination, and explicit rule
are reviewed.

`matrix-commander-rs` is an external deployment prerequisite. Its supported
credentials/store, stdin-message, room, timeout, and JSON-output options have
been verified against version 0.11.0. See
[configuration](docs/configuration.md).

For encrypted Matrix transports, follow the dedicated
[provisioning guide](docs/matrix-provisioning.md). It documents the bot account,
one-time token, credentials bundle, persistent E2EE store, and safe test order.

The optional Matrix-Nio E2EE/SAS path has completed an end-to-end staging
verification with Element: it provisions a dedicated device, compares the same
SAS on both sides, persists device trust, and sends an encrypted test message.
It remains an **alpha integration**: its pinned Python runtime and narrowly
scoped interoperability patches are an external deployment prerequisite, not
bundled with this extension archive. Read the [Matrix-Nio status and design
rationale](docs/matrix-nio-limitations.md) before selecting it.

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
