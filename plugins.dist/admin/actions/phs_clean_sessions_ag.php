<?php

namespace phs\plugins\admin\actions;

use \phs\PHS_Scope;
use \phs\PHS_session;
use \phs\libraries\PHS_Action;
use \phs\libraries\PHS_Logger;

class PHS_Action_Clean_sessions_ag extends PHS_Action
{
    public function allowed_scopes()
    {
        return array( PHS_Scope::SCOPE_AGENT );
    }

    public function execute()
    {
        PHS_Logger::logf( ' ----- Checking sessions...', PHS_Logger::TYPE_MAINTENANCE );

        if( !($check_result = PHS_session::sessions_gc()) )
            PHS_Logger::logf( 'ERROR checking session files.', PHS_Logger::TYPE_MAINTENANCE );

        else
        {
            PHS_Logger::logf( 'Checked '.$check_result['total'].' session files, '.$check_result['deleted'].
                              ' deleted (older than '.$check_result['maxlifetime'].' seconds) in '.$check_result['sess_dir'].
                              ' with pattern '.$check_result['dir_pattern'].'.', PHS_Logger::TYPE_MAINTENANCE );
        }

        PHS_Logger::logf( ' ----- END Checking sessions...', PHS_Logger::TYPE_MAINTENANCE );

        return self::default_action_result();
    }
}
