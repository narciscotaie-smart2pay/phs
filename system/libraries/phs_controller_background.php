<?php
namespace phs\libraries;

use \phs\PHS_Scope;

abstract class PHS_Controller_Background extends \phs\libraries\PHS_Controller
{
    /**
     * Returns an array of scopes in which controller is allowed to run
     *
     * @return array If empty array, controller is allowed in all scopes...
     */
    public function allowed_scopes()
    {
        return array( PHS_Scope::SCOPE_BACKGROUND, PHS_Scope::SCOPE_AGENT );
    }
}
