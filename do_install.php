<?php

    include_once( 'main.php' );

    use \phs\PHS;

    PHS::st_throw_errors( true );

    echo '<pre style="background-color:black;color:white;padding: 5px;border:1px solid gray;">'."\n\n";
    echo 'Installing core plugins, models, etc...'."\n";

    if( @file_exists( PHS_SYSTEM_DIR.'install.php' ) )
    {
        $system_install_result = include_once( PHS_SYSTEM_DIR . 'install.php' );

        if( $system_install_result !== true )
        {
            echo PHS::_t( 'ERROR while running system install script [%s]:', 'CORE INSTALL' );
            var_dump( $system_install_result );
            echo '</pre>';
            exit;
        }
    }

    echo 'DONE Installing core plugins, models, etc.'."\n\n";

    echo 'Installing custom plugins, models, etc...'."\n";

    // Walk thgrough plugins install scripts (if any special install functionality is required)...
    foreach( array( PHS_CORE_PLUGIN_DIR, PHS_PLUGINS_DIR ) as $bstrap_dir )
    {
        if( ($install_scripts = @glob( $bstrap_dir . '*/install.php', GLOB_BRACE ))
        and is_array( $install_scripts ) )
        {
            foreach( $install_scripts as $install_script )
            {
                $install_result = include_once( $install_script );

                if( $install_result !== null )
                {
                    echo PHS::_t( 'ERROR while running system install script [%s]:', $install_script );
                    var_dump( $install_result );
                }
            }
        }
    }

    echo 'DONE Installing custom plugins, models, etc.'."\n\n";
    echo "\n".'</pre>';

    if( ($debug_data = PHS::platform_debug_data()) )
    {
        echo ' </br><small>'.$debug_data['db_queries_count'].' queries, '.
             ' bootstrap: '.number_format( $debug_data['bootstrap_time'], 6, '.', '' ).'s, '.
             ' running: '.number_format( $debug_data['running_time'], 6, '.', '' ).'s'.
             '</small>';
    }

