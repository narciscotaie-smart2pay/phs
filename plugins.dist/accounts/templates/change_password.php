<?php
    /** @var \phs\system\core\views\PHS_View $this */

    use \phs\PHS;
    use \phs\libraries\PHS_utils;
?>
<div style="min-width:650px;max-width:1000px;margin: 0 auto;">
    <form id="change_password_form" name="change_password_form" action="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'change_password' ) )?>" method="post">
        <input type="hidden" name="foobar" value="1" />

        <div class="form_container responsive" style="width: 500px;">

            <section class="heading-bordered">
                <h3><?php echo $this->_pt( 'Change Password' )?></h3>
            </section>

            <fieldset class="form-group">
                <label for="nick"><?php echo $this->_pt( 'Username' )?>:</label>
                <?php echo form_str( $this->context_var( 'nick' ) )?>
            </fieldset>

            <fieldset class="form-group">
                <label for="pass"><?php echo $this->_pt( 'Current Password' )?>:</label>
                <input type="password" id="pass" name="pass" class="form-control" value="<?php echo form_str( $this->context_var( 'pass' ) )?>" style="width: 260px;" required="required" />
            </fieldset>

            <fieldset class="form-group">
                <label for="pass1"><?php echo $this->_pt( 'New Password' )?>:</label>
                <div class="lineform_line">
                <input type="password" id="pass1" name="pass1" class="form-control" value="<?php echo form_str( $this->context_var( 'pass1' ) )?>" style="width: 260px;" required="required" /><br/>
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
                <label for="pass2"><?php echo $this->_pt( 'Confirm Password' )?>:</label>
                <input type="password" id="pass2" name="pass2" class="form-control" value="<?php echo form_str( $this->context_var( 'pass2' ) )?>" style="width: 260px;" required="required" />
            </fieldset>

            <fieldset>
                <input type="submit" id="submit" name="submit" class="btn btn-primary submit-protection" value="<?php echo $this->_pte( 'Change password' )?>" />
            </fieldset>

            <fieldset>
                <a href="<?php echo PHS::url( array( 'p' => 'accounts', 'a' => 'edit_profile' ) )?>"><?php echo $this->_pt( 'Edit Profile' )?></a>
            </fieldset>

        </div>
    </form>
</div>