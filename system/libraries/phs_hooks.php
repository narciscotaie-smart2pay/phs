<?php

namespace phs\libraries;

use \phs\PHS;
use \phs\libraries\PHS_Action;

//! This class define all core hooks (for usability)
class PHS_Hooks extends PHS_Registry
{
    const H_AFTER_BOOTSTRAP = 'after_bootstrap', H_BEFORE_ACTION_EXECUTE = 'before_action_execute', H_AFTER_ACTION_EXECUTE = 'after_action_execute',

         // Language hooks
         H_LANGUAGE_DEFINITION = 'phs_language_definition',

         // Model hooks
         H_MODEL_EMPTY_DATA = 'phs_model_empty_data', H_MODEL_VALIDATE_DATA_FIELDS = 'phs_model_validate_data_fields',

         // Paginator hooks
         H_PAGINATOR_ACTION_PARAMETERS = 'phs_paginator_action_parameters',

         // Plugins hooks
         H_PLUGIN_SETTINGS = 'phs_plugin_settings', H_PLUGIN_REGISTRY = 'phs_plugin_registry',

         // Logging hooks
         H_LOG = 'phs_logger',

         // URL hooks
         H_URL_REWRITE = 'phs_url_rewrite', H_ROUTE = 'phs_route',

         // Location / Scripts hooks
         H_PAGE_INDEX = 'phs_page_index', H_PAGE_REGISTER = 'phs_page_register',

         // Email hooks
         H_EMAIL_INIT = 'phs_email_init',

         // Notifications hooks
         H_NOTIFICATIONS_DISPLAY = 'phs_notifications_display',

         // Cookie notice hooks
         H_COOKIE_NOTICE_DISPLAY = 'phs_cookie_notice_display',

         // Messages hooks
         H_MSG_GET_SUMMARY = 'phs_messages_summary', H_MSG_TYPES = 'phs_messages_types',
         H_MSG_SINGLE_DISPLAY_TYPES_ACTIONS = 'phs_messages_single_types_actions',

         // Captcha hooks
         H_CAPTCHA_DISPLAY = 'phs_captcha_display', H_CAPTCHA_CHECK = 'phs_captcha_check', H_CAPTCHA_REGENERATE = 'phs_captcha_regenerate',

         // Roles hooks
         H_GUEST_ROLES_SLUGS = 'phs_guest_roles_slugs',

         // API hooks
         H_API_REQUEST_INIT = 'phs_api_request_init',
         H_API_ROUTE = 'phs_api_route', H_API_REQUEST_ENDED = 'phs_api_request_ended',
         H_API_API_INITED = 'phs_api_inited',
         H_API_ACTION_INITED = 'phs_api_action_inited', H_API_ACTION_ENDED = 'phs_api_action_ended',

         // User account hooks
         H_USER_DB_DETAILS = 'phs_user_db_details', H_USER_LEVELS = 'phs_user_levels', H_USER_STATUSES = 'phs_user_statuses',
         // triggered to obtain an account structure for a given int, array (obtained from database) or false (for guest accounts)
         H_USER_ACCOUNT_STRUCTURE = 'phs_user_account_structure',
         // triggered to get list of roles to assign to new users
         H_USER_REGISTRATION_ROLES = 'phs_user_registration_roles',
         // triggered to manage user fields at registration
         H_USERS_REGISTRATION = 'phs_users_registration',
         // triggered before user details get updated (used to change user details fields)
         H_USERS_DETAILS_FIELDS = 'phs_users_details_fields',
         // triggered after user details are updated
         H_USERS_DETAILS_UPDATED = 'phs_users_details_updated',
         // triggered when encoding user passwords
         H_USERS_ENCODE_PASS = 'phs_users_encode_pass',
         // triggered when generating user passwords
         H_USERS_GENERATE_PASS = 'phs_users_generate_pass',
         // triggered after user logs in successfully
         H_USERS_AFTER_LOGIN = 'phs_users_after_login',

         // Layout hooks
         H_WEB_TEMPLATE_RENDERING = 'phs_web_template_rendering',
         H_WEB_SUBVIEW_RENDERING = 'phs_web_subview_rendering',
         H_ADMIN_TEMPLATE_PAGE_HEAD = 'phs_admin_template_page_head',
         H_ADMIN_TEMPLATE_PAGE_START = 'phs_admin_template_page_start',
         H_ADMIN_TEMPLATE_PAGE_END = 'phs_admin_template_page_end',

         H_ADMIN_TEMPLATE_BEFORE_LEFT_MENU = 'phs_admin_template_before_left_menu',
         H_ADMIN_TEMPLATE_AFTER_LEFT_MENU = 'phs_admin_template_after_left_menu',
         H_ADMIN_TEMPLATE_BEFORE_RIGHT_MENU = 'phs_admin_template_before_right_menu',
         H_ADMIN_TEMPLATE_AFTER_RIGHT_MENU = 'phs_admin_template_after_right_menu',

         H_ADMIN_TEMPLATE_BEFORE_MAIN_MENU = 'phs_admin_template_before_main_menu',
         H_ADMIN_TEMPLATE_AFTER_MAIN_MENU = 'phs_admin_template_after_main_menu',

         H_MAIN_TEMPLATE_PAGE_HEAD = 'phs_main_template_page_head',
         H_MAIN_TEMPLATE_PAGE_START = 'phs_main_template_page_start',
         H_MAIN_TEMPLATE_PAGE_END = 'phs_main_template_page_end',

         H_MAIN_TEMPLATE_BEFORE_LEFT_MENU = 'phs_main_template_before_left_menu',
         H_MAIN_TEMPLATE_AFTER_LEFT_MENU = 'phs_main_template_after_left_menu',
         H_MAIN_TEMPLATE_BEFORE_RIGHT_MENU = 'phs_main_template_before_right_menu',
         H_MAIN_TEMPLATE_AFTER_RIGHT_MENU = 'phs_main_template_after_right_menu',

         H_MAIN_TEMPLATE_BEFORE_MAIN_MENU = 'phs_main_template_before_main_menu',
         H_MAIN_TEMPLATE_AFTER_MAIN_MENU = 'phs_main_template_after_main_menu',
         H_MAIN_TEMPLATE_BEFORE_MAIN_MENU_LOGGED_IN = 'phs_main_template_before_main_menu_logged_in',
         H_MAIN_TEMPLATE_AFTER_MAIN_MENU_LOGGED_IN = 'phs_main_template_after_main_menu_logged_in',
         H_MAIN_TEMPLATE_BEFORE_MAIN_MENU_LOGGED_OUT = 'phs_main_template_before_main_menu_logged_out',
         H_MAIN_TEMPLATE_AFTER_MAIN_MENU_LOGGED_OUT = 'phs_main_template_after_main_menu_logged_out';

    public static function default_common_hook_args()
    {
        return array(
            'hook_errors' => self::default_error_array(),
        );
    }

    public static function reset_common_hook_args( $hook_args )
    {
        if( empty( $hook_args ) or !is_array( $hook_args ) )
            return self::validate_array( $hook_args, self::default_common_hook_args() );

        $hook_args['hook_errors'] = self::default_error_array();

        return $hook_args;
    }

    public static function default_url_rewrite_hook_args()
    {
        return self::validate_array_recursive( array(

            // Received parameters
            'route_arr' => array(),
            'args' => array(),
            'raw_args' => array(),

            // URL generated by PHS
            'stock_args' => array(),
            'stock_query_string' => array(),
            'stock_url' => '',

            // URL generated by plugins
            'new_url' => false,

        ), self::default_common_hook_args() );
    }

    public static function default_phs_route_hook_args()
    {
        return self::validate_array_recursive( array(

            // Ask plugins if they want to change this route
            'original_route' => false,

            // Plugins should change this into a route array if they want to change the route (see PHS::validate_route_from_parts( $route_arr ))
            'altered_route' => false,

        ), self::default_common_hook_args() );
    }

    public static function default_api_hook_args()
    {
        return self::validate_array_recursive( array(
            // Instantiated API instance. If no plugin picks up PHS_Hooks::H_API_REQUEST_INIT hook call \phs\PHS_api class instance will be used
            'api_obj' => false,

            // This is an array of API route tokens (NOT a PHS route)
            // This can be translated by plugins in other API route tokens (change action which will be run in the end)
            // If no plugin alters this, API class will check all API defined routes to obtain a PHS route from tokens in this array
            // (array)
            'api_route_tokens' => false,

            // Plugins should set this to an API tokenized path (see PHS_api::tokenize_api_route()) (array)
            'altered_api_route_tokens' => false,

            // Matched API route defined in plugins (if any) after comparing api_route_tokens against route segments of each API route
            //// (array)
            'api_route' => false,

            // PHS route action to be run for current API request
            'phs_route' => false,

            'action_obj' => false,

        ), self::default_common_hook_args() );
    }

    public static function default_paginator_action_parameters_hook_args()
    {
        return self::validate_array_recursive( array(
            'paginator_action_obj' => false,
            'paginator_params' => array(),
        ), self::default_common_hook_args() );
    }

    public static function default_language_definition_hook_args()
    {
        return self::validate_array_recursive( array(
            'default_language' => false,
            'languages_arr' => array(),
        ), self::default_common_hook_args() );
    }

    public static function default_page_location_hook_args()
    {
        return self::validate_array_recursive( array(
            'action_result' => false,
            'page_template' => false,
            'page_template_args' => false,
            'new_page_template' => false,
            'new_page_template_args' => false,
        ), self::default_common_hook_args() );
    }

    public static function default_action_execute_hook_args()
    {
        return self::validate_array_recursive( array(
            'action_obj' => false,
            'action_result' => PHS_Action::default_action_result(),
        ), self::default_common_hook_args() );
    }

    public static function default_message_types_hook_args()
    {
        return self::validate_array_recursive( array(
            'types_arr' => array(),
        ), self::default_common_hook_args() );
    }

    public static function default_single_types_actions_hook_args()
    {
        return self::validate_array_recursive( array(
            'actions_arr' => array(),
            'message_data' => false,
            'destination_str' => '',
            'author_handle' => '',
        ), self::default_common_hook_args() );
    }

    public static function default_model_validate_data_fields_hook_args()
    {
        return self::validate_array_recursive( array(
            'flow_params' => false,
            'table_fields' => array(),
        ), self::default_common_hook_args() );
    }

    public static function default_model_empty_data_hook_args()
    {
        return self::validate_array_recursive( array(
            'data_arr' => array(),
            'flow_params' => false,
        ), self::default_common_hook_args() );
    }

    // Default hook parameters sent for hooks related to guest roles
    public static function default_guest_roles_hook_args()
    {
        return self::validate_array_recursive( array(
            'guest_roles' => array(),
        ), self::default_common_hook_args() );
    }

    // Default hook parameters sent for hooks related to user account
    public static function default_user_account_hook_args()
    {
        return self::validate_array_recursive( array(
            'account_data' => false,
            'account_details_data' => false,
        ), self::default_common_hook_args() );
    }

    // Default hook parameters sent for hooks related to user account (including insert/edit parameters)
    public static function default_user_account_fields_hook_args()
    {
        return self::validate_array_recursive( array(
            'account_data' => false,
            'account_details_data' => false,
            'account_fields' => false,
            'account_details_fields' => false,
        ), self::default_common_hook_args() );
    }

    public static function default_user_registration_roles_hook_args()
    {
        return self::validate_array_recursive( array(
            'roles_arr' => array(),
            'account_data' => false,
        ), self::default_common_hook_args() );
    }

    public static function default_user_db_details_hook_args()
    {
        return self::validate_array_recursive( array(
            'force_check' => false,
            'user_db_data' => false,
            'session_db_data' => false,
            // How many seconds since session expired (0 - session didn't expired)
            'session_expired_secs' => 0,
        ), self::default_common_hook_args() );
    }

    // Used to get account structure (including roles) Account data can be empty or an empty structure (a guest empty structure)
    public static function default_account_structure_hook_args()
    {
        return self::validate_array_recursive( array(
            // Account id or array to be transformed into account structure (input)
            'account_data' => false,
            // Account structure (from database or empty strcuture for guests)
            'account_structure' => false,
        ), self::default_common_hook_args() );
    }

    public static function default_buffer_hook_args()
    {
        return self::validate_array_recursive( array(
            'concatenate_buffer' => 'buffer',
            'buffer_data' => array(),
            'buffer' => '',
        ), self::default_common_hook_args() );
    }

    public static function default_messages_summary_hook_args()
    {
        return self::validate_array_recursive( array(
            'messages_new' => 0,
            'messages_count' => 0,
            'messages_list' => array(),
            'list_limit' => 5,
            'summary_container_id' => '',
            'template' => array(
                'file' => '',
                'extra_paths' => array(),
            ), // default template
            'summary_buffer' => '',
        ), self::default_common_hook_args() );
    }

    public static function default_notifications_hook_args()
    {
        return self::validate_array_recursive( array(
            'warnings' => array(),
            'errors' => array(),
            'success' => array(),
            'template' => array(
                'file' => '',
                'extra_paths' => array(),
            ), // default template
            'display_channels' => array( 'warnings', 'errors', 'success' ),
            'output_ajax_placeholders' => true,
            'notifications_buffer' => '',
        ), self::default_common_hook_args() );
    }

    public static function default_captcha_display_hook_args()
    {
        return self::validate_array_recursive( array(
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
            'captcha_buffer' => '',
        ), self::default_common_hook_args() );
    }

    public static function default_init_email_hook_args()
    {
        return self::validate_array_recursive( array(
            'template' => array(
                'file' => '',
                'extra_paths' => array(),
            ), // default template

            // In case we don't use a template and we just pass a string as email body
            'body_buffer' => false,

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

            'attach_files' => array(),

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

            'send_result' => false,
        ), self::default_common_hook_args() );
    }

    public static function reset_email_hook_args( $hook_args )
    {
        if( empty( $hook_args ) or !is_array( $hook_args ) )
            return self::default_init_email_hook_args();

        // in case hook arguments are cascaded...
        $hook_args['send_result'] = false;

        return self::reset_common_hook_args( $hook_args );
    }

    public static function default_captcha_check_hook_args()
    {
        return self::validate_array_recursive( array(
            'check_code' => '',
            'check_valid' => false,
        ), self::default_common_hook_args() );
    }

    public static function default_captcha_regeneration_hook_args()
    {
        return self::validate_array_recursive( array(
        ), self::default_common_hook_args() );
    }

    public static function trigger_email( $hook_args )
    {
        self::st_reset_error();

        $hook_args = self::reset_email_hook_args( self::validate_array( $hook_args, PHS_Hooks::default_init_email_hook_args() ) );

        // If we don't have hooks registered, we don't use captcha
        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_EMAIL_INIT, $hook_args )) === null )
            return null;

        if( is_array( $hook_args )
        and !empty( $hook_args['hook_errors'] ) and is_array( $hook_args['hook_errors'] )
        and self::arr_has_error( $hook_args['hook_errors'] ) )
        {
            self::st_copy_error_from_array( $hook_args['hook_errors'] );
            return false;
        }

        return $hook_args;
    }

    public static function trigger_guest_roles( $hook_args = false )
    {
        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_guest_roles_hook_args() );

        // If we don't have hooks registered, we don't use captcha
        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_GUEST_ROLES_SLUGS, $hook_args )) === null )
            return false;

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

    public static function trigger_account_structure( $hook_args = false )
    {
        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_account_structure_hook_args() );

        if( ($hook_args = PHS::trigger_hooks( PHS_Hooks::H_USER_ACCOUNT_STRUCTURE, $hook_args )) === null )
            return false;

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
