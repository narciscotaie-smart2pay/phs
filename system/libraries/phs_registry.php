<?php

namespace phs\libraries;

if( !defined( 'PHS_VERSION' ) )
    exit;

class PHS_Registry extends PHS_Language
{
    private static $data = array();

    // Array with variables set for current view only. General information will be set using self::set_data()
    protected $_context = array();

    public static function get_full_data()
    {
        return self::$data;
    }

    public function get_full_context()
    {
        return $this->_context;
    }

    public function get_context( $key )
    {
        if( array_key_exists( $key, $this->_context ) )
            return $this->_context[$key];

        return null;
    }

    public function set_context( $key, $val = null )
    {
        if( $val === null )
        {
            if( !is_array( $key ) )
                return false;

            foreach( $key as $kkey => $kval )
            {
                if( !is_scalar( $kkey ) )
                    continue;

                $this->_context[$kkey] = $kval;
            }
        }

        if( !is_scalar( $key ) )
            return false;

        $this->_context[$key] = $val;

        return true;
    }

    public static function get_data( $key )
    {
        if( array_key_exists( $key, self::$data ) )
            return self::$data[$key];

        return null;
    }

    public static function set_full_data( $arr, $merge = false )
    {
        if( !is_array( $arr ) )
            return false;

        if( empty( $merge ) )
            self::$data = $arr;
        else
            self::$data = self::merge_array_assoc( self::$data, $arr );

        return true;
    }

    public static function set_data( $key, $val = null )
    {
        if( $val === null )
        {
            if( !is_array( $key ) )
                return false;

            foreach( $key as $kkey => $kval )
            {
                if( !is_scalar( $kkey ) )
                    continue;

                self::$data[$kkey] = $kval;
            }

            return true;
        }

        if( !is_scalar( $key ) )
            return false;

        self::$data[$key] = $val;

        return true;
    }

    public static function validate_array( $arr, $default_arr )
    {
        if( empty( $default_arr ) or !is_array( $default_arr ) )
            return false;

        if( empty( $arr ) or !is_array( $arr ) )
            $arr = array();

        foreach( $default_arr as $key => $val )
        {
            if( !array_key_exists( $key, $arr ) )
                $arr[$key] = $val;
        }

        return $arr;
    }

    public static function merge_array_assoc( $arr1, $arr2 )
    {
        if( empty( $arr1 ) or !is_array( $arr1 ) )
            return $arr2;
        if( empty( $arr2 ) or !is_array( $arr2 ) )
            return $arr1;

        foreach( $arr2 as $key => $val )
        {
            $arr1[$key] = $val;
        }

        return $arr1;
    }

    public static function validate_array_recursive( $arr, $default_arr )
    {
        if( empty( $default_arr ) or !is_array( $default_arr ) )
            return false;

        if( empty( $arr ) or !is_array( $arr ) )
            $arr = array();

        foreach( $default_arr as $key => $val )
        {
            if( !array_key_exists( $key, $arr ) )
                $arr[$key] = $val;

            elseif( is_array( $val ) )
            {
                if( !is_array( $arr[$key] ) )
                    $arr[$key] = array();

                if( !empty( $val ) )
                    $arr[$key] = self::validate_array_recursive( $arr[$key], $val );
            }
        }

        return $arr;
    }

    public static function validate_array_to_new_array( $arr, $default_arr )
    {
        if( empty( $default_arr ) or !is_array( $default_arr ) )
            return false;

        if( empty( $arr ) or !is_array( $arr ) )
            $arr = array();

        $new_array = array();
        foreach( $default_arr as $key => $val )
        {
            if( !array_key_exists( $key, $arr ) )
                $new_array[$key] = $val;
            else
                $new_array[$key] = $arr[$key];
        }

        return $new_array;
    }

    public static function validate_array_to_new_array_recursive( $arr, $default_arr )
    {
        if( empty( $default_arr ) or !is_array( $default_arr ) )
            return false;

        if( empty( $arr ) or !is_array( $arr ) )
            $arr = array();

        $new_array = array();
        foreach( $default_arr as $key => $val )
        {
            if( !array_key_exists( $key, $arr ) )
                $new_array[$key] = $val;

            elseif( is_array( $val ) )
            {
                if( !is_array( $arr[$key] ) )
                {
                    $arr[$key] = array();
                    $new_array[$key] = array();
                }

                if( !empty( $val ) )
                    $new_array[$key] = self::validate_array_to_new_array_recursive( $arr[$key], $val );
            } else
                $new_array[$key] = $arr[$key];
        }

        return $new_array;
    }

    public static function array_merge_unique_values( $arr1, $arr2 )
    {
        if( empty( $arr1 ) or !is_array( $arr1 ) )
            $arr1 = array();
        if( empty( $arr2 ) or !is_array( $arr2 ) )
            $arr2 = array();

        $return_arr = array();
        foreach( $arr2 as $val )
        {
            if( !is_scalar( $val ) )
                continue;

            $return_arr[$val] = 1;
        }
        foreach( $arr1 as $val )
        {
            if( !is_scalar( $val ) )
                continue;

            $return_arr[$val] = 1;
        }

        return @array_keys( $return_arr );
    }
}

