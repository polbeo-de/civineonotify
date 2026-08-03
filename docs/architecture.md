# Architecture

CiviNeo Notify implements outbound Matrix notifications only. A narrow `hook_civicrm_post` normalizes a CiviCRM change, evaluates active rules, renders an allowlisted-token message, and materializes it in `MatrixNotification`. It never opens a process or network connection.

The scheduled worker is the only component permitted to send. It owns a single global lock because a local Matrix client store must not be used concurrently. It invokes the deployed `matrix-commander-rs` 0.11.0 client using separate process arguments and stdin for the message. Stable correlation and transaction IDs are persisted before send.

The CiviCRM Scheduled Jobs runner uses API v3. `CiviNeoNotifyDelivery.runoutbox` is therefore a narrow API v3 bridge to the worker; the administrative UI uses API v4 actions separately. A rule match only enqueues a notification. Retrying an entry returns it to `pending`; the serial worker performs the actual send.

The resolver boundary is `rule → event default (reserved) → entity default (reserved) → global setting`. Version 0.0.1 exposes rule and global values. Inbound Matrix support is deliberately not implemented; see `inbound-roadmap.md`.

## Environment evidence

On 2026-07-30, the local development shell has PHP 8.4.16 and Composer 2.10.1. The authoritative `phprime8` environment has CiviCRM 6.16.2 Standalone, PHP 8.4.22, `cv` 0.3.71, and `matrix-commander-rs` 0.11.0 at `/home/phprime8/bin/matrix-commander-rs`. Civix is not installed there; the extension follows the local CiviVerify Civix reference (`format 25.10.2`, entity-types PHP mixin, CiviMix schema upgrader). The verified CLI supports credentials/store paths, stdin message input, rooms, timeouts and JSON output; it exposes no explicit transaction-ID option, so timeout-after-send is `delivery_unknown`.
