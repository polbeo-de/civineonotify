# Changelog

## Unreleased

- Rename the extension key from `de.polbeo.civicrm.matrixnotify` to
  `de.polbeo.civicrm.civineonotify`; Matrix remains the first supported transport.
- Reconcile the initial scaffolding with CiviCRM 6.16.2 Standalone and the deployed matrix-commander-rs 0.11.0 CLI.
- Expose the shared scheduled-job bridge as `CiviNeoNotifyDelivery.runoutbox`;
  transports are processed through one common outbox.
- Add administration screens for connections, rooms, rules, delivery log, editable configuration records, contextual breadcrumbs and per-room Matrix tests.
- Add a searchable event selector for rules and deterministic JSON conditions, including a `changed` operator for state transitions.
- Add case-status change routing for `polishubinstanz` cases, with message tokens for case ID, old/new status and associated contact name.
- Add connections, rooms, rules, persistent outbox worker, retries, recovery and scheduled-job processing through the CiviCRM API v3 job bridge.
- Verify encrypted outbound delivery, rule matching and scheduled-job execution on phprime8; add the PHPUnit/PHPCS baseline.
