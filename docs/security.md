# Security and privacy

Credentials are referenced only by filesystem path. Never put access tokens, passwords, credential contents, Matrix stores, or real room identifiers in this repository. Logs and retained errors must be passed through the redactor, which masks common secret labels and limits length.

Templates deliberately provide only explicit event/entity/site tokens; there is no Twig, PHP, SQL, expression evaluator, or unbounded CiviCRM token expansion. Administrators remain responsible for limiting personal data routed to Matrix. The extension starts disabled and no installation or activation action sends a message. A timed-out process after dispatch is `delivery_unknown`, not retried automatically, because duplicate delivery may be possible.
