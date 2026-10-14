# Security and privacy

Transport credentials remain outside CiviCRM. Matrix credentials and E2EE
stores are referenced by filesystem path; Mattermost, Mastodon, Telegram, and
Bluesky secrets are held in protected external token-store files and are
displayed only by their final four characters where a form needs to confirm
their presence. Never put access tokens, passwords, credential contents,
encrypted stores, webhook tokens, or real destination identifiers in this
repository. Logs and retained errors must be passed through the redactor, which
masks common secret labels and limits length.

Templates deliberately provide only explicit event/entity/site tokens; there is
no Twig, PHP, SQL, expression evaluator, or unbounded CiviCRM token expansion.
Administrators remain responsible for limiting personal data routed to every
configured destination. The extension starts disabled and no installation or
activation action sends a message. A timed-out request after dispatch is
`delivery_unknown`, not retried automatically, because duplicate delivery may
be possible.
