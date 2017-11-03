<?php

namespace phs\system\core\scopes;

use \phs\PHS;
use \phs\PHS_Scope;
use \phs\libraries\PHS_Action;
use \phs\system\core\views\PHS_View;

class PHS_Scope_Web extends PHS_Scope
{
    public function get_scope_type()
    {
        return self::SCOPE_WEB;
    }

    public function process_action_result( $action_result, $static_error_arr = false )
    {
        /** @var \phs\libraries\PHS_Action $action_obj */
        if( !($action_obj = PHS::running_action()) )
            $action_obj = false;
        /** @var \phs\libraries\PHS_Controller $controller_obj */
        if( !($controller_obj = PHS::running_controller()) )
            $controller_obj = false;

        $action_result = self::validate_array( $action_result, PHS_Action::default_action_result() );

        if( !empty( $action_result['request_login'] ) )
        {
            $args = array();
            if( !empty( $action_result['redirect_to_url'] ) )
                $args['back_page'] = $action_result['redirect_to_url'];
            else
                $args['back_page'] = PHS::current_url();

            $action_result['redirect_to_url'] = PHS::url( array( 'p' => 'accounts', 'a' => 'login' ), $args );
        }

        if( !empty( $action_result['redirect_to_url'] )
        and !@headers_sent() )
        {
            @header( 'Location: '.$action_result['redirect_to_url'] );
            exit;
        }

        if( empty( $action_obj )
        and empty( $action_result['page_template'] ) )
        {
            echo 'No running action to render page template.';
            exit;
        }

        // send custom headers as we will echo page content here...
        if( !@headers_sent() )
        {
            $result_headers = array();
            if( !empty( $action_result['custom_headers'] ) and is_array( $action_result['custom_headers'] ) )
            {
                foreach( $action_result['custom_headers'] as $key => $val )
                {
                    if( empty( $key ) )
                        continue;

                    if( !is_null( $val ) )
                        $result_headers[$key] = $val;
                    else
                        $result_headers[$key] = '';
                }
            }

            $result_headers['X-Powered-By'] = 'PHS-'.PHS_VERSION;

            $result_headers = self::unify_array_insensitive( $result_headers, array( 'trim_keys' => true ) );

            foreach( $result_headers as $key => $val )
            {
                if( $val == '' )
                    @header( $key );
                else
                    @header( $key.': '.$val );
            }
        }

        if( self::arr_has_error( $static_error_arr ) )
            echo self::arr_get_error_message( $static_error_arr );

        elseif( empty( $action_obj )
         or empty( $action_result['page_template'] ) )
            echo $action_result['buffer'];

        else
        {
            $view_params = array();
            $view_params['action_obj'] = $action_obj;
            $view_params['controller_obj'] = $controller_obj;
            $view_params['parent_plugin_obj'] = (!empty( $action_obj )?$action_obj->get_plugin_instance():false);
            $view_params['plugin'] = (!empty( $action_obj )?$action_obj->instance_plugin_name():false);
            $view_params['template_data'] = (!empty( $action_result['action_data'] )?$action_result['action_data']:false);
            $view_params['as_singleton'] = false;

            if( !($view_obj = PHS_View::init_view( $action_result['page_template'], $view_params )) )
            {
                if( self::st_has_error() )
                    echo self::st_get_error_message();
                else
                    echo self::_t( 'Error instantiating view object.' );

                exit;
            }

            if( empty( $action_result['page_settings']['page_title'] ) )
                $action_result['page_settings']['page_title'] = '';

            $action_result['page_settings']['page_title'] .= ($action_result['page_settings']['page_title']!=''?' - ':'').PHS_SITE_NAME;

            echo $view_obj->render();
        }

        return true;
    }
}
