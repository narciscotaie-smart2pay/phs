<?php

namespace phs\plugins\accounts\actions;

use \phs\PHS;
use \phs\PHS_Scope;
use \phs\PHS_bg_jobs;
use \phs\libraries\PHS_Error;
use \phs\libraries\PHS_Action;
use \phs\libraries\PHS_params;
use \phs\libraries\PHS_Hooks;
use \phs\libraries\PHS_Notifications;

class PHS_Action_Forgot extends PHS_Action
{
    /** @inheritdoc */
    public function action_roles()
    {
        return array( self::ACT_ROLE_FORGOT_PASSWORD );
    }

    public function allowed_scopes()
    {
        return array( PHS_Scope::SCOPE_WEB, PHS_Scope::SCOPE_AJAX );
    }

    /**
     * @return array|bool
     */
    public function execute()
    {
        PHS::page_settings( 'page_title', $this->_pt( 'Forgot Password' ) );

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
        {
            PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load accounts model.' ) );
            return self::default_action_result();
        }

        if( PHS::user_logged_in() )
        {
            PHS_Notifications::add_success_notice( $this->_pt( 'Already logged in...' ) );

            $action_result = self::default_action_result();

            $action_result['redirect_to_url'] = PHS::url();

            return $action_result;
        }

        $foobar = PHS_params::_p( 'foobar', PHS_params::T_INT );
        $email = PHS_params::_pg( 'email', PHS_params::T_EMAIL );
        $vcode = PHS_params::_p( 'vcode', PHS_params::T_NOHTML );
        $do_submit = PHS_params::_p( 'do_submit' );

        if( PHS_params::_g( 'email_sent', PHS_params::T_INT ) )
            PHS_Notifications::add_success_notice( $this->_pt( 'Email with instructions sent to provided email address.' ) );

        if( !empty( $do_submit ) )
        {
            /** @var \phs\plugins\captcha\PHS_Plugin_Captcha $captcha_plugin */
            if( !($captcha_plugin = PHS::load_plugin( 'captcha' )) )
                PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load captcha plugin.' ) );

            elseif( ($hook_result = PHS_Hooks::trigger_captcha_check( $vcode )) !== null
                and empty( $hook_result['check_valid'] ) )
            {
                if( PHS_Error::arr_has_error( $hook_result['hook_errors'] ) )
                    PHS_Notifications::add_error_notice( PHS_Error::arr_get_error_message( $hook_result['hook_errors'] ) );
                else
                    PHS_Notifications::add_error_notice( $this->_pt( 'Invalid validation code.' ) );
            }

            elseif( !($account_arr = $accounts_model->get_details_fields( array( 'email' => $email, 'status' => $accounts_model::STATUS_ACTIVE ) )) )
                PHS_Notifications::add_error_notice( $this->_pt( 'Invalid account.' ) );

            else
            {
                if( !PHS_bg_jobs::run( array( 'plugin' => 'accounts', 'action' => 'forgot_password_bg' ), array( 'uid' => $account_arr['id'] ) ) )
                {
                    if( self::st_has_error() )
                        $error_msg = self::st_get_error_message();
                    else
                        $error_msg = $this->_pt( 'Error sending forgot password email. Please try again.' );

                    PHS_Notifications::add_error_notice( $error_msg );
                }

                PHS_Hooks::trigger_captcha_regeneration();
            }

            if( !PHS_Notifications::have_notifications_errors() )
            {
                $action_result = self::default_action_result();

                $action_result['redirect_to_url'] = PHS::url( array( 'p' => 'accounts', 'a' => 'forgot' ), array( 'email_sent' => 1 ) );

                return $action_result;
            }
        }

        $data = array(
            'email' => $email,
            'vcode' => $vcode,
        );

        return $this->quick_render_template( 'forgot', $data );
    }
}
