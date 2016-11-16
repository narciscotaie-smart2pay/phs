<?php

namespace phs\plugins\foobar\models;

use \phs\libraries\PHS_Model;
use \phs\libraries\PHS_params;

class PHS_Model_Foobar extends PHS_Model
{
    const ERR_DB_JOB = 10000;

    /**
     * @return string Returns version of model
     */
    public function get_model_version()
    {
        return '1.0.2';
    }

    /**
     * @return array of string Returns an array of strings containing tables that model will handle
     */
    public function get_table_names()
    {
        return array( 'foobar' );
    }

    /**
     * @return string Returns main table name used when calling insert with no table name
     */
    function get_main_table_name()
    {
        return 'foobar';
    }

    public function get_settings_structure()
    {
        return array(
            'minutes_to_stall' => array(
                'display_name' => 'Minutes to stall',
                'display_hint' => 'After how many minutes should we consider a job as stalling',
                'type' => PHS_params::T_INT,
                'default' => 15,
            ),
            'another_foobar_var' => array(
                'display_name' => 'Just a foobar value',
                'display_hint' => 'Bla bla...',
                'type' => PHS_params::T_INT,
                'default' => 2,
                'editable' => false,
            ),
            'check_update' => array(
                'display_name' => 'Just a foobar value',
                'display_hint' => 'Bla bla...',
                'type' => PHS_params::T_INT,
                'default' => 2,
                'editable' => false,
            ),
        );
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
            case 'foobar':
                $return_arr = array(
                    self::T_DETAILS_KEY => array(
                        'engine' => 'InnoDB',
                        'charset' => 'utf8',
                        'collate' => 'utf8_general_ci',
                        'comment' => 'A foobar model',
                    ),

                    'id' => array(
                        'type' => self::FTYPE_INT,
                        'primary' => true,
                        'auto_increment' => true,
                    ),
                    'pid' => array(
                        'type' => self::FTYPE_INT,
                    ),
                    'route' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '255',
                        'nullable' => true,
                        'default' => null,
                    ),
                    'params' => array(
                        'type' => self::FTYPE_LONGTEXT,
                        'nullable' => true,
                    ),
                    'last_error' => array(
                        'type' => self::FTYPE_VARCHAR,
                        'length' => '255',
                        'nullable' => true,
                        'default' => null,
                    ),
                    'last_action2' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => true,
                    ),
                    'timed_action' => array(
                        'type' => self::FTYPE_DATETIME,
                        'index' => true,
                    ),
                    'cdate' => array(
                        'type' => self::FTYPE_DATETIME,
                    ),
                );
            break;
       }

        return $return_arr;
    }
}
