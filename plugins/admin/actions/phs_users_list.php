<?php

namespace phs\plugins\admin\actions;

use \phs\PHS;
use \phs\PHS_Scope;
use \phs\libraries\PHS_Paginator;
use \phs\libraries\PHS_Action;
use \phs\libraries\PHS_params;
use \phs\libraries\PHS_Notifications;

class PHS_Action_Users_list extends PHS_Action
{
    const ERR_DEPENCIES = 1, ERR_ACTION = 2;

    /** @var bool|PHS_Paginator */
    private $_paginator = false;

    /** @var \phs\plugins\accounts\PHS_Plugin_Accounts $_accounts_plugin */
    private $_accounts_plugin = false;

    /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $_accounts_model */
    private $_accounts_model = false;

    /**
     * Returns an array of scopes in which action is allowed to run
     *
     * @return array If empty array, action is allowed in all scopes...
     */
    public function allowed_scopes()
    {
        return array( PHS_Scope::SCOPE_WEB, PHS_Scope::SCOPE_AJAX );
    }

    public function load_depencies()
    {
        if( !($this->_accounts_plugin = PHS::load_plugin( 'accounts' )) )
        {
            $this->set_error( self::ERR_DEPENCIES, self::_t( 'Couldn\'t load accounts plugin.' ) );
            return false;
        }

        if( !($this->_accounts_model = PHS::load_model( 'accounts', 'accounts' )) )
        {
            $this->set_error( self::ERR_DEPENCIES, self::_t( 'Couldn\'t load accounts model.' ) );
            return false;
        }

        return true;
    }

    /**
     * @return array|bool
     */
    public function execute()
    {
        if( !($current_user = PHS::user_logged_in()) )
        {
            PHS_Notifications::add_warning_notice( self::_t( 'You should login first...' ) );

            $action_result = self::default_action_result();

            $args = array(
                'back_page' => PHS::current_url()
            );

            $action_result['redirect_to_url'] = PHS::url( array( 'p' => 'accounts', 'a' => 'login' ), $args );

            return $action_result;
        }

        if( !$this->load_depencies() )
        {
            if( $this->has_error() )
                PHS_Notifications::add_error_notice( $this->get_error_message() );
            else
                PHS_Notifications::add_error_notice( self::_t( 'Couldn\'t load action depencies.' ) );

            return self::default_action_result();
        }

        if( !($accounts_plugin_settings = $this->_accounts_plugin->get_plugin_settings()) )
        {
            PHS_Notifications::add_error_notice( self::_t( 'Couldn\'t load accounts plugin settings.' ) );
            return self::default_action_result();
        }

        if( !$this->_accounts_model->can_list_accounts( $current_user ) )
        {
            PHS_Notifications::add_error_notice( self::_t( 'You don\'t have rights to create accounts.' ) );
            return self::default_action_result();
        }

        $account_created = PHS_params::_g( 'account_created', PHS_params::T_NOHTML );

        if( !empty( $account_created ) )
            PHS_Notifications::add_success_notice( self::_t( 'User account created.' ) );

        $flow_params = array(
            'term_singular' => self::_t( 'user' ),
            'term_plural' => self::_t( 'users' ),
            'after_table_callback' => array( $this, 'after_table_callback' ),
        );

        if( !($this->_paginator = new PHS_Paginator( PHS::url( array( 'p' => 'admin', 'a' => 'users_list' ) ), $flow_params )) )
        {
            PHS_Notifications::add_error_notice( self::_t( 'Couldn\'t instantiate paginator class.' ) );
            return self::default_action_result();
        }

        if( !($users_levels = $this->_accounts_model->get_levels_as_key_val()) )
            $users_levels = array();
        if( !($users_statuses = $this->_accounts_model->get_statuses_as_key_val()) )
            $users_statuses = array();

        if( !empty( $users_levels ) )
            $users_levels = self::merge_array_assoc( array( 0 => self::_t( ' - Choose - ' ) ), $users_levels );
        if( !empty( $users_statuses ) )
            $users_statuses = self::merge_array_assoc( array( 0 => self::_t( ' - Choose - ' ) ), $users_statuses );

        $bulk_actions = array(
            array(
                'display_name' => self::_t( 'Inactivate' ),
                'action' => 'bulk_inactivate',
                'js_callback' => 'phs_users_list_bulk_inactivate',
                'checkbox_column' => 'id',
            ),
            array(
                'display_name' => self::_t( 'Activate' ),
                'action' => 'bulk_activate',
                'js_callback' => 'phs_users_list_bulk_activate',
                'checkbox_column' => 'id',
            ),
            array(
                'display_name' => self::_t( 'Delete' ),
                'action' => 'bulk_delete',
                'js_callback' => 'phs_users_list_bulk_delete',
                'checkbox_column' => 'id',
            ),
        );

        $filters_arr = array(
            array(
                'display_name' => self::_t( 'IDs' ),
                'display_hint' => self::_t( 'Comma separated ids' ),
                'display_placeholder' => self::_t( 'eg. 1,2,3' ),
                'var_name' => 'fids',
                'record_field' => 'id',
                'record_check' => array( 'check' => 'IN', 'value' => '(%s)' ),
                'type' => PHS_params::T_ARRAY,
                'extra_type' => array( 'type' => PHS_params::T_INT ),
                'default' => array(),
            ),
            array(
                'display_name' => self::_t( 'Nickname' ),
                'display_hint' => self::_t( 'All records containing this value' ),
                'var_name' => 'fnick',
                'record_field' => 'nick',
                'record_check' => array( 'check' => 'LIKE', 'value' => '%%%s%%' ),
                'type' => PHS_params::T_NOHTML,
                'default' => '',
            ),
            array(
                'display_name' => self::_t( 'Level' ),
                'var_name' => 'flevel',
                'record_field' => 'level',
                'type' => PHS_params::T_INT,
                'default' => 0,
                'values_arr' => $users_levels,
            ),
            array(
                'display_name' => self::_t( 'Status' ),
                'var_name' => 'fstatus',
                'record_field' => 'status',
                'type' => PHS_params::T_INT,
                'default' => 0,
                'values_arr' => $users_statuses,
            ),
        );

        $columns_arr = array(
            array(
                'column_title' => self::_t( '#' ),
                'record_field' => 'id',
                'checkbox_record_index_key' => array(
                    'key' => 'id',
                    'type' => PHS_params::T_INT,
                ),
                'invalid_value' => self::_t( 'N/A' ),
                'extra_style' => 'min-width:50px;max-width:80px;',
                'extra_records_style' => 'text-align:center;',
            ),
            array(
                'column_title' => self::_t( 'Nickname' ),
                'record_field' => 'nick',
            ),
            array(
                'column_title' => self::_t( 'Email' ),
                'record_field' => 'email',
                'invalid_value' => self::_t( 'N/A' ),
                'extra_records_style' => 'text-align:center;',
            ),
            array(
                'column_title' => self::_t( 'Status' ),
                'record_field' => 'status',
                'display_key_value' => $users_statuses,
                'invalid_value' => self::_t( 'Undefined' ),
                'extra_records_style' => 'text-align:center;',
            ),
            array(
                'column_title' => self::_t( 'Level' ),
                'record_field' => 'level',
                'display_key_value' => $users_levels,
                'extra_records_style' => 'text-align:center;',
            ),
            array(
                'column_title' => self::_t( 'Last Login' ),
                'record_field' => 'lastlog',
                'display_callback' => array( $this->_paginator, 'pretty_date' ),
                'date_format' => 'd M y H:i',
                'invalid_value' => self::_t( 'Never' ),
                'extra_style' => 'width:100px;',
                'extra_records_style' => 'text-align:right;',
            ),
            array(
                'column_title' => self::_t( 'Created' ),
                'default_sort' => 1,
                'record_field' => 'cdate',
                'display_callback' => array( $this->_paginator, 'pretty_date' ),
                'date_format' => 'd M y H:i',
                'invalid_value' => self::_t( 'Invalid' ),
                'extra_style' => 'width:100px;',
                'extra_records_style' => 'text-align:right;',
            ),
            array(
                'column_title' => self::_t( 'Actions' ),
                'display_callback' => array( $this, 'display_actions' ),
                'extra_style' => 'width:100px;',
                'extra_records_style' => 'text-align:right;',
            ),
        );

        if( !$this->_paginator->set_bulk_actions( $bulk_actions )
         or !$this->_paginator->set_columns( $columns_arr )
         or !$this->_paginator->set_filters( $filters_arr )
         or !$this->_paginator->set_model( $this->_accounts_model ) )
        {
            if( $this->_paginator->has_error() )
                $error_msg = $this->_paginator->get_error_message();
            else
                $error_msg = self::_t( 'Something went wrong while preparing paginator class.' );

            $data = array(
                'filters' => $error_msg,
                'listing' => '',
            );
        } else
        {
            // check actions...
            if( ($current_action = $this->_paginator->get_current_action())
            and is_array( $current_action )
            and !empty( $current_action['action'] ) )
            {
                if( !($pagination_action_result = $this->manage_action( $current_action )) )
                {
                    if( $this->has_error() )
                        PHS_Notifications::add_error_notice( $this->get_error_message() );
                } elseif( is_array( $pagination_action_result )
                      and !empty( $pagination_action_result['action'] ) )
                {
                    $pagination_action_result = self::validate_array( $pagination_action_result, $this->_paginator->default_action_params() );

                    $url_params = array(
                        'action' => $pagination_action_result,
                    );

                    if( !empty( $pagination_action_result['action_redirect_url_params'] )
                    and is_array( $pagination_action_result['action_redirect_url_params'] ) )
                        $url_params = self::merge_array_assoc( $pagination_action_result['action_redirect_url_params'], $url_params );

                    $action_result = self::default_action_result();

                    $action_result['redirect_to_url'] = $this->_paginator->get_full_url( $url_params );

                    return $action_result;
                }
            }
            
            $data = array(
                'filters' => $this->_paginator->get_filters_buffer(),
                'listing' => $this->_paginator->get_listing_buffer(),
            );
        }

        return $this->quick_render_template( 'users_list', $data );
    }

    /**
     * Manages actions to be taken for current listing
     *
     * @param array $action Action details array
     *
     * @return array|bool Returns true if no error or no action taken, false if there was an error while taking action or an action array in case action was taken (with success or not)
     */
    public function manage_action( $action )
    {
        $this->reset_error();

        if( empty( $this->_accounts_model ) )
        {
            if( !$this->load_depencies() )
                return false;
        }

        $action_result_params = $this->_paginator->default_action_params();

        if( empty( $action ) or !is_array( $action )
         or empty( $action['action'] ) )
            return $action_result_params;

        $action_result_params['action'] = $action['action'];

        switch( $action['action'] )
        {
            default:
                PHS_Notifications::add_error_notice( self::_t( 'Unknown action.' ) );
                return true;
            break;

            case 'bulk_activate':
                if( !empty( $action['action_result'] ) )
                {
                    if( $action['action_result'] == 'success' )
                        PHS_Notifications::add_success_notice( self::_t( 'Required accounts activated with success.' ) );
                    elseif( $action['action_result'] == 'failed' )
                        PHS_Notifications::add_error_notice( self::_t( 'Activating selected accounts failed. Please try again.' ) );
                    elseif( $action['action_result'] == 'failed_some' )
                        PHS_Notifications::add_error_notice( self::_t( 'Failed activating all selected accounts. Accounts which failed activation are still selected. Please try again.' ) );

                    return true;
                }

                if( !($current_user = PHS::user_logged_in())
                 or !$this->_accounts_model->can_manage_accounts( $current_user ) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'You don\'t have rights to manage accounts.' ) );
                    return false;
                }

                if( !($scope_arr = $this->_paginator->get_scope())
                 or !($ids_checkboxes_name = $this->_paginator->get_checkbox_name_format())
                 or !($ids_all_checkbox_name = $this->_paginator->get_all_checkbox_name_format())
                 or !($scope_key = @sprintf( $ids_checkboxes_name, 'id' ))
                 or !($scope_all_key = @sprintf( $ids_all_checkbox_name, 'id' ))
                 or empty( $scope_arr[$scope_key] )
                 or !is_array( $scope_arr[$scope_key] ) )
                    return true;

                $remaining_ids_arr = array();
                foreach( $scope_arr[$scope_key] as $account_id )
                {
                    //PHS_Notifications::add_success_notice( self::_t( 'Activating account ['.$account_id.']' ) );
                    if( !$this->_accounts_model->activate_account( $account_id ) )
                    {
                        $remaining_ids_arr[] = $account_id;
                    }
                }

                if( isset( $scope_arr[$scope_all_key] ) )
                    unset( $scope_arr[$scope_all_key] );

                if( empty( $remaining_ids_arr ) )
                {
                    $action_result_params['action_result'] = 'success';

                    unset( $scope_arr[$scope_key] );

                    $action_result_params['action_redirect_url_params'] = array( 'force_scope' => $scope_arr );
                } else
                {
                    if( count( $remaining_ids_arr ) != count( $scope_arr[$scope_key] ) )
                        $action_result_params['action_result'] = 'failed_some';
                    else
                        $action_result_params['action_result'] = 'failed';

                    $scope_arr[$scope_key] = implode( ',', $remaining_ids_arr );

                    $action_result_params['action_redirect_url_params'] = array( 'force_scope' => $scope_arr );
                }
            break;

            case 'bulk_inactivate':
                PHS_Notifications::add_success_notice( self::_t( 'Bulk activating.' ) );
                return true;
            break;

            case 'bulk_delete':
                PHS_Notifications::add_success_notice( self::_t( 'Bulk activating.' ) );
                return true;
            break;

            case 'activate_account':
                if( !empty( $action['action_result'] ) )
                {
                    if( $action['action_result'] == 'success' )
                        PHS_Notifications::add_success_notice( self::_t( 'Account activated with success.' ) );
                    elseif( $action['action_result'] == 'failed' )
                        PHS_Notifications::add_error_notice( self::_t( 'Activating account failed. Please try again.' ) );

                    return true;
                }

                if( !($current_user = PHS::user_logged_in())
                 or !$this->_accounts_model->can_manage_accounts( $current_user ) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'You don\'t have rights to manage accounts.' ) );
                    return false;
                }

                if( !empty( $action['action_params'] ) )
                    $action['action_params'] = intval( $action['action_params'] );
                 
                if( empty( $action['action_params'] )
                 or !($account_arr = $this->_accounts_model->get_details( $action['action_params'] )) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'Cannot activate account. Account not found.' ) );
                    return false;
                }

                if( !$this->_accounts_model->activate_account( $account_arr ) )
                    $action_result_params['action_result'] = 'failed';
                else
                    $action_result_params['action_result'] = 'success';
            break;

            case 'inactivate_account':
                if( !empty( $action['action_result'] ) )
                {
                    if( $action['action_result'] == 'success' )
                        PHS_Notifications::add_success_notice( self::_t( 'Account inactivated with success.' ) );
                    elseif( $action['action_result'] == 'failed' )
                        PHS_Notifications::add_error_notice( self::_t( 'Inactivating account failed. Please try again.' ) );

                    return true;
                }

                if( !($current_user = PHS::user_logged_in())
                 or !$this->_accounts_model->can_manage_accounts( $current_user ) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'You don\'t have rights to manage accounts.' ) );
                    return false;
                }

                if( !empty( $action['action_params'] ) )
                    $action['action_params'] = intval( $action['action_params'] );

                if( empty( $action['action_params'] )
                 or !($account_arr = $this->_accounts_model->get_details( $action['action_params'] )) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'Cannot inactivate account. Account not found.' ) );
                    return false;
                }

                if( !$this->_accounts_model->inactivate_account( $account_arr ) )
                    $action_result_params['action_result'] = 'failed';
                else
                    $action_result_params['action_result'] = 'success';
           break;

            case 'delete_account':
                if( !empty( $action['action_result'] ) )
                {
                    if( $action['action_result'] == 'success' )
                        PHS_Notifications::add_success_notice( self::_t( 'Account deleted with success.' ) );
                    elseif( $action['action_result'] == 'failed' )
                        PHS_Notifications::add_error_notice( self::_t( 'Deleting account failed. Please try again.' ) );

                    return true;
                }

                if( !($current_user = PHS::user_logged_in())
                 or !$this->_accounts_model->can_manage_accounts( $current_user ) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'You don\'t have rights to manage accounts.' ) );
                    return false;
                }

                if( !empty( $action['action_params'] ) )
                    $action['action_params'] = intval( $action['action_params'] );

                if( empty( $action['action_params'] )
                 or !($account_arr = $this->_accounts_model->get_details( $action['action_params'] )) )
                {
                    $this->set_error( self::ERR_ACTION, self::_t( 'Cannot delete account. Account not found.' ) );
                    return false;
                }

                if( !$this->_accounts_model->delete_account( $account_arr ) )
                    $action_result_params['action_result'] = 'failed';
                else
                    $action_result_params['action_result'] = 'success';
            break;
        }

        return $action_result_params;
    }

    public function display_actions( $params )
    {
        if( empty( $this->_accounts_model ) )
        {
            if( !$this->load_depencies() )
                return false;
        }

        if( empty( $params )
         or !is_array( $params )
         or empty( $params['record'] ) or !is_array( $params['record'] )
         or !($account_arr = $this->_accounts_model->data_to_array( $params['record'] )) )
            return false;

        ob_start();
        if( $this->_accounts_model->is_inactive( $account_arr ) )
        {
            ?>
            <a href="javascript:void(0)" onclick="phs_users_list_activate_account( '<?php echo $account_arr['id']?>' )"><i class="fa fa-play-circle-o action-icons" title="<?php echo self::_t( 'Activate account' )?>"></i></a>
            <?php
        }
        if( $this->_accounts_model->is_active( $account_arr ) )
        {
            ?>
            <a href="javascript:void(0)" onclick="phs_users_list_inactivate_account( '<?php echo $account_arr['id']?>' )"><i class="fa fa-pause-circle-o action-icons" title="<?php echo self::_t( 'Inactivate account' )?>"></i></a>
            <?php
        }

        if( !$this->_accounts_model->is_deleted( $account_arr ) )
        {
            ?>
            <a href="javascript:void(0)" onclick="phs_users_list_delete_account( '<?php echo $account_arr['id']?>' )"><i class="fa fa-times-circle-o action-icons" title="<?php echo self::_t( 'Delete account' )?>"></i></a>
            <?php
        }

        return ob_get_clean();
    }

    public function after_table_callback( $params )
    {
        static $js_functionality = false;

        if( !empty( $js_functionality ) )
            return '';

        $js_functionality = true;
        
        if( !($flow_params_arr = $this->_paginator->flow_params()) )
            $flow_params_arr = array();

        ob_start();
        ?>
        <script type="text/javascript">
        function phs_users_list_activate_account( id )
        {
            if( confirm( "<?php echo self::_e( 'Are you sure you want to activate this account?', '"' )?>" ) )
            {
                <?php
                $url_params = array();
                $url_params['action'] = array(
                    'action' => 'activate_account',
                    'action_params' => '" + id + "',
                )
                ?>document.location = "<?php echo $this->_paginator->get_full_url( $url_params )?>";
            }
        }
        function phs_users_list_inactivate_account( id )
        {
            if( confirm( "<?php echo self::_e( 'Are you sure you want to inactivate this account?', '"' )?>" ) )
            {
                <?php
                $url_params = array();
                $url_params['action'] = array(
                    'action' => 'inactivate_account',
                    'action_params' => '" + id + "',
                )
                ?>document.location = "<?php echo $this->_paginator->get_full_url( $url_params )?>";
            }
        }
        function phs_users_list_delete_account( id )
        {
            if( confirm( "<?php echo self::_e( 'Are you sure you want to DELETE this account?', '"' )?>" + "\n" +
                         "<?php echo self::_e( 'NOTE: You cannot undo this action!', '"' )?>' ) )
            {
                <?php
                $url_params = array();
                $url_params['action'] = array(
                    'action' => 'delete_account',
                    'action_params' => '" + id + "',
                )
                ?>document.location = "<?php echo $this->_paginator->get_full_url( $url_params )?>";
            }
        }

        function phs_users_list_get_checked_ids_count()
        {
            var checkboxes_list = phs_paginator_get_checkboxes_checked( 'id' );
            if( !checkboxes_list || !checkboxes_list.length )
                return 0;

            return checkboxes_list.length;
        }

        function phs_users_list_bulk_activate()
        {
            var total_checked = phs_users_list_get_checked_ids_count();

            if( !total_checked )
            {
                alert( "<?php echo self::_e( 'Please select accounts you want to activate first.', '"' )?>" );
                return false;
            }

            if( confirm( "<?php echo sprintf( self::_e( 'Are you sure you want to activate %s accounts?', '"' ), '" + total_checked + "' )?>" ) )

            {
                var form_obj = $("#<?php echo $this->_paginator->get_listing_form_name()?>");
                if( form_obj )
                    form_obj.submit();
            }
        }

        function phs_users_list_bulk_inactivate()
        {
            var total_checked = phs_users_list_get_checked_ids_count();

            if( !total_checked )
            {
                alert( "<?php echo self::_e( 'Please select accounts you want to inactivate first.', '"' )?>" );
                return false;
            }

            if( confirm( "<?php echo sprintf( self::_e( 'Are you sure you want to inactivate %s accounts?', '"' ), '" + total_checked + "' )?>" ) )

            {
                var form_obj = $("#<?php echo $this->_paginator->get_listing_form_name()?>");
                if( form_obj )
                    form_obj.submit();
            }
        }

        function phs_users_list_bulk_delete()
        {
            var total_checked = phs_users_list_get_checked_ids_count();

            if( !total_checked )
            {
                alert( "<?php echo self::_e( 'Please select accounts you want to delete first.', '"' )?>" );
                return false;
            }

            if( confirm( "<?php echo sprintf( self::_e( 'Are you sure you want to DELETE %s accounts?', '"' ), '" + total_checked + "' )?>" + "\n" +
                         "<?php echo self::_e( 'NOTE: You cannot undo this action!', '"' )?>" ) )
            {
                var form_obj = $("#<?php echo $this->_paginator->get_listing_form_name()?>");
                if( form_obj )
                    form_obj.submit();
            }
        }
        </script>
        <?php

        return ob_get_clean();
    }
}