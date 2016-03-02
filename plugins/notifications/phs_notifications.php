<?php

namespace phs\plugins\notifications;

use phs\libraries\PHS_Hooks;
use \phs\PHS;
use \phs\libraries\PHS_Plugin;
use \phs\libraries\PHS_Notifications;
use \phs\system\core\views\PHS_View;

class PHS_Plugin_Notifications extends PHS_Plugin
{
    const ERR_TEMPLATE = 40000, ERR_RENDER = 40001;

    /**
     * @return string Returns version of model
     */
    public function get_plugin_version()
    {
        return '1.0.0';
    }

    public function get_models()
    {
        return array();
    }

    /**
     * Override this function and return an array with default settings to be saved for current plugin
     * @return array
     */
    public function get_default_settings()
    {
        return array(
            'template' => array(
                'file' => 'notifications',
                'extra_paths' => array(
                    PHS::relative_path( $this->instance_plugin_templates_path() ) => PHS::relative_url( $this->instance_plugin_templates_www() ),
                ),
            ), // default template
            'display_channels' => array( 'warnings', 'errors', 'success' ),
        );
    }

    public function get_notifications_hook_args( $hook_args )
    {
        $this->reset_error();

        if( !($settings_arr = $this->get_plugin_db_settings())
         or empty( $settings_arr['template'] ) )
        {
            $this->set_error( self::ERR_TEMPLATE, self::_t( 'Couldn\'t load template from plugin settings.' ) );
            return false;
        }

        $extra_paths = array();
        if( !empty( $settings_arr['template']['extra_paths'] ) and is_array( $settings_arr['template']['extra_paths'] ) )
        {
            foreach( $settings_arr['template']['extra_paths'] as $dir_path => $dir_www )
            {
                $extra_paths[PHS::from_relative_path( $dir_path )] = PHS::from_relative_url( $dir_www );
            }
        }

        $settings_arr['template']['extra_paths'] = $extra_paths;

        $notifications_arr = PHS_Notifications::get_all_notifications();

        $hook_args = self::validate_array_recursive( $hook_args, PHS_Hooks::default_notifications_hook_args() );

        $hook_args['warnings'] = $notifications_arr['warnings'];
        $hook_args['errors'] = $notifications_arr['errors'];
        $hook_args['success'] = $notifications_arr['success'];

        $hook_args['display_channels'] = $settings_arr['display_channels'];
        $hook_args['template'] = $settings_arr['template'];

        $view_params = array();
        $view_params['action_obj'] = false;
        $view_params['controller_obj'] = false;
        $view_params['plugin'] = $this->instance_plugin_name();
        $view_params['template_data'] = array(
            'notifications' => $notifications_arr,
            'display_channels' => $hook_args['display_channels']
        );

        if( !($view_obj = PHS_View::init_view( $settings_arr['template'], $view_params )) )
        {
            if( self::st_has_error() )
                $this->copy_static_error();

            return false;
        }

        if( !($hook_args['notifications_buffer'] = $view_obj->render()) )
        {
            if( $view_obj->has_error() )
                $this->copy_error( $view_obj );
            else
                $this->set_error( self::ERR_RENDER, self::_t( 'Error rendering template [%s].', $view_obj->get_template() ) );

            return false;
        }

        return $hook_args;
    }
}
