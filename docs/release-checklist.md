# Public release checklist

Use this checklist for every public pre-release and stable release. The
maintainer authorising publication records the completed checks in the release
pull request.

- [ ] Start from the approved staging commit and create `publish/<version>`.
- [ ] Update `info.xml`, the changelog, relevant documentation, and release
  notes.
- [ ] Run lint, unit tests, PHP syntax checks, archive validation, and a secret
  scan.
- [ ] Create an annotated `<version>` tag at the approved release commit.
- [ ] Build the installation ZIP from that tag with
  `bin/build-extension-archive --require-annotated-tag` and verify its
  single-directory layout and checksum.
- [ ] Push the release branch and open a pull request to `main`.
- [ ] Publish the GitHub release as `polbeo`, attach the ZIP as an asset, and
  verify the release author, title, pre-release status, release notes, and
  contributors.
- [ ] Merge the approved release pull request and keep the branch, tag, and
  published release references aligned.
