<?php

namespace phs\libraries;

if( !defined( 'PHS_VERSION' ) )
    exit;

use \phs\PHS;

class PHS_Language extends PHS_Error
{
    /** @var PHS_Language_Container $lang_callable_obj */
    private static $lang_callable_obj = false;

    /**
     * Returns language class that handles translation tasks
     *
     * @return PHS_Language_Container
     */
    static function language_container()
    {
        if( empty( self::$lang_callable_obj ) )
            self::$lang_callable_obj = new PHS_Language_Container();

        return self::$lang_callable_obj;
    }

    /**
     * @return array Returns array of defined languages
     */
    public static function get_defined_languages()
    {
        return self::language_container()->get_defined_languages();
    }

    /**
     * @param string $key Language array key to be returned
     * @return mixed Returns currently selected language details from language array definition
     */
    public static function get_current_language_key( $key )
    {
        return self::language_container()->get_current_language_key( $key );
    }

    /**
     * @return string Returns currently selected language
     */
    public static function get_default_language()
    {
        return self::language_container()->get_default_language();
    }

    /**
     * @return string Returns currently selected language
     */
    public static function get_current_language()
    {
        return self::language_container()->get_current_language();
    }

    /**
     * @param string $lang Language to be set as current
     * @return string Returns currently selected language
     */
    public static function set_current_language( $lang )
    {
        return self::language_container()->set_current_language( $lang );
    }

    /**
     * @param string $lang Language to be set as default
     * @return string Returns default language
     */
    public static function set_default_language( $lang )
    {
        return self::language_container()->set_default_language( $lang );
    }

    /**
     * @param string $lang For which language to add files
     * @param string $files_arr Array of files to be added
     *
     * @return string Returns currently selected language
     */
    public static function add_language_files( $lang, $files_arr )
    {
        return self::language_container()->add_language_files( $lang, $files_arr );
    }

    /**
     * Define a language used by platform
     *
     * @param string $lang ISO 2 chars (lowercase) language code
     * @param array $lang_params Language details
     *
     * @return bool True if adding language was successful, false otherwise
     */
    public static function define_language( $lang, array $lang_params )
    {
        if( !self::language_container()->define_language( $lang, $lang_params ) )
        {
            self::st_copy_error( self::language_container() );
            return false;
        }

        return true;
    }

    /**
     * @param $index
     */
    public function _pte( $index, $ch = '"' )
    {
        return self::_e( $this->_pt( $index ), $ch );
    }

    /**
     * @param $index
     */
    public function _pt( $index )
    {
        /** @var PHS_Plugin|PHS_Library $this */
        if( (!($this instanceof PHS_Instantiable) and !($this instanceof PHS_Library))
         or !($plugin_obj = $this->get_plugin_instance()) )
            return self::_t( func_get_args() );

        $plugin_obj->include_plugin_language_files();

        if( !($result = @forward_static_call_array( array( '\phs\libraries\PHS_Language', '_t' ), func_get_args() )) )
            $result = '';

        return $result;
    }

    /**
     * @param string $index Language index
     *
     * @return mixed|string
     */
    public static function st_pt( $index )
    {
        if( !($called_class = get_called_class())
         or !($clean_class_name = ltrim( $called_class, '\\' ))
         or strtolower( substr( $clean_class_name, 0, 12 ) ) != 'phs\\plugins\\'
         or !($parts_arr = explode( '\\', $clean_class_name, 4 ))
         or empty( $parts_arr[2] )
         or !($plugin_obj = PHS::load_plugin( $parts_arr[2] )) )
            return self::_t( func_get_args() );

        $plugin_obj->include_plugin_language_files();

        if( !($result = @forward_static_call_array( array( '\phs\libraries\PHS_Language', '_t' ), func_get_args() )) )
            $result = '';

        return $result;
    }

    /**
     * Translate a specific text in currently selected language. This method receives a variable number of parameters in same way as sprintf works.
     * @param string $index Language index to be translated
     *
     * @return string Translated string
     */
    public static function _t( $index )
    {
        if( is_array( $index ) )
        {
            $arg_list = $index;
            $numargs = count( $arg_list );
            if( isset( $arg_list[0] ) )
                $index = $arg_list[0];
        } else
        {
            $numargs = func_num_args();
            $arg_list = func_get_args();
        }

        if( $numargs > 1 )
            @array_shift( $arg_list );
        else
            $arg_list = array();

        return self::language_container()->_t( $index, $arg_list );
    }

    /**
     * Translate a specific text in currently selected language then escape the resulting string.
     * This method receives a variable number of parameters in same way as sprintf works.
     *
     * @param string $index Language index to be translated
     * @param string $ch Escaping character
     *
     * @return string Translated and escaped string
     */
    public static function _te( $index, $ch = '"' )
    {
        return self::_e( self::_t( $index ), $ch );
    }

    /**
     * Escapes ' or " in string parameter (useful when displaying strings in html/javascript strings)
     *
     * @param string $str String to be escaped
     * @param string $ch Escaping character (' or ")
     */
    public static function _e( $str, $ch = '"' )
    {
        return str_replace( $ch, ($ch=='\''?'\\\'':'\\"'), $str );
    }

    /**
     * Translate a text into a specific language.
     * This method receives a variable number of parameters in same way as sprintf works.
     *
     * @param string $index Language index to be translated
     * @param string $lang ISO 2 chars (lowercase) language code
     *
     * @return string Translated text
     */
    public static function _tl( $index, $lang )
    {
        $numargs = func_num_args();
        $arg_list = func_get_args();

        if( $numargs > 2 )
        {
            @array_shift( $arg_list );
            @array_shift( $arg_list );
        } else
            $arg_list = array();

        return self::language_container()->_tl( $index, $lang, $arg_list );
    }
}

class PHS_Language_Container extends PHS_Error
{
    // We take error codes from 100000+ to let 1-99999 for custom defined constant errors
    const ERR_LANGUAGE_DEFINITION = 100000, ERR_LANGUAGE_LOAD = 100001, ERR_NOT_STRING = 100002;

    //! Fallback language in case we try to translate a text which is not defined in current language
    private static $DEFAULT_LANGUAGE = '';
    //! Current language
    private static $CURRENT_LANGUAGE = '';
    //! Contains defined language which can be used by the system. These are not necessary loaded in memory in order to optimize memory
    //! eg. $DEFINED_LANGUAGES['en'] = array(
    // 'title' => 'English (friendly name of language)',
    // 'files' => array( 'path_to_csv_file1', 'path_to_csv_file2' ),
    // 'browser_lang' => 'en-GB', // what should be sent to browser as language
    // 'flag_file' => 'en.png', // file name of language flag (used when displaying language chooser)
    // );
    private static $DEFINED_LANGUAGES = array();

    private static $LANGUAGE_INDEXES = array();

    private static $_LOADED_FILES = array();

    function __construct()
    {
        parent::__construct();
    }

    public static function st_get_default_language()
    {
        return self::$DEFAULT_LANGUAGE;
    }

    public static function st_get_current_language()
    {
        return self::$CURRENT_LANGUAGE;
    }

    public static function st_set_current_language( $lang )
    {
        if( !self::valid_language( $lang ) )
            return false;

        self::$CURRENT_LANGUAGE = $lang;
        return self::$CURRENT_LANGUAGE;
    }

    public static function st_set_default_language( $lang )
    {
        if( !self::valid_language( $lang ) )
            return false;

        self::$DEFAULT_LANGUAGE = $lang;
        return self::$DEFAULT_LANGUAGE;
    }

    public static function st_get_current_language_key( $key )
    {
        $clang = self::st_get_current_language();
        if( empty( $clang )
         or !is_scalar( $key )
         or empty( self::$DEFINED_LANGUAGES[$clang] ) or !is_array( self::$DEFINED_LANGUAGES[$clang] )
         or !array_key_exists( $key, self::$DEFINED_LANGUAGES[$clang] ) )
            return false;

        return self::$DEFINED_LANGUAGES[$clang][$key];
    }

    /**
     * Returns defined languages array (defined using self::define_language())
     *
     * @return array
     */
    public static function st_get_defined_languages()
    {
        return self::$DEFINED_LANGUAGES;
    }

    public function get_current_language_key( $key )
    {
        return self::st_get_current_language_key( $key );
    }

    public function get_defined_languages()
    {
        return self::st_get_defined_languages();
    }

    public function get_default_language()
    {
        return self::st_get_default_language();
    }

    public function get_current_language()
    {
        return self::st_get_current_language();
    }

    public function set_current_language( $lang )
    {
        return self::st_set_current_language( $lang );
    }

    public function set_default_language( $lang )
    {
        return self::st_set_default_language( $lang );
    }

    static function prepare_lang_index( $lang )
    {
        return strtolower( trim( $lang ) );
    }

    /**
     *
     * Reset all indexes loaded for provided language if $lang !== false or all loaded indexes if $lang === false
     *
     * @param string|bool $lang
     *
     * @return $this
     */
    function reset_language_indexes( $lang = false )
    {
        if( $lang === false )
        {
            self::$LANGUAGE_INDEXES = array();
            self::$_LOADED_FILES = array();
        } else
        {
            $lang = self::prepare_lang_index( $lang );
            if( isset( self::$LANGUAGE_INDEXES[$lang] ) )
                self::$LANGUAGE_INDEXES[$lang] = array();
            if( isset( self::$_LOADED_FILES[$lang] ) )
                self::$_LOADED_FILES[$lang] = array();
        }

        return $this;
    }

    /**
     * Tells if language $lang is a valid defined language in the system
     *
     * @param string $lang
     *
     * @return bool|string
     */
    static function valid_language( $lang )
    {
        $lang = self::prepare_lang_index( $lang );
        return (isset( self::$DEFINED_LANGUAGES[$lang] )?$lang:false);
    }

    /**
     * Tells if language $lang is loaded (files were parsed and added to indexes array)
     *
     * @param $lang
     *
     * @return bool|string
     */
    public static function language_loaded( $lang )
    {
        $lang = self::prepare_lang_index( $lang );
        return (isset( self::$LANGUAGE_INDEXES[$lang] )?$lang:false);
    }

    public static function get_default_language_structure()
    {
        return array(
            'title' => '',
            'dir' => '',
            'www' => '',
            'files' => array(),
            'browser_lang' => '',
            'browser_charset' => '',
            'flag_file' =>'',
        );
    }

    /**
     * Define a language used by platform
     *
     * @param string $lang ISO 2 chars (lowercase) language code
     * @param array $lang_params Language details
     *
     * @return bool True if adding language was successful, false otherwise
     */
    public function define_language( $lang, array $lang_params )
    {
        $this->reset_error();

        $lang = self::prepare_lang_index( $lang );
        if( empty( $lang )
         or empty( $lang_params ) or !is_array( $lang_params )
         or empty( $lang_params['title'] ) )
        {
            $this->set_error( self::ERR_LANGUAGE_DEFINITION, 'Please provide valid parameters for language definition.' );
            return false;
        }

        if( empty( self::$DEFINED_LANGUAGES[$lang] ) )
            self::$DEFINED_LANGUAGES[$lang] = array();

        self::$DEFINED_LANGUAGES[$lang]['title'] = trim( $lang_params['title'] );

        if( empty( self::$DEFINED_LANGUAGES[$lang]['files'] ) )
            self::$DEFINED_LANGUAGES[$lang]['files'] = array();

        if( !empty( $lang_params['files'] ) )
        {
            if( !$this->add_language_files( $lang, $lang_params['files'] ) )
                return false;
        }

        $default_language_structure = self::get_default_language_structure();
        foreach( $default_language_structure as $key => $def_value )
        {
            if( array_key_exists( $key, $lang_params ) )
                self::$DEFINED_LANGUAGES[$lang][$key] = $lang_params[$key];

            elseif( !array_key_exists( $key, self::$DEFINED_LANGUAGES[$lang] ) )
                self::$DEFINED_LANGUAGES[$lang][$key] = $def_value;
        }

        return true;
    }

    public function add_language_files( $lang, array $files_arr, $force_language_reload = false )
    {
        $this->reset_error();

        $lang = self::prepare_lang_index( $lang );
        if( empty( $lang )
         or !($lang = self::valid_language( $lang )) )
        {
            $this->set_error( self::ERR_LANGUAGE_LOAD, 'Language not defined.' );
            return false;
        }

        if( !is_array( $files_arr ) )
        {
            $this->set_error( self::ERR_LANGUAGE_DEFINITION, 'You should provide an array of files to be added to language ['.$lang.'].' );
            return false;
        }

        foreach( $files_arr as $lang_file )
        {
            if( empty( $lang_file )
                or !@file_exists( $lang_file ) or !@is_readable( $lang_file ) )
            {
                $this->set_error( self::ERR_LANGUAGE_DEFINITION, 'Language file ['.@basename( $lang_file ).'] for language ['.$lang.'] not found or not readable.' );
                return false;
            }
        }

        self::$DEFINED_LANGUAGES[$lang]['files'] = array_merge( self::$DEFINED_LANGUAGES[$lang]['files'], $files_arr );

        // if language was already loaded, reload all files to include newly added files
        if( $this->language_loaded( $lang ) )
            $this->load_language( $lang, $force_language_reload );

        return true;
    }

    /**
     * Returns language details as it was defined using self::define_language()
     *
     * @param string $lang
     *
     * @return bool|array
     */
    public static function get_defined_language( $lang )
    {
        if( !($lang = self::valid_language( $lang )) )
            return false;

        return self::$DEFINED_LANGUAGES[$lang];
    }

    /**
     * Loads provided CSV files in 'files' index of language definition array for language $lang
     *
     * @param string $lang ISO 2 chars (lowercase) language code
     *
     * @return bool True if loading was with success, false otherwise
     */
    public function load_language( $lang, $force = false )
    {
        $this->reset_error();

        if( !($lang = self::valid_language( $lang ))
         or !($lang_details = self::get_defined_language( $lang ))
         or empty( $lang_details['files'] ) or !is_array( $lang_details['files'] ) )
        {
            $this->set_error( self::ERR_LANGUAGE_LOAD, 'Language ['.$lang.'] not defined or has no files to be loaded.' );
            return false;
        }

        foreach( $lang_details['files'] as $file )
        {
            if( !$this->load_language_file( $file, $lang, $force ) )
                return false;
        }

        return true;
    }

    /**
     * Loads a specific CSV file for language $lang. This file is provided in 'files' index of language definition array for provided language
     *
     * @param string $file
     * @param string $lang
     *
     * @return bool
     */
    private function load_language_file( $file, $lang, $force = false )
    {
        if( !($lang = self::valid_language( $lang )) )
        {
            $this->set_error( self::ERR_LANGUAGE_LOAD, 'Language ['.$lang.'] not defined.' );
            return false;
        }

        if( empty( $force )
        and !empty( self::$_LOADED_FILES[$lang][$file] ) )
            return true;

        if( !($utf8_file = self::convert_to_utf8( $file )) )
        {
            $this->set_error( self::ERR_LANGUAGE_LOAD, 'Couldn\'t convert language file ['.@basename( $file ).'], language ['.$lang.'] to UTF-8 encoding.' );
            return false;
        }

        if( !@file_exists( $utf8_file ) or !is_readable( $utf8_file )
         or !($fil = @fopen( $utf8_file, 'r' )) )
        {
            $this->set_error( self::ERR_LANGUAGE_LOAD, 'File ['.@basename( $utf8_file ).'] doesn\'t exist or is not readable for language ['.$lang.'].' );
            return false;
        }

        if( function_exists( 'mb_internal_encoding' ) )
            @mb_internal_encoding( 'UTF-8' );

        $mb_substr_exists = false;
        if( function_exists( 'mb_substr' ) )
            $mb_substr_exists = true;

        while( ($buf = @fgets( $fil )) )
        {
            if( ($mb_substr_exists and mb_substr( ltrim( $buf ), 0, 1 ) == '#')
             or (!$mb_substr_exists and substr( ltrim( $buf ), 0, 1 ) == '#') )
                continue;

            $buf = rtrim( $buf, "\r\n" );

            if( !($csv_line = @str_getcsv( $buf, ',', '"', '\\' ))
             or !is_array( $csv_line )
             or count( $csv_line ) != 2 )
                continue;

            $index = $csv_line[0];
            $index_lang = $csv_line[1];

            self::$LANGUAGE_INDEXES[$lang][$index] = $index_lang;
        }

        @fclose( $fil );

        if( empty( self::$_LOADED_FILES[$lang] ) )
            self::$_LOADED_FILES[$lang] = array();

        self::$_LOADED_FILES[$lang][$file] = true;

       return true;
    }

    /**
     * Given an absolute file path, this method will return file name which should contain UTF-8 encoded content of original file
     *
     * @param string $file ablsolute path of file which should be converted to UTF-8 encoding
     *
     * @return string Resulting file name which will hold UTF-8 encoded content of original file
     */
    static function get_utf8_file_name( $file )
    {
        $path_info = @pathinfo( $file );
        return $path_info['dirname'].'/'.$path_info['filename'].'-utf8.'.$path_info['extension'];
    }

    /**
     * Converts a given file to a UTF-8 encoded content.
     *
     * @param string $file ablsolute path of file which should be converted to UTF-8 encoding
     * @param bool|array $params Method parameters allows to overwrite UTF-8 encoded file name
     *
     * @return bool|string Returns absolute path of UTF-8 encoded file
     */
    static function convert_to_utf8( $file, $params = false )
    {
        if( empty( $file ) or !@file_exists( $file ) )
            return false;

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['utf8_file'] ) )
            $params['utf8_file'] = self::get_utf8_file_name( $file );

        ob_start();
        if( !($file_bin = @system( 'which file' ))
         or !($iconv_bin = @system( 'which iconv' )) )
        {
            // we don't have required files to convert csv to utf8... check if we have a utf8 language file...
            if( @file_exists( $params['utf8_file'] ) )
                return $params['utf8_file'];

            ob_end_clean();

            return false;
        }

        if( !($file_mime = @system( $file_bin.' --mime-encoding '.escapeshellarg( $file ) )) )
            return false;

        $file_mime = str_replace( $file.': ', '', $file_mime );

        if( !in_array( strtolower( $file_mime ), array( 'utf8', 'utf-8' ) ) )
        {
            if( @system( $iconv_bin.' -f ' . escapeshellarg( $file_mime ) . ' -t utf-8 ' . escapeshellarg( $file ) . ' > ' . escapeshellarg( $params['utf8_file'] ) ) === false
             or !@file_exists( $params['utf8_file'] ) )
            {
                ob_end_clean();

                return false;
            }
        } else
            @copy( $file, $params['utf8_file'] );
        ob_end_clean();

        if( !@file_exists( $params['utf8_file'] ) )
            return false;

        return $params['utf8_file'];
    }

    /**
     * Translate text $index. If $index contains %XX format (@see vsprintf), arguments will be passed in $args parameter.
     *
     * @param string $index Language index to be translated
     * @param array $args Array of arguments to be used to populate $index (@see vsprintf)
     *
     * @return string Translated string
     */
    public function _t( $index, array $args = array() )
    {
        if( empty( $args ) or !is_array( $args ) )
            $args = array();

        return $this->_tl( $index, self::get_current_language(), $args );
    }

    /**
     * Translate text $index for language $lang. If $index contains %XX format (@see vsprintf), arguments will be passed in $args parameter.
     *
     * @param string $index Language index to be translated
     * @param string $lang
     * @param array $args Array of arguments to be used to populate $index (@see vsprintf)
     *
     * @return string
     */
    public function _tl( $index, $lang, array $args = array() )
    {
        if( !is_string( $index ) )
            return 'Language index is not a string ('.gettype( $index ).' provided)';

        if( !($lang = self::valid_language( $lang )) )
            return $index;

        if( empty( $args ) or !is_array( $args ) )
            $args = array();

        if( !self::language_loaded( $lang ) )
        {
            if( !$this->load_language( $lang ) )
            {
                if( ($error_arr = $this->get_error()) and !empty( $error_arr['error_simple_msg'] ) )
                    $error_msg = $error_arr['error_simple_msg'];
                else
                    $error_msg = 'Error loading language [' . $lang . ']';

                return $error_msg;
            }
        }

        if( isset( self::$LANGUAGE_INDEXES[$lang][$index] ) )
            $working_index = self::$LANGUAGE_INDEXES[$lang][$index];
        else
            $working_index = $index;

        if( !empty( $args ) )
        {
            // we should replace some %s...
            if( ($result = @vsprintf( $working_index, $args )) !== false )
                return $result;

            return $working_index.' ['.count( $args ).' args]';
        }

        return $working_index;
    }

}
