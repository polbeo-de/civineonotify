# CiviNeo Notify – contribution guidance

This repository implements the CiviCRM extension
`de.polbeo.civicrm.civineonotify`.

- Production deployments accept only a validated release ZIP built from an
  annotated tag. Never deploy a working tree or an arbitrary branch directly
  to production.
- Do not record site names, host names, paths, account names, room IDs, or
  other operator infrastructure in public source, documentation, examples, or
  commit messages.
- Source code, comments, and technical documentation are English. User-visible
  strings go through `CRM_Civineonotify_ExtensionUtil::ts()`.
- Never commit Matrix credentials, access tokens, stores, real room IDs, or
  personal data. Run a secret scan before commits.
- Keep hooks short: they may enqueue notifications but must never start a
  process or access Matrix.
- Use APIv4/entity definitions for extension data and forward-only schema
  migrations. New PHP files use `declare(strict_types=1)`.
- The tested Matrix Commander interface accepts credentials and E2EE-store
  paths, a timeout, a stdin message, a room, and JSON output. Do not add
  unverified command-line options to the transport implementation.
- Do not introduce an abstraction, fake, or fallback merely because a required
  runtime component was not found by a broad probe. First ask the maintainer
  for the authoritative path or deployment detail. Test doubles are permitted
  only where tests genuinely need them, and must not replace the real supported
  production implementation.
- Run `composer lint`, unit tests, and PHP syntax checks for changed files when
  available. The separate PES audit is deliberately a later task.
- Do not tag or publish without an approved release checklist and maintainer
  approval.
