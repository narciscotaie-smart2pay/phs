<?php

namespace phs\cli\apps;

include_once( PHS_CORE_DIR.'phs_cli_plugins_trait.php' );

use \phs\PHS;
use \phs\PHS_cli;
use phs\libraries\PHS_utils;
use \phs\traits\PHS_cli_plugins_trait;
use \phs\libraries\PHS_Plugin;

class PHSMaintenance extends PHS_cli
{
    use PHS_cli_plugins_trait;

    const APP_NAME = 'PHSMaintenance',
          APP_VERSION = '1.0.0',
          APP_DESCRIPTION = 'Manage framework functionality and plugins.';

    public function get_app_dir()
    {
        return __DIR__.'/';
    }

    protected function _get_app_options_definition()
    {
        return [];
    }

    protected function _get_app_commands_definition()
    {
        return array(
            'web_update' => array(
                'description' => 'Provide a link .',
                'callback' => array( $this, 'cmd_web_update' ),
            ),
            'update' => array(
                'description' => 'Check plugins database version against script version and update if required.',
                'callback' => array( $this, 'cmd_update' ),
            ),
            'plugins' => array(
                'description' => 'List available plugins',
                'callback' => array( $this, 'cmd_list_plugins' ),
            ),
            'plugin' => array(
                'description' => 'Plugin management plugin [name] [action]. If no action is provided, display plugin details.',
                'callback' => array( $this, 'cmd_plugin_action' ),
            ),
        );
    }

    private static function _get_plugin_command_actions()
    {
        return array( 'info', 'activate', 'inactivate' );
    }

    //
    //region Environment initialization
    //
    protected function _init_app()
    {
        $this->reset_error();

        return true;
    }
    //
    //endregion Environment initialization
    //

    public function cmd_plugin_action()
    {
        $this->reset_error();

        if( false === ($plugins_dirs_arr = $this->get_plugins_as_dirs()) )
        {
            $this->_echo_error( self::_t( 'Couldn\'t obtaining plugins list: %s', $this->get_simple_error_message() ) );
            return false;
        }

        if( empty( $plugins_dirs_arr ) )
        {
            $this->_echo( self::_t( 'No plugin installed in plugins directory yet.' ) );
            return false;
        }

        if( !($command_arr = $this->get_app_command())
         or empty( $command_arr['arguments'] )
         or !($plugin_name = $this->_get_argument_chained( $command_arr['arguments'] ))
         or !in_array( $plugin_name, $plugins_dirs_arr, true ) )
        {
            $this->_echo_error( self::_t( 'Please provide a valid plugin name. Use %s command to view all plugins.', $this->cli_color( 'plugins', 'green' ) ) );

            $this->_echo( 'Usage: '.$this->get_app_cli_script().' [options] plugin [plugin_action]' );
            return false;
        }

        if( !($plugin_action = $this->_get_argument_chained()) )
            $plugin_action = '';

        if( empty( $plugin_action ) )
        {
            return $this->_echo_plugin_details( $plugin_name );
        }

        if( !in_array( $plugin_action, self::_get_plugin_command_actions(), true ) )
        {
            $this->_echo_error( self::_t( 'Invalid action.' ) );

            $this->_echo( 'Usage: '.$this->get_app_cli_script().' [options] plugin [plugin_action]' );
            $this->_echo( 'Available actions: '.implode( ', ', self::_get_plugin_command_actions() ).'.' );
            return false;
        }

        switch( $plugin_action )
        {
            case 'activate':
                if( !($result_arr = $this->_activate_plugin( $plugin_name )) )
                {
                    if( $this->has_error() )
                        $this->_echo( $this->cli_color( self::_t( 'ERROR' ), 'red' ).': '.
                                      $this->get_simple_error_message() );

                    return false;
                }

                $this->_echo( self::_t( 'Plugin %s %s with success.',
                                        $this->cli_color( $plugin_name, 'white' ),
                                        $this->cli_color( self::_t( 'ACTIVATED' ), 'green' ) ) );
            break;

            case 'inactivate':
                if( !($result_arr = $this->_inactivate_plugin( $plugin_name )) )
                {
                    if( $this->has_error() )
                        $this->_echo( $this->cli_color( self::_t( 'ERROR' ), 'red' ).': '.
                                      $this->get_simple_error_message() );

                    return false;
                }

                $this->_echo( self::_t( 'Plugin %s %s with success.',
                                        $this->cli_color( $plugin_name, 'white' ),
                                        $this->cli_color( self::_t( 'INACTIVATED' ), 'red' ) ) );
            break;
        }

        return true;
    }

    private function _activate_plugin( $plugin_name )
    {
        if( !($plugin_obj = PHS::load_plugin( $plugin_name )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error instantiating plugin.' ) );
            return false;
        }

        if( !$plugin_obj->activate_plugin() )
        {
            $error_msg = self::_t( 'Error inactivating plugin' );
            if( $plugin_obj->has_error() )
                $error_msg .= ': '.$plugin_obj->get_simple_error_message();

            $this->set_error( self::ERR_FUNCTIONALITY, $error_msg );
            return false;
        }

        return true;
    }

    private function _inactivate_plugin( $plugin_name )
    {
        if( !($plugin_obj = PHS::load_plugin( $plugin_name )) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error instantiating plugin.' ) );
            return false;
        }

        if( !$plugin_obj->inactivate_plugin() )
        {
            $error_msg = self::_t( 'Error inactivating plugin' );
            if( $plugin_obj->has_error() )
                $error_msg .= ': '.$plugin_obj->get_simple_error_message();

            $this->set_error( self::ERR_FUNCTIONALITY, $error_msg );
            return false;
        }

        return true;
    }

    public function cmd_web_update()
    {
        $this->reset_error();

        echo self::_t( 'Update URL vailable for %s.', $this->cli_color( PHS_utils::parse_period( PHS::UPDATE_TOKEN_LIFETIME ), 'green' ) )."\n";
        echo self::_t( 'NOTE: Provided URL is forced to use HTTPS, if you don\'t have HTTPS enabled, change the link to use HTTP protocol.' )."\n";
        echo "\n";
        echo PHS::get_framework_update_url_with_token()."\n";

        return true;
    }

    public function cmd_update()
    {
        $this->reset_error();

        echo $this->cli_color( 'IN DEVELOPMENT...', 'green' )."\n";
        echo 'Use '.$this->cli_color( $this->get_app_cli_script().' '.'web_update', 'green' ).' option meanwhile to update the framework.'."\n";

        return true;
    }

    public function cmd_list_plugins()
    {
        $this->reset_error();

        if( false === ($plugins_dirs_arr = $this->get_plugins_as_dirs()) )
        {
            $this->_echo( self::_t( 'Couldn\'t obtaining plugins list: %s', $this->get_simple_error_message() ) );
            return false;
        }

        if( empty( $plugins_dirs_arr ) )
        {
            $this->_echo( self::_t( 'No plugin installed in plugin directory yet.' ) );
            return false;
        }

        $this->_echo( self::_t( 'Found %s plugin directories...', count( $plugins_dirs_arr ) ) );
        foreach( $plugins_dirs_arr as $plugin_name )
        {
            if( !($plugin_info = $this->_gather_plugin_info( $plugin_name )) )
                $plugin_info = self::_get_default_plugin_info_definition();

            $extra_info = '';
            if( !empty( $plugin_info ) )
            {
                if( !empty( $plugin_info['is_installed'] ) )
                    $extra_info .= '['.$this->cli_color( self::_t( 'Installed' ), 'green' ).']';
                else
                    $extra_info .= '['.$this->cli_color( self::_t( 'NOT INSTALLED' ), 'red' ).']';

                if( !empty( $plugin_info['is_installed'] ) )
                {
                    $extra_info .= (!empty( $extra_info )?' ':'');
                    if( !empty( $plugin_info['is_active'] ) )
                        $extra_info .= '['.$this->cli_color( self::_t( 'Active' ), 'green' ).']';
                    else
                        $extra_info .= '['.$this->cli_color( self::_t( 'NOT ACTIVE' ), 'red' ).']';

                    $extra_info .= ' ';
                }

                if( !empty( $plugin_info['name'] ) )
                    $extra_info .= $plugin_info['name'];
                if( !empty( $plugin_info['version'] ) )
                    $extra_info .= ($extra_info!==''?' ':'').'(v'.$plugin_info['version'].')';
                if( !empty( $plugin_info['models_count'] ) )
                    $extra_info .= ($extra_info!==''?', ':'').$plugin_info['models_count'].' models';
            }

            $this->_echo( ' - '.$this->cli_color( $plugin_name, 'blue' ).($extra_info!==''?': ':'').$extra_info );
        }

        $this->_echo( 'DONE' );

        return true;
    }

}
