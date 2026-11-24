# Release checklist

Record the version, candidate commit, test environment, results and any accepted
limitations for each release. An unchecked item is not evidence of completion.

## Candidate record — 0.0.3-alpha.8

- **Scope:** Generic Webhook, Slack, Rocket.Chat, Discord, Microsoft Teams,
  ntfy, and Gotify outbound transports; transport setup documentation;
  expanded German administration translations; optional ntfy Android
  notification imagery; reproducible installation archives.
- **Candidate:** Annotated tag `0.0.3-alpha.8`, built as a reproducible
  installation ZIP from its tagged commit.
- **Validation environment:** CiviCRM 6.20.1 on an isolated test installation.
  The extension was installed from a tagged ZIP and upgraded from the previous
  tagged alpha release. The APIv4 outbox job configuration and worker action
  were verified.
- **Accepted limitation:** Matrix-Nio remains an experimental integration. Its
  external runtime bundle and upstream interoperability status remain subject
  to [the documented Matrix-Nio limitations](matrix-nio-limitations.md).

## Candidate validation

- [ ] Agree the release scope and document experimental features and limitations.
- [ ] Run lint, unit tests, PHP syntax checks and translation catalogue checks.
- [ ] Run a secret scan and review the public archive for operator-specific data.
- [ ] Exercise each included transport against its service, including success,
  invalid credentials, failure handling and unknown delivery outcomes.
- [ ] Verify rules, destination selection, protected storage, masked secrets,
  worker retries and delivery logs on a staging CiviCRM installation.
- [ ] Install the release ZIP on a clean supported CiviCRM installation and
  test the upgrade from the previous public release, including scheduled jobs.
- [ ] Confirm documentation, changelog, version and translations match the
  candidate; review Matrix-Nio's experimental status separately.

## Publication

- [ ] Create `publish/<version>` and open a pull request to `main`.
- [ ] Obtain maintainer approval for this checklist and the release.
- [ ] Create an annotated version tag and build and inspect the installation ZIP
  from that tag as described in [deployment](deployment.md).
- [ ] Publish as `polbeo`, attach the ZIP as a release asset, include meaningful
  release notes and the full changelog comparison, and mark pre-releases.
- [ ] Verify the published author, tag, asset and links; merge the approved PR.
