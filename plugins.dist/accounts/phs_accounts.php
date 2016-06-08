<?php

namespace phs\plugins\accounts;

use \phs\PHS;
use \phs\PHS_session;
use \phs\PHS_crypt;
use \phs\libraries\PHS_params;
use \phs\libraries\PHS_Plugin;
use \phs\libraries\PHS_Hooks;
use \phs\libraries\PHS_Roles;

class PHS_Plugin_Accounts extends PHS_Plugin
{
    const ERR_LOGOUT = 40000, ERR_LOGIN = 40001, ERR_CONFIRMATION = 40002;

    const PARAM_CONFIRMATION = '_a';

    const CONF_REASON_ACTIVATION = 'activation', CONF_REASON_EMAIL = 'email';

    // After how many seconds from last request should we clean up sessions?
    // !!! should be less than 'session_expire_minutes_normal' config value
    const IDLERS_GC_SECONDS = 900; // 15 min

    private static $_session_key = 'PHS_sess';

    /**
     * @return string Returns version of model
     */
    public function get_plugin_version()
    {
        return '1.0.1';
    }

    /**
     * @return array Returns an array with plugin details populated array returned by default_plugin_details_fields() method
     */
    public function get_plugin_details()
    {
        return array(
            'name' => $this->_pt( 'Accounts Management' ),
            'description' => $this->_pt( 'Handles all functionality related to user accounts.' ),
        );
    }

    public function get_models()
    {
        return array( 'accounts', 'accounts_details' );
    }

    /**
     * @inheritdoc
     */
    public function get_settings_structure()
    {
        return array(
            'email_mandatory' => array(
                'display_name' => $this->_pt( 'Email mandatory at registration' ),
                'type' => PHS_params::T_BOOL,
                'default' => true,
            ),
            'replace_nick_with_email' => array(
                'display_name' => $this->_pt( 'Replace nick with email' ),
                'display_hint' => $this->_pt( 'If, by any reasons, nickname is not provided when creating an account should it be replaced with provided email?' ),
                'type' => PHS_params::T_BOOL,
                'default' => true,
            ),
            'no_nickname_only_email' => array(
                'display_name' => $this->_pt( 'Use only email, no nickname' ),
                'display_hint' => $this->_pt( 'Hide nickname complately and use only email as nickname.' ),
                'type' => PHS_params::T_BOOL,
                'default' => false,
            ),
            'account_requires_activation' => array(
                'display_name' => $this->_pt( 'Account requires activation' ),
                'display_hint' => $this->_pt( 'Should an account be activated before login after registration? When admin creates accounts, these will be automatically active.' ),
                'type' => PHS_params::T_BOOL,
                'default' => true,
            ),
            'generate_pass_if_not_present' => array(
                'display_name' => $this->_pt( 'Generate password if not present' ),
                'display_hint' => $this->_pt( 'If, by any reasons, password is not present when creating an account autogenerate a password or return error?' ),
                'type' => PHS_params::T_BOOL,
                'default' => true,
            ),
            'email_unique' => array(
                'display_name' => $this->_pt( 'Emails should be unique' ),
                'display_hint' => $this->_pt( 'Should account creation fail if same email already exists in database?' ),
                'type' => PHS_params::T_BOOL,
                'default' => true,
            ),
            'min_password_length' => array(
                'display_name' => $this->_pt( 'Minimum password length' ),
                'type' => PHS_params::T_INT,
                'default' => 8,
            ),
            // Make sure password generator method in accounts model follows this rule... (escape char is /)
            // password regular expression (leave empty if not wanted)
            'password_regexp' => array(
                'display_name' => $this->_pt( 'Password reg-exp' ),
                'display_hint' => $this->_pt( 'If provided, all passwords have to pass this regular expression. Previous created accounts will not be affected by this.' ),
                'type' => PHS_params::T_ASIS,
                'default' => '',
            ),
            'pass_salt_length' => array(
                'display_name' => $this->_pt( 'Password salt length' ),
                'display_hint' => $this->_pt( 'Each account uses it\'s own password salt. (Google salt for more details)' ),
                'type' => PHS_params::T_INT,
                'default' => 8,
            ),
            'announce_pass_change' => array(
                'display_name' => $this->_pt( 'Announce password change' ),
                'display_hint' => $this->_pt( 'Should system send an email to account\'s email address when password changes?' ),
                'type' => PHS_params::T_BOOL,
                'default' => true,
            ),
            'session_expire_minutes_remember' => array(
                'display_name' => $this->_pt( 'Password lifetime (long) mins' ),
                'display_hint' => $this->_pt( 'After how many minutes should session expire if user ticked "Remember Me" checkbox' ),
                'type' => PHS_params::T_INT,
                'default' => 2880, // 2 days
            ),
            'session_expire_minutes_normal' => array(
                'display_name' => $this->_pt( 'Password lifetime (short) mins' ),
                'display_hint' => $this->_pt( 'After how many minutes should session expire if user DIDN\'T tick "Remember Me" checkbox' ),
                'type' => PHS_params::T_INT,
                'default' => 60, // 1 hour
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function get_roles_definition()
    {
        $return_arr = array(
            PHS_Roles::ROLE_GUEST => array(
                'name' => $this->_pt( 'Guests' ),
                'description' => $this->_pt( 'Role used by non-logged visitors' ),
                'role_units' => array(
                    PHS_Roles::ROLEU_CONTACT_US => array(
                        'name' => $this->_pt( 'Contact Us' ),
                        'description' => $this->_pt( 'Allow user to use contact us form' ),
                    ),
                    PHS_Roles::ROLEU_REGISTER => array(
                        'name' => $this->_pt( 'Register' ),
                        'description' => $this->_pt( 'Allow user to use registration form' ),
                    ),
                ),
            ),

            PHS_Roles::ROLE_MEMBER => array(
                'name' => $this->_pt( 'Member accounts' ),
                'description' => $this->_pt( 'Default functionality role (what normal members can do)' ),
                'role_units' => array(
                    PHS_Roles::ROLEU_CONTACT_US => array(
                        'name' => $this->_pt( 'Contact Us' ),
                        'description' => $this->_pt( 'Allow user to use contact us form' ),
                    ),
                ),
            ),

            PHS_Roles::ROLE_ADMIN => array(
                'name' => $this->_pt( 'Admin accounts' ),
                'description' => $this->_pt( 'Role assigned to admin accounts.' ),
                'role_units' => array(

                    // Roles...
                    PHS_Roles::ROLEU_MANAGE_ROLES => array(
                        'name' => $this->_pt( 'Manage roles' ),
                        'description' => $this->_pt( 'Allow user to define or edit roles' ),
                    ),
                    PHS_Roles::ROLEU_LIST_ROLES => array(
                        'name' => $this->_pt( 'List roles' ),
                        'description' => $this->_pt( 'Allow user to view defined roles' ),
                    ),

                    // Plugins...
                    PHS_Roles::ROLEU_MANAGE_PLUGINS => array(
                        'name' => $this->_pt( 'Manage plugins' ),
                        'description' => $this->_pt( 'Allow user to manage plugins' ),
                    ),
                    PHS_Roles::ROLEU_LIST_PLUGINS => array(
                        'name' => $this->_pt( 'List plugins' ),
                        'description' => $this->_pt( 'Allow user to list plugins' ),
                    ),

                    // Accounts...
                    PHS_Roles::ROLEU_MANAGE_ACCOUNTS => array(
                        'name' => $this->_pt( 'Manage accounts' ),
                        'description' => $this->_pt( 'Allow user to manage accounts' ),
                    ),
                    PHS_Roles::ROLEU_LIST_ACCOUNTS => array(
                        'name' => $this->_pt( 'List accounts' ),
                        'description' => $this->_pt( 'Allow user to list accounts' ),
                    ),
                    PHS_Roles::ROLEU_LOGIN_SUBACCOUNT => array(
                        'name' => $this->_pt( 'Login sub-account' ),
                        'description' => $this->_pt( 'Allow user to login as other user' ),
                    ),
                ),
            ),
        );

        $return_arr[PHS_Roles::ROLE_ADMIN]['role_units'] = self::validate_array( $return_arr[PHS_Roles::ROLE_ADMIN]['role_units'],
            self::validate_array( $return_arr[PHS_Roles::ROLE_MEMBER]['role_units'], $return_arr[PHS_Roles::ROLE_GUEST]['role_units'] ) );

        return $return_arr;
    }

    public static function session_key( $key = null )
    {
        if( $key === null )
            return self::$_session_key;

        if( ! is_string( $key ) )
            return false;

        self::$_session_key = $key;

        return self::$_session_key;
    }

    // This method should not set any errors as it runs independent of user actions...
    public function resolve_idler_sessions()
    {
        // preserve previous errors...
        $prev_errors = $this->stack_all_errors();

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            $this->restore_errors( $prev_errors );
            return false;
        }

        // If current request doesn't have a session ID which means it's a logged in user, there's no use in cleaning old sessions...
        if( !($online_db_data = $this->_get_current_session_data( array( 'accounts_model' => $accounts_model ) ))
         or seconds_passed( $online_db_data['idle'] ) < self::IDLERS_GC_SECONDS )
        {
            if( !empty( $online_db_data ) )
                $accounts_model->update_current_session( $online_db_data );

            $this->restore_errors( $prev_errors );
            return true;
        }

        $accounts_model->clear_idler_sessions();

        // if session expired refresh cached session data...
        if( parse_db_date( $online_db_data['expire_date'] ) < time()
        and !($online_db_data = $this->_get_current_session_data( array( 'force' => true, 'accounts_model' => $accounts_model ) )) )
        {
            $this->restore_errors( $prev_errors );
            return true;
        }

        $accounts_model->update_current_session( $online_db_data );

        $this->restore_errors( $prev_errors );

        return true;
    }

    public function do_logout_subaccount()
    {
        $this->reset_error();

        if( !($db_details = $this->get_current_user_db_details())
         or empty( $db_details['session_db_data'] )
         or ! is_array( $db_details['session_db_data'] )
         or empty( $db_details['session_db_data']['id'] )
         or empty( $db_details['session_db_data']['auid'] ) )
            return true;

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            if( self::st_has_error() )
                $this->copy_static_error();

            return false;
        }

        if( !($accounts_model->session_logout_subaccount( $db_details['session_db_data'] )) )
        {
            if( $accounts_model->has_error() )
                $this->copy_error( $accounts_model );
            else
                $this->set_error( self::ERR_LOGOUT, $this->_pt( 'Couldn\'t logout from subaccount.' ) );

            return false;
        }

        return true;
    }

    public function do_logout()
    {
        $this->reset_error();

        if( !($db_details = $this->get_current_user_db_details())
         or empty( $db_details['session_db_data'] ) or !is_array( $db_details['session_db_data'] )
         or empty( $db_details['session_db_data']['id'] ) or empty( $db_details['session_db_data']['uid'] ) )
            return true;

        if( !empty( $db_details['session_db_data']['auid'] ) )
            return $this->do_logout_subaccount();

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            if( self::st_has_error() )
                $this->copy_static_error();

            return false;
        }

        if( !($accounts_model->session_logout( $db_details['session_db_data'] )) )
        {
            if( $accounts_model->has_error() )
                $this->copy_error( $accounts_model );
            else
                $this->set_error( self::ERR_LOGOUT, $this->_pt( 'Couldn\'t logout from your account. Please retry.' ) );

            return false;
        }

        if( !PHS_session::_d( self::session_key() ) )
        {
            $this->set_error( self::ERR_LOGOUT, $this->_pt( 'Couldn\'t logout from your account. Please retry.' ) );
            return false;
        }

        return true;
    }

    public function do_login( $account_data, $params = false )
    {
        $this->reset_error();

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['expire_mins'] ) )
            $params['expire_mins'] = 0;
        else
            $params['expire_mins'] = intval( $params['expire_mins'] );

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( ! ($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            $this->set_error( self::ERR_LOGIN, $this->_pt( 'Couldn\'t load accounts model.' ) );

            return false;
        }

        if( empty( $account_data )
         or !($account_arr = $accounts_model->data_to_array( $account_data ))
         or ! $accounts_model->is_active( $account_arr ) )
        {
            $this->set_error( self::ERR_LOGIN, $this->_pt( 'Unknown or inactive account.' ) );

            return false;
        }

        $login_params = array();
        $login_params['expire_mins'] = $params['expire_mins'];

        if( !($onuser_arr = $accounts_model->login( $account_arr, $login_params ))
         or empty( $onuser_arr['wid'] ) )
        {
            if( $accounts_model->has_error() )
                $this->copy_error( $accounts_model, self::ERR_LOGIN );
            else
                $this->set_error( self::ERR_LOGIN, $this->_pt( 'Login failed. Please try again.' ) );

            return false;
        }

        if( !PHS_session::_s( self::session_key(), $onuser_arr['wid'] ) )
        {
            $accounts_model->session_logout( $onuser_arr );

            $this->set_error( self::ERR_LOGIN, $this->_pt( 'Login failed. Please try again.' ) );

            return false;
        }

        return $onuser_arr;
    }

    public function get_confirmation_params( $account_data, $reason = false )
    {
        $this->reset_error();

        if( $reason === false )
            $reason = self::CONF_REASON_ACTIVATION;

        if( !$this->valid_confirmation_reason( $reason ) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Invalid confirmation reason.' ) );
            return false;
        }

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Couldn\'t load accounts model.' ) );
            return false;
        }

        if( empty( $account_data )
         or !($account_arr = $accounts_model->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        $pub_key = microtime( true );
        $confirmation_param = PHS_crypt::quick_encode( $account_arr['id'].'::'.$reason.'::'.md5( $account_arr['nick'].':'.$pub_key.':'.$account_arr['email'] ) ).'::'.$pub_key;

        return array(
            'confirmation_param' => $confirmation_param,
            'pub_key' => $pub_key,
            'account_data' => $account_arr,
        );
    }

    public function decode_confirmation_param( $param_str )
    {
        $this->reset_error();

        if( empty( $param_str )
         or @strstr( $param_str, '::' ) === false
         or !($parts_arr = explode( '::', $param_str, 2 ))
         or empty( $parts_arr[0] ) or empty( $parts_arr[1] ) )
        {
            $this->set_error( self::ERR_CONFIRMATION, 'Invalid confirmation parameter.' );
            return false;
        }

        $crypted_data = $parts_arr[0];
        $pub_key = $parts_arr[1];

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($decrypted_data = PHS_crypt::quick_decode( $crypted_data ))
         or !($decrypted_parts = explode( '::', $decrypted_data, 3 ))
         or empty( $decrypted_parts[0] ) or empty( $decrypted_parts[1] ) or empty( $decrypted_parts[2] )
         or !($account_id = intval( $decrypted_parts[0] ))
         or !$this->valid_confirmation_reason( $decrypted_parts[1] )
         or !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() ))
         or !($account_arr = $accounts_model->get_details( $account_id ))
         or $decrypted_parts[2] != md5( $account_arr['nick'].':'.$pub_key.':'.$account_arr['email'] ) )
        {
            $this->set_error( self::ERR_CONFIRMATION, 'Invalid confirmation parameter.' );
            return false;
        }

        return array(
            'reason' => $decrypted_parts[1],
            'pub_key' => $pub_key,
            'account_data' => $account_arr,
        );
    }

    public function confirmation_reasons()
    {
        // key - value pair of reson name and success message...
        return array(
            self::CONF_REASON_ACTIVATION => $this->_pt( 'Your account is now active.' ),
            self::CONF_REASON_EMAIL => $this->_pt( 'Your email address is now confirmed.' ),
        );
    }

    public function valid_confirmation_reason( $reason )
    {
        if( empty( $reason )
         or !($reasons_arr = $this->confirmation_reasons()) or empty( $reasons_arr[$reason] ) )
            return false;

        return $reasons_arr[$reason];
    }

    public function get_confirmation_link( $account_data, $reason = false )
    {
        $this->reset_error();

        if( $reason === false )
            $reason = self::CONF_REASON_ACTIVATION;

        if( !$this->valid_confirmation_reason( $reason ) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Invalid confirmation reason.' ) );
            return false;
        }

        if( !($confirmation_parts = $this->get_confirmation_params( $account_data, $reason ))
         or empty( $confirmation_parts['confirmation_param'] ) or empty( $confirmation_parts['pub_key'] ) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Couldn\'t obtain confirmation parameters.' ) );

            return false;
        }

        return PHS::url( array( 'p' => 'accounts', 'a' => 'activation' ), array( self::PARAM_CONFIRMATION => $confirmation_parts['confirmation_param'] ) );
    }

    public function do_confirmation_reason( $account_data, $reason )
    {
        $this->reset_error();

        if( !$this->valid_confirmation_reason( $reason ) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Invalid confirmation reason.' ) );
            return false;
        }

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Couldn\'t load accounts model.' ) );
            return false;
        }

        if( empty( $account_data )
         or !($account_arr = $accounts_model->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        switch( $reason )
        {
            default:
                $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Confirmation reason unknown.' ) );
                return false;
            break;

            case self::CONF_REASON_ACTIVATION:
                if( !$accounts_model->needs_activation( $account_arr ) )
                {
                    $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Account doesn\'t require activation.' ) );
                    return false;
                }
                
                if( !($account_arr = $accounts_model->activate_account_after_registration( $account_arr )) )
                {
                    $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Failed activating account. Please try again.' ) );
                    return false;
                }
            break;

            case self::CONF_REASON_EMAIL:
                if( empty( $account_arr['email_verified'] ) )
                {
                    if( !($account_arr = $accounts_model->email_verified( $account_arr )) )
                    {
                        $this->set_error( self::ERR_CONFIRMATION, $this->_pt( 'Failed confirming email address. Please try again.' ) );
                        return false;
                    }
                }
            break;
        }

        return array(
            'account_data' => $account_arr,
        );
    }

    private function _get_current_session_data( $params = false )
    {
        static $online_db_details = false;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['force'] ) )
            $params['force'] = false;

        if( !empty( $online_db_details )
        and empty( $params['force'] ) )
            return $online_db_details;

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( empty( $params['accounts_model'] ) )
            $accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() );
        else
            $accounts_model = $params['accounts_model'];

        if( empty( $accounts_model )
         or !($skey_value = PHS_session::_g( self::session_key() ))
         or !($online_db_details = $accounts_model->get_details_fields(
                array(
                    'wid' => $skey_value,
                ),
                array(
                    'table_name' => 'online',
                )
            ))
        )
            return false;

        return $online_db_details;
    }

    public function get_current_user_db_details( $hook_args = false )
    {
        static $check_result = false;

        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_user_db_details_hook_args() );

        if( empty( $hook_args['force_check'] )
        and !empty( $check_result ) )
            return $check_result;

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
            return $hook_args;

        if( !($skey_value = PHS_session::_g( self::session_key() ))
         or !($online_db_details = $this->_get_current_session_data( array( 'accounts_model' => $accounts_model ) )) )
        {
            $hook_args['session_db_data'] = $accounts_model->get_empty_data( array( 'table_name' => 'online' ) );
            $user_db_data = $accounts_model->get_empty_data();

            if( !($units_slugs_arr = PHS_Roles::get_role_role_units_slugs( PHS_Roles::ROLE_GUEST )) )
                $units_slugs_arr = array();

            $user_db_data[$accounts_model::ROLES_USER_KEY] = array( PHS_Roles::ROLE_GUEST );
            $user_db_data[$accounts_model::ROLE_UNITS_USER_KEY] = $units_slugs_arr;

            $hook_args['user_db_data'] = $user_db_data;

            return $hook_args;
        }

        if( empty( $online_db_details['uid'] )
         or !($user_db_details = $accounts_model->get_details( $online_db_details['uid'] ))
         or !$accounts_model->is_active( $user_db_details )
        )
        {
            $accounts_model->hard_delete( $online_db_details, array( 'table_name' => 'online' ) );

            // session expired?
            $hook_args['session_expired_secs'] = seconds_passed( $online_db_details['idle'] );

            $hook_args['session_db_data'] = $accounts_model->get_empty_data( array( 'table_name' => 'online' ) );
            $user_db_data = $accounts_model->get_empty_data();

            if( !($units_slugs_arr = PHS_Roles::get_role_role_units_slugs( PHS_Roles::ROLE_GUEST )) )
                $units_slugs_arr = array();

            $user_db_data[$accounts_model::ROLES_USER_KEY] = array( PHS_Roles::ROLE_GUEST );
            $user_db_data[$accounts_model::ROLE_UNITS_USER_KEY] = $units_slugs_arr;

            $hook_args['user_db_data'] = $user_db_data;

            return $hook_args;
        }

        if( !($units_slugs_arr = PHS_Roles::get_user_role_units_slugs( $user_db_details )) )
            $units_slugs_arr = array();
        if( !($slugs_arr = PHS_Roles::get_user_roles_slugs( $user_db_details )) )
            $slugs_arr = array();

        $user_db_details[$accounts_model::ROLES_USER_KEY] = $slugs_arr;
        $user_db_details[$accounts_model::ROLE_UNITS_USER_KEY] = $units_slugs_arr;

        $hook_args['session_db_data'] = $online_db_details;
        $hook_args['user_db_data'] = $user_db_details;

        $check_result = $hook_args;

        return $hook_args;
    }

}