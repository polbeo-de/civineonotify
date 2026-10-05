# Architecture

CiviNeo Notify implements outbound Matrix notifications only. A narrow `hook_civicrm_post` normalizes a CiviCRM change, evaluates active rules, renders an allowlisted-token message, and materializes it in `CiviNeoNotifyDelivery`. It never opens a process or network connection.

The scheduled worker is the only component permitted to send. It owns a single global lock because a local Matrix client store must not be used concurrently. It invokes the deployed `matrix-commander-rs` 0.11.0 client using separate process arguments and stdin for the message. Stable correlation and transaction IDs are persisted before send. Stateful initial setup is not a web-request concern; it follows the isolated background-job contract in [provisioning](provisioning.md).

The CiviCRM Scheduled Jobs runner uses API v3. `CiviNeoNotifyDelivery.runoutbox` is therefore a narrow API v3 bridge to the worker; the administrative UI uses API v4 actions separately. A rule match only enqueues a notification. Retrying an entry returns it to `pending`; the serial worker performs the actual send.

The resolver boundary is `rule → event default (reserved) → entity default (reserved) → global setting`. Version 0.0.1 exposes rule and global values. Inbound Matrix support is deliberately not implemented; see `inbound-roadmap.md`.

## Matrix client strategy

`matrix-commander-rs` remains a supported Matrix transport implementation for
installations with sufficient memory for its initial E2EE device synchronisation.
It is not the only intended implementation. On the phprime8 reference host,
an isolated `matrix-nio` E2EE proof successfully created a device, persisted a
store, synchronised, and delivered a test notification within a 350 MiB service
limit, where matrix-commander-rs was OOM-killed. The Matrix-Nio helper remains
deliberately non-production code pending a complete verification flow and a
validated deployment dependency.

The prototype can also send the first targeted verification request to Element,
but it cannot yet complete an Element SAS comparison. Its raw request/ready
compatibility workaround, the observed Matrix-Nio limitations and production
exit criteria are documented in [matrix-nio-limitations.md](matrix-nio-limitations.md).
It must not be represented as a trusted-device implementation before those
criteria are met.

Matrix-Nio refuses by default to share message keys with unverified recipient
devices. A future transport must retain that safe default and expose any
`allow unverified recipient devices` setting as an explicit, documented
administrator choice. It does not replace verification of the persistent bot
device itself.

## Environment evidence

On 2026-07-30, the local development shell has PHP 8.4.16 and Composer 2.10.1. The authoritative `phprime8` environment has CiviCRM 6.16.2 Standalone, PHP 8.4.22, `cv` 0.3.71, and `matrix-commander-rs` 0.11.0 at `/home/phprime8/bin/matrix-commander-rs`. Civix is not installed there; the extension follows the local CiviVerify Civix reference (`format 25.10.2`, entity-types PHP mixin, CiviMix schema upgrader). The verified CLI supports credentials/store paths, stdin message input, rooms, timeouts and JSON output; it exposes no explicit transaction-ID option, so timeout-after-send is `delivery_unknown`.
