# CiviNeo Notify – working agreement

This repository implements the CiviCRM extension `de.polbeo.civicrm.civineonotify`.

- The deployed extension directory on `phprime8` is `/home/phprime8/www/html/civicrm-standalone/ext/de.polbeo.civicrm.civineonotify`.
- The predecessor `de.polbeo.civicrm.matrixnotify` was retired during the key migration; do not deploy it again.

- Apply the canonical standards in `../de.polbeo.engineering`: PES-001 through
  PES-007 and PEW-001. Do not duplicate them here.
- Source code, comments, and technical documentation are English. User-visible
  strings go through `CRM_Civineonotify_ExtensionUtil::ts()`.
- Never commit Matrix credentials, access tokens, stores, real room IDs, or
  personal data. Run a secret scan before commits.
- Keep hooks short: they may enqueue notifications but must never start a
  process or access Matrix.
- Use APIv4/entity definitions for extension data and forward-only schema
  migrations. New PHP files use `declare(strict_types=1)`.
- The authoritative integration test system is `phprime8`. Its CiviCRM
  standalone installation is `/home/phprime8/www/html/civicrm-standalone`.
  Its CiviCRM command-line tool is `/home/phprime8/bin/cv` (Civix is not
  installed there). The local operational repository is `/home/phprime8/poc`
  and the Matrix CLI binary is `/home/phprime8/bin/matrix-commander-rs`. Inspect
  these paths before choosing CiviCRM/Civix or transport behavior.
- The verified runtime is CiviCRM 6.16.2 Standalone, PHP 8.4.22, `cv` 0.3.71,
  and `matrix-commander-rs` 0.11.0. The Matrix CLI accepts `--credentials`,
  `--store`, `--timeout`, `--message -` (stdin), `--room`, and `--output json`;
  no explicit transaction-ID option was found.
- For Matrix access-token provisioning, use the proven implementation in
  `/home/mcc8/poc/functions/_lib/matrix_client.sh` as the authoritative
  operational reference. It obtains `user_id`/`device_id` via Matrix `/whoami`,
  builds a mode-0600 credentials JSON with `jq --rawfile`, keeps the token out
  of child-process arguments, and serializes access to the persistent E2EE
  store.
- Do not introduce an abstraction, fake, or fallback merely because a required
  runtime component was not found by a broad probe. First ask the maintainer
  for the authoritative path or deployment detail. Test doubles are permitted
  only where tests genuinely need them, and must not replace the real supported
  production implementation.
- Run `composer lint`, unit tests, and PHP syntax checks for changed files when
  available. The separate PES audit is deliberately a later task.
- Version 0.0.1 is an implementation baseline, not a public release: do not
  tag or publish it without PEW-003 and maintainer approval.
