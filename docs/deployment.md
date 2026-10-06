# Deployment

## Environments and source of truth

| Environment | Accepted source | Purpose |
| --- | --- | --- |
| Staging | Exact commit from the designated staging branch | Integration and release-candidate checks |
| Production | Validated installation ZIP built from an annotated release tag | Live operation |

`main` is the integration and release source. `staging` is advanced from an
approved `main` commit and is the only branch that may be deployed to the
staging environment. Production never receives a Git working tree, an `rsync` of a
developer checkout, or an arbitrary branch. It receives the same ZIP that an
external administrator downloads for installation.

## Staging deployment

Advance `staging` deliberately, then deploy its current remote commit:

```bash
git switch staging
git merge --ff-only main
git push <staging-remote> staging
bin/deploy-staging \
  --host <staging-host> \
  --civi-root <civicrm-root> \
  --cv <cv-path>
```

The script fetches `origin/staging`, builds a validated ZIP from that exact
commit, transfers it to the host, switches only the extension directory,
enables the extension, and runs `upgrade:db`. It deliberately does not invoke
notification jobs.

## Release artifact and production deployment

Only an annotated tag matching the extension version is eligible. After the
release checks required by PEW-003, build the asset from the tag:

```bash
VERSION=0.1.0
bin/build-extension-archive \
  --ref "$VERSION" \
  --version "$VERSION" \
  --require-annotated-tag \
  --output "dist/civineonotify-${VERSION}.zip"
```

Validate the archive before publishing it to the designated forge. The archive
contains exactly one top-level directory:
`de.polbeo.civicrm.civineonotify/`.

The production deployer compares the supplied asset byte-for-byte with a fresh
archive rebuilt from the local annotated tag. It has no branch or checkout
mode:

```bash
bin/deploy-release \
  --artifact "dist/civineonotify-${VERSION}.zip" \
  --version "$VERSION" \
  --host <production-host> \
  --civi-root <civicrm-root> \
  --cv <cv-path>
```

The CiviCRM and `cv` paths must be supplied by the maintainer before the first
production deployment; they are intentionally not guessed or baked into the
repository.

## Retention and recovery

Each successful directory switch retains the prior extension directory on the
target host as a versioned backup adjacent to the active directory. Database
upgrades are forward-only; restoring code alone after an upgrade is not a safe
rollback. Investigate a failed production upgrade and restore from the
documented CiviCRM/database backup procedure instead of deleting data or
blindly switching code back.
