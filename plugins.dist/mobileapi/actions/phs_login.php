<?php

namespace phs\plugins\mobileapi\actions;

use \phs\PHS;
use \phs\PHS_Scope;
use \phs\PHS_api;
use \phs\libraries\PHS_Action;

class PHS_Action_Login extends PHS_Action
{
    /** @inheritdoc */
    public function action_roles()
    {
        return array( self::ACT_ROLE_LOGIN );
    }

    public function allowed_scopes()
    {
        return array( PHS_Scope::SCOPE_API );
    }

    public function execute()
    {
        /** @var \phs\PHS_api $api_obj */
        if( !($api_obj = PHS_api::global_api_instance()) )
        {
            $this->set_error( self::ERR_FUNCTIONALITY, $this->_pt( 'Error obtaining API instance.' ) );
            return false;
        }

        /** @var \phs\plugins\mobileapi\models\PHS_Model_Api_online $online_model */
        /** @var \phs\plugins\mobileapi\PHS_Plugin_Mobileapi $mobile_plugin */
        /** @var \phs\plugins\accounts\models\PHS_Model_Accounts $accounts_model */
        if( !($online_model = PHS::load_model( 'api_online', 'mobileapi' ))
         or !($mobile_plugin = PHS::load_plugin( 'mobileapi' ))
         or !($accounts_model = PHS::load_model( 'accounts', 'accounts' )) )
        {
            if( !$api_obj->send_header_response( $api_obj::H_CODE_INTERNAL_SERVER_ERROR, $this->_pt( 'Couldn\'t load required models.' ) ) )
            {
                $this->set_error( $api_obj::ERR_API_INIT, $this->_pt( 'Couldn\'t load required models.' ) );
                return false;
            }

            exit;
        }

        if( !($request_arr = PHS_api::get_request_body_as_json_array())
         or empty( $request_arr['nick'] )
         or empty( $request_arr['pass'] )
         or empty( $request_arr['device_info'] ) )
        {
            if( !$api_obj->send_header_response( $api_obj::H_CODE_UNAUTHORIZED, $this->_pt( 'Please provide credentials.' ) ) )
            {
                $this->set_error( $api_obj::ERR_AUTHENTICATION, $this->_pt( 'Please provide credentials.' ) );
                return false;
            }

            exit;
        }

        if( !($account_arr = $accounts_model->get_details_fields( array( 'nick' => $request_arr['nick'] ) ))
         or !$accounts_model->check_pass( $account_arr, $request_arr['pass'] )
         or !$accounts_model->is_active( $account_arr ) )
        {
            if( !$api_obj->send_header_response( $api_obj::H_CODE_UNAUTHORIZED, $this->_pt( 'Authentication failed.' ) ) )
            {
                $this->set_error( $api_obj::ERR_AUTHENTICATION, $this->_pt( 'Authentication failed.' ) );
                return false;
            }

            exit;
        }

        $device_data = array();
        $device_info_keys = array(
            'device_type' => $online_model::DEV_TYPE_UNDEFINED,
            'device_name' => '',
            'device_version' => '',
            'device_token' => '',
        );
        foreach( $device_info_keys as $field => $def_value )
        {
            if( !array_key_exists( $field, $request_arr['device_info'] ) )
                $device_data[$field] = $def_value;
            else
                $device_data[$field] = $request_arr['device_info'][$field];
        }

        $device_data['uid'] = $account_arr['id'];

        if( !($session_arr = $online_model->generate_session( $account_arr['id'], $device_data )) )
        {
            if( !$api_obj->send_header_response( $api_obj::H_CODE_INTERNAL_SERVER_ERROR, $this->_pt( 'Error generating session.' ) ) )
            {
                $this->set_error( $api_obj::ERR_AUTHENTICATION, $this->_pt( 'Error generating session.' ) );
                return false;
            }

            exit;
        }

        $action_result = self::default_action_result();

        $response_arr = array(
            'session_data' => $online_model->export_data_from_session_data( $session_arr ),
            'account_data' => $mobile_plugin->export_data_from_account_data( $account_arr ),
        );

        // trigger hook to populate with other details if required

        $action_result['api_json_result_array'] = $response_arr;

        return $action_result;
    }
}
