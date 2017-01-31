# MailChimp for Multisite Auth

Multisite Auth MailChimp Addon lets you easily opt-in users from your WordPress network registration forms.

This plugin uses <a href="https://github.com/drewm/mailchimp-api">MailChimp API</a> lib by <a href="https://github.com/drewm">@drewm</a> to make API calls with MailChimp servers.

This plugin requires the parent plugin <a href="https://github.com/elhardoum/multisite-auth">Multisite Auth</a>

## Preview

<img src="http://i.imgur.com/sPOHOMo.png" alt="admin setings preview" />
<img src="http://i.imgur.com/AlYxbaR.png" alt="register form preview" />

## Hook into opt-in

```php
add_action('muauth_mc_catch_optin_response', function($response, $user_email, $lists){
	// loop through lists
	foreach ( $lists as $list_id ) {
		$res = $response[$list_id];

		if ( muauth_mc_status_success( $res['succes'] ) ) {
			// this email ($user_email) was successfully opted-in, do actions
		} else {
			// error occured, could not opt-in, debug $res
		}
	}
}, 10, 3);
```

## Update user after signup success

By default, the only information inserted to the mailing list is the email address. But there is a method to edit the list signup to add more information:

```php
$MailChimp = muauth_mc();
$list_id = 'b1234346';
$subscriber_hash = $MailChimp->subscriberHash('davy@example.com');
$result = $MailChimp->patch("lists/$list_id/members/$subscriber_hash", [
    'merge_fields' => ['FNAME'=>'Davy', 'LNAME'=>'Jones'],
    'interests'    => ['2s3a384h' => true],
]);

print_r($result);
```
