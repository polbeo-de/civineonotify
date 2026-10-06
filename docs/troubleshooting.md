# Troubleshooting

Verify the configured binary exists and is executable, then verify credentials, store directory and working directory permissions. A missing binary is a configuration error. Network, rate limit and store-lock failures are retryable; invalid room and authorization failures are permanent. A timeout after a possible send becomes `delivery_unknown` and needs an administrator's reviewed manual retry.

If Matrix reports an invalid authorization header, regenerate the credentials JSON from the protected token file after stripping a trailing newline. Never pass the token as a process argument or CiviCRM setting.
