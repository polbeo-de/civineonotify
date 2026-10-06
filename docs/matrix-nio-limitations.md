# Matrix-Nio E2EE and device-verification status

## Verified alpha scope

This note records the Matrix-Nio staging evaluation on 2026-10-05 and
2026-10-06. It is both an operator-facing explanation and the basis for
upstream reports or narrowly scoped patches. It contains no account names,
access tokens, room IDs, device keys, or event payloads.

On the CiviNeo Notify staging system, the Matrix-Nio path has demonstrated all
of the following with Element and an encrypted Matrix room:

1. Create and persist a dedicated Matrix device, protected session, and E2EE
   store outside the web root.
2. Send a targeted `m.key.verification.request` to an administrator-selected
   Element device, receive `ready`, and negotiate SAS.
3. Display the same seven emoji and decimal SAS values in Element and CiviNeo
   Notify, then complete the explicit human confirmation.
4. Persist the verified Element device in the Matrix-Nio trust store and send
   the required terminal `m.key.verification.done` event.
5. Send an encrypted Matrix test message without enabling the opt-in for
   unverified recipient devices.

The Matrix-Nio transport is therefore usable for the documented alpha path.
It is not yet a generally supported production runtime: the working Python
environment includes a pinned Matrix-Nio version and local interoperability
patches that must be packaged reproducibly before a stable CiviNeo release.

## Why CiviNeo Notify contains a small verification worker

Current Element clients expect the verification framework sequence
`request`, `ready`, `start`, `accept`, `key`, `mac`, `done`. Matrix-Nio does
not expose a complete high-level initiator for the request/ready part, so the
short-lived CiviNeo worker sends a targeted raw request, waits for the matching
ready event, and then uses Matrix-Nio's SAS machinery for the remainder. The
worker is bounded, targets exactly one administrator-selected device, and does
not participate in normal message delivery.

When the peer MAC arrives after the local confirmation, Matrix-Nio can mark the
SAS object verified without persisting device trust on its own. CiviNeo Notify
explicitly persists that verified device before reporting success. It also
sends the terminal `done` event because Element keeps its comparison dialog
open without it.

## Current runtime limitation

The required Matrix-Nio interoperability fixes are not yet upstream releases.
They cover the modern `hkdf-hmac-sha256.v2` MAC negotiation, Vodozemac emoji
index handling, request/ready event modelling, and safe verification-state
diagnostics. CiviNeo Notify must not silently rely on an arbitrary system
`pip install matrix-nio`: an operator needs the documented, pinned runtime
bundle for the matching CiviNeo release.

The source fixes should be split into small, independently tested upstream pull
requests. CiviNeo-specific concerns — the bounded worker, protected status
files, administration UI, and delivery policy — remain in this extension.

## Operator guidance

- A successful **Verify Matrix Nio device** result means the selected Element
  device is trusted by the CiviNeo Matrix-Nio store. It does not automatically
  trust other newly created Element devices.
- Do not enable **allow unverified recipient devices** merely to make a warning
  disappear. It is a separate security decision that allows room-key sharing
  with unverified recipient devices.
- If a test reports `unverified_recipient_device`, verify the indicated
  recipient device or deliberately make that opt-in; do not re-provision the
  transport solely for that error.
- Provisioning and verification state is stored outside the extension archive.
  Editing a transport preserves its references; reinstalling the extension
  alone neither recreates nor deletes the protected Matrix-Nio store.

## Conditions before a stable runtime

Before Matrix-Nio is called a stable CiviNeo transport, we need all of the
following:

1. A reproducible, versioned runtime artifact with pinned dependencies and a
   documented upgrade path.
2. Automated interoperability coverage for Element-compatible SAS verification
   and encrypted room delivery.
3. Upstream pull requests or an explicitly maintained, reviewed patch set for
   the required Matrix-Nio fixes.
4. A user-oriented verification flow that does not expose protocol vocabulary
   or unsafe diagnostics to ordinary administrators.

When reporting problems upstream, include only redacted protocol metadata:
versions, event types and order, and sanitized exception classes. Never attach
access tokens, passwords, recovery keys, room IDs, device IDs, public keys,
session files, or raw logs.
