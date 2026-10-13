# Security and privacy

Credentials and Mattermost webhook tokens remain outside CiviCRM. Matrix
credentials and stores are referenced by filesystem path; Mattermost hook
tokens are held in a protected external token-store file and are displayed only
by their final four characters. Never put access tokens, passwords, credential
contents, Matrix stores, webhook tokens, or real destination identifiers in
this repository. Logs and retained errors must be passed through the redactor,
which masks common secret labels and limits length.

Templates deliberately provide only explicit event/entity/site tokens; there is no Twig, PHP, SQL, expression evaluator, or unbounded CiviCRM token expansion. Administrators remain responsible for limiting personal data routed to Matrix. The extension starts disabled and no installation or activation action sends a message. A timed-out process after dispatch is `delivery_unknown`, not retried automatically, because duplicate delivery may be possible.
