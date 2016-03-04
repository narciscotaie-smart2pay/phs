<?php

namespace phs\plugins\captcha;

use phs\libraries\PHS_Error;
use \phs\PHS;
use \phs\PHS_session;
use \phs\libraries\PHS_Hooks;
use \phs\libraries\PHS_Plugin;
use \phs\system\core\views\PHS_View;

class PHS_Plugin_Captcha extends PHS_Plugin
{
    const ERR_TEMPLATE = 40000, ERR_RENDER = 40001, ERR_NOGD = 40002, ERR_IMAGE = 40003, ERR_LIBRARY = 40004;

    const OUTPUT_JPG = 1, OUTPUT_GIF = 2, OUTPUT_PNG = 3;

    const IMG_QUALITY = 95;

    const FONT_DIR = 'fonts';

    const SESSION_VAR = 'phs_image_code';

    /**
     * @return string Returns version of model
     */
    public function get_plugin_version()
    {
        return '1.0.0';
    }

    public function get_models()
    {
        return array();
    }

    /**
     * Override this function and return an array with default settings to be saved for current plugin
     * @return array
     */
    public function get_default_settings()
    {
        return array(
            'template' => array(
                'file' => 'captcha',
                'extra_paths' => array(
                    PHS::relative_path( $this->instance_plugin_templates_path() ) => PHS::relative_url( $this->instance_plugin_templates_www() ),
                ),
            ), // default template
            'font' => 'default.ttf',
            'characters_count' => 5,
            'image_format' => self::OUTPUT_PNG,
            'default_width' => 200,
            'default_height' => 50,
        );
    }

    public function indexes_to_vars()
    {
        return array( 'default_width' => 'w', 'default_height' => 'h' );
    }

    public function vars_to_indexes()
    {
        $return_arr = array();
        foreach( $this->indexes_to_vars() as $index => $var )
        {
            $return_arr[$var] = $index;
        }

        return $return_arr;
    }

    public function get_font_full_path( $font )
    {
        $font = make_sure_is_filename( $font );
        if( empty( $font )
         or !($dir_path = $this->instance_plugin_path())
         or !@is_dir( $dir_path.self::FONT_DIR )
         or !@file_exists( $dir_path.self::FONT_DIR.'/'.$font ) )
            return false;

        return $dir_path.self::FONT_DIR.'/'.$font;
    }

    public function check_captcha_code( $code )
    {
        $this->reset_error();

        if( !($settings_arr = $this->get_plugin_db_settings()) )
        {
            $this->set_error( self::ERR_TEMPLATE, self::_t( 'Couldn\'t load template from plugin settings.' ) );
            return false;
        }

        $settings_arr = self::validate_array_recursive( $settings_arr, $this->get_default_settings() );

        if( ($cimage_code = PHS_session::_g( self::SESSION_VAR )) === null )
            $cimage_code = '';

        $library_params = array();
        $library_params['full_class_name'] = '\\phs\\plugins\\captcha\\libraries\\PHS_image_code';
        $library_params['init_params'] = array(
            'cnumbers' => $settings_arr['characters_count'],
            'param_code' => $cimage_code,
            'img_type' => $settings_arr['image_format'],
        );
        $library_params['as_singleton'] = false;

        /** @var \phs\plugins\captcha\libraries\PHS_image_code $img_library */
        if( !($img_library = $this->load_library( 'phs_image_code', $library_params )) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_LIBRARY, self::_t( 'Error loading image captcha library.' ) );

            return false;
        }

        $code_valid = false;
        if( !empty( $code )
        and $img_library->check_input( $code ) )
        {
            $code_valid = true;
            if( $img_library->refresh_public_code() )
                $cimage_code = $img_library->get_public_code();
        } else
        {
            $img_library->regenerate_public_code();
            $cimage_code = $img_library->get_public_code();
        }

        PHS_session::_s( self::SESSION_VAR, $cimage_code );

        return $code_valid;
    }

    public function captcha_regeneration()
    {
        $this->reset_error();

        if( !($settings_arr = $this->get_plugin_db_settings()) )
        {
            $this->set_error( self::ERR_TEMPLATE, self::_t( 'Couldn\'t load template from plugin settings.' ) );
            return false;
        }

        $settings_arr = self::validate_array_recursive( $settings_arr, $this->get_default_settings() );

        if( ($cimage_code = PHS_session::_g( self::SESSION_VAR )) === null )
            $cimage_code = '';

        $library_params = array();
        $library_params['full_class_name'] = '\\phs\\plugins\\captcha\\libraries\\PHS_image_code';
        $library_params['init_params'] = array(
            'cnumbers' => $settings_arr['characters_count'],
            'param_code' => $cimage_code,
            'img_type' => $settings_arr['image_format'],
        );
        $library_params['as_singleton'] = false;

        /** @var \phs\plugins\captcha\libraries\PHS_image_code $img_library */
        if( !($img_library = $this->load_library( 'phs_image_code', $library_params )) )
        {
            if( !$this->has_error() )
                $this->set_error( self::ERR_LIBRARY, self::_t( 'Error loading image captcha library.' ) );

            return false;
        }

        $img_library->regenerate_public_code();
        $cimage_code = $img_library->get_public_code();

        PHS_session::_s( self::SESSION_VAR, $cimage_code );

        return true;
    }

    public function get_captcha_check_hook_args( $hook_args )
    {
        $this->reset_error();

        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_captcha_check_hook_args() );

        $hook_args['check_valid'] = true;
        if( empty( $hook_args['check_code'] )
         or !$this->check_captcha_code( $hook_args['check_code'] ) )
            $hook_args['check_valid'] = false;

        if( $this->has_error() )
            $hook_args['hook_errors'] = self::validate_array( $this->get_error(), PHS_Error::default_error_array() );

        return $hook_args;
    }

    public function captcha_regenerate_hook_args( $hook_args )
    {
        $this->reset_error();

        $hook_args = self::validate_array( $hook_args, PHS_Hooks::default_captcha_regeneration_hook_args() );

        if( !$this->captcha_regeneration() )
        {
            if( $this->has_error() )
                $hook_args['hook_errors'] = self::validate_array( $this->get_error(), PHS_Error::default_error_array() );
        }

        return $hook_args;
    }

    public function get_captcha_display_hook_args( $hook_args )
    {
        $this->reset_error();

        $hook_args = self::validate_array_recursive( $hook_args, PHS_Hooks::default_captcha_display_hook_args() );

        if( !($settings_arr = $this->get_plugin_db_settings())
         or empty( $settings_arr['template'] ) )
        {
            $this->set_error( self::ERR_TEMPLATE, self::_t( 'Couldn\'t load template from plugin settings.' ) );

            $hook_args['hook_errors'] = self::validate_array( $this->get_error(), PHS_Error::default_error_array() );

            return $hook_args;
        }

        $settings_arr = self::validate_array_recursive( $settings_arr, $this->get_default_settings() );

        $extra_paths = array();
        if( !empty( $settings_arr['template']['extra_paths'] ) and is_array( $settings_arr['template']['extra_paths'] ) )
        {
            foreach( $settings_arr['template']['extra_paths'] as $dir_path => $dir_www )
            {
                $extra_paths[PHS::from_relative_path( $dir_path )] = PHS::from_relative_url( $dir_www );
            }
        }

        $settings_arr['template']['extra_paths'] = $extra_paths;

        $hook_args['font'] = $settings_arr['font'];
        $hook_args['characters_count'] = $settings_arr['characters_count'];
        $hook_args['image_format'] = $settings_arr['image_format'];
        $hook_args['default_width'] = $settings_arr['default_width'];
        $hook_args['default_height'] = $settings_arr['default_height'];
        $hook_args['template'] = $settings_arr['template'];

        $view_params = array();
        $view_params['action_obj'] = false;
        $view_params['controller_obj'] = false;
        $view_params['plugin'] = $this->instance_plugin_name();
        $view_params['template_data'] = array(
            'hook_args' => $hook_args,
            'settings_arr' => $settings_arr,
        );

        if( !($view_obj = PHS_View::init_view( $settings_arr['template'], $view_params )) )
        {
            if( self::st_has_error() )
                $this->copy_static_error();

            $hook_args['hook_errors'] = self::validate_array( $this->get_error(), PHS_Error::default_error_array() );

            return $hook_args;
        }

        if( !($hook_args['captcha_buffer'] = $view_obj->render()) )
        {
            // Make sure buffer is a string
            $hook_args['captcha_buffer'] = '';

            if( $view_obj->has_error() )
                $this->copy_error( $view_obj );
            else
                $this->set_error( self::ERR_RENDER, self::_t( 'Error rendering template [%s].', $view_obj->get_template() ) );

            $hook_args['hook_errors'] = self::validate_array( $this->get_error(), PHS_Error::default_error_array() );

            return $hook_args;
        }

        return $hook_args;
    }
}
