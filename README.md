# mailpiler-mailcow-integration

This is a simple integration of mailcow aliases and the mailbox name into mailpiler when using IMAP authentication. [A guide can be found in the official mailcow docs here](https://mailcow.github.io/mailcow-dockerized-docs/u_e-mailpiler-integration/).

## Requirements

### piler

Tested combinations:

| piler version | PHP versions | Notes |
| ------------- | ------------ | ----- |
| 1.3.9         | 7.4          | Working, but domain wildcards are not yet implemented. |
| 1.3.10        | 7.4          | None |
| 1.4.1/1.4.2   | 7.4, 8.1     | None |
| Future        | n/a          | Future versions might work, but not tested. |

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

[See more details in the official mailcow docs here](https://mailcow.github.io/mailcow-dockerized-docs/u_e-mailpiler-integration/).
