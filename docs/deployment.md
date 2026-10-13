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

Staging deployment is performed by private operator automation. It deploys only
the exact current `origin/staging` commit as a validated extension ZIP, enables
the extension, and runs its forward-only database upgrades. The public
repository intentionally contains no server connection details or deployment
automation.

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

Private operator automation compares the supplied asset byte-for-byte with a
fresh archive rebuilt from the annotated tag. It has no branch or checkout
mode. Server connection details and deployment paths are intentionally absent
from this repository.

## Retention and recovery

Each successful directory switch retains the prior extension directory on the
target host as a versioned backup adjacent to the active directory. Database
upgrades are forward-only; restoring code alone after an upgrade is not a safe
rollback. Investigate a failed production upgrade and restore from the
documented CiviCRM/database backup procedure instead of deleting data or
blindly switching code back.
