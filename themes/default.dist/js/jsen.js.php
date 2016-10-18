<?php

// /version 1.25

    include( '../../../main.php' );

    header( 'Content-type: text/javascript' );

    use \phs\PHS;
    use \phs\PHS_ajax;
    use \phs\libraries\PHS_Language;
?>
if( typeof( PHS_JSEN ) != "undefined" || !PHS_JSEN )
{
    var PHS_JSEN =
    {
        debugging_mode: <?php echo (PHS::st_debugging_mode()?'true':'false')?>,

        version: 1.25,

        // Base URL
        baseUrl : "<?php echo PHS::get_base_url()?>",

        default_action_13 : ".def-link",

        dialogs_prefix: "PHS_JSENd",

        dialogs_options: [],

        keys : function( o )
        {
            var keys = [];
            for( var i in o )
                if( o.hasOwnProperty( i ) )
                {
                    keys.push( i );
                }

            return keys;
        },

        in_array : function( needle, haystack )
        {
            var length = haystack.length;
            for( var i = 0; i < length; i++ )
            {
                if( haystack[i] == needle )
                    return true;
            }

            return false;
        },

        js_messages_hide_all: function()
        {
            PHS_JSEN.js_messages_hide( "success" );
            PHS_JSEN.js_messages_hide( "warning" );
            PHS_JSEN.js_messages_hide( "error" );
        },

        js_messages_hide: function( type )
        {
            var message_box = $("#phs_ajax_" + type + "_box");
            if( message_box )
            {
                message_box.find( ".dismissible" ).html( "" );
                message_box.hide();
            }
        },

        js_messages: function( messages_arr, type )
        {
            if( typeof messages_arr == "undefined" || !messages_arr
             || typeof messages_arr.length == "undefined" || !messages_arr.length )
                return;

            var message_box = $("#phs_ajax_" + type + "_box");
            if( message_box )
            {
                for( var i = 0; i < messages_arr.length; i++ )
                    message_box.find( ".dismissible" ).append( "<p>" + messages_arr[i] + "</p>" );
                message_box.show();
            }
        },

        do_autocomplete: function( container, o )
        {
            var defaults = {
                url : '',
                autocomplete_obj : {
                    minLength: 1,
                    select: false
                },
                ajax_options : {}
            };

            var options = $.extend( {}, defaults, o );

            if( typeof o.autocomplete_obj != "undefined" )
                options.autocomplete_obj = $.extend( {}, defaults.autocomplete_obj, o.autocomplete_obj );
            if( typeof o.ajax_options != "undefined" )
                options.ajax_options = $.extend( {}, defaults.ajax_options, o.ajax_options );

            var container_obj = false;
            if( typeof container == "string" )
                container_obj = $(container);
            else if( typeof container == "object" )
                container_obj = container;

            if( !container_obj )
            {
                alert( "<?php echo PHS_Language::_te( 'Couldn\'t obtain jQuery object for autocomplete field.' )?>" );
                return false;
            }

            if( !options.url )
            {
                PHS_JSEN.js_messages( [ "<?php echo PHS_Language::_te( 'URL not provided for autocomplete field.' )?>" ], "error" );
                return container_obj.autocomplete();
            }

            return container_obj.autocomplete( $.extend( {}, {
                source: function( request, response )
                {
                    PHS_JSEN.do_ajax( options.url, $.extend( {},
                    {
                        url_data: {
                            term: request.term
                        },
                        data_type: "json",
                        onsuccess: function( data, status, ajax_obj )
                        {
                            response( data );
                        }
                    }, options.ajax_options ) );
                }
            }, options.autocomplete_obj ) );
        },

        do_ajax: function( url, o )
        {
            var defaults = {
                cache_response    : false,
                method            : "GET",
                url_data          : "",
                data_type         : "html",
                async             : true,
                full_buffer       : false,

                onfailed          : null,
                onsuccess         : null
            };

            var options = $.extend( {}, defaults, o );

            var fb_new_url = PHS_JSEN.addURLParameter( url, '<?php echo PHS_ajax::PARAM_FB_KEY?>', (options.full_buffer?1:0) );

            var ajax_parameters_obj = {
                type: options.method,
                url: fb_new_url,
                data: options.url_data,
                dataType: options.data_type,
                cache: options.cache_response,
                async: options.async,

                success: function( data, status, ajax_obj )
                {
                    var result_response = false;
                    if( !options.full_buffer )
                    {
                        if( typeof data.response == 'undefined' || !data.response )
                            data.response = false;

                        result_response = data.response;
                    } else
                        result_response = data;

                    if( options.onsuccess )
                    {
                        var onsuccess_result = null;
                        if( jQuery.isFunction( options.onsuccess ) )
                            onsuccess_result = options.onsuccess( result_response, status, ajax_obj, data );
                        else if( typeof options.onsuccess == "string" )
                            onsuccess_result = eval( options.onsuccess );

                        if( typeof onsuccess_result == "object"
                         && onsuccess_result )
                            data = onsuccess_result;
                    }

                    if( data && typeof data.redirect_to_url != 'undefined' && data.redirect_to_url )
                    {
                        document.location = data.redirect_to_url;
                        return;
                    }

                    if( data && typeof data.status != 'undefined' && data.status )
                    {
                        if( typeof data.status.success_messages != 'undefined' && data.status.success_messages )
                            PHS_JSEN.js_messages( data.status.success_messages, "success" );
                        if( typeof data.status.warning_messages != 'undefined' && data.status.warning_messages )
                            PHS_JSEN.js_messages( data.status.warning_messages, "warning" );
                        if( typeof data.status.error_messages != 'undefined' && data.status.error_messages )
                            PHS_JSEN.js_messages( data.status.error_messages, "error" );
                    }
                },

                error: function( ajax_obj, status, error_exception ) {

                    if( options.onfailed )
                    {
                        if( jQuery.isFunction( options.onfailed ) )
                            options.onfailed( ajax_obj, status, error_exception );
                        else if( typeof options.onfailed == "string" )
                            eval( options.onfailed );
                    }
                }
            };

            return $.ajax( ajax_parameters_obj );
        },

        dialogErrorsDivsIds : [],
        dialogErrorsDivs : 0,
        dialogErrorsClose : function ()
        {
            var container_obj = null;
            for( var i = 0; i < this.dialogErrorsDivsIds.length; i++ )
            {
                container_obj = $(this.dialogErrorsDivsIds[i]);
                if( container_obj )
                    container_obj.remove();
            }
        },

        removeURLParameter: function ( url, parameter )
        {
            //prefer to use l.search if you have a location/link object
            var urlparts= url.split('?');

            if( urlparts.length < 2 )
                return url;

            var prefix = encodeURIComponent( parameter ) + '=';
            var parts = urlparts[1].split( /[&;]/g );

            //reverse iteration as may be destructive
            for ( var i = parts.length; i-- > 0;) {
                //idiom for string.startsWith
                if ( parts[i].lastIndexOf( prefix, 0 ) !== -1 )
                {
                    parts.splice(i, 1);
                }
            }

            url = urlparts[0]+'?'+parts.join('&');

            return url;
        },

        addURLParameter: function ( url, key, value )
        {
            var urlparts= url.split( "?" );
            key = encodeURI( key );
            value = encodeURI( value );

            if( urlparts.length < 2 )
                return url + "?" + key + "=" + value;

            var parts = urlparts[1].split( "&" );

            var i = parts.length;
            var x;

            while( i-- )
            {
                x = parts[i].split( "=" );

                if( x[0] == key )
                {
                    x[1] = value;
                    parts[i] = x.join( "=" );
                    break;
                }
            }

            if( i < 0 )
            {
                parts[parts.length] = [key,value].join("=");
            }

            url = urlparts[0]+"?"+parts.join("&");

            return url;
        },

        refreshPage : function( to_url )
        {
            if( typeof to_url == "undefined" || to_url == "" )
                to_url = window.location.href;

            to_url = PHS_JSEN.removeURLParameter( to_url, '_PHS_JSENr' );

            rand_no = Math.round(((new Date()).getTime()-Date.UTC(1970,0,1))/1000);
            if( to_url.search( '\\?' ) == -1 )
                to_url = to_url + "?&_PHS_JSENr=" + rand_no;
            else
                to_url = to_url + "&_PHS_JSENr=" + rand_no;

            window.location.href = to_url;
        },

        dialogErrors : function( error_arr )
        {
            if( !error_arr || typeof error_arr != "object" || !error_arr.length )
                return;

            this.dialogErrorsClose();

            for( var i = 0; i < error_arr.length; i++ )
            {
                container_obj = false;
                appendto_obj = false;

                if( !error_arr[i].highlight_classes || typeof error_arr[i].highlight_classes != "object" )
                    error_arr[i].highlight_classes = [];

                container_name_id = '';
                container_div_id = '';
                if( !error_arr[i].container || !error_arr[i].container.length )
                {
                    if( !error_arr[i].appendto || !error_arr[i].appendto.length )
                        continue;

                    appendto_obj = $(error_arr[i].appendto);
                    if( !appendto_obj )
                        continue;

                    container_div_id = "PHS_JSENDiaErr" + this.dialogErrorsDivs;
                    container_name_id = "#PHS_JSENDiaErr" + this.dialogErrorsDivs;
                }
                else
                {
                    container_obj = $(error_arr[i].container);
                    if( !container_obj )
                        continue;

                    container_name_id = error_arr[i].container;

                    this.dialogErrorsDivsIds.push( error_arr[i].container );
                }

                if( !error_arr[i].check_visible || typeof error_arr[i].check_visible != "object" )
                {
                    error_arr[i].check_visible = [];
                    error_arr[i].check_visible.push( container_name_id );
                } else if( !this.in_array( container_name_id, error_arr[i].check_visible ) )
                    error_arr[i].check_visible.push( container_name_id );

                var len = 0;
                var ki = 0;

                if( error_arr[i].highlight_field )
                {
                    if( typeof error_arr[i].highlight_field == "object" && error_arr[i].highlight_field.length )
                    {
                        len = error_arr[i].highlight_field.length;
                        for( ki = 0; ki < len; ki++ )
                        {
                            var highlight_id = error_arr[i].highlight_field[ki];
                            if( $("#" + highlight_id) )
                                $("#" + highlight_id).addClass( "ui-highlight-error" );
                        }
                    } else if( $("#" + error_arr[i].highlight_field) )
                    {
                        $("#" + error_arr[i].highlight_field).addClass("ui-highlight-error");
                    }
                }

                if( appendto_obj )
                {
                    appendto_obj.append( '<div id="' + container_div_id + '"></div>' );
                    this.dialogErrorsDivs++;
                    container_obj = $(container_name_id);

                    if( !container_obj )
                        continue;

                    this.dialogErrorsDivsIds.push( container_name_id );
                }

                if( error_arr[i].check_visible && typeof error_arr[i].check_visible == "object" )
                {
                    len = error_arr[i].check_visible.length;
                    for( ki = 0; ki < len; ki++ )
                    {
                        container_name = error_arr[i].check_visible[ki];
                        if( container_name.substr( 0, 1 ) != '#' )
                            container_name = '#' + container_name;

                        var vis_obj = $(container_name);
                        if( !vis_obj )
                            continue;

                        if( vis_obj.css( 'display' ) != 'block' )
                            vis_obj.css( 'display', 'block' );
                    }
                }

                if( error_arr[i].highlight_classes && typeof error_arr[i].highlight_classes == "object" )
                {
                    for( ki = 0; knti < error_arr[i].highlight_classes.length; ki++ )
                    {
                        if( !container_obj.hasClass( error_arr[i].highlight_classes[ki] ) )
                            container_obj.addClass( error_arr[i].highlight_classes[ki] );
                    }
                }

                // if( !container_obj.hasClass( "ui-state-error" ) )
                //     container_obj.addClass( "ui-state-error" );
                // if( !container_obj.hasClass( "dialog-field-error" ) )
                //     container_obj.addClass( "dialog-field-error" );

                if( error_arr[i].text_message && error_arr[i].text_message.length )
                    container_obj.text( error_arr[i].text_message );
                if( error_arr[i].html_message && error_arr[i].html_message.length )
                    container_obj.html( error_arr[i].html_message );

                container_obj.show();
            }
        },

        closeAjaxDialog : function( suffix )
        {
            if( typeof suffix == "undefined" )
                suffix = "";

            var dialog_obj = $("#" + PHS_JSEN.dialogs_prefix + suffix);
            if( dialog_obj )
            {
                //var obj_options = PHS_JSEN.dialogOptions( suffix );
                //if( obj_options && obj_options.onclose )
                //{
                //    if( jQuery.isFunction( obj_options.onclose ) )
                //        obj_options.onclose();
                //    else if( typeof obj_options.onclose == "string" )
                //        eval( obj_options.onclose );
                //}
                //
                //dialog_obj.remove();
                dialog_obj.dialog( "close" );
            }
        },

        // Create AJAX request
        redirectAjaxDialog : function( o )
        {
            var defaults = {
                suffix            : "",
                cache_response    : false,
                url               : null,
                method            : "GET",
                url_data          : "",
                title             : "",
                cssclass          : ["phs_jsenOverlay"]
            };

            var options = $.extend( {}, defaults, o );

            var ajax_obj = false;
            var dialog_obj = $("#" + PHS_JSEN.dialogs_prefix + options.suffix);
            if( typeof options.url != "undefined" && options.url && dialog_obj )
            {
                if( options.title && options.title.length )
                {
                    dialog_obj.dialog( "option", "title",  options.title );
                    PHS_JSEN.dialogOptions( options.suffix, 'title', options.title );
                }

                if( typeof( options.cssclass ) != "undefined" && options.cssclass )
                {
                    dialog_obj.dialog( "option", "dialogClass", options.cssclass );
                    PHS_JSEN.dialogOptions( options.suffix, 'cssclass', options.cssclass );
                }

                var ajax_params = {
                    cache_response: options.cache_response,
                    method: options.method,
                    url_data: options.url_data,
                    full_buffer: true,

                    onsuccess: function( response, status, ajax_obj ) {
                        dialog_obj.html( response );
                    },

                    onfailed: function( ajax_obj, status, error_exception ) {
                        dialog_obj.html( "<?php echo PHS_Language::_te( 'Error' )?>" );
                    }
                };

                if( typeof options.data_type == "string" )
                    ajax_params.data_type = options.data_type;

                ajax_obj = PHS_JSEN.do_ajax( options.url, ajax_params );
            }

            return ajax_obj;
        },

        // Reload HTML content of an AJAX dialog
        reloadAjaxDialog : function( o )
        {
            var defaults = {
                suffix            : "",
                cache_response    : false,
                url               : null,
                method            : "GET",
                url_data          : "",

                onfailed          : null,
                onsuccess         : null
            };

            var options = $.extend( {}, defaults, o );

            var dialog_obj = $('#'+ PHS_JSEN.dialogs_prefix + options.suffix);
            if( !dialog_obj )
                return false;

            if( ( typeof options.url != "undefined" ) && options.url )
            {
                var ajax_params = {
                    cache_response: options.cache_response,
                    method: options.method,
                    url_data: options.url_data,
                    full_buffer: true,

                    onsuccess: function( response, status, ajax_obj ) {
                        dialog_obj.html( response );

                        if( options.onsuccess )
                        {
                            if( jQuery.isFunction( options.onsuccess ) )
                                options.onsuccess();
                            else if( typeof options.onsuccess == "string" )
                                eval( options.onsuccess );
                        }
                    },

                    onfailed: function( ajax_obj, status, error_exception ) {
                        dialog_obj.html( "<?php echo PHS_Language::_te( 'Error' )?>" );
                    }
                };

                if( typeof options.data_type == "string" )
                    ajax_params.data_type = options.data_type;

                PHS_JSEN.do_ajax( options.url, ajax_params );

                PHS_JSEN.dialogOptions( options.suffix, 'url', options.url );
            }

            PHS_JSEN.modifyAjaxDialog( o );

            return true;
        },

        // Alter an AJAX dialog
        modifyAjaxDialog : function( o )
        {
            var defaults = {
                width             : 0,
                height            : 0,
                suffix            : '',
                opacity           : 0.9,
                title             : "",
                draggable         : true,
                resizable         : false
            };

            var options = [];
            var options_to_change = false;

            for( key in o )
            {
                if( defaults.hasOwnProperty( key ) )
                {
                    options[key] = o[key];
                    options_to_change = true;
                }
            }

            if( !options.hasOwnProperty( 'suffix' ) )
                options['suffix'] = '';

            var dialog_obj = $("#" + PHS_JSEN.dialogs_prefix + options.suffix);
            if( options_to_change && dialog_obj )
            {
                for( key in options )
                {
                    if( key == 'suffix' )
                        continue;

                    dialog_obj.dialog( 'option', key, options[key] );
                    PHS_JSEN.dialogOptions( options.suffix, key, options[key] );
                }
            }
        },

        // Create AJAX request
        createAjaxDialog : function( o )
        {
            var defaults = {
                width             : 600,
                height            : 400,
                suffix            : "",
                cache_response    : false,
                stack             : true,

                // Source of dialog content
                url               : null,
                method            : "GET",
                url_data          : "",
                // Content from where html() will be used
                source_obj        : null,
                // If true source_obj will be appended (preserving DOM objects)
                source_not_cloned : false,
                // Tells where to put source_obj DOM object once dialog will get destroyed (if source_not_cloned is true)
                source_parent     : null,

                draggable         : true,
                resizable         : false,
                title             : "",
                parent_tag        : "body",
                autoshow          : false,
                opacity           : 0.9,
                cssclass          : ["phs_jsenOverlay"],
                close_outside_click : true,
                dialog_show         : "",

                onclose           : null,
                onbeforeclose     : null,
                onsuccess         : null
            };

            var options = $.extend( {}, defaults, o );

            // Remove Dialog ( if previously created )
            var dialog_obj = $("#" + PHS_JSEN.dialogs_prefix + options.suffix);
            if( dialog_obj )
            {
                dialog_obj.dialog('destroy').remove();
            }

            // Create Dialog
            if( typeof $(options.parent_tag) == "undefined" )
                options.parent_tag = "body";

            $(options.parent_tag).append( '<div id="' + PHS_JSEN.dialogs_prefix + options.suffix + '"></div>' );

            dialog_obj = $("#" + PHS_JSEN.dialogs_prefix + options.suffix);
            if( dialog_obj )
            {
                dialog_obj.dialog( {
                    width: options.width,
                    height: options.height,
                    draggable: options.draggable,
                    dialogClass: options.cssclass,
                    stack: options.stack,
                    title: options.title,
                    overlay: { opacity: 0.9, background: "#000" },

                    modal: true,
                    minHeight: 300,
                    autoOpen: false,
                    position: { my: "center", at: "center", of: window },
                    resizable: options.resizable,

                    beforeClose: function(event,ui) {
                        if( options.onbeforeclose )
                        {
                            if( jQuery.isFunction( options.onbeforeclose ) )
                                options.onbeforeclose();
                            else if( typeof options.onbeforeclose == "string" )
                                eval( options.onbeforeclose );
                        }
                    },

                    open: function(event, ui) {
                        $('.ui-widget-overlay').css('opacity', options.opacity);
                    }
                });

                // Check if we should call an url
                if( typeof options.url != "undefined" && options.url )
                {
                    var ajax_params = {
                        cache_response: options.cache_response,
                        method: options.method,
                        url_data: options.url_data,
                        full_buffer: true,

                        onsuccess: function( response, status, ajax_obj ) {
                            //var diag_container = $( "#" + PHS_JSEN.dialogs_prefix + options.suffix );

                            if( dialog_obj )
                            {
                                dialog_obj.html( response );
                                dialog_obj.dialog( "open" );
                            }

                            if( options.onsuccess )
                            {
                                if( jQuery.isFunction( options.onsuccess ) )
                                    options.onsuccess();
                                else if( typeof options.onsuccess == "string" )
                                    eval( options.onsuccess );
                            }
                        },

                        onfailed: function( ajax_obj, status, error_exception ) {
                            //var diag_container = $( "#" + PHS_JSEN.dialogs_prefix + options.suffix );
                            dialog_obj.html( "<?php echo PHS_Language::_te( 'Error ontaining dialogue body. Please try again.' )?>" );
                            dialog_obj.dialog( "open" );
                        }
                    };

                    PHS_JSEN.do_ajax( options.url, ajax_params );
                } else

                // Check if we have an object to extract html() from
                if( typeof( options.source_obj ) != "undefined" && options.source_obj )
                {
                    var source_container = null;
                    if( typeof( options.source_obj ) == "string" )
                    {
                        source_container = $( '#' + options.source_obj );
                        options.source_obj = source_container;
                    } else
                        source_container = options.source_obj;

                    if( !options.source_parent )
                        options.source_parent = source_container.parent();

                    if( options.source_not_cloned )
                        dialog_obj.append( source_container );
                    else
                        dialog_obj.html( source_container.html() );


                    dialog_obj.dialog( "open" );

                    if( options.onsuccess )
                    {
                        if( jQuery.isFunction( options.onsuccess ) )
                            options.onsuccess();
                        else if( typeof options.onsuccess == "string" )
                            eval( options.onsuccess );
                    }
                }

                if( options.close_outside_click )
                {
                    $(document).on('click', '.ui-widget-overlay', function() {
                        if( dialog_obj )
                            dialog_obj.dialog( "close" );
                    });

                }

                dialog_obj.bind( 'dialogclose', function(event) {
                    if( options.onclose )
                    {
                        if( jQuery.isFunction( options.onclose ) )
                            options.onclose();
                        else if( typeof options.onclose == "string" )
                            eval( options.onclose );
                    }

                    if( options.source_not_cloned && options.source_obj && options.source_parent )
                        options.source_parent.append( options.source_obj );

                    dialog_obj.dialog('destroy').remove();
                    dialog_obj = false;
                });

                PHS_JSEN.dialogOptions( options.suffix, options );
            }

            if( window.innerHeight < options.height )
            {
                setTimeout(function() {
                    dialog_obj.parent().css( "top", "30px" );
                }, 500 );
            }
        },

        // Close Loading div...
        dialogOptions : function( suffix, key, val )
        {
            if( typeof key == 'undefined' && typeof val == 'undefined' )
                return PHS_JSEN.dialogs_options[suffix];

            if( typeof key == 'string' && typeof val == 'undefined' )
            {
                if( typeof PHS_JSEN.dialogs_options[suffix] != 'undefined' && typeof PHS_JSEN.dialogs_options[suffix][key] != 'undefined' )
                    return PHS_JSEN.dialogs_options[suffix][key];
                else
                    return null;
            }

            if( typeof key == 'object' )
            {
                if( typeof PHS_JSEN.dialogs_options[suffix] != 'undefined' )
                    PHS_JSEN.dialogs_options[suffix] = $.extend( {}, PHS_JSEN.dialogs_options[suffix], key );
                else
                    PHS_JSEN.dialogs_options[suffix] = key;

                return PHS_JSEN.dialogs_options[suffix];
            }

            if( typeof key == 'string' && typeof val != 'undefined'
             && typeof PHS_JSEN.dialogs_options[suffix] != 'undefined' )
            {
                PHS_JSEN.dialogs_options[suffix][key] = val;
                return true;
            }

            return false;
        },

        // Close Loading div...
        closeLoadingDialog : function( suffix )
        {
            // Remove Dialog ( if previously created )
            var loading_dialog_obj = $("#phs_jsen_loading" + suffix);
            if( loading_dialog_obj )
            {
                loading_dialog_obj.remove();
            }
        },

        // Create Loading div...
        createLoadingDialog : function( o )
        {
            var options = $.extend( {}, {
                width       : 320,
                height      : 100,
                suffix      : "",
                message     : "",
                stack       : true,
                draggable   : true,
                close_on_escape : false,
                title       : "<?php echo PHS::_te( 'Please wait...' )?>",
                parent_tag  : "body",
                cssclass    : "ui-dialog-no-close ui-dialog-loading"
            }, o );

            // Remove Dialog ( if previously created )
            var loading_dialog_obj = $("#phs_jsen_loading" + options.suffix);
            if( loading_dialog_obj )
                loading_dialog_obj.remove();

            // Create Dialog
            if( typeof $(options.parent_tag) == "undefined" )
                options.parent_tag = "body";

            $(options.parent_tag).append( '<div id="phs_jsen_loading' + options.suffix + '"></div>' );

            if( loading_dialog_obj )
            {
                loading_dialog_obj.dialog( {
                        width: options.width,
                        height: options.height,
                        draggable: options.draggable,
                        dialogClass: options.cssclass,
                        stack: options.stack,
                        title: options.title,
                        closeOnEscape: options.close_on_escape,
                        appendTo: options.parent_tag,

                        modal: true,
                        minHeight: 300,
                        overlay: { opacity: 0.4, background: "#000" },
                        show: "",
                        autoOpen: false,
                        position: { my: "center", at: "center", of: $(options.parent_tag) },
                        resizable: false
                       } );

                loading_dialog_obj.html( options.message + '<div id="loading-animation-pb' + options.suffix + '"></div>' );

                $( "#loading-animation-pb" + options.suffix ).progressbar( { value: false } );

                loading_dialog_obj.dialog( "open" );
            }
        },

        keyPressHandler : function()
        {
            var r = $(PHS_JSEN.default_action_13);
            if( !r || !r.length )
                return;

            r.each( function()
            {
                var a = $(this);
                while( a && a.length && a.attr( "tagName" ) != "FORM" )
                {
                    a = a.parent()
                }

                if( a && a.length )
                    a.find("input").keypress( function(e){ if( ( e.which && e.which == 13 ) || (e.keyCode && e.keyCode == 13) ) { a.find(PHS_JSEN.default_action_13).click(); return false } else { return true } } );

            });
        },

        logf : function( str )
        {
            if( console && PHS_JSEN.debugging_mode )
                console.log( str );
        }

    };
}

