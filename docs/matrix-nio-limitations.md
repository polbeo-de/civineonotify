# Matrix-Nio E2EE and device-verification status

## Scope and current status

This note records results from the CiviNeo Notify staging evaluation of
`matrix-nio` 0.26.0 on 2026-10-05. It is both an operator-facing explanation
and a reproducible basis for upstream reports or a narrowly scoped local
patch. It contains no account names, access tokens, room IDs, device keys, or
event payloads.

The Matrix-Nio prototype has demonstrated that it can create and persist a
dedicated Matrix device and E2EE store, publish that device, send an encrypted
test message after an explicit opt-in for unverified recipients, query other
devices of the bot account, and deliver a manually sent
`m.key.verification.request` to Element.

It has **not** demonstrated a completed SAS verification with Element. Element
accepts the request but remains on the “compare emoji” spinner and CiviNeo
Notify receives no usable SAS comparison. A Matrix-Nio transport must therefore
not be described as trusted or used where verified-device trust is required.
Its current purpose is a staging prototype and diagnostic path, not a
production replacement for the documented Matrix Commander transport.

## Reproduced interoperability gaps

### 1. The high-level Nio SAS start is not the current Element handshake

Calling Matrix-Nio's high-level `start_key_verification()` directly does not
first send the request/ready exchange expected by current Matrix clients. In
the staging test, Element rejected or did not continue that direct start with
an unknown transaction. The Matrix specification's verification framework
requires a `m.key.verification.request`, a `m.key.verification.ready`, and
only then the SAS start event. See the [Matrix key-verification
framework](https://spec.matrix.org/latest/client-server-api/#key-verification-framework).

### 2. Nio does not provide a complete request/ready event flow

Matrix-Nio 0.26.0 does not expose a complete high-level implementation of the
modern `m.key.verification.request` / `m.key.verification.ready` exchange.
This is consistent with the long-standing upstream report [matrix-nio#430](https://github.com/matrix-nio/matrix-nio/issues/430)
and the broken verification example described in [matrix-nio#512](https://github.com/matrix-nio/matrix-nio/issues/512).

CiviNeo Notify therefore has a deliberately small compatibility workaround:

1. It sends a targeted raw `m.key.verification.request` to the administrator's
   selected Element device, with a new transaction ID.
2. It reads the matching raw `m.key.verification.ready` to-device event.
3. Only after that event does it create the Nio `Sas` object with the same
   transaction ID and send the SAS start event.

The workaround is confined to the prototype verification worker. It does not
change normal encrypted delivery, does not broadcast a request to all devices,
and expires after a short-lived worker run. It is present because direct Nio
initiation was demonstrably incompatible with Element, not because the
extension redefines the Matrix protocol.

### 3. SAS never reaches a comparable state

With the compatibility request in place, Element shows the incoming request
and enters its emoji-comparison dialog. It then remains loading; neither side
has produced a SAS for the administrator to compare. This prevents a claim of
successful verification even though the preceding request was delivered.

One current upstream report, [matrix-nio#570](https://github.com/matrix-nio/matrix-nio/issues/570), describes a SAS commitment encoding incompatibility with
Element in a recent Matrix-Nio release. It is a plausible lead for this
symptom, but it has **not** been proven to be its cause in the CiviNeo Notify
test. Do not report it as the root cause without a minimal upstream
reproduction or event-level evidence.

### 4. Own-device discovery requires an explicit key query

A normal sync did not populate Matrix-Nio's local device store with the bot
account's other devices in the staging environment. An explicit `keys_query`
for the bot user did. CiviNeo Notify performs that query before presenting
devices for verification. This is an interoperability detail, rather than a
reason to infer that no Element devices exist.

## Consequences for administrators

- Treat **Verify Matrix Nio device** as experimental while this document says
  SAS completion is incomplete. A failed or waiting verification does not make
  the transport trusted.
- Do not enable delivery to unverified recipient devices merely to make the
  warning disappear. That opt-in is a separate security decision, documented
  in the transport configuration.
- If verified-device trust is required today, use the supported Matrix
  Commander deployment path on a host with sufficient memory, or defer the
  Matrix-Nio transport until end-to-end SAS verification is proven.
- A provisioned Matrix-Nio device and a successfully delivered encrypted probe
  show that credentials, server access and basic E2EE work; they do not prove
  SAS verification.

## How to make a useful upstream report or patch

First reproduce with the smallest possible Matrix-Nio script: one account,
two devices for that account, an encrypted-capable homeserver, and a single
target device. Capture only redacted protocol metadata:

- Matrix-Nio and Python versions;
- homeserver implementation and version;
- Element version and platform;
- event *types* and their order (`request`, `ready`, `start`, `accept`, `key`,
  `mac`, `done`), plus whether their transaction IDs match;
- whether the SAS initiator or responder advances state; and
- the exact exception class/message after redacting identifiers.

Never attach access tokens, passwords, recovery keys, room IDs, complete
device IDs, Curve25519/Ed25519 keys, session files, or raw logs that might
contain them. A local patch should first be covered by an automated two-device
interoperability test against a supported Element-compatible client, then be
proposed upstream rather than becoming permanent extension protocol code.

## Exit criteria for production use

The Matrix-Nio transport may be considered for normal production use only when
all of the following are demonstrated on a supported homeserver and Element
version:

1. Element receives the request and shows a finite SAS comparison.
2. Both participants produce the same emoji or decimal SAS.
3. Confirming the match completes the protocol and marks the CiviNeo device
   verified.
4. Cancel, timeout and a fresh retry leave no stale worker or verification
   state.
5. Logs and the CiviCRM UI disclose no credential, token, key, or sensitive
   event data.

Until then, the workaround is intentionally documented technical debt with a
clear removal condition.
