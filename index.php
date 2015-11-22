<?php
    include_once( 'main.inc.php' );

    if( !($plugins_model = PHS::load_model( 'plugins' )) )
    {
        var_dump( PHS::st_get_error() );
        exit;
    }

    $plugins_model->add_connection( 'PHS_Model_Accounts', 'accounts', $plugins_model::INSTANCE_TYPE_MODEL );
    $plugins_model->add_connection( 'PHS_Model_Accounts_details', 'accounts', $plugins_model::INSTANCE_TYPE_MODEL );

    $plugins_model->install();

    /** @var PHS_Model_Accounts $accounts_obj */
    //if( !($accounts_obj = PHS::load_model( 'accounts', 'accounts' ))
    // or !($plugins_model = PHS::load_model( 'plugins' )) )
    //{
    //    var_dump( PHS::st_get_error() );
    //    exit;
    //}
    //
    //$plugins_model->install();
    //
    //var_dump( $accounts_obj->install() );
    //var_dump( $accounts_obj->instance_details() );

    //$fields_arr = array();
    //$fields_arr['email'] = 'gica@email.com';
    //
    //$insert_arr = array();
    //$insert_arr['fields'] = $fields_arr;
    //
    //var_dump( $accounts_obj->insert( $insert_arr ) );

    //var_dump( $accounts_obj );

    //PHS_session::_s( 'bubu', 12 );
    //var_dump( PHS_session::_g() );

