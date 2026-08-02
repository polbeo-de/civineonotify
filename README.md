# CiviNeo Notify

`de.polbeo.civicrm.civineonotify` is an AGPL-3.0-or-later CiviCRM extension for
rule-based outbound Matrix notifications. It is currently at implementation
baseline **0.0.1** and is disabled by default.

## What it does

- Normalizes `hook_civicrm_post` events into stable names such as
  `civicrm.contact.create`.
- Matches deterministic, JSON-structured rules; no PHP, SQL, Twig, or general
  expression evaluator is accepted.
- Renders an explicit, small token set and writes materialized delivery records
  to a persistent outbox.
- Provides a searchable event selector, JSON conditions, editable configuration
  records, delivery log, and an explicitly requested per-room Matrix test.
- Separates hooks from transport. Sending is reserved for a serial scheduled
  worker and is designed for `matrix-commander-rs` through a safe transport
  boundary.

No Matrix message is sent at installation or activation. Stage 2 inbound Matrix
commands are documented but intentionally not present.

## Requirements and installation

Target runtime is CiviCRM 6.15+ and PHP 8.1+; development reference is Civix
format 25.10.2. Install the extension normally into CiviCRM's extension
directory, install Composer development dependencies when running tests, enable
it in CiviCRM, then configure it under **Administer → System Settings → Matrix
Notify**. Keep it disabled until a connection, room, and explicit rule are
reviewed.

`matrix-commander-rs` is an external deployment prerequisite and is installed
on `phprime8` as `/home/phprime8/bin/matrix-commander-rs` (version 0.11.0).
Its supported credentials/store, stdin-message, room, timeout, and JSON-output
options have been verified against that binary. See
[configuration](docs/configuration.md).

## Documentation and support

- [Architecture](docs/architecture.md)
- [Configuration](docs/configuration.md)
- [Security and privacy](docs/security.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Inbound roadmap](docs/inbound-roadmap.md)

Report security vulnerabilities privately to the maintainer; do not include
credentials or personal data in tickets. This repository adopts the canonical
polbeo Engineering Standards in `../de.polbeo.engineering` (PES-001–PES-007 and
PEW-001); the independent PES audit is a separate follow-up task.
