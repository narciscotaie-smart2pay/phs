<?php
/** @var \phs\system\core\views\PHS_View $this */

use \phs\libraries\PHS_Hooks;

$hook_args = $this::validate_array( $this->context_var( 'hook_args' ), PHS_Hooks::default_init_email_hook_args() );

$email_vars = $hook_args['email_vars'];
if( empty( $email_vars ) or !is_array( $email_vars ) )
    $email_vars = array();

?>
Hi <?php echo $email_vars['destination_nick']?>,<br/>
<br/>
On <?php echo $email_vars['message_date']?> you received a new internal message from <?php echo $email_vars['author_handle']?> with subject "<?php echo $email_vars['message_subject']?>".<br/>
<br/>
In order to view this message, please click on this link: <a href="<?php echo $email_vars['message_link']?>"><?php echo $email_vars['message_link']?></a><br/>
<br/>
Any problems? <a href="<?php echo $email_vars['contact_us_link']?>">Get in touch</a><br/>
<br/>
Best wishes,<br/>
<?php echo $email_vars['site_name']?> team
