# Troubleshooting

Start with the delivery log. It records the transport result, retry timing, and
sanitised error category for every manual test and queued delivery.

Configuration errors, invalid destinations, and authentication failures require
an administrator correction. Temporary transport errors and rate limits are
retried according to the configured retry policy. A timeout after a possible
send becomes `delivery_unknown` and needs an administrator's reviewed manual
retry, because retrying it automatically could duplicate a notification or
public post.

For token-based transports, confirm that the protected token-store directory is
outside the web root, writable by the CiviCRM service account, and contains the
secret shown by the form only as a masked suffix. For Mattermost API
destinations, confirm that the bot or token account is a member of the target
channel. For Telegram, send a new explicit command or channel post before
looking up a chat ID with `getUpdates`.

For Matrix, verify that the configured client binary is executable and that the
credentials file, E2EE store, and working directory have the required service
account permissions. If Matrix reports an invalid authorization header,
regenerate the credentials JSON from the protected token file after stripping a
trailing newline. Never pass the token as a process argument or CiviCRM setting.
