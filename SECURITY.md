# Security policy

## Report a vulnerability privately

Use GitHub’s **Report a vulnerability** form for this repository:

[Submit a private vulnerability report](https://github.com/RishadAlam/folkscript/security/advisories/new)

Do not post exploit details, credentials, private stories, or personal information in a public issue, discussion, or pull request. If the private reporting form is unavailable, open an issue asking for a private reporting channel without disclosing the vulnerability itself.

Include the affected version or commit, a clear description of the impact, and minimal reproduction steps using test data. If possible, explain which account role and configuration are required. Share only what is necessary to reproduce the problem, and redact secrets from logs and screenshots.

Maintainers will assess the report and coordinate a fix and disclosure with the reporter. There is no guaranteed response time or paid bounty program.

## Supported code

Security fixes target the current `main` branch. Separate maintenance branches and long-term support releases are not currently promised. When a fix is published, update to the fixed revision or release and follow any migration instructions. Check the repository’s [security advisories](https://github.com/RishadAlam/folkscript/security/advisories) for published notices.

## Safe research and deployment

Reproduce issues on an installation you own or have permission to test. Do not access another person’s private content, disrupt services, or send test messages to real users.

Installation owners are responsible for configuring HTTPS, production secrets, mail, backups, access controls, and dependency updates. Keep `APP_DEBUG=false` in production, serve only the `public/` directory, and do not enable demo data on a public installation. See [the deployment guide](docs/DEPLOYMENT.md).

The automated test suite and documented browser checks do not constitute an independent security audit.
