<?php

namespace phs\plugins\backup\models;

use \phs\PHS;
use \phs\libraries\PHS_Roles;
use \phs\libraries\PHS_Model;

class PHS_Model_Rules extends PHS_Model
{
    const STATUS_ACTIVE = 1, STATUS_INACTIVE = 2, STATUS_DELETED = 3;
    protected static $STATUSES_ARR = array(
        self::STATUS_ACTIVE => array( 'title' => 'Active' ),
        self::STATUS_INACTIVE => array( 'title' => 'Inactive' ),
        self::STATUS_DELETED => array( 'title' => 'Deleted' ),
    );

    const BACKUP_TARGET_DATABASE = 1, BACKUP_TARGET_UPLOADS = 2;
    protected static $BACKUP_TARGETS_ARR = array(
        self::BACKUP_TARGET_DATABASE => array( 'title' => 'Database' ),
        self::BACKUP_TARGET_UPLOADS => array( 'title' => 'Uploaded files' ),
    );

    const BACKUP_TARGET_ALL = ((1 << self::BACKUP_TARGET_DATABASE)|(1 << self::BACKUP_TARGET_UPLOADS));

    /**
     * @return string Returns version of model
     */
    public function get_model_version()
    {
        return '1.0.1';
    }

    /**
     * @return array of string Returns an array of strings containing tables that model will handle
     */
    public function get_table_names()
    {
        return array( 'backup_rules', 'backup_rules_days' );
    }

    /**
     * @return string Returns main table name used when calling insert with no table name
     */
    function get_main_table_name()
    {
        return 'backup_rules';
    }

    final public function get_statuses( $lang = false )
    {
        static $statuses_arr = array();

        if( $lang === false
        and !empty( $statuses_arr ) )
            return $statuses_arr;

        $result_arr = $this->translate_array_keys( self::$STATUSES_ARR, array( 'title' ), $lang );

        if( $lang === false )
            $statuses_arr = $result_arr;

        return $result_arr;
    }

    final public function get_statuses_as_key_val( $lang = false )
    {
        static $statuses_key_val_arr = false;

        if( $lang === false
        and $statuses_key_val_arr !== false )
            return $statuses_key_val_arr;

        $key_val_arr = array();
        if( ($statuses = $this->get_statuses( $lang )) )
        {
            foreach( $statuses as $key => $val )
            {
                if( !is_array( $val ) )
                    continue;

                $key_val_arr[$key] = $val['title'];
            }
        }

        if( $lang === false )
            $statuses_key_val_arr = $key_val_arr;

        return $key_val_arr;
    }

    public function valid_status( $status, $lang = false )
    {
        $all_statuses = $this->get_statuses( $lang );
        if( empty( $status )
         or !isset( $all_statuses[$status] ) )
            return false;

        return $all_statuses[$status];
    }

    final public function get_targets( $lang = false )
    {
        static $targets_arr = array();

        if( $lang === false
        and !empty( $targets_arr ) )
            return $targets_arr;

        $result_arr = $this->translate_array_keys( self::$BACKUP_TARGETS_ARR, array( 'title' ), $lang );

        if( $lang === false )
            $targets_arr = $result_arr;

        return $result_arr;
    }

    final public function get_targets_as_key_val( $lang = false )
    {
        static $targets_key_val_arr = false;

        if( $lang === false
        and $targets_key_val_arr !== false )
            return $targets_key_val_arr;

        $key_val_arr = array();
        if( ($targets = $this->get_statuses( $lang )) )
        {
            foreach( $targets as $key => $val )
            {
                if( !is_array( $val ) )
                    continue;

                $key_val_arr[$key] = $val['title'];
            }
        }

        if( $lang === false )
            $targets_key_val_arr = $key_val_arr;

        return $key_val_arr;
    }

    public function valid_target( $target, $lang = false )
    {
        $all_targets = $this->get_targets( $lang );
        if( empty( $target )
         or !isset( $all_targets[$target] ) )
            return false;

        return $all_targets[$target];
    }

    public function is_active( $record_data )
    {
        if( !($record_arr = $this->data_to_array( $record_data ))
         or $record_arr['status'] != self::STATUS_ACTIVE )
            return false;

        return $record_arr;
    }

    public function is_inactive( $record_data )
    {
        if( !($record_arr = $this->data_to_array( $record_data ))
         or $record_arr['status'] != self::STATUS_INACTIVE )
            return false;

        return $record_arr;
    }

    public function is_deleted( $record_data )
    {
        if( !($record_arr = $this->data_to_array( $record_data ))
         or $record_arr['status'] != self::STATUS_DELETED )
            return false;

        return $record_arr;
    }

    public function act_activate( $record_data, $params = false )
    {
        if( empty( $record_data )
         or !($record_arr = $this->data_to_array( $record_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Backup rule details not found in database.' ) );
            return false;
        }

        if( $this->is_active( $record_arr ) )
            return $record_arr;

        $edit_arr = array();
        $edit_arr['status'] = self::STATUS_ACTIVE;

        $edit_params = array();
        $edit_params['fields'] = $edit_arr;

        return $this->edit( $record_arr, $edit_params );
    }

    public function act_inactivate( $record_data, $params = false )
    {
        if( empty( $record_data )
         or !($record_arr = $this->data_to_array( $record_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Backup rule details not found in database.' ) );
            return false;
        }

        if( $this->is_inactive( $record_arr ) )
            return $record_arr;

        $edit_arr = array();
        $edit_arr['status'] = self::STATUS_INACTIVE;

        $edit_params = array();
        $edit_params['fields'] = $edit_arr;

        return $this->edit( $record_arr, $edit_params );
    }

    public function act_delete( $record_data, $params = false )
    {
        $this->reset_error();

        if( empty( $record_data )
         or !($record_arr = $this->data_to_array( $record_data )) )
        {
            $this->set_error( self::ERR_DELETE, $this->_pt( 'Backup rule details not found in database.' ) );
            return false;
        }

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        $edit_arr = array();
        $edit_arr['status'] = self::STATUS_DELETED;

        $edit_params_arr = array();
        $edit_params_arr['fields'] = $edit_arr;

        return $this->edit( $record_arr, $edit_params_arr );
    }

    public function can_user_edit( $record_data, $account_data )
    {
        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        /** @var \phs\plugins\backup\PHS_Plugin_Backup $plugin_obj */
        if( empty( $record_data ) or empty( $account_data )
         or !($rule_arr = $this->data_to_array( $record_data ))
         or $this->is_deleted( $rule_arr )
         or !($plugin_obj = PHS::load_plugin( 'backup' ))
         or !($accounts_model = PHS::load_model( 'accounts', 'accounts' ))
         or !($account_arr = $accounts_model->data_to_array( $account_data ))
         or !PHS_Roles::user_has_role_units( $account_arr, $plugin_obj::ROLEU_MANAGE_RULES ) )
            return false;

        $return_arr = array();
        $return_arr['rule_data'] = $rule_arr;
        $return_arr['account_data'] = $account_arr;

        return $return_arr;
    }

    public function targets_arr_to_bits( $target_arr )
    {
        if( empty( $target_arr ) or !is_array( $target_arr ) )
            return 0;

        $target_bits = 0;
        foreach( $target_arr as $target )
        {
            $target = intval( $target );
            if( !self::valid_target( $target ) )
                continue;

            $target_bits |= (1 << $target);
        }

        return $target_bits;
    }

    public function get_rule_days_as_array( $rule_id )
    {
        $this->reset_error();

        $rule_id = intval( $rule_id );
        if( empty( $rule_id )
         or !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'backup_rules_days' ) ))
         or !($qid = db_query( 'SELECT * FROM `'.$this->get_flow_table_name( $flow_params ).'` WHERE rule_id = \''.$rule_id.'\'', $this->get_db_connection( $flow_params ) ))
         or !@mysqli_num_rows( $qid ) )
            return array();

        $return_arr = array();
        while( ($link_arr = @mysqli_fetch_assoc( $qid )) )
        {
            $return_arr[] = $link_arr['day'];
        }

        return $return_arr;
    }

    public function unlink_all_days_for_rule( $rule_data )
    {
        $this->reset_error();

        if( !($rule_arr = $this->data_to_array( $rule_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, self::_t( 'Backup rule not found in database.' ) );
            return false;
        }

        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'backup_rules_days' ) ))
         or !db_query( 'DELETE FROM `'.$this->get_flow_table_name( $flow_params ).'` WHERE rule_id = \''.$rule_arr['id'].'\'', $this->get_db_connection( $flow_params ) ) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Couldn\'t unlink days for backup rule.' ) );
            return false;
        }

        return true;
    }

    public function link_days_to_rule( $rule_data, $days_arr, $params = false )
    {
        $this->reset_error();

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !is_array( $days_arr ) )
        {
            $this->set_error( self::ERR_PARAMETERS, self::_t( 'No days provided to link to backup rule.' ) );
            return false;
        }

        if( !($rule_arr = $this->data_to_array( $rule_data )) )
        {
            $this->set_error( self::ERR_PARAMETERS, self::_t( 'Backup rule not found in database.' ) );
            return false;
        }

        if( !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'backup_rules_days' ) ))
         or !($rules_days_table_name = $this->get_flow_table_name( $flow_params )) )
        {
            $this->set_error( self::ERR_PARAMETERS, self::_t( 'Invalid flow parameters.' ) );
            return false;
        }

        $db_connection = $this->get_db_connection( $flow_params );

        $return_arr = array();
        if( empty( $days_arr ) )
        {
            // Unlink all roles...
            if( !db_query( 'DELETE FROM `'.$rules_days_table_name.'` WHERE rule_id = \''.$rule_arr['id'].'\'', $db_connection ) )
            {
                $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error un-linking days for backup rule.' ) );
                return false;
            }

            return $return_arr;
        } else
        {
            if( !($existing_days = $this->get_rule_days_as_list( $rule_arr['id'] )) )
                $existing_days = array();

            $days_current_list = array();
            $insert_days = array();
            $delete_days = array();
            $delete_ids = array();
            $day_zero_record = false;
            $should_delete_rest_but_zero = false;
            foreach( $existing_days as $day_id => $day_arr )
            {
                if( empty( $day_arr ) or !is_array( $day_arr )
                 or !isset( $day_arr['day'] ) )
                    continue;

                $days_current_list[] = $day_arr['day'];

                if( empty( $day_arr['day'] ) )
                    $day_zero_record = $day_arr;
                else
                    $should_delete_rest_but_zero = true;

                if( in_array( $day_arr['day'], $days_arr ) )
                    $return_arr[$day_id] = $day_arr;

                else
                {
                    $delete_ids[] = $day_arr['id'];
                    $delete_days[] = $day_arr['day'];
                }
            }

            $day_zero_to_be_added = false;
            foreach( $days_arr as $day_no )
            {
                if( empty( $day_no ) )
                {
                    $day_zero_to_be_added = true;
                    break;
                }

                if( !in_array( $day_no, $delete_days )
                and !in_array( $day_no, $days_current_list ) )
                    $insert_days[] = $day_no;
            }

            if( $day_zero_to_be_added )
            {
                if( $should_delete_rest_but_zero
                and !db_query( 'DELETE FROM `'.$rules_days_table_name.'` WHERE rule_id = \''.$rule_arr['id'].'\' AND day != 0', $db_connection ) )
                {
                    $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error un-linking days for backup rule.' ) );
                    return false;
                }

                $day_insert_arr = $flow_params;
                $day_insert_arr['fields']['rule_id'] = $rule_arr['id'];
                $day_insert_arr['fields']['day'] = 0;

                if( empty( $day_zero_record )
                and !($day_zero_record = $this->insert( $day_insert_arr )) )
                {
                    if( !$this->has_error() )
                        $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error linking days to backup rule.' ) );
                    return false;
                }

                return array( $day_zero_record['id'] => $day_zero_record );
            }

            if( !empty( $insert_days ) )
            {
                $day_insert_arr = $flow_params;
                $day_insert_arr['fields']['rule_id'] = $rule_arr['id'];

                foreach( $insert_days as $day_no )
                {
                    $day_insert_arr['fields']['day'] = $day_no;

                    if( !($day_record = $this->insert( $day_insert_arr )) )
                    {
                        if( !$this->has_error() )
                            $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error linking days to backup rule.' ) );
                        return false;
                    }

                    $return_arr[$day_record['id']] = $day_record;
                }
            }

            if( !empty( $delete_ids )
            and !db_query( 'DELETE FROM `'.$rules_days_table_name.'` WHERE rule_id = \''.$rule_arr['id'].'\' AND id IN ('.implode( ',', $delete_ids ).')', $db_connection ) )
            {
                $this->set_error( self::ERR_FUNCTIONALITY, self::_t( 'Error un-linking days for backup rule.' ) );
                return false;
            }
        }

        return $return_arr;
    }

    public function get_rule_days_as_list( $rule_id )
    {
        $this->reset_error();

        $rule_id = intval( $rule_id );
        if( empty( $rule_id )
         or !($flow_params = $this->fetch_default_flow_params( array( 'table_name' => 'backup_rules_days' ) ))
         or !($qid = db_query( 'SELECT * FROM `'.$this->get_flow_table_name( $flow_params ).'` WHERE rule_id = \''.$rule_id.'\'', $this->get_db_connection( $flow_params ) ))
         or !@mysqli_num_rows( $qid ) )
            return array();

        $return_arr = array();
        while( ($link_arr = @mysqli_fetch_assoc( $qid )) )
        {
            $return_arr[$link_arr['id']] = $link_arr;
        }

        return $return_arr;
    }

    /**
     * @inheritdoc
     */
    protected function get_insert_prepare_params_backup_rules( $params )
    {
        if( empty( $params ) or !is_array( $params ) )
            return false;

        if( empty( $params['fields']['title'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide a rule title.' ) );
            return false;
        }

        if( empty( $params['fields']['location'] ) )
            $params['fields']['location'] = '';

        else
        {
            $params['fields']['location'] = rtrim( $params['fields']['location'], '/\\' );
            $location_check = $params['fields']['location'];
            if( substr( $params['fields']['location'], 0, 1 ) != '/'
            and substr( $params['fields']['location'], 1, 2 ) != ':\\' )
                $location_check = PHS_PATH.$params['fields']['location'];

            // we have an absolute path
            if( !@file_exists( $location_check )
             or !@is_dir( $location_check ) )
            {
                $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Provided backup location is not a directory.' ) );
                return false;
            }

            if( !@is_writable( $location_check ) )
            {
                $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Provided backup location is not writable.' ) );
                return false;
            }
        }

        $cdate = date( self::DATETIME_DB );

        if( empty( $params['fields']['uid'] ) )
            $params['fields']['uid'] = 0;

        if( empty( $params['fields']['hour'] ) )
            $params['fields']['hour'] = 0;

        else
        {
            $params['fields']['hour'] = intval( $params['fields']['hour'] );
            if( $params['fields']['hour'] < 0 or $params['fields']['hour'] > 23 )
            {
                $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Please provide a valid backup hour.' ) );
                return false;
            }
        }

        if( empty( $params['fields']['target'] ) )
            $params['fields']['target'] = self::BACKUP_TARGET_ALL;

        elseif( is_array( $params['fields']['target'] ) )
        {
            if( !($target_bits = $this->targets_arr_to_bits( $params['fields']['target'] )) )
                $target_bits = self::BACKUP_TARGET_ALL;

            $params['fields']['target'] = $target_bits;
        } else
        {
            $params['fields']['target'] = intval( $params['fields']['target'] );
            if( ($targets_arr = $this->get_targets_as_key_val()) )
            {
                $target_bits = 0;
                foreach( $targets_arr as $target_key => $target_name )
                {
                    if( ($params['fields']['target'] & (1 << $target_key)) )
                        $target_bits |= (1 << $target_key);
                }

                $params['fields']['target'] = $target_bits;
            }
        }

        if( empty( $params['fields']['status'] )
         or !$this->valid_status( $params['fields']['status'] ) )
            $params['fields']['status'] = self::STATUS_ACTIVE;

        $params['fields']['cdate'] = $cdate;

        if( empty( $params['fields']['status_date'] )
         or empty_db_date( $params['fields']['status_date'] ) )
            $params['fields']['status_date'] = $params['fields']['cdate'];
        else
            $params['fields']['status_date'] = date( self::DATETIME_DB, parse_db_date( $params['fields']['status_date'] ) );

        // Default backup on all days
        if( empty( $params['{days_arr}'] ) or !is_array( $params['{days_arr}'] ) )
            $params['{days_arr}'] = array( 0 );

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
    protected function insert_after_backup_rules( $insert_arr, $params )
    {
        $insert_arr['{days_arr}'] = array();

        if( empty( $params['{days_arr}'] ) or !is_array( $params['{days_arr}'] ) )
        {
            $this->set_error( self::ERR_INSERT, $this->_pt( 'Please provide backup rule days.' ) );
            return false;
        }

        if( !($insert_arr['{days_arr}'] = $this->link_days_to_rule( $insert_arr, $params['{days_arr}'] )) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_INSERT, $this->_pt( 'Error linking days to backup rule.' ) );

            return false;
        }

        return $insert_arr;
    }

    protected function get_edit_prepare_params_backup_rules( $existing_arr, $params )
    {
        if( isset( $params['fields']['title'] ) and empty( $params['fields']['title'] ) )
        {
            $this->set_error( self::ERR_EDIT, $this->_pt( 'Please provide a rule title.' ) );
            return false;
        }

        if( isset( $params['fields']['target'] ) and empty( $params['fields']['target'] ) )
        {
            $this->set_error( self::ERR_EDIT, $this->_pt( 'Please provide backup targets.' ) );
            return false;
        }

        if( isset( $params['fields']['status'] ) and !$this->valid_status( $params['fields']['status'] ) )
        {
            $this->set_error( self::ERR_EDIT, $this->_pt( 'Please provide valid status for backup rule.' ) );
            return false;
        }

        if( !empty( $params['fields']['location'] ) )
        {
            $params['fields']['location'] = rtrim( $params['fields']['location'], '/\\' );
            $location_check = $params['fields']['location'];
            if( substr( $params['fields']['location'], 0, 1 ) != '/'
            and substr( $params['fields']['location'], 1, 2 ) != ':\\' )
                $location_check = PHS_PATH.$params['fields']['location'];

            // we have an absolute path
            if( !@file_exists( $location_check )
             or !@is_dir( $location_check ) )
            {
                $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Provided backup location is not a directory.' ) );
                return false;
            }

            if( !@is_writable( $location_check ) )
            {
                $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Provided backup location is not writable.' ) );
                return false;
            }
        }

        if( !empty( $params['fields']['hour'] ) )
        {
            $params['fields']['hour'] = intval( $params['fields']['hour'] );
            if( $params['fields']['hour'] < 0 or $params['fields']['hour'] > 23 )
            {
                $this->set_error( self::ERR_PARAMETERS, $this->_pt( 'Please provide a valid backup hour.' ) );
                return false;
            }
        }

        if( !empty( $params['fields']['status'] )
        and (empty( $params['fields']['status_date'] ) or empty_db_date( $params['fields']['status_date'] ))
        and $this->valid_status( $params['fields']['status'] )
        and $params['fields']['status'] != $existing_arr['status'] )
            $params['fields']['status_date'] = date( self::DATETIME_DB );

        elseif( !empty( $params['fields']['status_date'] ) )
            $params['fields']['status_date'] = date( self::DATETIME_DB, parse_db_date( $params['fields']['status_date'] ) );

        if( empty( $params['{days_arr}'] ) or !is_array( $params['{days_arr}'] ) )
            $params['{days_arr}'] = false;

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
    protected function edit_after_backup_rules( $existing_data, $edit_arr, $params )
    {
        if( !empty( $params['{days_arr}'] ) and is_array( $params['{days_arr}'] ) )
        {
            if( !($existing_data['{days_arr}'] = $this->link_days_to_rule( $existing_data, $params['{days_arr}'] )) )
            {
                if( !$this->has_error() )
                    $this->set_error( self::ERR_INSERT, $this->_pt( 'Error linking days to backup rule.' ) );

                return false;
            }
        }

        return $existing_data;
    }

    /**
     * Called first in insert flow.
     * Parses flow parameters if anything special should be done.
     * This should do checks on raw parameters received by insert method.
     *
     * @param array|bool $params Parameters in the flow
     *
     * @return array|bool Flow parameters array
     */
    protected function get_insert_prepare_params_backup_rules_days( $params )
    {
        if( empty( $params ) or !is_array( $params ) )
            return false;

        if( !empty( $params['fields']['rule_id'] ) )
            $params['fields']['rule_id'] = intval( $params['fields']['rule_id'] );
        if( !empty( $params['fields']['day'] ) )
            $params['fields']['day'] = intval( $params['fields']['day'] );

        if( empty( $params['fields']['rule_id'] ) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a backup rule id.' ) );
            return false;
        }

        if( $params['fields']['day'] < 0 or $params['fields']['day'] > 7 )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a valid day for backup rule.' ) );
            return false;
        }

        return $params;
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
    protected function get_edit_prepare_params_backup_rules_days( $existing_data, $params )
    {
        if( empty( $params ) or !is_array( $params ) )
            return false;

        if( isset( $params['fields']['rule_id'] ) )
            $params['fields']['rule_id'] = intval( $params['fields']['rule_id'] );
        if( isset( $params['fields']['day'] ) )
            $params['fields']['day'] = intval( $params['fields']['day'] );

        if( isset( $params['fields']['rule_id'] ) and empty( $params['fields']['rule_id'] ) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a backup rule id.' ) );
            return false;
        }

        if( isset( $params['fields']['day'] )
        and ($params['fields']['day'] < 0 or $params['fields']['day'] > 7) )
        {
            $this->set_error( self::ERR_INSERT, self::_t( 'Please provide a valid day for backup rule.' ) );
            return false;
        }

        return $params;
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
            case 'backup_rules':
                $return_arr = array(
                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'uid' => array(
                        'type' => self::FTYPE_INT,
                        'index' => true,
                    ),
                    'title' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '255',
                        'nullable' => true,
                    ),
                    'hour' => array(
                        'type' => self::FTYPE_INT,
                    ),
                    'target' => array(
                        'type' => self::FTYPE_INT,
                    ),
                    'location' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '255',
                        'nullable' => true,
                    ),
                    'status' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                        'index' => true,
                    ),
                    'status_date' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                    'cdate' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                );
            break;

            case 'backup_rules_days':
                $return_arr = array(
                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'rule_id' => array(
                        'type' => self::FTYPE_INT,
                        'index' => true,
                    ),
                    'day' => array(
                        'type' => self::FTYPE_TINYINT,
                        'length' => '2',
                        'index' => true,
                    ),
                );
            break;
       }

        return $return_arr;
    }
}
