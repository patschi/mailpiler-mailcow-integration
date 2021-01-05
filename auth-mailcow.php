<?php
/***
/usr/local/etc/piler/auth-mailcow.php
(C) 2021, Patrik Kernstock - patrik.kernstock.net.
GNU GPL3 License - No warranty. Use at own risk.

## REQUIREMENTS
- working mailpiler installation:
piler 1.3.9 (no domain wildcards supported, but working)
piler 1.3.10 (fully working, tested)
- working mailcow installation
- adjusting mailpiler configuration as below

## DESCRIPTION
After logging in with IMAP authentication, this can be used
to retrieve all alias email addresses, including wildcard,
the mailbox/username has access to, including the realname.

## USAGE
Add config settings to /usr/local/etc/piler/config-site.php:
```
$config['MAILCOW_API_KEY'] = 'YOUR_READONLY_API_KEY';
$config['MAILCOW_SET_REALNAME'] = true; // default = false
$config['CUSTOM_EMAIL_QUERY_FUNCTION'] = 'query_mailcow_for_email_access';
include('auth-mailcow.php');
```
*/

// query_mailcow_for_email_access($username) is a custom authentication
// function to overwrite the email addresses the user has access to.
function query_mailcow_for_email_access($username = '')
{
	global $config;
	$session = Registry::get('session');
	$data = $session->get("auth_data");

	// get emails where user has access to.
	$emails = mailcow_get_aliases($username);
	$wildcards = [];
	foreach ($emails as $i => $email) {
		if (substr($email, 0, 1) === '@') {
			$wildcards[$i] = substr($email, 1);
			unset($emails[$i]);
		}
	}
	$data['emails'] = array_merge($data['emails'] , $emails);

	// set realname, if available.
	if (isset($config['MAILCOW_SET_REALNAME'])
	&& $config['MAILCOW_SET_REALNAME'] === true) {
		$realname = mailcow_get_mailbox_realname($username);
		if ($realname !== null) {
			$data['realname'] = $realname;
		}
	}

	// wildcard_domains support were implemented on 2020-10-31:
	// https://bitbucket.org/jsuto/piler/issues/1102
	// Released in piler 1.3.10.
	$session->set("wildcard_domains", $wildcards);
	$session->set("auth_data", $data);
}

// mailcow_get_aliases($mailbox) returns back the name of the
// mailbox user (specified in the mailbox details)
// Used to display this name on the top-right of mailpiler.
function mailcow_get_mailbox_realname($mailbox = '')
{
	// get mailbox info from mailcow instance
	$api = mailcow_query_api(sprintf('v1/get/mailbox/%s', $mailbox));
	if ($api !== null && !empty($api->name)) {
		return trim($api->name);
	}
	return null;
}

// mailcow_get_aliases($mailbox) returns back all aliases of
// given mailbox by querying the mailcow API.
function mailcow_get_aliases($mailbox = '')
{
	// get all aliases from mailcow instance
	$api = mailcow_query_api('v1/get/alias/all');
	if ($api !== null) {
		// valid data. yay!
		$emails = [];
		$mailbox = strtolower($mailbox);
		foreach ($api as $alias) {
			// check if alias is active (this is for newer instances)
			if (isset($alias->active_int) && $alias->active_int !== 1) {
				continue;
			}
			// check if alias is active (for older instances where active used)
			if (isset($alias->active) && $alias->active !== 1) {
				continue;
			}
			// if user email address is added to alias goto, allow access
			if (strpos(strtolower($alias->goto), $mailbox) !== false) {
				array_push($emails, strtolower($alias->address));
			}
		}
		return $emails;
	}

	// something went wrong.
	return [];
}

// mailcow_query_api($path) queries the mailcow API and returns
// back object with data or null when error occurred.
// In short: Basic API query function.
function mailcow_query_api($path)
{
	global $config;
	$path = urlencode($path);

	if (isset($config['MAILCOW_HOST'])) {
		$host = $config['MAILCOW_HOST'];
	} else {
		$host = $config['IMAP_HOST'];
	}

	// get data from mailcow instance
	$api = file_get_contents(
		sprintf('https://%s/api/%s', $host, $path),
		false, stream_context_create([
		'http' => [
			'method' => 'GET',
			'header' => [
				'Accept: application/json',
				sprintf('X-API-Key: %s', $config["MAILCOW_API_KEY"])
			],
			'timeout' => 5, // 5 secs timeout.
		]
	]));

	// decode json
	$api = json_decode($api);
	// check if we got valid json.
	if (json_last_error() === JSON_ERROR_NONE) {
		return $api;
	} else {
		return null;
	}
}
