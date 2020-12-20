<?php

namespace SimpleSAML\Module\authfacebook\Auth\Source;

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;
use Webmozart\Assert\Assert;

/**
 * Authenticate using Facebook Platform.
 *
 * @package SimpleSAMLphp
 */

class Facebook extends Auth\Source
{
    /**
     * The string used to identify our states.
     */
    public const STAGE_INIT = 'facebook:init';


    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = 'facebook:AuthId';


    /**
     * Facebook App ID or API Key
     * @var string
     */
    private $api_key;


    /**
     * Facebook App Secret
     * @var string
     */
    private $secret;


    /**
     * Which additional data permissions to request from user
     * @var string|null
     */
    private $req_perms;


    /**
     * A comma-separated list of user profile fields to request.
     *
     * Note that some user fields require appropriate permissions. For
     * example, to retrieve the user's primary email address, "email" must
     * be specified in both the req_perms and the user_fields parameter.
     *
     * When empty, only the app-specific user id and name will be returned.
     *
     * See the Graph API specification for all available user fields:
     * https://developers.facebook.com/docs/graph-api/reference/v2.6/user
     * @var string|null
     */
    private $user_fields;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $cfgParse = Configuration::loadFromArray(
            $config,
            'authsources[' . var_export($this->authId, true) . ']'
        );

        $this->api_key = $cfgParse->getString('api_key');
        $this->secret = $cfgParse->getString('secret');
        $this->req_perms = $cfgParse->getString('req_perms', null);
        $this->user_fields = $cfgParse->getString('user_fields', null);
    }


    /**
     * Log-in using Facebook platform
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(array &$state): void
    {
        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;
        Auth\State::saveState($state, self::STAGE_INIT);

        $facebook = new Module\authfacebook\Facebook(
            ['appId' => $this->api_key, 'secret' => $this->secret],
            $state
        );
        $facebook->destroySession();

        $linkback = Module::getModuleURL('authfacebook/linkback.php');
        $url = $facebook->getLoginUrl(['redirect_uri' => $linkback, 'scope' => $this->req_perms]);
        Auth\State::saveState($state, self::STAGE_INIT);

        Utils\HTTP::redirectTrustedURL($url);
    }


    /**
     * @param array &$state
     */
    public function finalStep(array &$state): void
    {
        $facebook = new Module\authfacebook\Facebook(
            ['appId' => $this->api_key, 'secret' => $this->secret],
            $state
        );
        $uid = $facebook->getUser();

        $info = null;
        if ($uid > 0) {
            try {
                $info = $facebook->api("/" . $uid . ($this->user_fields ? "?fields=" . $this->user_fields : ""));
            } catch (\FacebookApiException $e) {
                throw new Error\AuthSource($this->authId, 'Error getting user profile.', $e);
            }
        }

        if (!isset($info)) {
            throw new Error\AuthSource($this->authId, 'Error getting user profile.');
        }

        $attributes = [];
        foreach ($info as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $attributes['facebook.' . $key] = [$value];
            }
        }

        if (array_key_exists('third_party_id', $info)) {
            $attributes['facebook_user'] = [$info['third_party_id'] . '@facebook.com'];
        } else {
            $attributes['facebook_user'] = [$uid . '@facebook.com'];
        }

        $attributes['facebook_targetedID'] = ['http://facebook.com!' . $uid];
        $attributes['facebook_cn'] = [$info['name']];

        Logger::debug('Facebook Returned Attributes: ' . implode(", ", array_keys($attributes)));

        $state['Attributes'] = $attributes;

        $facebook->destroySession();
    }
}
