<?php

namespace phs\plugins\accounts\actions;

use \phs\PHS;
use \phs\libraries\PHS_Action;
use \phs\libraries\PHS_params;
use \phs\libraries\PHS_Notifications;
use \phs\PHS_Scope;

class PHS_Action_Login extends PHS_Action
{

    /**
     * Returns an array of scopes in which action is allowed to run
     *
     * @return array If empty array, action is allowed in all scopes...
     */
    public function allowed_scopes()
    {
        return array( PHS_Scope::SCOPE_WEB, PHS_Scope::SCOPE_AJAX );
    }

    /**
     * @return array|bool
     */
    public function execute()
    {
        PHS::page_settings( 'page_title', $this->_pt( 'Login' ) );

        $foobar = PHS_params::_p( 'foobar', PHS_params::T_INT );
        $nick = PHS_params::_pg( 'nick', PHS_params::T_NOHTML );
        $pass = PHS_params::_pg( 'pass', PHS_params::T_NOHTML );
        $do_remember = PHS_params::_pg( 'do_remember', PHS_params::T_INT );
        $submit = PHS_params::_p( 'submit' );

        $back_page = PHS_params::_gp( 'back_page', PHS_params::T_NOHTML );

        $reason = PHS_params::_g( 'reason', PHS_params::T_NOHTML );

        /** @var \phs\plugins\accounts\PHS_Plugin_Accounts $accounts_plugin */
        if( !($accounts_plugin = $this->get_plugin_instance()) )
            PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load accounts plugin.' ) );

        if( !empty( $accounts_plugin )
        and !empty( $reason )
        and ($reason_success_text = $accounts_plugin->valid_confirmation_reason( $reason )) )
            PHS_Notifications::add_success_notice( $reason_success_text );

        if( PHS_params::_g( 'registered', PHS_params::T_INT ) )
            PHS_Notifications::add_success_notice( $this->_pt( 'Account registered and active. You can login now.' ) );
        if( PHS_params::_g( 'password_changed', PHS_params::T_INT ) )
            PHS_Notifications::add_success_notice( $this->_pt( 'Password changed with success. You can login now.' ) );

        if( empty( $foobar )
        and PHS::user_logged_in()
        and !PHS_Notifications::have_notifications_errors() )
        {
            PHS_Notifications::add_success_notice( $this->_pt( 'Already logged in...' ) );

            $action_result = self::default_action_result();

            $action_result['redirect_to_url'] = (!empty( $back_page )?$back_page:PHS::url());

            return $action_result;
        }

        if( !($plugin_settings = $this->get_plugin_settings()) )
            $plugin_settings = array();

        if( empty( $plugin_settings['session_expire_minutes_remember'] ) )
            $plugin_settings['session_expire_minutes_remember'] = 43200; // 30 days
        if( empty( $plugin_settings['session_expire_minutes_normal'] ) )
            $plugin_settings['session_expire_minutes_normal'] = 0; // till browser closes

        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !empty( $submit )
        and !PHS_Notifications::have_notifications_errors() )
        {
            if( empty( $nick ) or empty( $pass ) )
                PHS_Notifications::add_error_notice( $this->_pt( 'Please provide complete mandatory fields.' ) );

            elseif( !($accounts_model = PHS::load_model( 'accounts', $this->instance_plugin_name() )) )
                PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load accounts model.' ) );

            elseif( !($account_arr = $accounts_model->get_details_fields( array( 'nick' => $nick ) ))
                 or !$accounts_model->check_pass( $account_arr, $pass )
                 or !$accounts_model->is_active( $account_arr ) )
                PHS_Notifications::add_error_notice( $this->_pt( 'Bad username or password.' ) );

            else
            {
                $login_params = array();
                $login_params['expire_mins'] = (!empty( $do_remember )?$plugin_settings['session_expire_minutes_remember']:$plugin_settings['session_expire_minutes_normal']);

                if( $accounts_plugin->do_login( $account_arr, $login_params ) )
                {
                    PHS_Notifications::add_success_notice( $this->_pt( 'Successfully logged in...' ) );

                    $action_result = self::default_action_result();

                    $action_result['redirect_to_url'] = (!empty( $back_page )?from_safe_url( $back_page ):PHS::url());

                    return $action_result;
                }

                if( $accounts_plugin->has_error() )
                    PHS_Notifications::add_error_notice( $accounts_plugin->get_error_message() );
                else
                    PHS_Notifications::add_error_notice( $this->_pt( 'Error logging in... Please try again.' ) );
            }
        }

        $data = array(
            'back_page' => $back_page,
            'nick' => $nick,
            'pass' => $pass,
            'remember_me_session_minutes' => $plugin_settings['session_expire_minutes_remember'],
            'normal_session_minutes' => $plugin_settings['session_expire_minutes_normal'],
            'no_nickname_only_email' => $plugin_settings['no_nickname_only_email'],
            'do_remember' => (!empty( $do_remember )?'checked="checked"':''),
        );

        return $this->quick_render_template( 'login', $data );
    }
}
