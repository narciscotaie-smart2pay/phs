<?php
namespace phs\libraries;

use \phs\PHS;
use \phs\libraries\PHS_Controller;
use phs\PHS_Scope;
use \phs\system\core\views\PHS_View;

abstract class PHS_Action extends PHS_Signal_and_slot
{
    const SIGNAL_ACTION_BEFORE_RUN = 'action_before_run', SIGNAL_ACTION_AFTER_RUN = 'action_after_run';

    const ERR_CONTROLLER_INSTANCE = 30000, ERR_RUN_ACTION = 30001, ERR_RENDER = 30002;

    /** @var PHS_Controller */
    private $_controller_obj = null;

    /** @var array|null */
    private $_action_result = null;

    /**
     * @return bool|string Returns buffer which should be displayed as result of request or false on an error
     */
    abstract public function execute();

    public function __construct( $instance_details = false )
    {
        parent::__construct( $instance_details );

        $this->define_signal( self::SIGNAL_ACTION_BEFORE_RUN, array(
            'action_obj' => $this,
            'controller_obj' => $this->_controller_obj,
        ) );
        $this->define_signal( self::SIGNAL_ACTION_AFTER_RUN, array(
            'action_obj' => $this,
            'controller_obj' => $this->_controller_obj,
        ) );
    }

    protected function instance_type()
    {
        return self::INSTANCE_TYPE_ACTION;
    }

    public function init_view( $template, $theme = false, $view_class = false, $plugin = null )
    {
        if( $plugin === null )
            $plugin = $this->instance_plugin_name();

        if( !($view_obj = PHS::load_view( $view_class, $plugin )) )
        {
            $this->copy_static_error();
            return false;
        }

        if( !$view_obj->set_action( $this )
         or !$view_obj->set_controller( $this->get_controller() )
         or !$view_obj->set_theme( $theme )
         or !$view_obj->set_template( $template )
        )
        {
            $this->copy_error( $view_obj );
            return false;
        }

        return $view_obj;
    }

    static function default_action_result()
    {
        return array(
            'buffer' => '',
            'redirect_to_url' => '', // any URLs that we should redirect to (we might have to do javascript redirect or header redirect)
            'page_template' => 'template_main', // if empty, scope template will be used...

            // page related variables
            'page_title' => '', // title of current page
            'page_description' => '',
            'page_keywords' => '',
            // anything that is required in head tag
            'page_in_header' => '',
            // anything that is required as attributes to body tag
            'page_body_extra_tags' => '',

            'scope' => PHS_Scope::default_scope(),
        );
    }

    public function set_action_defaults()
    {
        $this->_action_result = self::default_action_result();
    }

    /**
     * @return array|null
     */
    public function get_action_result()
    {
        return $this->_action_result;
    }

    public function set_action_result( $result )
    {
        $this->_action_result = self::validate_array( $result, self::default_action_result() );
        return $this->_action_result;
    }

    final public function quick_render_template( $template, $template_data = false )
    {
        $view_params = array();
        $view_params['action_obj'] = $this;
        $view_params['controller_obj'] = $this->get_controller();
        $view_params['plugin'] = $this->instance_plugin_name();
        $view_params['template_data'] = $template_data;

        if( !($view_obj = PHS_View::init_view( $template, $view_params )) )
        {
            if( self::st_has_error() )
                $this->copy_static_error();

            return false;
        }

        $action_result = self::default_action_result();

        if( !($action_result['buffer'] = $view_obj->render()) )
        {
            if( $view_obj->has_error() )
                $this->copy_error( $view_obj );
            else
                $this->set_error( self::ERR_RENDER, self::_t( 'Error rendering template [%s].', $template ) );

            return false;
        }

        return $action_result;
    }

    /**
     * @return array|bool|null
     */
    final public function run_action()
    {
        PHS::running_action( $this );

        if( !$this->instance_is_core()
        and (!($plugin_instance = $this->get_plugin_instance())
                or !$plugin_instance->plugin_active()) )
        {
            $this->set_error( self::ERR_RUN_ACTION, self::_t( 'Unknown or not active action.' ) );
            return false;
        }

        $this->set_action_defaults();

        $default_result = self::default_action_result();

        if( ($signal_result = $this->signal_trigger( self::SIGNAL_ACTION_BEFORE_RUN, array(
                    'controller_obj' => $this->_controller_obj,
                ) )) )
        {
            if( !empty( $signal_result['stop_process'] ) )
            {
                if( $signal_result['replace_result'] !== null )
                {
                    $this->set_action_result( self::validate_array( $signal_result['replace_result'], $default_result ) );
                    return $this->get_action_result();
                }
            }
        }

        PHS::trigger_hooks( PHS_Hooks::H_BEFORE_ACTION_EXECUTE, array(
            'action' => $this,
        ) );

        self::st_reset_error();

        if( !($action_result = $this->execute()) )
            return false;

        $this->set_action_result( $action_result );

        if( ($signal_result = $this->signal_trigger( self::SIGNAL_ACTION_AFTER_RUN, array(
            'controller_obj' => $this->_controller_obj,
        ) )) )
        {
            if( !empty( $signal_result['stop_process'] ) )
            {
                if( $signal_result['replace_result'] !== null )
                {
                    $this->set_action_result( self::validate_array( $signal_result['replace_result'], $default_result ) );
                    return $this->get_action_result();
                }
            }
        }

        PHS::trigger_hooks( PHS_Hooks::H_AFTER_ACTION_EXECUTE, array(
            'action' => $this,
        ) );

        return $this->get_action_result();
    }

    public function set_controller( PHS_Controller $controller_obj )
    {
        if( !($controller_obj instanceof PHS_Controller) )
        {
            self::st_set_error( self::ERR_CONTROLLER_INSTANCE, self::_t( 'Controller doesn\'t appear to be a PHS instance.' ) );
            return false;
        }

        $this->_controller_obj = $controller_obj;

        return true;
    }

    public function get_controller()
    {
        return $this->_controller_obj;
    }

}
