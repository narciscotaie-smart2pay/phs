<?php

namespace phs\plugins\accounts\contracts;

use \phs\libraries\PHS_Params;
use \phs\libraries\PHS_Contract;
use \phs\libraries\PHS_Model;

class PHS_Contract_Account extends PHS_Contract
{
    /**
     * Returns an array with data nodes definition
     * @return array|bool
     * @see \phs\libraries\PHS_Contract::_get_contract_node_definition()
     */
    public function get_contract_data_definition()
    {
        return [
            'id' => [
                'title' => 'Account ID',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'key_type' => self::FROM_INSIDE,
            ],
            'nick' => [
                'title' => 'Account nickname',
                'type' => PHS_Params::T_NOHTML,
                'default' => '',
                'import_if_not_found' => false,
            ],
            'pass' => [
                'title' => 'Account password (only for updates)',
                'type' => PHS_Params::T_NOHTML,
                'default' => '',
                'key_type' => self::FROM_OUTSIDE,
                'import_if_not_found' => false,
            ],
            'email' => [
                'title' => 'Account email address',
                'type' => PHS_Params::T_NOHTML,
                'default' => '',
                'import_if_not_found' => false,
            ],
            'email_verified' => [
                'title' => 'Account email is verified? (0/1)',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'import_if_not_found' => false,
            ],
            'language' => [
                'title' => 'Account language (ISO 2 char language code)',
                'type' => PHS_Params::T_NOHTML,
                'default' => '',
                'import_if_not_found' => false,
            ],
            'pass_generated' => [
                'title' => 'Was password auto-generated? (0/1)',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'key_type' => self::FROM_INSIDE,
                'import_if_not_found' => false,
            ],
            'added_by' => [
                'title' => 'Account ID which created this account',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'key_type' => self::FROM_INSIDE,
            ],
            'details_id' => [
                'title' => 'Account details ID (if available)',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'key_type' => self::FROM_INSIDE,
            ],
            'status' => [
                'title' => 'Account status',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'import_if_not_found' => false,
            ],
            'status_date' => [
                'title' => 'Account status change date',
                'type' => PHS_Params::T_DATE,
                'type_extra' => [ 'format' => PHS_Model::DATETIME_DB ],
                'default' => null,
                'key_type' => self::FROM_INSIDE,
            ],
            'level' => [
                'title' => 'Account level',
                'type' => PHS_Params::T_INT,
                'default' => 0,
                'import_if_not_found' => false,
            ],
            'deleted' => [
                'title' => 'Account deletion date',
                'type' => PHS_Params::T_DATE,
                'type_extra' => [ 'format' => PHS_Model::DATETIME_DB ],
                'default' => null,
                'key_type' => self::FROM_INSIDE,
            ],
            'last_pass_change' => [
                'title' => 'Date of last account update',
                'type' => PHS_Params::T_DATE,
                'type_extra' => [ 'format' => PHS_Model::DATETIME_DB ],
                'default' => null,
                'key_type' => self::FROM_INSIDE,
            ],
            'lastlog' => [
                'title' => 'Date of last login',
                'type' => PHS_Params::T_DATE,
                'type_extra' => [ 'format' => PHS_Model::DATETIME_DB ],
                'default' => null,
                'key_type' => self::FROM_INSIDE,
            ],
            'lastip' => [
                'title' => 'Last login IP',
                'type' => PHS_Params::T_NOHTML,
                'default' => '',
                'key_type' => self::FROM_INSIDE,
            ],
            'cdate' => [
                'title' => 'Date of account creation',
                'type' => PHS_Params::T_DATE,
                'type_extra' => [ 'format' => PHS_Model::DATETIME_DB ],
                'default' => null,
                'key_type' => self::FROM_INSIDE,
            ],
        ];
    }
}
