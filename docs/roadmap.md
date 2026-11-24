# Roadmap

This roadmap describes intended work; it is not a delivery commitment. Features
remain subject to security review, transport capability, and a stable data
model.

## Individual media for outbound notifications

CiviNeo Notify currently renders text messages. A future media feature should
let an individual rule or delivery select an explicit media source rather than
interpreting a URL embedded in a message template.

The common model must define:

- an explicit source, initially either a CiviCRM-managed file or a public HTTPS
  URL, with an opt-in policy for remote retrieval;
- required alt text, media type and size limits, plus per-transport capability
  checks before queueing a delivery;
- a durable, delivery-safe snapshot of the selected source so a retry cannot
  publish different media;
- data-retention, access-control and privacy rules, including whether a remote
  service may retrieve an image directly from the CiviCRM site;
- clear behaviour for transports that support text but not media.

The transport implementations will use their native media mechanisms rather
than relying on link previews: Mastodon media upload followed by status media
IDs, Bluesky blob upload and record embeds, Telegram media methods such as
`sendPhoto`, and the corresponding webhook payloads where available. ntfy's
small notification icon and Gotify's optional Android large image remain
transport settings, not per-message media attachments.

## Inbound commands

Inbound work is documented separately in
[Inbound roadmap](inbound-roadmap.md). It remains intentionally lower priority
than safe, observable outbound delivery.
