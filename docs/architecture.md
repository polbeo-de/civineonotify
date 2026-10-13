# Architecture

CiviNeo Notify implements configurable outbound transports, initially Matrix and Mattermost incoming webhooks. A narrow `hook_civicrm_post` normalizes a CiviCRM change, evaluates active rules, renders an allowlisted-token message, and materializes it in `CiviNeoNotifyDelivery`. It never opens a process or network connection.

The scheduled worker is the only component permitted to send. It owns a single global lock because a local Matrix client store must not be used concurrently. It invokes the deployed `matrix-commander-rs` 0.11.0 client using separate process arguments and stdin for the message. Stable correlation and transaction IDs are persisted before send. Stateful initial setup is not a web-request concern; it follows the isolated background-job contract in [provisioning](provisioning.md).

The CiviCRM Scheduled Jobs runner uses API v3. `CiviNeoNotifyDelivery.runoutbox` is therefore a narrow API v3 bridge to the worker; the administrative UI uses API v4 actions separately. A rule match only enqueues a notification. Retrying an entry returns it to `pending`; the serial worker performs the actual send.

The resolver boundary is `rule → event default (reserved) → entity default (reserved) → global setting`. Version 0.0.1 exposes rule and global values. Inbound Matrix support is deliberately not implemented; see `inbound-roadmap.md`.

## Matrix client strategy

`matrix-commander-rs` remains a supported Matrix transport implementation for
installations with sufficient memory for its initial E2EE device synchronisation.
It is not the only intended implementation. An isolated `matrix-nio` E2EE
integration successfully created a device,
persisted a store, completed Element SAS verification, and delivered an
encrypted test notification within a 350 MiB service limit, where
matrix-commander-rs was OOM-killed. Matrix-Nio remains an alpha transport until
its pinned runtime and interoperability patches are independently reproducible
as a releaseable external deployment artifact.

The integration sends a targeted verification request to Element, waits for
the matching ready event, compares the same SAS on both sides, persists local
trust for the verified Element device, and sends the terminal verification
event. Its raw request/ready compatibility workaround, remaining upstream work,
and release criteria are documented in
[matrix-nio-limitations.md](matrix-nio-limitations.md).

Matrix-Nio refuses by default to share message keys with unverified recipient
devices. A future transport must retain that safe default and expose any
`allow unverified recipient devices` setting as an explicit, documented
administrator choice. It does not replace verification of the persistent bot
device itself.

## Compatibility evidence

The reference runtime is CiviCRM 6.16+ with PHP 8.1–8.4 and
`matrix-commander-rs` 0.11.0. The verified CLI supports credentials/store
paths, stdin message input, rooms, timeouts and JSON output; it exposes no
explicit transaction-ID option, so timeout-after-send is `delivery_unknown`.
