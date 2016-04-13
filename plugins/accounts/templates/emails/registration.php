<?php
/** @var \phs\system\core\views\PHS_View $this */

use \phs\libraries\PHS_Hooks;

$hook_args = $this::validate_array( $this->context_var( 'hook_args' ), PHS_Hooks::default_init_email_hook_args() );

$email_vars = $hook_args['email_vars'];
if( empty( $email_vars ) or !is_array( $email_vars ) )
    $email_vars = array();

?>
Hi <?php echo $email_vars['nick']?>,<br/>
<br/>
Welcome to the <?php echo $email_vars['site_name']?> Platform!<br/>
You're almost ready, just confirm your registration by clicking here: <a target="_blank" href="<?php echo $email_vars['activation_link']?>">Confirm Registration</a>
<br/>
or copy and paste this link below in your browser:<hr/>
<?php echo $email_vars['activation_link']?>
<hr/>
Here are your login details:<br/>
Login: <?php echo $email_vars['nick']?><br/>
Password: <?php echo $email_vars['obfuscated_pass']?><br/>
<small>For security reasons we obfuscated your password.</small>
<hr/>
Any problems? <a href="<?php echo $email_vars['contact_us_link']?>">Get in touch</a><br/>
<br/>
We're looking forward to working with you!<br/>
<br/>
Best wishes,<br/>
<?php echo $email_vars['site_name']?> team