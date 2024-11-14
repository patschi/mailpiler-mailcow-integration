# mailpiler-mailcow-integration

This is a simple integration of mailcow aliases and the mailbox name into mailpiler when using IMAP authentication. [A guide can be found in the official mailcow docs here](https://mailcow.github.io/mailcow-dockerized-docs/u_e-mailpiler-integration/).

## Requirements

**Tested combinations:**

| piler version | PHP versions | Notes |
| ------------- | ------------ | ----- |
| 1.3.9         | 7.4          | Working, but domain wildcards are not yet implemented. |
| 1.3.10        | 7.4          | None |
| 1.4.1/1.4.2   | 7.4, 8.1     | None |
| 1.4.6         | 8.3          | Fixed on 2024-11-14 |
| Future        | n/a          | Future versions might work, but not tested. Raise [issue](https://github.com/patschi/mailpiler-mailcow-integration#issue) if broken. |

## Issue

Should you encounter any issue, please do following first:
1. Try the [latest version on GitHub](https://raw.githubusercontent.com/patschi/mailpiler-mailcow-integration/refs/heads/master/auth-mailcow.php) and check if the issue still exists.
2. Ensure you're using one of the [latest mailpiler versions](https://github.com/jsuto/piler/releases). Ancient versions won't be supported.
3. Check all [GitHub issues](https://github.com/patschi/mailpiler-mailcow-integration/issues?q=is%3Aissue) if your issue is already known or was already addressed.
4. If no luck, [open a issue](https://github.com/patschi/mailpiler-mailcow-integration/issues/new). Include as much details as possible to help understanding your issue and your environment.

## The problem to solve

mailpiler offers the authentication based on IMAP:

```php
$config['ENABLE_IMAP_AUTH'] = 1;
$config['IMAP_HOST'] = 'mail.example.com';
$config['IMAP_PORT'] = 993;
$config['IMAP_SSL'] = true;
```

So when you log in using `patrik@example.com`, you will only see delivered emails sent from or to this specific email address. When additional aliases are defined in mailcow, like `team@example.com`, you won't see emails sent from or to this email even the fact you're a recipient of mails sent to this alias.

With hooking into the authentication process of mailpiler this fires API requests to the mailcow API (requiring read-only API access) to read out the aliases your email address participates. Beside that, it will also read the "Name" of the mailbox specified to display it on the top-right of mailpiler after login.

## Configuration

[See setup instructions in the official mailcow docs here](https://mailcow.github.io/mailcow-dockerized-docs/u_e-mailpiler-integration/).

This integration is already automatically built-in when using the unofficial [mailpiler docker](https://github.com/simatec/piler-docker) project.
