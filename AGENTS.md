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

## Public release workflow

Use the same review trail for every public pre-release and stable release:

1. Create `publish/<version>` from the approved staging commit and update the
   version, changelog, documentation, and release checklist.
2. Run the required validation and secret scan, build the installation ZIP from
   the annotated tag, and verify the intended archive before publication.
3. Push the release branch and open a pull request from `publish/<version>` to
   `main`. A tag or GitHub Release never replaces this pull request.
4. With maintainer approval, publish the GitHub Release as the `polbeo`
   account, never as an individual maintainer or release preparer. Verify the
   displayed release author after publication. Use `<version>` alone as the
   release title (no product-name prefix), attach the installation ZIP as a
   release **asset**, and mark testing builds as pre-releases.
5. Write release notes with a `## What's Changed` heading and concise,
   noteworthy changes, followed by GitHub's `**Full Changelog**:` comparison
   link from the preceding tag to the new tag. Use GitHub's generated release
   notes/contributor data where available so release contributors are shown;
   do not put the sole archive link only in the release text.
6. Merge the approved release pull request into `main`. Keep the branch and
   tag references consistent with the published release.

## Deployment paths

- The CiviCRM CLI is available as `~/bin/cv`.
- On U8, the CiviCRM root is `~/www/html/civicrm-standalone`.
- On U7, the CiviCRM root is `~/html/civicrm-standalone`.
