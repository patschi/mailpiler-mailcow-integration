<?php
/***
/usr/local/etc/piler/auth-mailcow.php
(C) 2020, Patrik Kernstock - patrik.kernstock.net.
GNU GPL3 License - No warranty. Use at own risk.

## DESCRIPTION
After logging in with IMAP authentication, this can be used
to retrieve all alias email addresses the mailbox/username
has access to, including the realname.

## USAGE
Add to /usr/local/etc/piler/config-site.php:
```
$config["MAILCOW_API_KEY"] = 'YOUR_READONLY_API_KEY';
$config['CUSTOM_EMAIL_QUERY_FUNCTION'] = 'query_mailcow_for_email_access';
include('auth-mailcow.php');
```
*/

// query_mailcow_for_email_access($username) is a custom authentication 
// function to overwrite the email addresses the user has access to.
function query_mailcow_for_email_access($username = '')
{
	$session = Registry::get('session');
	$data = $session->get("auth_data");

	// get emails where user has access to.
	$emails = mailcow_get_aliases($username);
	foreach ($emails as $i => $email) {
		if (substr($email, 0, 1) === '@') {
			$emails[$i] = '*'.$email;
		}
	}
	$data['emails'] = array_merge($data['emails'] , $emails);

	// set realname, if available.
	$realname = mailcow_get_mailbox_realname($username);
	if ($realname !== null) {
		$data['realname'] = $realname;
	}

	$session->set("auth_data", $data);
	file_put_contents("/tmp/test.txt", print_r($data, true));
}

// mailcow_get_aliases($mailbox) returns back the name of the
// mailbox user (specified in the mailbox details)
// Used to display this name on the top-right of mailpiler.
function mailcow_get_mailbox_realname($mailbox = '')
{
	// get mailbox info from mailcow instance
	$api = mailcow_query_api(sprintf('v1/get/mailbox/%s', urlencode($mailbox)));
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
			if ($alias->active_int === 1
			&& strpos(strtolower($alias->goto), $mailbox) !== false) {
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

	// get data from mailcow instance
	$api = file_get_contents(
		sprintf('https://%s/api/%s', $config['IMAP_HOST'], $path),
		false, stream_context_create([
		'http' => [
			'method' => 'GET',
			'header' => [
				'Accept: application/json',
				sprintf('X-API-Key: %s', $config["MAILCOW_API_KEY"])
			],
			'timeout' => 10, // 10 secs timeout.
		]
	]));

	// decode json
	$api = json_decode($api);
	// check if we got valid json.
	if (json_last_error() == JSON_ERROR_NONE) {
		return $api;
	} else {
		return null;
	}
}
