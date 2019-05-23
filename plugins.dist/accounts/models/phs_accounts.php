<?php

namespace phs\plugins\accounts\models;

use \phs\PHS;
use \phs\PHS_crypt;
use \phs\PHS_bg_jobs;
use \phs\libraries\PHS_Model;
use \phs\libraries\PHS_params;
use \phs\libraries\PHS_Roles;
use \phs\libraries\PHS_Hooks;
use \phs\libraries\PHS_Logger;
use \phs\libraries\PHS_utils;

class PHS_Model_Accounts extends PHS_Model
{
    const ERR_LOGIN = 10001, ERR_EMAIL = 10002;

    const PASSWORDS_ALGO = 'sha256';

    const ROLES_USER_KEY = '{roles_slugs}', ROLE_UNITS_USER_KEY = '{role_units_slugs}';

    const HOOK_LEVELS = 'phs_accounts_levels', HOOK_STATUSES = 'phs_accounts_statuses';

    // "Hardcoded" minimum password length (if 'min_password_length' is not found in settings)
    const DEFAULT_MIN_PASSWORD_LENGTH = 8;

    const STATUS_INACTIVE = 1, STATUS_ACTIVE = 2, STATUS_SUSPENDED = 3, STATUS_DELETED = 4;
    protected static $STATUSES_ARR = array(
        self::STATUS_INACTIVE => array( 'title' => 'Inactive' ),
        self::STATUS_ACTIVE => array( 'title' => 'Active' ),
        self::STATUS_SUSPENDED => array( 'title' => 'Suspended' ),
        self::STATUS_DELETED => array( 'title' => 'Deleted' ),
    );

    const LVL_GUEST = 0, LVL_MEMBER = 1,
          LVL_OPERATOR = 10, LVL_ADMIN = 11, LVL_SUPERADMIN = 12, LVL_DEVELOPER = 13;
    protected static $LEVELS_ARR = array(
        self::LVL_MEMBER => array( 'title' => 'Member' ),
        self::LVL_OPERATOR => array( 'title' => 'Operator' ),
        self::LVL_ADMIN => array( 'title' => 'Admin' ),
        self::LVL_SUPERADMIN => array( 'title' => 'Super admin' ),
        self::LVL_DEVELOPER => array( 'title' => 'Developer' ),
    );

    function __construct( $instance_details = false )
    {
        //$this->add_connection( 'PHS_Model_Accounts_details', 'accounts', self::INSTANCE_TYPE_MODEL );

        parent::__construct( $instance_details );
    }

    /**
     * @return string Returns version of model
     */
    public function get_model_version()
    {
        return '1.1.0';
    }

    /**
     * @return array of string Returns an array of strings containing tables that model will handle
     */
    public function get_table_names()
    {
        // 'users_pass_salts' is first so we are sure table is created before changing users table...
        return array( 'users_pass_salts', 'users', 'online', 'users_pass_history', );
    }

    /**
     * @return string Returns main table name used when calling insert with no table name
     */
    function get_main_table_name()
    {
        return 'users';
    }

    //
    // Custom updates
    //
    protected function custom_after_update( $old_version, $new_version )
    {
        if( @version_compare( $old_version, '1.0.3', '<=' )
        and @version_compare( $new_version, '1.0.4', '>=' )
        and !$this->_update_to_104_or_higher() )
            return false;

        return true;
    }

    protected function custom_after_missing_tables_update( $old_version, $new_version, $params_arr = false )
    {
        if( @version_compare( $old_version, '1.0.4', '<=' )
        and @version_compare( $new_version, '1.1.0', '>=' )
        and !$this->_update_to_110_or_higher() )
            return false;

        return true;
    }
    //
    // END Custom updates
    //

    //
    //  Level checks
    //
    public static function is_developer( $lvl )
    {
        return ($lvl == self::LVL_DEVELOPER);
    }

    public static function is_sadmin( $lvl )
    {
        return ($lvl == self::LVL_SUPERADMIN or $lvl == self::LVL_DEVELOPER);
    }

    public static function is_admin( $lvl, $strict = false )
    {
        return ($lvl == self::LVL_ADMIN or (!$strict and ($lvl == self::LVL_SUPERADMIN or $lvl == self::LVL_DEVELOPER)));
    }

    public static function is_operator( $lvl, $strict = false )
    {
        return ($lvl == self::LVL_OPERATOR or (!$strict and self::is_admin( $lvl )));
    }

    public static function is_member( $lvl, $strict = false )
    {
        return ($lvl == self::LVL_MEMBER or (!$strict and self::is_admin( $lvl )));
    }
    //
    //  END Level checks
    //

    //
    //  Account level checks
    //
    public function acc_is_developer( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !self::is_developer( $user_arr['level'] ) )
            return false;

        return $user_arr;
    }

    public function acc_is_sadmin( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !self::is_sadmin( $user_arr['level'] ) )
            return false;

        return $user_arr;
    }

    public function acc_is_admin( $user_data, $strict = false )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !self::is_admin( $user_arr['level'], $strict ) )
            return false;

        return $user_arr;
    }

    public function acc_is_operator( $user_data, $strict = false )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !self::is_operator( $user_arr['level'], $strict ) )
            return false;

        return $user_arr;
    }

    public function acc_is_member( $user_data, $strict = false )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !self::is_member( $user_arr['level'], $strict ) )
            return false;

        return $user_arr;
    }
    //
    //  END Account level checks
    //

    public function is_active( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or $user_arr['status'] != self::STATUS_ACTIVE )
            return false;

        return $user_arr;
    }

    public function is_inactive( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or $user_arr['status'] != self::STATUS_INACTIVE )
            return false;

        return $user_arr;
    }

    public function is_deleted( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or $user_arr['status'] != self::STATUS_DELETED )
            return false;

        return $user_arr;
    }

    public function is_just_registered( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or (!empty( $user_arr['lastlog'] ) and !empty_db_date( $user_arr['lastlog'] )) )
            return false;

        return $user_arr;
    }

    public function has_logged_in( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or empty( $user_arr['lastlog'] ) or empty_db_date( $user_arr['lastlog'] ) )
            return false;

        return $user_arr;
    }

    public function needs_after_registration_email( $user_data, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['send_confirmation_email'] ) )
            $params['send_confirmation_email'] = false;

        if( empty( $params['accounts_plugin_settings'] )
         or !is_array( $params['accounts_plugin_settings'] ) )
            $params['accounts_plugin_settings'] = false;

        if( empty( $params['accounts_plugin_settings'] )
        and (!($params['accounts_plugin_settings'] = $this->get_plugin_settings())
                or !is_array( $params['accounts_plugin_settings'] )
            ) )
            $params['accounts_plugin_settings'] = array();

        if( !($user_arr = $this->data_to_array( $user_data )) )
            return false;

        return ($this->needs_activation( $user_arr, $params ) or $this->needs_confirmation_email( $user_arr ));
    }


    public function needs_activation( $user_data, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['accounts_plugin_settings'] )
         or !is_array( $params['accounts_plugin_settings'] ) )
            $params['accounts_plugin_settings'] = false;

        if( empty( $params['accounts_plugin_settings'] )
        and (!($params['accounts_plugin_settings'] = $this->get_plugin_settings())
                or !is_array( $params['accounts_plugin_settings'] )
            ) )
            $params['accounts_plugin_settings'] = array();

        if( empty( $params['accounts_plugin_settings']['account_requires_activation'] )
         or !($user_arr = $this->data_to_array( $user_data ))
         or !$this->is_just_registered( $user_arr )
         or $this->is_active( $user_arr )
         or $this->is_deleted( $user_arr ) )
            return false;

        return $user_arr;
    }

    public function needs_confirmation_email( $user_data )
    {
        // If password was provided by user or he did already login no need to send him password confirmation
        if( !($user_arr = $this->data_to_array( $user_data ))
         or empty( $user_arr['pass_generated'] )
         or $this->is_active( $user_arr )
         or $this->has_logged_in( $user_arr ) )
            return false;

        return $user_arr;
    }

    public function needs_email_verification( $user_data )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !empty( $user_arr['email_verified'] )
         or $this->is_deleted( $user_arr ) )
            return false;

        return $user_arr;
    }

    public function can_manage_account( $user_data, $user_to_manage )
    {
        if( !($user_arr = $this->data_to_array( $user_data ))
         or !($user_to_manage_arr = $this->data_to_array( $user_to_manage ))
         or !PHS_Roles::user_has_role_units( $user_arr, PHS_Roles::ROLEU_MANAGE_ROLES )
         or $user_arr['level'] < $user_to_manage_arr['level'] )
            return false;

        return array(
            'user_data' => $user_arr,
            'user_to_manage' => $user_to_manage_arr,
        );
    }

    public function get_account_details( $account_data, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['populate_with_empty_data'] ) )
            $params['populate_with_empty_data'] = false;

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts_details $accounts_details_model */
        if( !($accounts_details_model = PHS::load_model( 'accounts_details', $this->instance_plugin_name() )) )
            return false;

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or empty( $account_arr['details_id'] )
         or !($accounts_details_arr = $accounts_details_model->get_details( $account_arr['details_id'] )) )
            return (empty( $params['populate_with_empty_data'] )?false:$accounts_details_model->get_empty_data());

        return $accounts_details_arr;
    }

    final public function get_levels( $lang = false )
    {
        static $levels_arr = array();

        if( empty( $lang )
        and !empty( $levels_arr ) )
            return $levels_arr;

        // Let these here so language parser would catch the texts...
        $this->_pt( 'Member', $lang );
        $this->_pt( 'Operator', $lang );
        $this->_pt( 'Admin', $lang );
        $this->_pt( 'Super admin', $lang );
        $this->_pt( 'Developer', $lang );

        $new_levels_arr = self::$LEVELS_ARR;
        $hook_args = PHS_Hooks::default_common_hook_args();
        $hook_args['levels_arr'] = self::$LEVELS_ARR;

        if( ($extra_levels_arr = PHS::trigger_hooks( PHS_Hooks::H_USER_LEVELS, $hook_args ))
        and is_array( $extra_levels_arr ) and !empty( $extra_levels_arr['levels_arr'] ) )
            $new_levels_arr = self::merge_array_assoc( $extra_levels_arr['levels_arr'], $new_levels_arr );

        $return_arr = array();
        // Translate and validate levels...
        if( !empty( $new_levels_arr ) and is_array( $new_levels_arr ) )
        {
            foreach( $new_levels_arr as $level_id => $level_arr )
            {
                $level_id = intval( $level_id );
                if( empty( $level_id ) )
                    continue;

                if( empty( $level_arr['title'] ) )
                    $level_arr['title'] = $this->_pt( 'Level %s', $lang, $level_id );
                else
                    $level_arr['title'] = $this->_pt( $level_arr['title'], $lang );

                $return_arr[$level_id] = array(
                    'title' => $level_arr['title']
                );
            }
        }

        if( empty( $lang ) )
            $levels_arr = $return_arr;

        return $return_arr;
    }

    final public function get_levels_as_key_val( $lang = false )
    {
        static $user_levels_key_val_arr = false;

        if( empty( $lang )
        and $user_levels_key_val_arr !== false )
            return $user_levels_key_val_arr;

        $return_arr = array();
        if( ($user_levels = $this->get_levels( $lang )) )
        {
            foreach( $user_levels as $key => $val )
            {
                if( !is_array( $val ) )
                    continue;

                $return_arr[$key] = $val['title'];
            }
        }

        if( empty( $lang ) )
            $user_levels_key_val_arr = $return_arr;

        return $return_arr;
    }

    public function valid_level( $level, $lang = false )
    {
        $all_levels = $this->get_levels( $lang );
        if( empty( $level )
         or empty( $all_levels[$level] ) )
            return false;

        return $all_levels[$level];
    }

    final public function get_statuses( $lang = false )
    {
        static $statuses_arr = array();

        if( empty( $lang )
        and !empty( $statuses_arr ) )
            return $statuses_arr;

        // Let these here so language parser would catch the texts...
        $this->_pt( 'Inactive', $lang );
        $this->_pt( 'Active', $lang );
        $this->_pt( 'Suspended', $lang );
        $this->_pt( 'Deleted', $lang );

        $hook_args = PHS_Hooks::default_common_hook_args();
        $hook_args['statuses_arr'] = self::$STATUSES_ARR;

        $new_statuses_arr = self::$STATUSES_ARR;
        if( ($extra_statuses_arr = PHS::trigger_hooks( PHS_Hooks::H_USER_STATUSES, $hook_args ))
        and is_array( $extra_statuses_arr ) and !empty( $extra_statuses_arr['statuses_arr'] ) )
            $new_statuses_arr = self::merge_array_assoc( $extra_statuses_arr['statuses_arr'], $new_statuses_arr );

        $return_arr = array();
        // Translate and validate statuses...
        if( !empty( $new_statuses_arr ) and is_array( $new_statuses_arr ) )
        {
            foreach( $new_statuses_arr as $status_id => $status_arr )
            {
                $status_id = intval( $status_id );
                if( empty( $status_id ) )
                    continue;

                if( empty( $status_arr['title'] ) )
                    $status_arr['title'] = $this->_pt( 'Status %s', $status_id );
                else
                    $status_arr['title'] = $this->_pt( $status_arr['title'] );

                $return_arr[$status_id] = array(
                    'title' => $status_arr['title']
                );
            }
        }

        if( empty( $lang ) )
            $statuses_arr = $return_arr;

        return $return_arr;
    }

    final public function get_statuses_as_key_val( $lang = false )
    {
        static $user_statuses_key_val_arr = false;

        if( empty( $lang )
        and $user_statuses_key_val_arr !== false )
            return $user_statuses_key_val_arr;

        $return_arr = array();
        if( ($user_statuses = $this->get_statuses()) )
        {
            foreach( $user_statuses as $key => $val )
            {
                if( !is_array( $val ) )
                    continue;

                $return_arr[$key] = $val['title'];
            }
        }

        if( empty( $lang ) )
            $user_statuses_key_val_arr = $return_arr;

        return $return_arr;
    }

    public function valid_status( $status, $lang = false )
    {
        $all_statuses = $this->get_statuses( $lang );
        if( empty( $status )
         or empty( $all_statuses[$status] ) )
            return false;

        return $all_statuses[$status];
    }

    public static function generate_password( $len = 10, $params = false )
    {
        $hook_args = PHS_Hooks::default_common_hook_args();
        $hook_args['length'] = $len;
        // encoded password here...
        $hook_args['generated_pass'] = false;

        if( ($new_hook_args = PHS::trigger_hooks( PHS_Hooks::H_USERS_GENERATE_PASS, $hook_args ))
        and is_array( $new_hook_args ) and !empty( $new_hook_args['generated_pass'] ) )
            return $new_hook_args['generated_pass'];

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['percents'] ) or !is_array( $params['percents'] ) )
            $params['percents'] = array( 'spacial_chars' => 10, 'digits_chars' => 20, 'normal_chars' => 70, );

        if( !isset( $params['percents']['spacial_chars'] ) )
            $params['percents']['spacial_chars'] = 10;
        if( !isset( $params['percents']['digits_chars'] ) )
            $params['percents']['digits_chars'] = 20;
        if( !isset( $params['percents']['normal_chars'] ) )
            $params['percents']['normal_chars'] = 70;

        $spacial_chars_perc = intval( $params['percents']['spacial_chars'] );
        $digits_chars_perc = intval( $params['percents']['digits_chars'] );
        $normal_chars_perc = intval( $params['percents']['normal_chars'] );

        if( $spacial_chars_perc + $digits_chars_perc + $normal_chars_perc > 100 )
        {
            $spacial_chars_perc = 10;
            $digits_chars_perc = 20;
            $normal_chars_perc = 70;
        }

        $special_chars_dict = '!@#%^&*()_-+}{:;?/.,;';
        $digits_dict = '123456789';
        $letters_dict = 'abcdbefghklmnqprstuvwxyz';
        $special_chars_dict_len = strlen( $special_chars_dict );
        $digits_dict_len = strlen( $digits_dict );
        $letters_dict_len = strlen( $letters_dict );

        $uppercase_chars = 0;
        $special_chars = 0;
        $digit_chars = 0;

        $ret = '';
        for( $ret_len = 0; $ret_len < $len; $ret_len++ )
        {
            $uppercase_char = false;
            // 10% spacial char, 20% digit, 70% letter
            $dict_index = mt_rand( 0, 100 );
            if( $dict_index <= $spacial_chars_perc )
            {
                $current_dict = $special_chars_dict;
                $dict_len = $special_chars_dict_len;
                $special_chars++;
            } elseif( $dict_index <= $spacial_chars_perc + $digits_chars_perc )
            {
                $current_dict = $digits_dict;
                $dict_len = $digits_dict_len;
                $digit_chars++;
            } else
            {
                $current_dict = $letters_dict;
                $dict_len = $letters_dict_len;
                if( mt_rand( 0, 100 ) > 50 )
                {
                    $uppercase_char = true;
                    $uppercase_chars++;
                }
            }

            $ch = substr( $current_dict, mt_rand( 0, $dict_len - 1 ), 1 );
            if( $uppercase_char )
                $ch = strtoupper( $ch );

            $ret .= $ch;
        }

        // Add a special char if none was added already
        if( !$special_chars )
        {
            $ch = substr( $special_chars_dict, mt_rand( 0, $special_chars_dict_len - 1 ), 1 );
            // 50% in front or in back of the result
            if( mt_rand( 0, 100 ) > 50 )
                $ret .= $ch;
            else
                $ret = $ch.$ret;
        }

        // Add a special char if none was added already
        while( $digit_chars < 2 )
        {
            $ch = substr( $digits_dict, mt_rand( 0, $digits_dict_len - 1 ), 1 );
            // 50% in front or in back of the result
            if( mt_rand( 0, 100 ) > 50 )
                $ret .= $ch;
            else
                $ret = $ch.$ret;

            $digit_chars++;
        }

        return $ret;
    }

    public static function encode_pass( $pass, $salt )
    {
        $hook_args = PHS_Hooks::default_common_hook_args();
        $hook_args['pass'] = $pass;
        $hook_args['salt'] = $salt;
        // encoded password here...
        $hook_args['encoded_pass'] = false;

        if( ($new_hook_args = PHS::trigger_hooks( PHS_Hooks::H_USERS_ENCODE_PASS, $hook_args ))
        and is_array( $new_hook_args ) and !empty( $new_hook_args['encoded_pass'] ) )
            return $new_hook_args['encoded_pass'];

        return @hash( self::PASSWORDS_ALGO, $salt.'_'.$pass, false );
    }

    public function raw_check_pass( $acc_pass, $acc_salt, $pass )
    {
        if( empty( $acc_pass ) or empty( $acc_salt )
         or empty( $pass )
         or !($encoded_pass = self::encode_pass( $pass, $acc_salt ))
         or !@hash_equals( $acc_pass, $encoded_pass ) )
            return false;

        return true;
    }

    public function check_pass( $account_data, $pass )
    {
        if( !($account_arr = $this->data_to_array( $account_data )) )
            return false;

        $pass_salt = '';
        if( !empty( $account_arr['pass_salt'] ) )
            $pass_salt = $account_arr['pass_salt'];

        if( empty( $pass_salt )
        and (!($account_salt_arr = $this->get_details_fields( array( 'uid' => $account_arr['id'] ), array( 'table_name' => 'users_pass_salts' ) ))
         or !isset( $account_salt_arr['pass_salt'] )
         or !$this->raw_check_pass( $account_arr['pass'], $account_salt_arr['pass_salt'], $pass )) )
            return false;

        return $account_arr;
    }

    public function obfuscate_password( $account_data )
    {
        $this->reset_error();

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or empty( $account_arr['pass_clear'] ) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        $clean_pass = PHS_crypt::quick_decode( $account_arr['pass_clear'] );

        $obfuscated_pass = substr( $clean_pass, 0, 1 ).str_repeat( '*', strlen( $clean_pass ) - 2 ).substr( $clean_pass, -1 );

        return $obfuscated_pass;
    }

    public function clean_password( $account_data )
    {
        $this->reset_error();

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or empty( $account_arr['pass_clear'] ) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( !($clean_pass = PHS_crypt::quick_decode( $account_arr['pass_clear'] )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Couldn\'t obtain account password.' ) );
            return false;
        }

        return $clean_pass;
    }

    public function is_password_expired( $account_data, $params = false )
    {
        $return_arr = PHS_Hooks::default_password_expiration_data();

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or $this->is_deleted( $account_arr )
         or !($settings_arr = $this->get_plugin_settings())
         or !is_array( $settings_arr )
         or empty( $settings_arr['expire_passwords_days'] )
         or ($expire_days = intval( $settings_arr['expire_passwords_days'] )) <= 0 )
            return $return_arr;

        $now_time = time();

        // block_after_expiration in hours
        if( empty( $settings_arr['block_after_expiration'] ) )
            $settings_arr['block_after_expiration'] = 0;
        else
            $settings_arr['block_after_expiration'] = intval( $settings_arr['block_after_expiration'] );

        $block_after_seconds = -1;
        if( $settings_arr['block_after_expiration'] != -1 )
            $block_after_seconds = $settings_arr['block_after_expiration'] * 3600;

        $expire_seconds = $expire_days * 86400;

        if( empty( $account_arr['last_pass_change'] )
         or empty_db_date( $account_arr['last_pass_change'] ) )
            // in case password was never changed, consider password is expired and force user to change password
            $last_pass_change_time = $now_time - $expire_seconds - $block_after_seconds - 3600;
        else
            $last_pass_change_time = parse_db_date( $account_arr['last_pass_change'] );

        $expiration_time = $last_pass_change_time + $expire_seconds;

        $expired_for_seconds = 0;
        if( $expiration_time < $now_time )
            $expired_for_seconds = $now_time - $expiration_time;

        $return_arr['is_expired'] = ($expired_for_seconds > 0?true:false);
        $return_arr['show_only_warning'] = (($block_after_seconds == -1 or $expired_for_seconds < $block_after_seconds)?true:false);
        $return_arr['pass_expires_seconds'] = $expiration_time;
        $return_arr['last_pass_change_seconds'] = $last_pass_change_time;
        $return_arr['expiration_days'] = $expire_days;
        $return_arr['expired_for_seconds'] = $expired_for_seconds;
        $return_arr['account_data'] = $account_arr;

        return $return_arr;
    }

    private function _add_account_password_to_history( $account_data, $params = false )
    {
        $this->reset_error();

        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'users_pass_history' ) ))
         or !($uph_table_name = $this->get_flow_table_name( $flow_params )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Cannot obtain flow params.' ) );
            return false;
        }

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or !($account_salt_arr = $this->get_details_fields( array( 'uid' => $account_arr['id'] ), array( 'table_name' => 'users_pass_salts' ) )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Please provide a valid account to save password history.' ) );
            return false;
        }

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( (empty( $params['{accounts_settings}'] )
                and !($params['{accounts_settings}'] = $this->get_plugin_settings())
            ) or !is_array( $params['{accounts_settings}'] ) )
            $params['{accounts_settings}'] = array();

        $accounts_settings = $params['{accounts_settings}'];

        if( empty( $accounts_settings['passwords_history_count'] )
         or !($history_count = intval( $accounts_settings['passwords_history_count'] )) )
        {
            // delete extra records
            db_query( 'DELETE FROM `'.$uph_table_name.'`'.
                      ' WHERE uid = \''.$account_arr['id'].'\'', $flow_params['db_connection'] );

            return true;
        }

        if( ($qid = db_query( 'SELECT COUNT(*) AS total_history_records '.
                              ' FROM `'.$uph_table_name.'`'.
                              ' WHERE uid = \''.$account_arr['id'].'\'', $flow_params['db_connection'] ))
        and ($record_arr = @mysqli_fetch_assoc( $qid ))
        and ($records_to_delete = $record_arr['total_history_records'] - $history_count + 1) > 0 )
        {
            // delete extra records
            db_query( 'DELETE FROM `'.$uph_table_name.'`'.
                      ' WHERE uid = \''.$account_arr['id'].'\' ORDER BY cdate ASC LIMIT '.$records_to_delete, $flow_params['db_connection'] );
        }

        $changed_by_uid = 0;
        if( ($changed_account_arr = PHS::user_logged_in()) )
            $changed_by_uid = $changed_account_arr['id'];

        $insert_fields_arr = array();
        $insert_fields_arr['uid'] = $account_arr['id'];
        $insert_fields_arr['changed_by_uid'] = $changed_by_uid;
        $insert_fields_arr['pass_salt'] = $account_salt_arr['pass_salt'];
        $insert_fields_arr['pass'] = $account_arr['pass'];
        $insert_fields_arr['pass_clear'] = $account_arr['pass_clear'];
        $insert_fields_arr['cdate'] = date( self::DATETIME_DB );

        $insert_arr = $flow_params;
        $insert_arr['fields'] = $insert_fields_arr;

        if( !($history_arr = $this->insert( $insert_arr )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error saving password history data.' ) );
            return false;
        }

        return $history_arr;
    }

    public function is_password_in_history( $account_data, $pass, $params = false )
    {
        $this->reset_error();

        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'users_pass_history' ) ))
         or !($uph_table_name = $this->get_flow_table_name( $flow_params )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Cannot obtain flow params.' ) );
            return false;
        }

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Please provide a valid account to save password history.' ) );
            return false;
        }

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( (empty( $params['{accounts_settings}'] )
                and !($params['{accounts_settings}'] = $this->get_plugin_settings())
            ) or !is_array( $params['{accounts_settings}'] ) )
            $params['{accounts_settings}'] = array();

        $accounts_settings = $params['{accounts_settings}'];

        if( empty( $accounts_settings['passwords_history_count'] )
         or !($history_count = intval( $accounts_settings['passwords_history_count'] ))
         or !($qid = db_query( 'SELECT * '.
                              ' FROM `'.$uph_table_name.'`'.
                              ' WHERE uid = \''.$account_arr['id'].'\' '.
                              ' ORDER BY cdate DESC LIMIT 0, '.$history_count, $flow_params['db_connection'] ))
         or !@mysqli_num_rows( $qid ) )
            return false;

        $return_arr = array();
        $return_arr['history_count'] = $history_count;
        $return_arr['oldest_password_date_timestamp'] = false;
        $return_arr['matched_history_data'] = false;

        while( ($history_arr = @mysqli_fetch_assoc( $qid )) )
        {
            if( empty( $return_arr['oldest_password_date_timestamp'] ) )
                $return_arr['oldest_password_date_timestamp'] = parse_db_date( $history_arr['cdate'] );

            if( !empty( $history_arr['pass'] )
            and !empty( $history_arr['pass_salt'] )
            and $this->raw_check_pass( $history_arr['pass'], $history_arr['pass_salt'], $pass ) )
            {
                $return_arr['matched_history_data'] = $history_arr;

                return $return_arr;
            }
        }

        return false;
    }

    public function clear_idler_sessions()
    {
        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'online' ) ))
         or !db_query( 'DELETE FROM `'.$this->get_flow_table_name( $flow_params ).'` WHERE expire_date < \''.date( self::DATETIME_DB ).'\'', $flow_params['db_connection'] ) )
            return false;

        return true;
    }

    public function update_current_session( $online_data, $params = false )
    {
        if( empty( $online_data )
         or !($online_arr = $this->data_to_array( $online_data, array( 'table_name' => 'online' ) )) )
            return false;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['location'] ) )
            $params['location'] = PHS::relative_url( PHS::current_url() );
        else
            $params['location'] = trim( $params['location'] );

        if( isset( $params['auid'] ) )
            $params['auid'] = intval( $params['auid'] );
        if( isset( $params['uid'] ) )
            $params['uid'] = intval( $params['uid'] );

        if( !($host = request_ip()) )
            $host = '127.0.0.1';

        $now_time = time();
        $cdate = date( self::DATETIME_DB, $now_time );

        $edit_arr = array();
        if( !empty( $params['uid'] ) )
            $edit_arr['uid'] = $params['uid'];
        if( !empty( $params['auid'] ) )
            $edit_arr['auid'] = $params['auid'];
        $edit_arr['host'] = $host;
        $edit_arr['idle'] = $cdate;
        $edit_arr['expire_date'] = date( self::DATETIME_DB, $now_time + $online_arr['expire_mins'] * 60 );
        $edit_arr['location'] = $params['location'];

        $edit_params = array();
        $edit_params['table_name'] = 'online';
        $edit_params['fields'] = $edit_arr;

        if( !($online_arr = $this->edit( $online_arr, $edit_params )) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving session details to database.' ) );

            return false;
        }

        return $online_arr;
    }

    public function session_logout_subaccount( $online_data )
    {
        if( empty( $online_data )
         or !($online_arr = $this->data_to_array( $online_data, array( 'table_name' => 'online' ) ))
         or empty( $online_arr['auid'] ) )
            return false;

        $edit_arr = array();
        $edit_arr['table_name'] = 'online';
        $edit_arr['fields'] = array();
        $edit_arr['fields']['uid'] = $online_arr['auid'];
        $edit_arr['fields']['auid'] = 0;

        return $this->edit( $online_arr, $edit_arr );
    }

    public function session_logout( $online_data )
    {
        if( empty( $online_data )
         or !($online_arr = $this->data_to_array( $online_data, array( 'table_name' => 'online' ) ))
         or empty( $online_arr['id'] ) )
            return false;

        return $this->hard_delete( $online_arr, array( 'table_name' => 'online' ) );
    }

    public function create_session_id()
    {
        return md5( uniqid( rand(), true ) );
    }

    public function login( $account_data, $params = false )
    {
        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or empty( $account_arr['id'] ) )
        {
            $this->set_error( self::ERR_LOGIN, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['expire_mins'] ) )
            $params['expire_mins'] = 0;
        else
            $params['expire_mins'] = intval( $params['expire_mins'] );

        if( empty( $params['location'] ) )
            $params['location'] = PHS::relative_url( PHS::current_url() );
        else
            $params['location'] = trim( $params['location'] );

        $auid = 0;
        if( ($current_user = PHS::user_logged_in())
        and ($current_session = PHS::current_user_session())
        and !empty( $current_session['id'] ) )
        {
            if( !PHS_Roles::user_has_role_units( $current_user, PHS_Roles::ROLEU_LOGIN_SUBACCOUNT ) )
            {
                $this->set_error( self::ERR_LOGIN, $this->_pt( 'Already logged in.' ) );
                return false;
            }

            $new_session_params = array();
            $new_session_params['uid'] = $account_arr['id'];
            $new_session_params['auid'] = $current_user['id'];
            $new_session_params['location'] = $params['location'];

            if( !($onuser_arr = $this->update_current_session( $current_session, $new_session_params )) )
            {
                if( !$this->has_error() )
                    $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving session details to database.' ) );

                return false;
            }

            return $onuser_arr;
        }

        if( !($host = request_ip()) )
            $host = '127.0.0.1';
        
        $now_time = time();
        $cdate = date( self::DATETIME_DB, $now_time );

        $insert_arr = array();
        $insert_arr['wid'] = $this->create_session_id();
        $insert_arr['uid'] = $account_arr['id'];
        $insert_arr['auid'] = $auid;
        $insert_arr['host'] = $host;
        $insert_arr['idle'] = $cdate;
        $insert_arr['connected'] = $cdate;
        $insert_arr['expire_date'] = (empty( $params['expire_mins'] )?null:date( self::DATETIME_DB, $now_time + $params['expire_mins'] ));
        $insert_arr['expire_mins'] = $params['expire_mins'];
        $insert_arr['location'] = $params['location'];

        $insert_params = array();
        $insert_params['table_name'] = 'online';
        $insert_params['fields'] = $insert_arr;

        if( !($onuser_arr = $this->insert( $insert_params )) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving session details to database.' ) );

            return false;
        }

        $edit_arr = array();
        $edit_arr['lastlog'] = $cdate;
        $edit_arr['lastip'] = $host;

        $edit_params = array();
        $edit_params['fields'] = $edit_arr;

        if( ($new_account_arr = $this->edit( $account_arr, $edit_params )) )
            $account_arr = $new_account_arr;

        return $onuser_arr;
    }

    public function email_verified( $account_data, $params = false )
    {
        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );

            return false;
        }

        if( !empty( $account_arr['email_verified'] ) )
            return $account_arr;

        $edit_arr = array();
        $edit_arr['email_verified'] = 1;

        $edit_params = array();
        $edit_params['fields'] = $edit_arr;

        return $this->edit( $account_arr, $edit_params );
    }

    public function activate_account_after_registration( $account_data, $params = false )
    {
        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data ))
         or !$this->needs_activation( $account_arr ) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );

            return false;
        }

        $edit_arr = array();
        $edit_arr['status'] = self::STATUS_ACTIVE;

        $edit_params = array();
        $edit_params['{activate_after_registration}'] = true;
        $edit_params['fields'] = $edit_arr;

        return $this->edit( $account_arr, $edit_params );
    }

    public function activate_account( $account_data, $params = false )
    {
        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( $this->is_active( $account_arr ) )
            return $account_arr;

        $edit_arr = array();
        $edit_arr['status'] = self::STATUS_ACTIVE;

        $edit_params = array();
        if( $this->needs_confirmation_email( $account_arr )
        and $this->is_just_registered( $account_arr ) )
            $edit_params['{activate_after_registration}'] = true;
        $edit_params['fields'] = $edit_arr;

        return $this->edit( $account_arr, $edit_params );
    }

    public function inactivate_account( $account_data, $params = false )
    {
        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( $this->is_inactive( $account_arr ) )
            return $account_arr;

        $edit_arr = array();
        $edit_arr['status'] = self::STATUS_INACTIVE;

        $edit_params = array();
        $edit_params['fields'] = $edit_arr;

        return $this->edit( $account_arr, $edit_params );
    }

    public function delete_account( $account_data, $params = false )
    {
        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( $this->is_deleted( $account_arr ) )
            return $account_arr;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['unlink_roles'] ) )
            $params['unlink_roles'] = false;

        $edit_arr = array();
        $edit_arr['nick'] = $account_arr['nick'].'-DELETED-'.time();
        $edit_arr['email'] = $account_arr['email'].'-DELETED-'.time();
        $edit_arr['status'] = self::STATUS_DELETED;

        $edit_params = array();
        $edit_params['fields'] = $edit_arr;

        if( !($new_account_arr = $this->edit( $account_arr, $edit_params )) )
            return false;

        if( !empty( $params['unlink_roles'] ) )
            PHS_Roles::unlink_all_roles_from_user( $account_arr );

        return $new_account_arr;
    }

    public function send_confirmation_email( $account_data, $params = false )
    {
        $this->reset_error();

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_EMAIL, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( !$this->needs_confirmation_email( $account_arr ) )
        {
            $this->set_error( self::ERR_EMAIL, $this->_pt( 'This account doesn\'t need a confirmation email anymore. Logged in before or already active.' ) );
            return false;
        }

        if( !PHS_bg_jobs::run( array( 'plugin' => 'accounts', 'action' => 'registration_confirmation_bg' ), array( 'uid' => $account_arr['id'] ) ) )
        {
            if( self::st_has_error() )
                $this->copy_static_error( self::ERR_EMAIL );
            else
                $this->set_error( self::ERR_EMAIL, $this->_pt( 'Error sending confirmation email. Please try again.' ) );

            return false;
        }

        return $account_arr;
    }

    public function send_after_registration_email( $account_data, $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $account_data )
         or !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_EMAIL, $this->_pt( 'Unknown account.' ) );
            return false;
        }

        if( empty( $params['send_confirmation_email'] ) )
            $params['send_confirmation_email'] = false;

        if( empty( $params['accounts_plugin_settings'] )
         or !is_array( $params['accounts_plugin_settings'] ) )
            $params['accounts_plugin_settings'] = false;

        if( empty( $params['accounts_plugin_settings'] )
            and (!($params['accounts_plugin_settings'] = $this->get_plugin_settings())
                or !is_array( $params['accounts_plugin_settings'] )
            ) )
            $params['accounts_plugin_settings'] = array();

        $return_arr = array();
        $return_arr['has_error'] = false;
        $return_arr['activation_email_required'] = false;
        $return_arr['activation_email_failed'] = false;
        $return_arr['confirmation_email_required'] = false;
        $return_arr['confirmation_email_failed'] = false;

        if( !$this->needs_after_registration_email( $account_arr, $params ) )
            return $return_arr;

        $registration_email_sent = false;
        if( $this->needs_activation( $account_arr, array( 'accounts_plugin_settings' => $params['accounts_plugin_settings'] ) ) )
        {
            $return_arr['activation_email_required'] = true;

            // send activation email...
            if( !PHS_bg_jobs::run( array( 'plugin' => 'accounts', 'action' => 'registration_email_bg' ), array( 'uid' => $account_arr['id'] ) ) )
            {
                $return_arr['has_error'] = true;
                $return_arr['activation_email_failed'] = true;

                if( self::st_has_error() )
                    $this->copy_static_error( self::ERR_EMAIL );
                else
                    $this->set_error( self::ERR_EMAIL, $this->_pt( 'Error sending activation email. Please try again.' ) );

                return $return_arr;
            }

            $registration_email_sent = true;
        }

        if( !$registration_email_sent
        and !empty( $params['send_confirmation_email'] ) )
        {
            $return_arr['confirmation_email_required'] = true;

            // send confirmation email...
            if( $this->needs_confirmation_email( $account_arr )
            and !$this->send_confirmation_email( $account_arr ) )
            {
                $return_arr['has_error'] = true;
                $return_arr['confirmation_email_failed'] = true;
            }

            $this->reset_error();
        }

        return $return_arr;
    }

    /**
     * Called first in insert flow.
     * Parses flow parameters if anything special should be done.
     * This should do checks on raw parameters received by insert method.
     *
     * @param array $params Parameters in the flow
     *
     * @return array|bool Flow parameters array
     */
    protected function get_insert_prepare_params_users( $params )
    {
        if( empty( $params ) or !is_array( $params ) )
            return false;

        if( !($accounts_settings = $this->get_plugin_settings())
         or !is_array( $accounts_settings ) )
            $accounts_settings = array();

        if( !empty( $accounts_settings['email_mandatory'] )
        and empty( $params['fields']['email'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide an email.' ) );
            return false;
        }

        if( !empty( $params['fields']['email'] )
        and !PHS_params::check_type( $params['fields']['email'], PHS_params::T_EMAIL ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide a valid email.' ) );
            return false;
        }

        if( !empty( $params['fields']['email'] )
        and (
                (empty( $params['fields']['nick'] ) and !empty( $accounts_settings['replace_nick_with_email'] ))
                or
                !empty( $accounts_settings['no_nickname_only_email'] )
            ) )
            $params['fields']['nick'] = $params['fields']['email'];

        if( empty( $params['fields']['nick'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide an username.' ) );
            return false;
        }

        if( empty( $params['fields']['level'] ) )
            $params['fields']['level'] = self::LVL_MEMBER;
        if( empty( $params['fields']['status'] ) )
        {
            if( empty( $accounts_settings['account_requires_activation'] ) )
                $params['fields']['status'] = self::STATUS_ACTIVE;
            else
                $params['fields']['status'] = self::STATUS_INACTIVE;
        }

        if( !$this->valid_level( $params['fields']['level'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide a valid account level.' ) );
            return false;
        }

        if( !$this->valid_status( $params['fields']['status'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide a valid status.' ) );
            return false;
        }

        if( empty( $params['fields']['pass'] ) and empty( $accounts_settings['generate_pass_if_not_present'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide a password.' ) );
            return false;
        }

        if( !empty( $params['fields']['pass'] ) )
        {
            if( !empty( $accounts_settings['min_password_length'] )
            and strlen( $params['fields']['pass'] ) < $accounts_settings['min_password_length'] )
            {
                $this->set_error( self::ERR_INSERT, $this->_pt( 'Password should be at least %s characters.',
                                                              $accounts_settings['min_password_length'] ) );

                return false;
            }

            if( !empty( $accounts_settings['password_regexp'] )
            and !@preg_match( $accounts_settings['password_regexp'], $params['fields']['pass'] ) )
            {
                if( !empty( $accounts_settings['password_regexp_explanation'] ) )
                    $this->set_error( self::ERR_INSERT, $this->_pt( $accounts_settings['password_regexp_explanation'] ) );

                elseif( ($regexp_parts = explode( '/', $accounts_settings['password_regexp'] ))
                and !empty( $regexp_parts[1] ) )
                {
                    if( empty( $regexp_parts[2] ) )
                        $regexp_parts[2] = '';

                    $this->set_error( self::ERR_INSERT,
                                      $this->_pt( 'Password doesn\'t match regular expression %s.',
                                                '<a href="https://regex101.com/?regex='.$regexp_parts[1].'&options='.$regexp_parts[2].'" title="'.$this->_pt( 'Click for details' ).'" target="_blank">'.$accounts_settings['password_regexp'].'</a>' ) );
                } else
                    $this->set_error( self::ERR_INSERT, $this->_pt( 'Password doesn\'t match regular expression %s.', $accounts_settings['password_regexp'] ) );

                return false;
            }
        }

        $check_arr = array();
        $check_arr['nick'] = $params['fields']['nick'];

        if( $this->get_details_fields( $check_arr ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Username already exists in database. Please pick another one.' ) );
            return false;
        }

        if( !empty( $params['fields']['email'] )
        and !empty( $accounts_settings['email_unique'] ) )
        {
            $check_arr = array();
            $check_arr['email'] = $params['fields']['email'];

            if( $this->get_details_fields( $check_arr ) )
            {
                $this->set_error( self::ERR_INSERT, $this->_pt( 'Email address exists in database. Please pick another one.' ) );
                return false;
            }
        }

        if( empty( $params['fields']['pass'] ) )
        {
            if( !empty( $accounts_settings['min_password_length'] ) )
                $pass_length = $accounts_settings['min_password_length'] + 3;
            else
                $pass_length = self::DEFAULT_MIN_PASSWORD_LENGTH;

            $params['fields']['pass'] = self::generate_password( $pass_length );
            $params['fields']['pass_generated'] = 1;
        } else
        {
            if( empty( $params['fields']['pass_generated'] ) )
                $params['fields']['pass_generated'] = 0;
            else
                $params['fields']['pass_generated'] = 1;
        }

        $now_date = date( self::DATETIME_DB );

        if( empty( $params['{pass_salt}'] ) )
            $params['{pass_salt}'] = self::generate_password( (!empty( $accounts_settings['pass_salt_length'] )?$accounts_settings['pass_salt_length']+3:self::DEFAULT_MIN_PASSWORD_LENGTH) );

        $params['fields']['pass_clear'] = PHS_crypt::quick_encode( $params['fields']['pass'] );
        $params['fields']['pass'] = self::encode_pass( $params['fields']['pass'], $params['{pass_salt}'] );
        $params['fields']['last_pass_change'] = $now_date;

        $params['fields']['status_date'] = $now_date;

        if( empty( $params['fields']['cdate'] ) or empty_db_date( $params['fields']['cdate'] ) )
            $params['fields']['cdate'] = $now_date;
        else
            $params['fields']['cdate'] = date( self::DATETIME_DB, parse_db_date( $params['fields']['cdate'] ) );

        $params['{accounts_settings}'] = $accounts_settings;

        if( empty( $params['{users_details}'] ) or !is_array( $params['{users_details}'] ) )
            $params['{users_details}'] = false;
        if( empty( $params['{account_roles}'] ) or !is_array( $params['{account_roles}'] ) )
            $params['{account_roles}'] = false;

        if( empty( $params['{send_confirmation_email}'] ) )
            $params['{send_confirmation_email}'] = false;

        return $params;
    }

    /**
     * Called right after a successfull insert in database. Some model need more database work after successfully adding records in database or eventually chaining
     * database inserts. If one chain fails function should return false so all records added before to be hard-deleted. In case of success, function will return an array with all
     * key-values added in database.
     *
     * @param array $insert_arr Data array added with success in database
     * @param array $params Flow parameters
     *
     * @return array|false Returns data array added in database (with changes, if required) or false if record should be deleted from database.
     * Deleted record will be hard-deleted
     */
    protected function insert_after_users( $insert_arr, $params )
    {
        if( empty( $params['{accounts_settings}'] ) or !is_array( $params['{accounts_settings}'] ) )
            $params['{accounts_settings}'] = array();

        $insert_arr['{users_details}'] = false;
        $insert_arr['{pass_salt}'] = false;

        if( !($accounts_details_model = PHS::load_model( 'accounts_details', $this->instance_plugin_name() )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error obtaining account details model instance.' ) );
            return false;
        }

        if( !empty( $params['{pass_salt}'] ) )
        {
            $salt_insert_arr = $this->fetch_default_flow_params( array( 'table_name' => 'users_pass_salts' ) );
            $salt_insert_arr['fields']['uid'] = $insert_arr['id'];
            $salt_insert_arr['fields']['pass_salt'] = $params['{pass_salt}'];

            if( !($salt_arr = $this->insert( $salt_insert_arr )) )
            {
                $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving account password. Please try again.' ) );
                return false;
            }

            $insert_arr['{pass_salt}'] = $salt_arr;
        }

        if( !empty( $params['{users_details}'] ) and is_array( $params['{users_details}'] ) )
        {
            if( !($insert_arr = $this->update_user_details( $insert_arr, $params['{users_details}'] )) )
            {
                if( !$this->has_error() )
                    $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving account details in database. Please try again.' ) );

                return false;
            }
        }

        if( $this->acc_is_admin( $insert_arr ) )
            $roles_arr = array( PHS_Roles::ROLE_MEMBER, PHS_Roles::ROLE_OPERATOR, PHS_Roles::ROLE_ADMIN );
        elseif( $this->acc_is_operator( $insert_arr ) )
            $roles_arr = array( PHS_Roles::ROLE_MEMBER, PHS_Roles::ROLE_OPERATOR );
        else
            $roles_arr = array( PHS_Roles::ROLE_MEMBER );

        $hook_args = PHS_Hooks::default_user_registration_roles_hook_args();
        $hook_args['roles_arr'] = $roles_arr;
        $hook_args['account_data'] = $insert_arr;

        if( ($extra_roles_arr = PHS::trigger_hooks( PHS_Hooks::H_USER_REGISTRATION_ROLES, $hook_args ))
        and is_array( $extra_roles_arr ) and !empty( $extra_roles_arr['roles_arr'] ) )
            $roles_arr = self::array_merge_unique_values( $extra_roles_arr['roles_arr'], $roles_arr );

        if( !empty( $params['{account_roles}'] ) and is_array( $params['{account_roles}'] ) )
            $roles_arr = self::array_merge_unique_values( $params['{account_roles}'], $roles_arr );

        PHS_Roles::link_roles_to_user( $insert_arr, $roles_arr );

        $registration_email_params = array();
        $registration_email_params['accounts_plugin_settings'] = $params['{accounts_settings}'];
        $registration_email_params['send_confirmation_email'] = $params['{send_confirmation_email}'];

        if( !($email_result = $this->send_after_registration_email( $insert_arr, $registration_email_params ))
         or !is_array( $email_result )
         or !empty( $email_result['has_error'] ) )
        {
            if( empty( $email_result ) or !is_array( $email_result ) )
                $email_result = array();

            // If only confirmation email fails don't delete the account...
            if( !empty( $insert_arr['{users_details}'] )
            and (
                empty( $email_result ) or !empty( $email_result['activation_email_failed'] )
                ) )
            {
                if( !$this->has_error() )
                    $this->set_error( self::ERR_EMAIL, $this->_pt( 'Error sending registration email. Please try again.' ) );

                $accounts_details_model->hard_delete( $insert_arr['{users_details}'] );
                return false;
            }
        }

        $hook_args = PHS_Hooks::default_user_account_hook_args();
        $hook_args['account_data'] = $insert_arr;
        $hook_args['account_details_data'] = $insert_arr['{users_details}'];

        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_USERS_REGISTRATION, $hook_args ))
        and is_array( $hook_args ) and !empty( $hook_args['account_data'] ) )
            $insert_arr = $hook_args['account_data'];

        return $insert_arr;
    }

    public function update_user_details( $account_data, $user_details_arr )
    {
        $this->reset_error();

        if( !($flow_params = $this->fetch_default_flow_params()) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Invalid flow parameters while updating user details.' ) );
            return false;
        }

        if( !($accounts_details_model = PHS::load_model( 'accounts_details', $this->instance_plugin_name() )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error obtaining account details model instance.' ) );
            return false;
        }

        if( !($account_arr = $this->data_to_array( $account_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Invalid account to update details.' ) );
            return false;
        }

        if( empty( $account_arr['details_id'] )
         or !($users_details = $accounts_details_model->get_details( $account_arr['details_id'] )) )
            $users_details = false;

        $hook_args = PHS_Hooks::default_user_account_fields_hook_args();
        $hook_args['account_data'] = $account_arr;
        $hook_args['account_details_data'] = $users_details;
        $hook_args['account_details_fields'] = $user_details_arr;

        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_USERS_DETAILS_FIELDS, $hook_args ))
        and is_array( $hook_args ) and !empty( $hook_args['account_details_fields'] ) )
            $user_details_arr = $hook_args['account_details_fields'];

        if( empty( $user_details_arr ) or !is_array( $user_details_arr ) )
            return true;

        if( empty( $users_details ) )
        {
            // no details yet saved...
            $user_details_arr['uid'] = $account_arr['id'];

            $details_params = array();
            $details_params['fields'] = $user_details_arr;

            if( !($users_details = $accounts_details_model->insert( $details_params )) )
            {
                if( $accounts_details_model->has_error() )
                    $this->copy_error( $accounts_details_model );
                else
                    $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving account details in database. Please try again.' ) );

                return false;
            }
        } else
        {
            $details_params = array();
            $details_params['fields'] = $user_details_arr;

            if( !($users_details = $accounts_details_model->edit( $users_details, $details_params )) )
            {
                if( $accounts_details_model->has_error() )
                    $this->copy_error( $accounts_details_model );
                else
                    $this->set_error( self::ERR_INSERT, $this->_pt( 'Error saving account details in database. Please try again.' ) );

                return false;
            }
        }

        if( empty( $account_arr['details_id'] )
        and !db_query( 'UPDATE `'.$this->get_flow_table_name( $flow_params ).'` SET details_id = \''.$users_details['id'].'\' WHERE id = \''.$account_arr['id'].'\'', $this->get_db_connection( $flow_params ) ) )
        {
            self::st_reset_error();

            $accounts_details_model->hard_delete( $users_details );

            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Couldn\'t link account details with the account. Please try again.' ) );
            return false;
        }

        $account_arr['details_id'] = $users_details['id'];
        $account_arr['{users_details}'] = $users_details;

        $hook_args = PHS_Hooks::default_user_account_hook_args();
        $hook_args['account_data'] = $account_arr;
        $hook_args['account_details_data'] = $users_details;

        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_USERS_DETAILS_UPDATED, $hook_args ))
        and is_array( $hook_args ) and !empty( $hook_args['account_data'] ) )
            $account_arr = $hook_args['account_data'];

        return $account_arr;
    }

    /**
     * Called first in edit flow.
     * Parses flow parameters if anything special should be done.
     * This should do checks on raw parameters received by edit method.
     *
     * @param array|int $existing_data Data which already exists in database (id or full array with all database fields)
     * @param array|false $params Parameters in the flow
     *
     * @return array|bool Flow parameters array
     */
    protected function get_edit_prepare_params_users( $existing_data, $params )
    {
        if( !($accounts_settings = $this->get_plugin_settings())
         or !is_array( $accounts_settings ) )
            $accounts_settings = array();

        $params['{password_was_changed}'] = false;
        if( !empty( $params['fields']['pass'] ) )
        {
            if( $this->check_pass( $existing_data, $params['fields']['pass'] ) )
            {
                $this->set_error( self::ERR_EDIT, $this->_pt( 'You used this password in the past. Please provide another one.' ) );
                return false;
            }

            if( !empty( $accounts_settings['min_password_length'] )
            and strlen( $params['fields']['pass'] ) < $accounts_settings['min_password_length'] )
            {
                $this->set_error( self::ERR_EDIT, $this->_pt( 'Password should be at least %s characters.', $accounts_settings['min_password_length'] ) );
                return false;
            }

            if( !empty( $accounts_settings['password_regexp'] )
            and !@preg_match( $accounts_settings['password_regexp'], $params['fields']['pass'] ) )
            {
                if( !empty( $accounts_settings['password_regexp_explanation'] ) )
                    $this->set_error( self::ERR_EDIT, $this->_pt( $accounts_settings['password_regexp_explanation'] ) );

                elseif( ($regexp_parts = explode( '/', $accounts_settings['password_regexp'] ))
                and !empty( $regexp_parts[1] ) )
                {
                    if( empty( $regexp_parts[2] ) )
                        $regexp_parts[2] = '';

                    $this->set_error( self::ERR_EDIT,
                                      $this->_pt( 'Password doesn\'t match regular expression %s.',
                                                  '<a href="https://regex101.com/?regex='.$regexp_parts[1].'&options='.$regexp_parts[2].'" title="'.$this->_pt( 'Click for details' ).'" target="_blank">'.$accounts_settings['password_regexp'].'</a>' ) );
                } else
                    $this->set_error( self::ERR_EDIT, $this->_pt( 'Password doesn\'t match regular expression %s.', $accounts_settings['password_regexp'] ) );

                return false;
            }

            if( ($history_details = $this->is_password_in_history( $existing_data, $params['fields']['pass'] )) )
            {
                if( !empty( $history_details['history_count'] )
                and !empty( $history_details['oldest_password_date_timestamp'] ) )
                    $this->set_error( self::ERR_EDIT, $this->_pt( 'You used this password in last %s, one of last %s passwords. Please provide another one.',
                                                                  PHS_utils::parse_period( abs( time() - $history_details['oldest_password_date_timestamp'] ), array( 'only_big_part' => true ) ),
                                                                  $history_details['history_count'] ) );
                else
                    $this->set_error( self::ERR_EDIT, $this->_pt( 'You used this password in the past. Please provide another one.' ) );

                return false;
            }

            $params['{pass_salt}'] = self::generate_password( (!empty( $accounts_settings['pass_salt_length'] )?$accounts_settings['pass_salt_length'] + 3 : 8) );
            $params['fields']['pass_clear'] = PHS_crypt::quick_encode( $params['fields']['pass'] );
            $params['fields']['pass'] = self::encode_pass( $params['fields']['pass'], $params['{pass_salt}'] );
            $params['fields']['last_pass_change'] = date( self::DATETIME_DB );

            $params['{password_was_changed}'] = true;
        }

        if( empty( $params['{password_was_changed}'] ) )
        {
            // make sure passwords fields are not set if password will not be changed
            if( isset( $params['{pass_salt}'] ) )
                unset( $params['{pass_salt}'] );
            if( isset( $params['fields']['pass_clear'] ) )
                unset( $params['fields']['pass_clear'] );
            if( isset( $params['fields']['pass'] ) )
                unset( $params['fields']['pass'] );
        }

        if( isset( $params['fields']['email'] )
        and $params['fields']['email'] != $existing_data['email'] )
        {
            // If we delete the account, just skip checks...
            if( empty( $params['fields']['status'] )
             or $params['fields']['status'] != self::STATUS_DELETED )
            {
                if( empty( $params['fields']['email'] )
                 or !PHS_params::check_type( $params['fields']['email'], PHS_params::T_EMAIL ) )
                {
                    $this->set_error( self::ERR_EDIT, $this->_pt( 'Invalid email address.' ) );
                    return false;
                }

                if( !empty( $accounts_settings['email_unique'] ) )
                {
                    $check_arr          = array();
                    $check_arr['email'] = $params['fields']['email'];
                    $check_arr['id']    = array( 'check' => '!=', 'value' => $existing_data['id'] );

                    if( $this->get_details_fields( $check_arr ) )
                    {
                        $this->set_error( self::ERR_EDIT, $this->_pt( 'Email address exists in database. Please pick another one.' ) );
                        return false;
                    }
                }
            }

            if( (empty( $params['fields']['nick'] ) and !empty( $accounts_settings['replace_nick_with_email'] ))
             or !empty( $accounts_settings['no_nickname_only_email'] ) )
                $params['fields']['nick'] = $params['fields']['email'];

            $params['fields']['email_verified'] = 0;
        }

        if( isset( $params['fields']['nick'] )
        and $params['fields']['nick'] != $existing_data['nick'] )
        {
            // If we delete the account, just skip checks...
            if( empty( $params['fields']['status'] )
             or $params['fields']['status'] != self::STATUS_DELETED )
            {
                $check_arr         = array();
                $check_arr['nick'] = $params['fields']['nick'];
                $check_arr['id']   = array( 'check' => '!=', 'value' => $existing_data['id'] );

                if( $this->get_details_fields( $check_arr ) )
                {
                    $this->set_error( self::ERR_EDIT, $this->_pt( 'Nickname already exists in database. Please pick another one.' ) );
                    return false;
                }
            }
        }

        if( isset( $params['fields']['status'] ) )
        {
            if( !$this->valid_status( $params['fields']['status'] ) )
            {
                $this->set_error( self::ERR_EDIT, $this->_pt( 'Please provide a valid status.' ) );
                return false;
            }

            $cdate = date( self::DATETIME_DB );
            $params['fields']['status_date'] = $cdate;

            if( $params['fields']['status'] == self::STATUS_DELETED )
                $params['fields']['deleted'] = $cdate;
        }

        if( isset( $params['fields']['level'] )
        and !$this->valid_level( $params['fields']['level'] ) )
        {
            $this->set_error( self::ERR_EDIT, $this->_pt( 'Please provide a valid account level.' ) );
            return false;
        }

        $params['{accounts_settings}'] = $accounts_settings;

        if( empty( $params['{users_details}'] ) or !is_array( $params['{users_details}'] ) )
            $params['{users_details}'] = false;

        if( empty( $params['{activate_after_registration}'] ) )
            $params['{activate_after_registration}'] = false;

        return $params;
    }

    /**
     * Called right after a successfull edit action. Some model need more database work after editing records. This action is called even if model didn't save anything
     * in database.
     *
     * @param array|int $existing_data Data which already exists in database (id or full array with all database fields)
     * @param array $edit_arr Data array saved with success in database. This can also be an empty array (nothing to save in database)
     * @param array $params Flow parameters
     *
     * @return array|bool Returns data array added in database (with changes, if required) or false if record should be deleted from database.
     * Deleted record will be hard-deleted
     */
    protected function edit_after_users( $existing_data, $edit_arr, $params )
    {
        if( !empty( $params['{users_details}'] ) and is_array( $params['{users_details}'] ) )
        {
            if( !($existing_data = $this->update_user_details( $existing_data, $params['{users_details}'] )) )
            {
                if( !$this->has_error() )
                    $this->set_error( self::ERR_EDIT, $this->_pt( 'Error saving account details in database. Please try again.' ) );

                return false;
            }
        }

        if( !empty( $params['{account_roles}'] ) and is_array( $params['{account_roles}'] ) )
        {
            /** @var \phs\system\core\models\PHS_Model_Roles $roles_model */
            if( !($roles_model = PHS::load_model( 'roles' ))
             or !$roles_model->link_roles_to_user( $existing_data, $params['{account_roles}'], array( 'append_roles' => false ) ) )
            {
                if( $roles_model->has_error() )
                    $this->copy_error( $roles_model, self::ERR_EDIT );
                else
                    $roles_model->set_error( self::ERR_EDIT, $this->_pt( 'Error saving account roles in database. Please try again.' ) );

                return false;
            }
        }

        if( !empty( $params['{password_was_changed}'] ) )
        {
            if( !empty( $params['{pass_salt}'] )
            and ($salt_flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'users_pass_salts' ) ))
            and ($salt_table_name = $this->get_flow_table_name( $salt_flow_params )) )
            {
                $check_arr = array();
                $check_arr['uid'] = $existing_data['id'];

                $fields_arr = array();
                $fields_arr['pass_salt'] = $params['{pass_salt}'];

                $salt_data_arr = $salt_flow_params;
                $salt_data_arr['fields'] = $fields_arr;

                if( ($existing_salt_arr = $this->get_details_fields( $check_arr, $salt_flow_params )) )
                {
                    if( !($salt_arr = $this->edit( $existing_salt_arr, $salt_data_arr )) )
                    {
                        $this->set_error( self::ERR_EDIT, $this->_pt( 'Error saving account password. Please try again.' ) );
                        return false;
                    }
                } else
                {
                    $salt_data_arr['fields']['uid'] = $existing_data['id'];

                    if( !($salt_arr = $this->insert( $salt_data_arr )) )
                    {
                        $this->set_error( self::ERR_EDIT, $this->_pt( 'Error inserting account password. Please try again.' ) );
                        return false;
                    }
                }

                $existing_data['{pass_salt}'] = $salt_arr;
            }

            $history_params = array();
            $history_params['{accounts_settings}'] = $params['{accounts_settings}'];

            // save old password to history
            if( !$this->_add_account_password_to_history( $existing_data, $history_params ) )
            {
                PHS_Logger::logf( 'Couldn\'t save user #'.$existing_data['id'].' password to history: ['.$this->get_error_message().']', PHS_Logger::TYPE_ERROR );

                $this->reset_error();
            }

            if( !empty( $params['{accounts_settings}'] ) and is_array( $params['{accounts_settings}'] )
            and !empty( $params['{accounts_settings}']['announce_pass_change'] ) )
            {
                // send password changed email...
                PHS_bg_jobs::run( array( 'plugin' => 'accounts', 'action' => 'pass_changed_email_bg' ), array( 'uid' => $existing_data['id'] ) );
            }
        }

        if( !empty( $params['{activate_after_registration}'] )
        and $this->needs_confirmation_email( $existing_data ) )
        {
            $this->send_confirmation_email( $existing_data );
        }

        // As we announce account action, we should have updated values...
        $structure_hook_args = PHS_Hooks::default_account_structure_hook_args();
        $structure_hook_args['account_data'] = $existing_data['id'];

        /** @var \phs\plugins\accounts\PHS_Plugin_Accounts $plugin_obj */
        if( ($plugin_obj = $this->get_plugin_instance())
        and ($account_structure = $plugin_obj->get_account_structure( $structure_hook_args ))
        and !empty( $account_structure['account_structure'] ) )
            $existing_data = $account_structure['account_structure'];

        $hook_args = PHS_Hooks::default_account_action_hook_args();
        $hook_args['account_data'] = $existing_data;
        $hook_args['action_alias'] = 'edit';
        $hook_args['action_params'] = $params;
        $hook_args['route'] = PHS::get_route_details();

        if( ($result_arr = PHS_Hooks::trigger_account_action( $hook_args ))
        and !empty( $result_arr['account_data'] ) )
            $existing_data = $result_arr['account_data'];

        return $existing_data;
    }

    /**
     * @inheritdoc
     */
    protected function get_count_list_common_params( $params = false )
    {
        $model_table = $this->get_flow_table_name( $params );

        if( !empty( $params['flags'] ) and is_array( $params['flags'] ) )
        {
            if( empty( $params['db_fields'] ) )
                $params['db_fields'] = '';

            foreach( $params['flags'] as $flag )
            {
                switch( $flag )
                {
                    case 'include_account_details':

                        $old_error_arr = PHS::st_stack_error();
                        if( !($account_details_model = PHS::load_model( 'accounts_details', $this->instance_plugin_name() ))
                         or !($user_details_table = $account_details_model->get_flow_table_name()) )
                        {
                            PHS::st_restore_errors( $old_error_arr );
                            continue;
                        }

                        $params['db_fields'] .= ', `'.$user_details_table.'`.title AS users_details_title, '.
                                                ' `'.$user_details_table.'`.fname AS users_details_fname, '.
                                                ' `'.$user_details_table.'`.lname AS users_details_lname, '.
                                                ' `'.$user_details_table.'`.phone AS users_details_phone, '.
                                                ' `'.$user_details_table.'`.company AS users_details_company ';
                        $params['join_sql'] .= ' LEFT JOIN `'.$user_details_table.'` ON `'.$user_details_table.'`.id = `'.$model_table.'`.details_id ';
                    break;
                }
            }
        }

        if( empty( $params['one_of_role_unit'] ) or !is_array( $params['one_of_role_unit'] ) )
            $params['one_of_role_unit'] = false;
        if( empty( $params['one_of_role'] ) or !is_array( $params['one_of_role'] ) )
            $params['one_of_role'] = false;

        if( empty( $params['all_role_units'] ) or !is_array( $params['all_role_units'] ) )
            $params['all_role_units'] = false;
        if( empty( $params['all_roles'] ) or !is_array( $params['all_roles'] ) )
            $params['all_roles'] = false;

        if( !empty( $params['one_of_role_unit'] )
         or !empty( $params['all_role_units'] )
         or !empty( $params['one_of_role'] )
         or !empty( $params['all_roles'] ) )
        {
            $old_error_arr = PHS::st_stack_error();
            /** @var \phs\system\core\models\PHS_Model_Roles $roles_model */
            if( !($roles_model = PHS::load_model( 'roles' ))
             or !($roles_users_flow = $roles_model->fetch_default_flow_params( array( 'table_name' => 'roles_users' ) ))
             or !($roles_users_table = $roles_model->get_flow_table_name( $roles_users_flow ))
            )
                PHS::st_restore_errors( $old_error_arr );

            else
            {
                $roles_users_joined = false;
                if( !empty( $params['one_of_role_unit'] ) and is_array( $params['one_of_role_unit'] ) )
                {
                    if( ($one_of_role = $roles_model->get_roles_ids_for_roles_units_list( $params['one_of_role_unit'] ))
                    and is_array( $one_of_role ) )
                    {
                        if( empty( $params['one_of_role'] ) or !is_array( $params['one_of_role'] ) )
                            $params['one_of_role'] = $one_of_role;

                        else
                            $params['one_of_role'] = array_merge( $params['one_of_role'], $one_of_role );
                    }
                }

                // if( !empty( $params['all_role_units'] ) and is_array( $params['all_role_units'] ) )
                // {
                //     if( ($all_roles_groups = $roles_model->get_roles_ids_for_roles_units_list_grouped( $params['all_role_units'] ))
                //     and is_array( $all_roles_groups ) )
                //     {
                //         $extra_sql = '';
                //         foreach( $all_roles_groups as $role_unit_id => $roles_arr )
                //         {
                //             if( empty( $roles_arr ) or !is_array( $roles_arr ) )
                //                 continue;
                //
                //             $extra_sql .= ($extra_sql!=''?' AND ':'').' `'.$roles_users_table.'`.role_id IN ('.@implode( ',', $roles_arr ).')';
                //         }
                //
                //         if( $extra_sql != '' )
                //         {
                //             if( empty( $roles_users_joined ) )
                //                 $params['join_sql'] .= ' LEFT JOIN `'.$roles_users_table.'` ON `'.$roles_users_table.'`.user_id = `'.$model_table.'`.id ';
                //
                //             $roles_users_joined = true;
                //
                //             $params['fields'][] = array(
                //                 'raw' => '('.$extra_sql.')',
                //             );
                //         }
                //
                //         // if( empty( $params['all_roles'] ) or !is_array( $params['all_roles'] ) )
                //         //     $params['all_roles'] = $all_roles;
                //         //
                //         // else
                //         //     $params['all_roles'] = array_merge( $params['all_roles'], $all_roles );
                //     }
                // }

                if( !empty( $params['one_of_role'] )
                and ($one_of_role_ids = $roles_model->roles_list_to_ids( $params['one_of_role'] ))
                and is_array( $one_of_role_ids ))
                {
                    if( empty( $roles_users_joined ) )
                        $params['join_sql'] .= ' LEFT JOIN `'.$roles_users_table.'` ON `'.$roles_users_table.'`.user_id = `'.$model_table.'`.id ';

                    $roles_users_joined = true;

                    $params['fields'][] = array(
                        'raw' => 'EXISTS (SELECT 1 FROM `'.$roles_users_table.'` '.
                                    ' WHERE `'.$roles_users_table.'`.user_id = `'.$model_table.'`.id AND `'.$roles_users_table.'`.role_id IN ('.@implode( ',', $one_of_role_ids ).'))',
                    );
                }

                // if( !empty( $params['all_roles'] )
                // and ($all_roles_ids = $roles_model->roles_list_to_ids( $params['all_roles'] ))
                // and is_array( $all_roles_ids ))
                // {
                //     if( empty( $roles_users_joined ) )
                //         $params['join_sql'] .= ' LEFT JOIN `'.$roles_users_table.'` ON `'.$roles_users_table.'`.user_id = `'.$model_table.'`.id ';
                //
                //     $roles_users_joined = true;
                //
                //     $params['fields'][] = array(
                //         'raw' => '(`'.$roles_users_table.'`.user_id = `'.$model_table.'`.id AND `'.$roles_users_table.'`.role_id IN ('.@implode( ',', $all_roles_ids ).'))',
                //     );
                // }
            }
        }

        return $params;
    }

    //
    // region Version Updates
    //
    private function _update_to_104_or_higher()
    {
        $this->reset_error();

        // Make sure we don't throw errors here...
        $st_throwing_errors = PHS::st_throw_errors();
        $throwing_errors = $this->throw_errors();
        $this->throw_errors( false );
        PHS::st_throw_errors( false );

        // Changed passwords encoding function from md5 to sha256
        if( @function_exists( 'hash_algos' )
        and !in_array( self::PASSWORDS_ALGO, (array)@hash_algos() ) )
        {
            $this->set_error( self::ERR_SERVER, $this->_pt( '%s hash algorithm not available on this server.', self::PASSWORDS_ALGO ) );
            $this->throw_errors( $throwing_errors );
            PHS::st_throw_errors( $st_throwing_errors );
            return false;
        }

        // we work with low level queries so we don't trigger functionalities from model...
        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'users' ) ))
         or !($user_table_name = $this->get_flow_table_name( $flow_params ))
         or !($qid = db_query( 'SELECT * FROM `'.$user_table_name.'`', $flow_params['db_connection'] )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error querying users from database.' ) );
            $this->throw_errors( $throwing_errors );
            PHS::st_throw_errors( $st_throwing_errors );
            return false;
        }

        if( !($users_count = @mysqli_num_rows( $qid )) )
            return true;

        PHS_Logger::logf( 'Converting passwords from md5 to sha256 for '.$users_count.' accounts...', PHS_Logger::TYPE_MAINTENANCE );

        while( ($users_arr = @mysqli_fetch_assoc( $qid )) )
        {
            if( empty( $users_arr['pass_clear'] )
             or !($pass_clear = PHS_crypt::quick_decode( $users_arr['pass_clear'] )) )
            {
                PHS_Logger::logf( 'Couldn\'t convert password for user #'.$users_arr['id'].'. Please change password manually or using forgot password.', PHS_Logger::TYPE_MAINTENANCE );
                continue;
            }

            // Already converted...
            if( $this->check_pass( $users_arr, $pass_clear ) )
                continue;

            $edit_arr = array();
            $edit_arr['pass'] = self::encode_pass( $pass_clear, $users_arr['pass_salt'] );

            if( !($sql = db_quick_edit( $user_table_name, $edit_arr, $flow_params['db_connection'] ))
             or !db_query( $sql.' WHERE id = \''.$users_arr['id'].'\'', $flow_params['db_connection'] ) )
            {
                PHS_Logger::logf( 'Couldn\'t save converted password for user #'.$users_arr['id'].'. Please change password manually or using forgot password.', PHS_Logger::TYPE_MAINTENANCE );
                continue;
            }
        }

        PHS_Logger::logf( 'FINISHED Converting passwords.', PHS_Logger::TYPE_MAINTENANCE );

        $this->throw_errors( $throwing_errors );
        PHS::st_throw_errors( $st_throwing_errors );

        return true;
    }

    private function _update_to_110_or_higher()
    {
        $this->reset_error();

        // Make sure we don't throw errors here...
        $st_throwing_errors = PHS::st_throw_errors();
        $throwing_errors = $this->throw_errors();
        $this->throw_errors( false );
        PHS::st_throw_errors( false );

        // we work with low level queries so we don't trigger functionalities from model...
        if( !($salt_flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'users_pass_salts' ) ))
         or !($salt_table_name = $this->get_flow_table_name( $salt_flow_params )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error obtaining password salts flow.' ) );
            $this->throw_errors( $throwing_errors );
            PHS::st_throw_errors( $st_throwing_errors );
            return false;
        }

        // we work with low level queries so we don't trigger functionalities from model...
        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'users' ) ))
         or !($user_table_name = $this->get_flow_table_name( $flow_params ))
         or !($qid = db_query( 'SELECT * FROM `'.$user_table_name.'`', $flow_params['db_connection'] )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error querying users from database.' ) );
            $this->throw_errors( $throwing_errors );
            PHS::st_throw_errors( $st_throwing_errors );
            return false;
        }

        if( !($users_count = @mysqli_num_rows( $qid )) )
            return true;

        PHS_Logger::logf( 'Converting passwords salts for '.$users_count.' accounts...', PHS_Logger::TYPE_MAINTENANCE );

        while( ($users_arr = @mysqli_fetch_assoc( $qid )) )
        {
            // Already converted...
            if( empty( $users_arr['pass_salt'] ) )
                continue;

            $check_arr = array();
            $check_arr['uid'] = $users_arr['id'];

            $fields_arr = array();
            $fields_arr['pass_salt'] = $users_arr['pass_salt'];

            if( ($existing_arr = $this->get_details_fields( $check_arr, $salt_flow_params )) )
            {
                if( !($sql = db_quick_edit( $salt_table_name, $fields_arr, $salt_flow_params['db_connection'] ))
                 or !db_query( $sql.' WHERE id = \''.$existing_arr['id'].'\'', $salt_flow_params['db_connection'] ) )
                {
                    PHS_Logger::logf( 'Couldn\'t save converted password salt for user #'.$users_arr['id'].'. Please change password manually or using forgot password.', PHS_Logger::TYPE_MAINTENANCE );
                    continue;
                }
            } else
            {
                $fields_arr['uid'] = $users_arr['id'];

                if( !($sql = db_quick_insert( $salt_table_name, $fields_arr, $salt_flow_params['db_connection'] ))
                 or !($item_id = db_query_insert( $sql, $salt_flow_params['db_connection'] )) )
                {
                    PHS_Logger::logf( 'Couldn\'t insert converted password salt for user #'.$users_arr['id'].'. Please change password manually or using forgot password.', PHS_Logger::TYPE_MAINTENANCE );
                    continue;
                }
            }
        }

        PHS_Logger::logf( 'FINISHED Converting password salts.', PHS_Logger::TYPE_MAINTENANCE );

        $this->throw_errors( $throwing_errors );
        PHS::st_throw_errors( $st_throwing_errors );

        return true;
    }
    //
    // endregion Version Updates
    //

    /**
     * @inheritdoc
     */
    final public function fields_definition( $params = false )
    {
        // $params should be flow parameters...
        if( empty( $params ) or !is_array( $params )
         or empty( $params['table_name'] ) )
            return false;

        $return_arr = array();
        switch( $params['table_name'] )
        {
            case 'users':
                $return_arr = array(
                    self::T_DETAILS_KEY => array(
                        'comment' => 'Account information (minimal details required for login)',
                    ),

                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'nick' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '150',
                        'nullable' => true,
                        'index' => true,
                    ),
                    'pass' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '100',
                        'nullable' => true,
                    ),
                    'pass_clear' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '150',
                        'nullable' => true,
                    ),
                    'email' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '150',
                        'index' => true,
                        'nullable' => true,
                    ),
                    'email_verified' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                        'default' => 0,
                    ),
                    'pass_generated' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                        'default' => 0,
                    ),
                    'added_by' => array(
                        'type' => self::FTYPE_INT,
                    ),
                    'details_id' => array(
                        'type' => self::FTYPE_INT,
                        'comment' => 'users_details.id',
                    ),
                    'status' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                        'index' => true,
                    ),
                    'status_date' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => false,
                    ),
                    'level' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                    ),
                    'deleted' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => true,
                    ),
                    'last_pass_change' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => false,
                    ),
                    'lastlog' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => false,
                    ),
                    'lastip' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'index' => false,
                        'length' => '50',
                        'nullable' => true,
                    ),
                    'cdate' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                );
            break;

            case 'users_pass_history':
                $return_arr = array(
                    self::T_DETAILS_KEY => array(
                        'comment' => 'Users passwords history',
                    ),

                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'uid' => array(
                        'type' => self::FTYPE_INT,
                        'index' => true,
                    ),
                    'changed_by_uid' => array(
                        'type' => self::FTYPE_INT,
                        'index' => true,
                    ),
                    'pass' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '100',
                        'nullable' => true,
                    ),
                    'pass_salt' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '50',
                        'nullable' => true,
                    ),
                    'pass_clear' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '150',
                        'nullable' => true,
                    ),
                    'cdate' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                );
            break;

            case 'users_pass_salts':
                $return_arr = array(
                    self::T_DETAILS_KEY => array(
                        'comment' => 'Users passwords salt',
                    ),

                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'uid' => array(
                        'type' => self::FTYPE_INT,
                        'index' => true,
                    ),
                    'pass_salt' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '50',
                        'nullable' => true,
                    ),
                );
            break;

            case 'online':
                $return_arr = array(
                    self::T_DETAILS_KEY => array(
                        'comment' => 'Users session details',
                    ),

                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'wid' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '50',
                        'index' => true,
                        'nullable' => true,
                    ),
                    'uid' => array(
                        'type' => self::FTYPE_INT,
                        'index' => true,
                    ),
                    'auid' => array(
                        'type' => self::FTYPE_INT,
                    ),
                    'host' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '50',
                        'nullable' => true,
                    ),
                    'idle' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                    'connected' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                    'expire_date' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => true,
                    ),
                    'expire_mins' => array(
                        'type' => self::FTYPE_INT,
                    ),
                    'location' => array(
                        'type' => self::FTYPE_TEXT,
                        'nullable' => true,
                    ),
                );
                break;
        }

        return $return_arr;
    }
}
