<?php
    /** @var \phs\system\core\views\PHS_View $this */
    /** @var array $_VIEW_CONTEXT */

    use \phs\PHS;
    use \phs\libraries\PHS_Action;
    use \phs\libraries\PHS_Language;

    if( !empty( $_VIEW_CONTEXT['action_result'] ) )
        $action_result = $this::validate_array( $_VIEW_CONTEXT['action_result'], PHS_Action::default_action_result() );
    else
        $action_result = PHS_Action::default_action_result();
?><!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo PHS_Language::get_current_language_key( 'browser_lang' )?>" lang="<?php echo PHS_Language::get_current_language_key( 'browser_lang' )?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo PHS_Language::get_current_language_key( 'browser_charset' )?>" />
    <meta name="HandheldFriendly"   content="true" />
    <meta name="MobileOptimized"    content="320">
    <meta name="viewport"       content="user-scalable=no, width=device-width, initial-scale=1.0" />
    <meta name="title"          content="<?php echo $action_result['page_title']?>" />
    <meta name="description"    content="<?php echo $action_result['page_description']?>" />
    <meta name="keywords"       content="<?php echo $action_result['page_keywords']?>" />
    <meta name="copyright"      content="Copyright <?php echo date( 'Y' ).' - '.PHS_SITE_NAME?>. All Right Reserved." />
    <meta name="author"         content="PHS Framework" />
    <meta name="revisit-after"  content="1 days" />

    <link href="<?php echo $this->get_resource_url( 'images/favicon.png' )?>" rel="shortcut icon" />

    <link href="<?php echo $this->get_resource_url( 'fileuploader.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'jquery-ui.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'jquery-ui.theme.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'jquery.checkbox.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'jquery.multiselect.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/grid.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/animate.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/responsive.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'font-awesome/css/font-awesome.min.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/lightbox.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/extra.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/style.css' )?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo $this->get_resource_url( 'css/style-colors.css' )?>" rel="stylesheet" type="text/css" />

    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/jquery.js' )?>"></script>
    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/jquery-ui.js' )?>"></script>
    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/jquery.validate.js' )?>"></script>
    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/jquery.checkbox.js' )?>"></script>
    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/jquery.multiselect.js' )?>"></script>

    <script  src="<?php echo $this->get_resource_url( 'js/jquery.placeholder.min.js' )?>"></script>
    <script  src="<?php echo $this->get_resource_url( 'js/include.js' )?>" ></script>

    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/fileuploader.js' )?>"></script>
    <?php
        if( ($jq_datepicker_lang_url = $this->get_resource_url( 'js/jquery.ui.datepicker-'.PHS_Language::get_current_language().'.js' )) )
        {
            ?><script type="text/javascript" src="<?php echo $jq_datepicker_lang_url?>"></script><?php
        }
    ?>
    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/jsen.js.php' )?>"></script>
    <script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/base.js.php' )?>"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            $('input:checkbox[rel="skin_chck_big"]').checkbox({cls:'jqcheckbox-big', empty:'<?php echo $this->get_resource_url( 'images/empty.png' )?>'});
            $('input:checkbox[rel="skin_chck_small"]').checkbox({cls:'jqcheckbox-small', empty:'<?php echo $this->get_resource_url( 'images/empty.png' )?>'});
            $('input:checkbox[rel="skin_checkbox"]').checkbox({cls:'jqcheckbox-checkbox', empty:'<?php echo $this->get_resource_url( 'images/empty.png' )?>'});
            $('input:radio[rel="skin_radio"]').checkbox({cls:'jqcheckbox-radio', empty:'<?php echo $this->get_resource_url( 'images/empty.png' )?>'});
            $('select[rel="skin_multiple"]').multiselect();
            $('select[rel="skin_single"]').multiselect({header: false, multiple: false, selectedList: 1 });

            $.datepicker.setDefaults( $.datepicker.regional["<?php echo PHS_Language::get_current_language()?>"] );

            $('.submit-protection').on('click', function( event ){

                var form_obj = $(this).parents('form:first');
                if( form_obj
                    && typeof document.createElement( 'input' ).checkValidity == 'function'
                    && !form_obj.checkValidity() )
                    return;

                var msg = $( this ).data( 'protectionTitle' );
                if( typeof msg == 'undefined' || !msg )
                    msg = '';

                show_submit_protection( msg );
            });
        });
    </script>

    <script type="text/javascript">
        function open_login_menu_pane()
        {
            $('#login_popup').slideToggle();
        }
        function open_right_menu_pane()
        {
            $('#menu-right-pane' ).fadeToggle();
            $('#menu-left-pane' ).hide();
        }
        function open_left_menu_pane()
        {
            $('#menu-right-pane' ).hide();
            $('#menu-left-pane' ).fadeToggle();
        }
        function close_menu_panes()
        {
            $('#menu-right-pane' ).hide();
            $('#menu-left-pane' ).hide();
        }
    </script>

    <title><?php echo $action_result['page_title']?></title>
    <?php echo $action_result['page_in_header']?>
</head>

<body <?php echo $action_result['page_body_extra_tags']?>>
<div id="main_submit_protection" style="display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; z-index: 10000;">
    <div style="position: relative; width: 100%; height: 100%;">
        <div style="position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; background: #333; opacity: 0.5; filter:alpha(opacity=50)"></div>
        <div style="position: absolute; top: 0px; left: 0px; width: 100%; height: 100%;">
            <div id="protection-wrapper" style="position: fixed; display: table; margin: 0px auto; margin-top: 50px; width: 100%">
                <div style="margin: 0px auto; display: table;">

                    <div id="main_submit_protection_loading_content" style="margin: 20% auto 0 auto; width:80%; background-color: white;border: 2px solid lightgrey; text-align: center; padding: 40px;">
                        <div class="ajax-loader" title="<?php echo $this::_te( 'Loading...' )?>"></div>
                        <p style="margin: 20px auto;" id="main_submit_protection_message"><?php echo $this::_t( 'Please wait.' )?></p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<div id="container">
    <!-- BEGIN: page_header -->
    <div id="menu-left-pane" class="menu-pane">
        <div class="main-menu-pane-close-button" style="float: right; "><a href="javascript:void()" onclick="close_menu_panes()" onfocus="this.blur();" class="fa fa-times"></a></div>
        <div class="clearfix"></div>

        <ul>
            <!-- BEGIN: guest_pane_menu -->
            <li><a href="<?php echo PHS::url()?>"><?php echo $this::_t( 'Home' )?></a></li>
            <li><a href="<?php echo PHS::url( array( 'p' => 'contact' ) )?>"><?php echo $this::_t( 'Contact Us' )?></a></li>
            <!-- END: guest_pane_menu -->

            <!-- BEGIN: user_pane_menu -->

            <!-- END: user_pane_menu -->
        </ul>

    </div>
    <div class="clearfix"></div>

    <div id="menu-right-pane" class="menu-pane">
        <div class="main-menu-pane-close-button" style="float: left; "><a href="javascript:void()" onclick="close_menu_panes()" onfocus="this.blur();" class="fa fa-times"></a></div>
        <div class="clearfix"></div>

        <!-- BEGIN: admin_link -->
        <li><a href="<?php echo PHS::url( array( 'p' => 'admin' ) )?>"><?php echo $this::_t( 'Admin Menu' )?></a></li>
        <!-- END: admin_link -->

        <!-- BEGIN: quick_profile -->
        <li><span><?php echo $this::_t( 'Hello %s', 'user' )?></span></li>
        <li><a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'edit_profile' ) )?>"><?php echo $this::_t( 'Edit Profile' )?></a></li>
        <li><a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'logout' ) )?>"><?php echo $this::_t( 'Logout' )?></a></li>
        <!-- END: quick_profile -->

        <!-- BEGIN: quick_login -->
        <li><a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'register' ) )?>"><?php echo $this::_t( 'Register' )?></a></li>
        <li><a href="javascript:void(0);" onclick="open_login_menu_pane();this.blur();"><?php echo $this::_t( 'Login' )?><div style="float:right;" class="fa fa-arrow-down"></div></a>
            <div id="login_popup" style="display: none; padding: 10px;">
                <form id="menu_pane_login_frm" name="menu_pane_login_frm" method="post" action="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'login' ) )?>" class="wpcf7">
                    <div class="menu-pane-form-line">
                        <label><?php echo $this::_t( 'Username' )?></label>
                        <input id="nick" class="wpcf7-text" type="text" name="nick" required />
                    </div>
                    <div class="menu-pane-form-line">
                        <label><?php echo $this::_t( 'Password' )?></label>
                        <input id="pass" class="wpcf7-text" type="password" name="pass" required />
                    </div>
                    <div class="menu-pane-form-line">
                        <div style="float: left;"><a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'forgot' ) )?>"><?php echo $this::_t( 'Forgot Password' )?></a></div>
                        <div style="float: right; right: 10px;"><input type="submit" value="<?php echo $this::_t( 'Login' )?>" name="submit" /></div>
                        <div class="clearfix"></div>
                    </div>
                </form>
            </div>
            <div class="clearfix"></div>
        </li>
        <!-- END: quick_login -->

        <!-- BEGIN: pane_manage_users -->
        <li><span>{LANG: MT_MANAGE_USERS}</span><!-- BEGIN: pane_add_users -->
            <ul>
                <li><a href="{PAGE_LINKS.comp_adduser}" onfocus="this.blur();">{LANG: MT_MANAGE_USERS_ADD}</a></li>
                <li><a href="{PAGE_LINKS.comp_listusers}" onfocus="this.blur();">{LANG: MT_MANAGE_USERS_MAN}</a></li>
            </ul>
            <!-- END: pane_add_users -->
        </li>
        <!-- END: pane_manage_users -->

        <li><a href="{PAGE_LINKS.contact_page}" onfocus="this.blur();"><?php echo $this::_t( 'Contact Us' )?></a></li>

        <!-- BEGIN: language_selection -->
        <?php

        if( ($defined_languages = PHS_Language::get_defined_languages())
        and count( $defined_languages ) > 1 )
        {
            if( !($current_language = PHS_Language::get_current_language())
             or empty( $defined_languages[$current_language] ) )
                $current_language = PHS_Language::get_default_language();

            ?>
            <li><span><?php echo $this::_t( 'Choose language' )?></span>
                <ul>
                <!-- BEGIN: language_item -->
                <?php
                foreach( $defined_languages as $lang => $lang_details )
                {
                    $language_flag = '';
                    if( !empty( $lang_details['flag_file'] ) and !empty( $lang_details['dir'] ) and !empty( $lang_details['www'] )
                    and @file_exists( $lang_details['dir'].$lang_details['flag_file'] ) )
                        $language_flag = '<span style="margin: 0 5px;"><img src="'.$lang_details['www'].$lang_details['flag_file'].'" /></span> ';

                    $language_link = 'javascript:alert( "In work..." )';

                    ?>
                    <li><a href="<?php echo $language_link?>"><?php echo $language_flag.$lang_details['title']?></a></li>
                    <?php
                }
                ?>
                <!-- END: language_item -->
                </ul>
            </li>
            <?php
        }
        ?>
        <!-- END: language_selection -->

    </div>
    <div class="clearfix"></div>

    <header id="header">
        <div id="header_content">
            <div id="logo">
                <a href="<?php echo PHS::url()?>"><img src="<?php echo $this->get_resource_url( 'images/logo.png' )?>" alt="<?php echo PHS_SITE_NAME?>" title="<?php echo PHS_SITE_NAME?>" /></a>
                <div class="clearfix"></div>
            </div>

            <div id="menu">
                <nav>
                    <ul>
                        <!-- BEGIN: junk -->
                        <li class="main-menu-placeholder"><a href="javascript:void(0)" onclick="open_left_menu_pane()" onfocus="this.blur();" class="fa fa-bars main-menu-icon"></a></li>
                        <!-- END: junk -->

                        <li><a href="<?php echo PHS::url()?>" onfocus="this.blur();"><?php echo $this::_t( 'Home' )?></a></li>

                        <!-- BEGIN: guest_menu -->
                        <li><a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'register' ) )?>" onfocus="this.blur();"><?php echo $this::_t( 'Register' )?></a></li>
                        <li><a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'login' ) )?>" onfocus="this.blur();"><?php echo $this::_t( 'Login' )?></a></li>
                        <!-- END: guest_menu -->

                        <!-- BEGIN: user_menu -->
                        <!-- END: user_menu -->

                    </ul>
                </nav>
                <div id="user_info">
                    <nav>
                        <ul>
                            <li class="main-menu-placeholder"><a href="javascript:void(0)" onclick="open_right_menu_pane()" onfocus="this.blur();" class="fa fa-user main-menu-icon"></a></li>

                            <!-- BEGIN: cart_in_menu -->
                            <li class="main-menu-placeholder" id="cart-menu-item"><a href="{PAGE_LINKS.cart_page}" class="fa fa-shopping-cart main-menu-icon"><span id="main_cart_count">0</span></a></li>
                            <!-- END: cart_in_menu -->

                        </ul>
                    </nav>
                </div>
            </div>

            <div class="clearfix"></div>
        </div>
    </header>
    <div class="clearfix"></div>
    <!-- END: page_header -->

    <div id="content"><?php echo $action_result['buffer']?></div>
    <div class="clearfix" style="margin-bottom: 10px;"></div>

    <footer id="footer">
        <!-- BEGIN: page_footer -->
        <div id="footer_content">
            <div class="footerlinks">
                <a href="<?php echo PHS::url( array( 'p' => 'contact' ) )?>" ><?php echo $this::_t( 'Contact Us' )?></a> |
                <a href="<?php echo PHS::url( array( 'a' => 'tandc' ) )?>" ><?php echo $this::_t( 'Terms and Conditions' )?></a>
            </div>
            <div class="clearfix"></div>
            <?php
            $debug_str = '';
            if( PHS::st_debugging_mode()
            and ($debug_data = PHS::platform_debug_data()) )
            {
                $debug_str = ' | '.$debug_data['db_queries_count'].' queries, '.
                             ' bootstrap: '.number_format( $debug_data['bootstrap_time'], 6, '.', '' ).'s, '.
                             ' running: '.number_format( $debug_data['running_time'], 6, '.', '' ).'s';
            }
            ?>
            <div style="float: right"><?php echo PHS_SITE_NAME?> &copy; <?php echo date( 'Y' ).' '.$this::_t( 'All rights reserved.' ).$debug_str?> &nbsp;</div>
        </div>
        <!-- END: page_footer -->
    </footer>
    <div class="clearfix"></div>

</div>
<script type="text/javascript" src="<?php echo $this->get_resource_url( 'js/lightbox.js' )?>"></script>
</body>
</html>
