<?php
    /** @var \phs\system\core\views\PHS_View $this */

    use \phs\PHS;
    use \phs\libraries\PHS_utils;

    if( !($accounts_plugin_settings = $this->context_var( 'accounts_plugin_settings' )) )
        $accounts_plugin_settings = array();

    if( !($user_levels = $this->context_var( 'user_levels' )) )
        $user_levels = array();

    $current_user = PHS::user_logged_in();
?>
<div style="min-width:100%;max-width:1000px;margin: 0 auto;">
    <form id="edit_user_form" name="edit_user_form" action="<?php echo PHS::url( array( 'p' => 'admin', 'a' => 'user_edit' ), array( 'uid' => $this->context_var( 'uid' ) ) )?>" method="post">
        <input type="hidden" name="foobar" value="1" />

        <div class="form_container responsive" style="width: 650px;">

            <section class="heading-bordered">
                <h3><?php echo $this->_pt( 'Edit User Account' )?></h3>
            </section>

            <fieldset class="form-group">
                <label for="nick"><?php echo $this->_pt( 'Username' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="nick" name="nick" class="form-control" required="required" value="<?php echo form_str( $this->context_var( 'nick' ) )?>" style="width: 260px;" autocomplete="off" /><br/>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="email"><?php echo $this->_pt( 'Email' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="email" name="email" class="form-control" <?php echo (!empty( $accounts_plugin_settings['email_mandatory'] )?'required="required"':'')?> value="<?php echo form_str( $this->context_var( 'email' ) )?>" style="width: 260px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="level"><?php echo $this->_pt( 'Level' )?>:</label>
                <div class="lineform_line">
                <select name="level" id="level" class="chosen-select-nosearch" style="width:260px;">
                    <option value="0"><?php echo $this->_pt( ' - Choose - ' )?></option>
                    <?php
                    foreach( $user_levels as $key => $level_details )
                    {
                        if( $key > $current_user['level'] )
                            break;

                        ?><option value="<?php echo $key?>" <?php echo ($this->context_var( 'level' )==$key?'selected="selected"':'')?>><?php echo $level_details['title']?></option><?php
                    }
                    ?>
                </select>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="title"><?php echo $this->_pt( 'Title' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="title" name="title" class="form-control" value="<?php echo form_str( $this->context_var( 'title' ) )?>" style="width: 60px;" autocomplete="off" /><br/>
                <small><?php echo $this->_pt( 'eg. Mr., Ms., Mss., etc' )?></small>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="fname"><?php echo $this->_pt( 'First Name' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="fname" name="fname" class="form-control" value="<?php echo form_str( $this->context_var( 'fname' ) )?>" style="width: 260px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="lname"><?php echo $this->_pt( 'Last Name' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="lname" name="lname" class="form-control" value="<?php echo form_str( $this->context_var( 'lname' ) )?>" style="width: 260px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="phone"><?php echo $this->_pt( 'Phone Number' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo form_str( $this->context_var( 'phone' ) )?>" style="width: 260px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="company"><?php echo $this->_pt( 'Company' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="company" name="company" class="form-control" value="<?php echo form_str( $this->context_var( 'company' ) )?>" style="width: 260px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset>
                <small><?php echo $this->_pt( 'Complete password fields ony if you want to change password' )?></small>
            </fieldset>

            <fieldset class="form-group">
                <label for="pass"><?php echo $this->_pt( 'Password' )?>:</label>
                <div class="lineform_line">
                <input type="password" id="pass" name="pass" class="form-control" value="<?php echo form_str( $this->context_var( 'pass' ) )?>" style="width: 260px;" autocomplete="off" /><br/>
                <small><?php

                    echo $this->_pt( 'Password should be at least %s characters.', $this->context_var( 'min_password_length' ) );

                    $pass_regexp = $this->context_var( 'password_regexp' );
                    if( !empty( $pass_regexp ) )
                    {
                        echo '<br/>'.$this->_pt( 'Password should pass regular expresion: ' );

                        if( ($regexp_parts = explode( '/', $pass_regexp ))
                            and !empty( $regexp_parts[1] ) )
                        {
                            if( empty($regexp_parts[2]) )
                                $regexp_parts[2] = '';

                            ?><a href="https://regex101.com/?regex=<?php echo rawurlencode( $regexp_parts[1] )?>&options=<?php echo $regexp_parts[2]?>" title="Click for details" target="_blank"><?php echo $pass_regexp?></a><?php
                        } else
                            echo $this->_pt( 'Password should pass regular expresion: %s.', $pass_regexp );
                    }

                ?></small>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="pass2"><?php echo $this->_pt( 'Password' )?>:</label>
                <div class="lineform_line">
                <input type="password" id="pass2" name="pass2" class="form-control" value="<?php echo form_str( $this->context_var( 'pass2' ) )?>" style="width: 260px;" autocomplete="off" />
                (<small><?php echo $this->_pt( 'confirm' )?></small>)
                </div>
            </fieldset>

            <fieldset>
                <input type="submit" id="do_submit" name="do_submit" class="btn btn-primary submit-protection" value="<?php echo $this->_pte( 'Save Changes' )?>" />
            </fieldset>

        </div>
    </form>
</div>
<div class="clearfix"></div>
