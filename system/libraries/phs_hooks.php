<?php

namespace phs\libraries;

use \phs\PHS;

//! This class define all core hooks (for usability)
class PHS_Hooks extends PHS_Registry
{
    const H_AFTER_BOOTSTRAP = 'after_bootstrap', H_BEFORE_ACTION_EXECUTE = 'before_action_execute', H_AFTER_ACTION_EXECUTE = 'after_action_execute',

         // Plugins hooks
         H_PLUGIN_SETTINGS = 'phs_plugin_settings',

         // Logging hooks
         H_LOG = 'phs_logger',

         // URL hooks
         H_URL_PARAMS = 'phs_url_params',

         // Email hooks
         H_EMAIL_INIT = 'phs_email_init',

         // Notifications hooks
         H_NOTIFICATIONS_DISPLAY = 'phs_notifications_display',

         // Captcha hooks
         H_CAPTCHA_DISPLAY = 'phs_captcha_display', H_CAPTCHA_CHECK = 'phs_captcha_check', H_CAPTCHA_REGENERATE = 'phs_captcha_regenerate',

         // User account hooks
         H_USER_DB_DETAILS = 'phs_user_db_details',

         // Layout triggers
         H_ADMIN_TEMPLATE_BEFORE_LEFT_MENU = 'phs_admin_template_before_left_menu',
         H_ADMIN_TEMPLATE_AFTER_LEFT_MENU = 'phs_admin_template_after_left_menu',
         H_ADMIN_TEMPLATE_BEFORE_RIGHT_MENU = 'phs_admin_template_before_right_menu',
         H_ADMIN_TEMPLATE_AFTER_RIGHT_MENU = 'phs_admin_template_after_right_menu',

         H_MAIN_TEMPLATE_BEFORE_LEFT_MENU = 'phs_main_template_before_left_menu',
         H_MAIN_TEMPLATE_AFTER_LEFT_MENU = 'phs_main_template_after_left_menu',
         H_MAIN_TEMPLATE_BEFORE_RIGHT_MENU = 'phs_main_template_before_right_menu',
         H_MAIN_TEMPLATE_AFTER_RIGHT_MENU = 'phs_main_template_after_right_menu';

    public static function default_user_db_details_hook_args()
    {
        return array(
            'force_check' => false,
            'user_db_data' => false,
            'session_db_data' => false,
            // How many seconds since session expired (0 - session didn't expired)
            'session_expired_secs' => 0,
        );
    }

    public static function default_buffer_hook_args()
    {
        return array(
            'concatenate_buffer' => 'buffer',
            'buffer' => '',
        );
    }

    public static function default_notifications_hook_args()
    {
        return array(
            'warnings' => array(),
            'errors' => array(),
            'success' => array(),
            'template' => array(
                'file' => '',
                'extra_paths' => array(),
            ), // default template
            'display_channels' => array( 'warnings', 'errors', 'success' ),
            'notifications_buffer' => '',
        );
    }

    public static function default_captcha_display_hook_args()
    {
        return array(
            'template' => array(
                'file' => '',
                'extra_paths' => array(),
            ), // default template
            'font' => 'default.ttf',
            'characters_count' => 5,
            'default_width' => 200,
            'default_height' => 50,
            'extra_img_style' => '',
            'extra_img_attrs' => '',
            'hook_errors' => false,
            'captcha_buffer' => '',
        );
    }

    public static function default_init_email_hook_args()
    {
        return array(
            'template' => array(
                'file' => '',
                'extra_paths' => array(),
            ), // default template

            'route' => false,
            'route_settings' => false,

            'to' => '',
            'to_name' => '',
            'from_name' => '',
            'from_email' => '',
            'from_noreply' => '',
            'reply_name' => '',
            'reply_email' => '',
            'subject' => '',

            'also_send' => true,
            'send_as_noreply' => true,
            'with_priority' => false,
            'native_mail_function' => false,
            'custom_headers' => array(),
            'email_vars' => array(),
            'internal_vars' => array(),

            'email_html_body' => false,
            'email_text_body' => false,
            'full_body' => false,

            'hook_errors' => false,
            'send_result' => false,
        );
    }

    public static function default_captcha_check_hook_args()
    {
        return array(
            'check_code' => '',
            'check_valid' => false,
            'hook_errors' => false,
        );
    }

    public static function default_captcha_regeneration_hook_args()
    {
        return array(
            'hook_errors' => false,
        );
    }

    public static function trigger_email( $hook_args )
    {
        self::st_reset_error();

        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_init_email_hook_args() );

        // If we don't have hooks registered, we don't use captcha
        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_EMAIL_INIT, $hook_args )) === null )
            return null;

        if( is_array( $hook_args )
        and !empty( $hook_args['hook_errors'] )
        and self::arr_has_error( $hook_args['hook_errors'] ) )
        {
            self::st_copy_error_from_array( $hook_args['hook_errors'] );
            return false;
        }

        return $hook_args;
    }

    public static function trigger_current_user( $hook_args = false )
    {
        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_user_db_details_hook_args() );

        // If we don't have hooks registered, we don't use captcha
        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_USER_DB_DETAILS, $hook_args )) === null )
            return false;

        if( is_array( $hook_args )
        and !empty( $hook_args['session_expired_secs'] ) )
        {
            if( !@headers_sent() )
            {
                header( 'Location: '.PHS::url( array( 'p' => 'accounts', 'a' => 'login' ), array( 'expired_secs' => $hook_args['session_expired_secs'] ) ) );
                exit;
            }

            return false;
        }

        return $hook_args;
    }

    public static function trigger_captcha_display( $hook_args )
    {
        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_captcha_display_hook_args() );

        // If we don't have hooks registered, we don't use captcha
        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_CAPTCHA_DISPLAY, $hook_args )) === null )
            return '';

        if( is_array( $hook_args )
        and !empty( $hook_args['captcha_buffer'] ) )
            return $hook_args['captcha_buffer'];

        if( !empty( $hook_args['hook_errors'] ) )
            return 'Error: ['.PHS_Error::arr_get_error_code( $hook_args['hook_errors'] ).'] '.PHS_Error::arr_get_error_message( $hook_args['hook_errors'] );

        return '';
    }

    public static function trigger_captcha_check( $code )
    {
        $hook_args = self::validate_array( array( 'check_code' => $code ), PHS_Hooks::default_captcha_check_hook_args() );

        return PHS::trigger_hooks( PHS_Hooks::H_CAPTCHA_CHECK, $hook_args );
    }

    public static function trigger_captcha_regeneration()
    {
        $hook_args = self::validate_array( array(), PHS_Hooks::default_captcha_regeneration_hook_args() );

        return PHS::trigger_hooks( PHS_Hooks::H_CAPTCHA_REGENERATE, $hook_args );
    }
}
