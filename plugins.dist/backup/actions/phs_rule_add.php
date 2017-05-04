<?php

namespace phs\plugins\backup\actions;

use \phs\PHS;
use \phs\PHS_bg_jobs;
use \phs\PHS_Scope;
use \phs\libraries\PHS_Action;
use \phs\libraries\PHS_params;
use \phs\libraries\PHS_Notifications;
use \phs\libraries\PHS_Roles;

class PHS_Action_Rule_add extends PHS_Action
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
        PHS::page_settings( 'page_title', $this->_pt( 'Add Rule' ) );

        if( !($current_user = PHS::user_logged_in()) )
        {
            PHS_Notifications::add_warning_notice( $this->_pt( 'You should login first...' ) );

            $action_result = self::default_action_result();

            $args = array(
                'back_page' => PHS::current_url()
            );

            $action_result['redirect_to_url'] = PHS::url( array( 'p' => 'accounts', 'a' => 'login' ), $args );

            return $action_result;
        }

        /** @var \phs\plugins\backup\PHS_Plugin_Backup $backup_plugin */
        if( !($backup_plugin = PHS::load_plugin( 'backup' )) )
        {
            PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load backup plugin.' ) );
            return self::default_action_result();
        }

        if( !PHS_Roles::user_has_role_units( $current_user, $backup_plugin::ROLEU_MANAGE_RULES ) )
        {
            PHS_Notifications::add_error_notice( $this->_pt( 'You don\'t have rights to manage backup rules.' ) );
            return self::default_action_result();
        }

        /** @var \phs\system\core\libraries\PHS_Ftp $ftp_obj */
        if( !($ftp_obj = PHS::get_core_library_instance( 'ftp' )) )
        {
            PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load FTP core library.' ) );
            return self::default_action_result();
        }

        /** @var \phs\plugins\backup\models\PHS_Model_Rules $rules_model */
        if( !($rules_model = PHS::load_model( 'rules', 'backup' )) )
        {
            PHS_Notifications::add_error_notice( $this->_pt( 'Couldn\'t load backup rules model.' ) );
            return self::default_action_result();
        }

        $days_options_arr = array(
            7 => $this->_pt( 'One week' ),
            14 => $this->_pt( 'Two weeks' ),
            30 => $this->_pt( '30 days' ),
            60 => $this->_pt( '60 days' ),
        );

        $foobar = PHS_params::_p( 'foobar', PHS_params::T_INT );
        $title = PHS_params::_p( 'title', PHS_params::T_NOHTML );
        $hour = PHS_params::_p( 'hour', PHS_params::T_INT );
        $delete_after_days = PHS_params::_p( 'delete_after_days', PHS_params::T_INT );
        $cdelete_after_days = PHS_params::_p( 'cdelete_after_days', PHS_params::T_INT );
        $copy_results = PHS_params::_p( 'copy_results', PHS_params::T_INT );
        if( !($ftp_settings = PHS_params::_p( 'ftp_settings', PHS_params::T_ARRAY, array( 'type' => PHS_params::T_ASIS ) )) )
            $ftp_settings = array();
        if( !($target_arr = PHS_params::_p( 'target_arr', PHS_params::T_ARRAY, array( 'type' => PHS_params::T_INT ) )) )
            $target_arr = array();
        if( !($days_arr = PHS_params::_p( 'days_arr', PHS_params::T_ARRAY, array( 'type' => PHS_params::T_INT ) )) )
            $days_arr = array();
        if( !($location = PHS_params::_p( 'location', PHS_params::T_NOHTML )) )
            $location = '';

        $do_submit = PHS_params::_p( 'do_submit' );
        $do_test_ftp = PHS_params::_pg( 'do_test_ftp' );

        if( !empty( $do_test_ftp ) )
            $do_submit = true;

        if( !empty( $do_submit ) )
        {
            if( $delete_after_days == -1 )
                PHS_Notifications::add_error_notice( $this->_pt( 'Please choose an option for delete action.' ) );

            elseif( $delete_after_days === 0 )
                $cdelete_after_days = 0;

            elseif( $delete_after_days == -2 )
            {
                if( empty( $cdelete_after_days ) or $cdelete_after_days < 0 )
                    $cdelete_after_days = 0;
            } else
                $cdelete_after_days = $delete_after_days;
        }

        if( !($rule_days = $rules_model->get_rule_days()) )
            $rule_days = array();
        if( !($targets_arr = $rules_model->get_targets_as_key_val()) )
            $targets_arr = array();
        if( !($rule_location = $backup_plugin->get_location_for_path( $location )) )
            $rule_location = '';

        if( !($copy_results_arr = $rules_model->get_copy_results_as_key_val()) )
            $copy_results_arr = array();
        if( !($ftp_connection_modes_arr = $ftp_obj->get_connection_types_as_key_val()) )
            $ftp_connection_modes_arr = array();

        if( !empty( $do_submit )
        and !PHS_Notifications::have_errors_or_warnings_notifications() )
        {
            if( !empty( $copy_results )
            and $copy_results == $rules_model::COPY_FTP )
            {
                if( empty( $ftp_settings ) or !is_array( $ftp_settings )
                 or empty( $ftp_settings['connection_mode'] )
                 or !$ftp_obj->valid_connection_type( $ftp_settings['connection_mode'] )
                 or !$ftp_obj->settings_valid( $ftp_settings ) )
                    PHS_Notifications::add_error_notice( $this->_pt( 'Please choose an option for delete action.' ) );

                else
                {
                    $ftp_settings['connection_mode'] = intval( $ftp_settings['connection_mode'] );

                    // We hardcode binary transfers...
                    $ftp_settings['transfer_mode'] = $ftp_obj::TRANSFER_MODE_BINARY;

                    if( !empty( $ftp_settings['timeout'] ) )
                        $ftp_settings['timeout'] = intval( $ftp_settings['timeout'] );
                    else
                        $ftp_settings['timeout'] = 0;

                    if( !empty( $ftp_settings['passive_mode'] ) )
                        $ftp_settings['passive_mode'] = true;
                    else
                        $ftp_settings['passive_mode'] = false;
                }
            }

            if( !PHS_Notifications::have_errors_or_warnings_notifications() )
            {
                $insert_arr = array();
                $insert_arr['uid'] = $current_user['id'];
                $insert_arr['title'] = $title;
                $insert_arr['location'] = $location;
                $insert_arr['hour'] = $hour;
                $insert_arr['delete_after_days'] = $cdelete_after_days;
                $insert_arr['copy_results'] = $copy_results;
                $insert_arr['ftp_settings'] = $ftp_settings;
                $insert_arr['target'] = $target_arr;

                $insert_params_arr = $rules_model->fetch_default_flow_params( array( 'table_name' => 'backup_rules' ) );
                $insert_params_arr['fields'] = $insert_arr;
                $insert_params_arr['{days_arr}'] = $days_arr;

                if( ($new_rule = $rules_model->insert( $insert_params_arr )) )
                {
                    PHS_Notifications::add_success_notice( $this->_pt( 'Backup rule details saved...' ) );

                    $action_result = self::default_action_result();

                    if( !empty( $do_test_ftp ) )
                    {
                        $url_args = array(
                            'rid' => $new_rule['id'],
                            'changes_saved' => 1,
                            'do_test_ftp' => 1,
                        );

                        $action_result['redirect_to_url'] = PHS::url( array( 'p' => 'backup', 'a' => 'rule_edit' ), $url_args );
                    } else
                    {
                        $action_result['redirect_to_url'] = PHS::url( array( 'p' => 'backup', 'a' => 'rules_list' ), array( 'rule_added' => 1 ) );
                    }

                    return $action_result;
                } else
                {
                    if( $rules_model->has_error() )
                        PHS_Notifications::add_error_notice( $rules_model->get_error_message() );
                    else
                        PHS_Notifications::add_error_notice( $this->_pt( 'Error saving details to database. Please try again.' ) );
                }
            }
        }

        $data = array(
            'title' => $title,
            'hour' => $hour,
            'delete_after_days' => $delete_after_days,
            'copy_results' => $copy_results,
            'ftp_settings' => $ftp_settings,
            'target_arr' => $target_arr,
            'days_arr' => $days_arr,
            'location' => $location,

            'copy_results_arr' => $copy_results_arr,
            'ftp_connection_modes_arr' => $ftp_connection_modes_arr,
            'days_options_arr' => $days_options_arr,
            'cdelete_after_days' => $cdelete_after_days,
            'rule_days' => $rule_days,
            'targets_arr' => $targets_arr,
            'rule_location' => $rule_location,
            'rules_model' => $rules_model,
            'backup_plugin' => $backup_plugin,
        );

        return $this->quick_render_template( 'rule_add', $data );
    }
}