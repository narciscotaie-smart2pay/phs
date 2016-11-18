<?php

namespace phs\system\core\models;

use phs\libraries\PHS_Hooks;
use \phs\PHS;
use \phs\libraries\PHS_Model;
use \phs\libraries\PHS_line_params;
use \phs\libraries\PHS_logger;

class PHS_Model_Plugins extends PHS_Model
{
    const ERR_FORCE_INSTALL = 100, ERR_DB_DETAILS = 101, ERR_DIR_DETAILS = 102;

    const HOOK_STATUSES = 'phs_plugins_statuses';

    const STATUS_INSTALLED = 1, STATUS_ACTIVE = 2, STATUS_INACTIVE = 3;

    // Cached database rows
    private static $db_plugins = array();

    // Cached directory rows
    private static $dir_plugins = array();

    // Cached plugin settings
    private static $plugin_settings = array();

    protected static $STATUSES_ARR = array(
        self::STATUS_INSTALLED => array( 'title' => 'Installed' ),
        self::STATUS_ACTIVE => array( 'title' => 'Active' ),
        self::STATUS_INACTIVE => array( 'title' => 'Inactive' ),
    );

    function __construct( $instance_details )
    {
        parent::__construct( $instance_details );

        $this->_reset_db_plugin_cache();
        $this->_reset_plugin_settings_cache();
    }

    /**
     * @return string Returns version of model
     */
    public function get_model_version()
    {
        return '1.0.0';
    }

    /**
     * @return array of string Returns an array of strings containing tables that model will handle
     */
    public function get_table_names()
    {
        return array( 'plugins' );
    }

    /**
     * @return string Returns main table name used when calling insert with no table name
     */
    function get_main_table_name()
    {
        return 'plugins';
    }

    final public function get_statuses_as_key_val()
    {
        static $plugins_statuses_key_val_arr = false;

        if( $plugins_statuses_key_val_arr !== false )
            return $plugins_statuses_key_val_arr;

        $plugins_statuses_key_val_arr = array();
        if( ($plugins_statuses = $this->get_statuses()) )
        {
            foreach( $plugins_statuses as $key => $val )
            {
                if( !is_array( $val ) )
                    continue;

                $plugins_statuses_key_val_arr[$key] = $val['title'];
            }
        }

        return $plugins_statuses_key_val_arr;
    }

    final public function get_statuses()
    {
        static $statuses_arr = array();

        if( !empty( $statuses_arr ) )
            return $statuses_arr;

        $new_statuses_arr = self::$STATUSES_ARR;
        $hook_args = PHS_Hooks::default_common_hook_args();
        $hook_args['statuses_arr'] = self::$STATUSES_ARR;

        if( ($extra_statuses_arr = PHS::trigger_hooks( self::HOOK_STATUSES, $hook_args ))
        and is_array( $extra_statuses_arr ) and !empty( $extra_statuses_arr['statuses_arr'] ) )
            $new_statuses_arr = self::merge_array_assoc( $extra_statuses_arr['statuses_arr'], $new_statuses_arr );

        $statuses_arr = array();
        // Translate and validate statuses...
        if( !empty( $new_statuses_arr ) and is_array( $new_statuses_arr ) )
        {
            foreach( $new_statuses_arr as $status_id => $status_arr )
            {
                $status_id = intval( $status_id );
                if( empty( $status_id ) )
                    continue;

                if( empty( $status_arr['title'] ) )
                    $status_arr['title'] = self::_t( 'Status %s', $status_id );
                else
                    $status_arr['title'] = self::_t( $status_arr['title'] );

                $statuses_arr[$status_id] = array(
                    'title' => $status_arr['title']
                );
            }
        }

        return $statuses_arr;
    }

    public function active_status( $status )
    {
        if( !$this->valid_status( $status )
         or !in_array( $status, array( self::STATUS_ACTIVE ) ) )
            return false;

        return true;
    }

    public function inactive_status( $status )
    {
        if( !$this->valid_status( $status )
         or !in_array( $status, array( self::STATUS_INSTALLED, self::STATUS_INACTIVE ) ) )
            return false;

        return true;
    }

    public function valid_status( $status )
    {
        $all_statuses = $this->get_statuses();
        if( empty( $status )
         or empty( $all_statuses[$status] ) )
            return false;

        return $all_statuses[$status];
    }

    private function _reset_plugin_settings_cache()
    {
        self::$plugin_settings = array();
    }

    private function _reset_db_plugin_cache()
    {
        self::$db_plugins = array();
    }

    public function is_active( $plugin_data )
    {
        if( empty( $plugin_data )
         or !($plugin_arr = $this->data_to_array( $plugin_data ))
         or $plugin_arr['status'] != self::STATUS_ACTIVE )
            return false;

        return $plugin_arr;
    }

    public function is_inactive( $plugin_data )
    {
        if( empty( $plugin_data )
         or !($plugin_arr = $this->data_to_array( $plugin_data ))
         or !$this->inactive_status( $plugin_arr['status'] ) )
            return false;

        return $plugin_arr;
    }

    public function is_installed( $plugin_data )
    {
        if( empty( $plugin_data )
         or !($plugin_arr = $this->data_to_array( $plugin_data ))
         or $plugin_arr['status'] != self::STATUS_INSTALLED )
            return false;

        return $plugin_arr;
    }

    public function save_db_settings( $settings_arr, $instance_id = null )
    {
        $this->reset_error();

        if( $instance_id != null
        and !self::valid_instance_id( $instance_id ))
        {
            $this->set_error( self::ERR_INSTANCE, self::_t( 'Invalid instance ID.' ) );
            return false;
        }

        if( $instance_id == null
        and !($instance_id = $this->instance_id()) )
        {
            $this->set_error( self::ERR_INSTANCE, self::_t( 'Unknown instance ID.' ) );
            return false;
        }

        $plugin_details = array();
        $plugin_details['instance_id'] = $instance_id;
        $plugin_details['settings'] = $settings_arr;

        if( !($db_details = $this->update_db_details( $plugin_details ))
         or empty( $db_details['new_data'] ) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_DB_DETAILS, self::_t( 'Error saving settings in database.' ) );

            return false;
        }

        // clean caches...
        if( isset( self::$plugin_settings[$instance_id] ) )
            unset( self::$plugin_settings[$instance_id] );
        if( isset( self::$db_plugins[$instance_id] ) )
            unset( self::$db_plugins[$instance_id] );

        return $this->get_db_settings( $instance_id, false, true );
    }

    public function get_db_settings( $instance_id = null, $default_settings = false, $force = false )
    {
        $this->reset_error();

        if( $instance_id != null
        and !self::valid_instance_id( $instance_id ))
        {
            $this->set_error( self::ERR_INSTANCE, self::_t( 'Invalid instance ID.' ) );
            return false;
        }

        if( $instance_id == null
        and !($instance_id = $this->instance_id()) )
        {
            $this->set_error( self::ERR_INSTANCE, self::_t( 'Unknown instance ID.' ) );
            return false;
        }

        if( !empty( $force )
        and isset( self::$plugin_settings[$instance_id] ) )
            unset( self::$plugin_settings[$instance_id] );

        if( isset( self::$plugin_settings[$instance_id] ) )
            return self::$plugin_settings[$instance_id];

        if( !($db_details = $this->get_db_details( $instance_id, $force )) )
            return false;

        if( empty( $db_details['settings'] ) )
            self::$plugin_settings[$instance_id] = (!empty( $default_settings )?$default_settings:array());

        else
        {
            // parse settings in database...
            self::$plugin_settings[$instance_id] = PHS_line_params::parse_string( $db_details['settings'] );

            // Merge database settings with default script settings
            if( !empty( $default_settings ) )
                self::$plugin_settings[$instance_id] = self::validate_array_recursive( self::$plugin_settings[$instance_id], $default_settings );

            $hook_args = PHS_Hooks::default_common_hook_args();
            $hook_args['settings_arr'] = self::$plugin_settings[$instance_id];

            if( ($extra_settings_arr = PHS::trigger_hooks( PHS_Hooks::H_PLUGIN_SETTINGS, $hook_args ))
            and is_array( $extra_settings_arr ) and !empty( $extra_settings_arr['settings_arr'] ) )
                self::$plugin_settings[$instance_id] = self::validate_array_recursive( $extra_settings_arr['settings_arr'], self::$plugin_settings[$instance_id] );
        }

        return self::$plugin_settings[$instance_id];
    }

    public function cache_all_dir_details( $force = false )
    {
        $this->reset_error();

        if( !empty( $force )
        and !empty( self::$dir_plugins ) )
            self::$dir_plugins = array();

        if( !empty( self::$dir_plugins ) )
            return self::$dir_plugins;

        @clearstatcache();

        if( ($dirs_list = @glob( PHS_PLUGINS_DIR.'*', GLOB_ONLYDIR )) === false
         or !is_array( $dirs_list ) )
        {
            $this->set_error( self::ERR_DIR_DETAILS, self::_t( 'Couldn\'t get a list of plugin directories.' ) );
            return false;
        }

        /** @var \phs\libraries\PHS_Plugin $plugin_instance */
        foreach( $dirs_list as $dir_name )
        {
            $dir_name = basename( $dir_name );
            if( !($plugin_instance = PHS::load_plugin( $dir_name )) )
                continue;

            self::$dir_plugins[$dir_name] = $plugin_instance;
        }

        return self::$dir_plugins;
    }

    public function cache_all_db_details( $force = false )
    {
        $this->reset_error();

        if( !empty( $force )
        and !empty( self::$db_plugins ) )
            self::$db_plugins = array();

        if( !empty( self::$db_plugins ) )
            return self::$db_plugins;

        $list_arr = array();

        if( !($all_db_plugins = $this->get_list( $list_arr )) )
        {
            self::$db_plugins = array();
            return true;
        }

        foreach( $all_db_plugins as $db_id => $db_arr )
        {
            if( empty( $db_arr['instance_id'] ) )
                continue;

            self::$db_plugins[$db_arr['instance_id']] = $db_arr;
        }


        return true;
    }

    /**
     * @param string|null $instance_id Instance ID to check in database
     * @param bool $force True if we should skip caching
     *
     * @return array|bool|false Array containing database fields of given instance_id (if available)
     */
    public function get_db_details( $instance_id = null, $force = false )
    {
        $this->reset_error();

        if( $instance_id != null
        and !self::valid_instance_id( $instance_id ))
        {
            $this->set_error( self::ERR_INSTANCE, self::_t( 'Invalid instance ID.' ) );
            return false;
        }

        if( $instance_id == null
        and !($instance_id = $this->instance_id()) )
        {
            $this->set_error( self::ERR_INSTANCE, self::_t( 'Unknown instance ID.' ) );
            return false;
        }

        if( !empty( $force )
        and !empty( self::$db_plugins[$instance_id] ) )
            unset( self::$db_plugins[$instance_id] );

        // Cache all plugin details at once instead of caching one at a time...
        $this->cache_all_db_details( $force );

        if( !empty( self::$db_plugins[$instance_id] ) )
            return self::$db_plugins[$instance_id];

        $check_arr = array();
        $check_arr['instance_id'] = $instance_id;

        db_supress_errors( $this->get_db_connection() );
        if( !($db_details = $this->get_details_fields( $check_arr )) )
        {
            db_restore_errors_state( $this->get_db_connection() );

            if( !$this->has_error() )
                $this->set_error( self::ERR_DB_DETAILS, self::_t( 'Couldn\'t find plugin settings in database. Try re-installing plugin.' ) );

            return false;
        }

        db_restore_errors_state( $this->get_db_connection() );

        self::$db_plugins[$instance_id] = $db_details;

        return $db_details;
    }

    public function update_db_details( $fields_arr )
    {
        if( empty( $fields_arr ) or !is_array( $fields_arr )
         or empty( $fields_arr['instance_id'] )
         or !($instance_details = self::valid_instance_id( $fields_arr['instance_id'] ))
         or !($params = $this->fetch_default_flow_params()) )
        {
            $this->set_error( self::ERR_PARAMETERS, self::_t( 'Unknown instance database details.' ) );
            return false;
        }

        $check_arr = array();
        $check_arr['instance_id'] = $fields_arr['instance_id'];

        $check_params = array();
        $check_params['result_type'] = 'single';
        $check_params['details'] = '*';

        $params['fields'] = $fields_arr;

        if( !($existing_arr = $this->get_details_fields( $check_arr, $check_params )) )
        {
            $existing_arr = false;
            $params['action'] = 'insert';
        } else
        {
            $params['action'] = 'edit';
        }

        PHS_Logger::logf( 'Plugins model action ['.$params['action'].'] on plugin ['.$fields_arr['instance_id'].']', PHS_Logger::TYPE_INFO );

        if( !($validate_fields = $this->validate_data_for_fields( $params ))
         or empty( $validate_fields['data_arr'] ) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_DB_DETAILS, self::_t( 'Error validating plugin database fields.' ) );
            return false;
        }

        $new_fields_arr = $validate_fields['data_arr'];
        // Try updating settings...
        if( !empty( $new_fields_arr['settings'] ) )
        {
            if( !empty( $existing_arr ) and !empty( $existing_arr['settings'] ) )
                $new_fields_arr['settings'] = self::merge_array_assoc( PHS_line_params::parse_string( $existing_arr['settings'] ), PHS_line_params::parse_string( $new_fields_arr['settings'] ) );

            $new_fields_arr['settings'] = PHS_line_params::to_string( $new_fields_arr['settings'] );

            PHS_Logger::logf( 'New settings ['.$new_fields_arr['settings'].']', PHS_Logger::TYPE_INFO );
        }

        // Prevent core plugins to be inactivated...
        if( !empty( $new_fields_arr['is_core'] ) and !empty( $new_fields_arr['status'] ) )
            $new_fields_arr['status'] = self::STATUS_ACTIVE;

        $details_arr = array();
        $details_arr['fields'] = $new_fields_arr;

        if( empty( $existing_arr ) )
            $plugin_arr = $this->insert( $details_arr );
        else
            $plugin_arr = $this->edit( $existing_arr, $details_arr );

        if( empty( $plugin_arr ) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_DB_DETAILS, self::_t( 'Couldn\'t save plugin details to database.' ) );

            PHS_Logger::logf( '!!! Error in plugins model action ['.$params['action'].'] on plugin ['.$fields_arr['instance_id'].'] ['.$this->get_error_message().']', PHS_Logger::TYPE_INFO );

            return false;
        }

        PHS_Logger::logf( 'DONE Plugins model action ['.$params['action'].'] on plugin ['.$fields_arr['instance_id'].']', PHS_Logger::TYPE_INFO );

        $return_arr = array();
        $return_arr['old_data'] = $existing_arr;
        $return_arr['new_data'] = $plugin_arr;

        return $return_arr;
    }

    /**
     * @inheritdoc
     */
    protected function get_insert_prepare_params( $params )
    {
        if( empty( $params ) or !is_array( $params ) )
            return false;

        if( empty( $params['fields']['status'] ) )
            $params['fields']['status'] = self::STATUS_INSTALLED;

        if( !$this->valid_status( $params['fields']['status'] ) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a valid plugin status.' ) );
            return false;
        }

        if( empty( $params['fields']['instance_id'] ) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a plugin id.' ) );
            return false;
        }

        $check_params = $params;
        $check_params['result_type'] = 'single';

        $check_arr = array();
        $check_arr['instance_id'] = $params['fields']['instance_id'];

        if( $this->get_details_fields( $check_arr, $check_params ) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'There is already a plugin with this id in database.' ) );
            return false;
        }

        $now_date = date( self::DATETIME_DB );

        $params['fields']['status_date'] = $now_date;

        if( empty( $params['fields']['cdate'] ) or empty_db_date( $params['fields']['cdate'] ) )
            $params['fields']['cdate'] = $now_date;
        else
            $params['fields']['cdate'] = date( self::DATETIME_DB, parse_db_date( $params['fields']['cdate'] ) );

        return $params;
    }

    /**
     * @inheritdoc
     */
    protected function get_edit_prepare_params( $existing_arr, $params )
    {
        if( empty( $existing_arr ) or !is_array( $existing_arr )
         or empty( $params ) or !is_array( $params ) )
            return false;

        if( isset( $params['fields']['status'] )
        and !$this->valid_status( $params['fields']['status'] ) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a valid plugin status.' ) );
            return false;
        }

        if( !empty( $params['fields']['instance_id'] ) )
        {
            $check_params = $params;
            $check_params['result_type'] = 'single';

            $check_arr = array();
            $check_arr['instance_id'] = $params['fields']['instance_id'];
            $check_arr['id'] = array( 'check' => '!=', 'value' => $existing_arr['id'] );

            if( $this->get_details_fields( $check_arr, $check_params ) )
            {
                $this->set_error( self::ERR_INSERT, self::_t( 'There is already a plugin with this id in database.' ) );
                return false;
            }
        }

        $now_date = date( self::DATETIME_DB );

        if( isset( $params['fields']['status'] )
        and empty( $params['fields']['status_date'] ) )
            $params['fields']['status_date'] = $now_date;

        elseif( !empty( $params['fields']['status_date'] ) )
            $params['fields']['status_date'] = date( self::DATETIME_DB, parse_db_date( $params['fields']['status_date'] ) );

        if( !empty( $params['fields']['cdate'] ) )
            $params['fields']['cdate'] = date( self::DATETIME_DB, parse_db_date( $params['fields']['cdate'] ) );

        return $params;
    }

    /**
     * Called right after finding a record in database in PHS_Model_Core_Base::insert_or_edit() with provided conditions. This helps unsetting some fields which should not
     * be passed to edit function in case we execute an edit.
     *
     * @param array $existing_arr Data which already exists in database (array with all database fields)
     * @param array $constrain_arr Conditional db fields
     * @param array $params Flow parameters
     *
     * @return array Returns modified parameters (if required)
     */
    protected function insert_or_edit_editing( $existing_arr, $constrain_arr, $params )
    {
        if( empty( $existing_arr ) or !is_array( $existing_arr )
         or empty( $params ) or !is_array( $params ) )
            return false;

        if( isset( $params['fields']['added_by'] ) )
            unset( $params['fields']['added_by'] );
        if( isset( $params['fields']['status'] ) )
            unset( $params['fields']['status'] );
        if( isset( $params['fields']['status_date'] ) )
            unset( $params['fields']['status_date'] );
        if( isset( $params['fields']['cdate'] ) )
            unset( $params['fields']['cdate'] );

        return $params;
    }

    final public function check_install_plugins_db()
    {
        static $check_result = null;

        if( $check_result !== null )
            return $check_result;

        if( $this->check_table_exists() )
        {
            $check_result = true;
            return true;
        }

        $this->reset_error();

        $check_result = $this->install();

        return $check_result;
    }

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
            case 'plugins':
                $return_arr = array(
                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'instance_id' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '255',
                        'nullable' => true,
                        'editable' => false,
                        'index' => true,
                    ),
                    'type' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '100',
                        'nullable' => true,
                        'editable' => false,
                        'index' => true,
                    ),
                    'plugin' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '100',
                        'nullable' => true,
                        'editable' => false,
                        'index' => true,
                    ),
                    'added_by' => array(
                        'type' => self::FTYPE_INT,
                        'editable' => false,
                    ),
                    'is_core' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                        'editable' => false,
                        'index' => true,
                    ),
                    'settings' => array(
                        'type' => self::FTYPE_LONGTEXT,
                        'nullable' => true,
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
                    'version' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '30',
                        'nullable' => true,
                    ),
                    'cdate' => array(
                        'type' => self::FTYPE_DATETIME,
                        'editable' => false,
                    ),
                );
            break;
        }

        return $return_arr;
    }

    public function force_install()
    {
        $this->install();

        if( !($signal_result = $this->signal_trigger( self::SIGNAL_FORCE_INSTALL )) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_INSTALL, self::_t( 'Error when triggering force install signal.' ) );

            return false;
        }

        if( !empty( $signal_result['error_arr'] ) and is_array( $signal_result['error_arr'] ) )
        {
            $this->copy_error_from_array( $signal_result['error_arr'], self::ERR_FORCE_INSTALL );
            return false;
        }

        return true;
    }

}
