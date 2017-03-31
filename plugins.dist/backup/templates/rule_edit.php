<?php
    /** @var \phs\system\core\views\PHS_View $this */

    use \phs\PHS;
    use \phs\libraries\PHS_utils;

    /** @var \phs\plugins\backup\PHS_Plugin_Backup $backup_plugin */
    if( !($backup_plugin = $this->context_var( 'backup_plugin' )) )
        return $this->_pt( 'Couldn\'t load backup plugin.' );

    if( !($target_arr = $this->context_var( 'target_arr' )) )
        $target_arr = array();
    if( !($days_arr = $this->context_var( 'days_arr' )) )
        $days_arr = array();

    if( !($plugin_location = $this->context_var( 'plugin_location' )) )
        $plugin_location = array();
    if( !($rule_location = $this->context_var( 'rule_location' )) )
        $rule_location = array();
    if( !($rule_days = $this->context_var( 'rule_days' )) )
        $rule_days = array();
    if( !($targets_arr = $this->context_var( 'targets_arr' )) )
        $targets_arr = array();

    $error_msg = '';
    $stats_str = '';
    if( empty( $rule_location )
     or !($location_details = $backup_plugin->resolve_directory_location( $rule_location['location_path'] )) )
        $error_msg = $this->_pt( 'Couldn\'t obtain current location details.' );

    elseif( empty( $location_details['location_exists'] ) )
        $error_msg = $this->_pt( 'At the moment directory doesn\'t exist. System will try creating it at first run.' );

    elseif( empty( $location_details['full_path'] )
        or !is_writeable( $location_details['full_path'] ) )
        $error_msg = $this->_pt( 'Resolved directory is not writeable.' );

    elseif( !($stats_arr = $backup_plugin->get_directory_stats( $location_details['full_path'] )) )
        $error_msg = $this->_pt( 'Couldn\'t obtain directory stats.' );

    else
        $stats_str = $this->_pt( 'Total space: %s, Free space: %s', format_filesize( $stats_arr['total_space'] ), format_filesize( $stats_arr['free_space'] ) );

    if( !($back_page = $this->context_var( 'back_page' )) )
        $back_page = PHS::url( array( 'p' => 'backup', 'a' => 'rules_list' ) );
?>
<div style="min-width:100%;max-width:1000px;margin: 0 auto;">
    <form id="edit_rule_form" name="edit_rule_form" action="<?php echo PHS::url( array( 'p' => 'backup', 'a' => 'rule_edit' ), array( 'rid' => $this->context_var( 'rid' ) ) )?>" method="post">
        <input type="hidden" name="foobar" value="1" />
        <?php
        if( !empty( $back_page ) )
        {
            ?><input type="hidden" name="back_page" value="<?php echo form_str( safe_url( $back_page ) )?>" /><?php
        }
        ?>

        <div class="form_container responsive" style="width: 700px;">

            <?php
            if( !empty( $back_page ) )
            {
                ?><i class="fa fa-chevron-left"></i> <a href="<?php echo form_str( from_safe_url( $back_page ) ) ?>"><?php echo $this->_pt( 'Back' )?></a><?php
            }
            ?>

            <section class="heading-bordered">
                <h3><?php echo $this->_pt( 'Edit Rule' )?></h3>
            </section>

            <fieldset class="form-group">
                <label for="title"><?php echo $this->_pt( 'Title' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="title" name="title" class="form-control" required="required" value="<?php echo form_str( $this->context_var( 'title' ) )?>" style="width: 360px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="location"><?php echo $this->_pt( 'Location' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="location" name="location" class="form-control" value="<?php echo form_str( $this->context_var( 'location' ) )?>" style="width: 360px;" autocomplete="off" />
                <br/>
                <small>
                <?php
                echo $this->_pt( 'Leave blank to use location set in plugin settings%s.', (!empty( $plugin_location['location_path'] )?' ('.$plugin_location['location_path'].')':'') ).'<br/>';
                echo $this->_pt( 'If path is not absolute, it will be relative to framework uploads dir (%s).', PHS_UPLOADS_DIR );
                ?><br/></small>
                <?php
                if( !empty( $error_msg ) )
                {
                    ?><div style="color:red;"><?php echo $error_msg?></div><br/><?php
                } elseif( !empty( $stats_str ) )
                {
                    ?><small><?php
                    if( !empty( $rule_location['location_path'] ) )
                        echo $this->_pt( '%s stats:', $rule_location['location_path'] ).'<br/>';
                    ?><strong><?php echo $stats_str?></strong>
                    </small><br/><?php
                }
                ?>
                <small>
                </small>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="hour"><?php echo $this->_pt( 'Hour' )?>:</label>
                <div class="lineform_line">
                <select name="hour" id="hour" class="chosen-select" style="min-width:150px;">
                <option value="-1"><?php echo $this->_pt( ' - Choose - ' )?></option>
                <?php
                $selected_hour = $this->context_var( 'hour' );
                for( $hour = 0; $hour < 24; $hour++ )
                {
                    ?><option value="<?php echo $hour?>" <?php echo (($selected_hour !== false and $selected_hour==$hour)?'selected="selected"':'')?>><?php echo ($hour<10?'0':'').$hour?></option><?php
                }
                ?></select>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="days_arr"><?php echo $this->_pt( 'Days' )?>:</label>
                <div class="lineform_line">
                <?php
                foreach( $rule_days as $day_id => $day_name )
                {
                    ?>
                    <div style="float:left; margin-right: 10px;">
                        <div style="float:left;"><input type="checkbox" id="days_arr_<?php echo $day_id ?>"
                                                        name="days_arr[]" value="<?php echo form_str( $day_id )?>" rel="skin_checkbox"
                                                        class="<?php echo (empty( $day_id )?'brule_each_day':'brule_day')?>"
                                                        <?php echo (in_array( $day_id, $days_arr ) ? 'checked="checked"' : '')?>
                                                        onclick="changed_days( this )" /></div>
                        <label style="margin-left:5px;width: auto !important;max-width: none !important;float:left;" for="days_arr_<?php echo $day_id ?>">
                            <?php echo $day_name?>
                        </label>
                    </div>
                    <?php

                    if( empty( $day_id ) )
                    {
                        ?><div class="clearfix"></div><?php
                    }
                }
                ?>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="email"><?php echo $this->_pt( 'Targets' )?>:</label>
                <div class="lineform_line">
                <?php
                foreach( $targets_arr as $target_id => $target_name )
                {
                    ?>
                    <div class="clearfix">
                        <div style="float:left;"><input type="checkbox" id="target_arr_<?php echo $target_id ?>"
                                                        name="target_arr[]" value="<?php echo form_str( $target_id )?>" rel="skin_checkbox"
                                                        <?php echo (in_array( $target_id, $target_arr ) ? 'checked="checked"' : '')?> /></div>
                        <label style="margin-left:5px;width: auto !important;max-width: none !important;float:left;" for="target_arr_<?php echo $target_id ?>">
                            <?php echo $target_name?>
                        </label>
                    </div>
                    <?php
                }
                ?>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="email"><?php echo $this->_pt( 'Web Server Note' )?>:</label>
                <div class="lineform_line">
                    <a href="javascript:void(0);" style="margin:0 10px;" onclick="$('#server_apache_note').slideToggle();">Apache Note</a>
                    <a href="javascript:void(0);" style="margin:0 10px;" onclick="$('#server_nginx_note').slideToggle();">Nginx Note</a>

                    <div id="server_apache_note" style="display:none;">
                        <strong>Apache Note</strong><br/>
                        Please note that you will have to place a <em>.htaccess</em> file in location directory that will restrict access to your backup files.
                        <code>eg. <?php echo (!empty( $rule_location['full_path'] )?'<br/>'.$rule_location['full_path'].'/.htaccess':'')?><pre>
&lt;Files "*.*"&gt;
    Require all denied
&lt;/Files&gt;
</pre></code>
                    </div>
                    <div id="server_nginx_note" style="display:none;">
                        <strong>Nginx Note</strong><br/>
                        Please note that you will have to place a <em>nginx.conf</em> file in location directory that will restrict access to your backup files.
                        <code>eg. <?php echo (!empty( $rule_location['full_path'] )?'<br/>'.$rule_location['full_path'].'/nginx.conf':'')?><pre>
location /backup_location_dir {
    deny all;
    return 404;
}
</pre></code>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <input type="submit" id="do_submit" name="do_submit" class="btn btn-primary submit-protection" value="<?php echo $this->_pte( 'Save changes' )?>" />
            </fieldset>

        </div>
    </form>
</div>
<div class="clearfix"></div>
<script type="text/javascript">
function changed_days( el )
{
    if( !el )
        return;

    var el_obj = $(el);
    if( !el_obj )
        return;

    var el_val = el_obj.val();
    if( el_val != 0 )
        $("#days_arr_0").prop("checked", false);

    else
    {
        $(".brule_day").each(function(){
            $(this).prop( "checked", false );
        });
    }
}
</script>
