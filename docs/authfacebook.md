Using the Facebook authentication source with SimpleSAMLphp
===========================================================

You need to configure `authsources.php`, with both App ID and App Secret.

To get an App ID and secret, register the application at:

 * <http://www.facebook.com/developers/>

Note: requests with App ID should be faster <https://github.com/facebook/php-sdk/issues/214>.

This module needs the CURL and JSON PHP extensions.

Once you have your App ID, add a facebook authsource to `authsources.php`.

Example config:
===============

```
    'facebook' => [
        'authfacebook:Facebook',
        // Register your Facebook application on http://www.facebook.com/developers
        // App ID or API key (requests with App ID should be faster; https://github.com/facebook/php-sdk/issues/214)
        'api_key' => 'xxxxxxxxxxxxxxxx',

        // App Secret
        'secret' => 'xxxxxxxxxxxxxxxx',

        // which additional data permissions to request from user
        // see http://developers.facebook.com/docs/authentication/permissions/ for the full list
        // 'req_perms' => 'email,user_birthday',

        // Which additional user profile fields to request.
        // When empty, only the app-specific user id and name will be returned
        // See https://developers.facebook.com/docs/graph-api/reference/v2.6/user for the full list
        // 'user_fields' => 'email,birthday,third_party_id,name,first_name,last_name',
    ],
```
