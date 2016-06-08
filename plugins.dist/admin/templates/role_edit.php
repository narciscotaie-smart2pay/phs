<?php
    /** @var \phs\system\core\views\PHS_View $this */

    use \phs\PHS;
    use \phs\libraries\PHS_utils;

    if( !($ru_slugs = $this->context_var( 'ru_slugs' )) )
        $ru_slugs = array();
    if( !($role_units_by_slug = $this->context_var( 'role_units_by_slug' )) )
        $role_units_by_slug = array();

    if( !($back_page = $this->context_var( 'back_page' )) )
        $back_page = PHS::url( array( 'p' => 'admin', 'a' => 'roles_list' ) );
?>
<div style="min-width:100%;max-width:1000px;margin: 0 auto;">
    <form id="edit_role_form" name="edit_role_form" action="<?php echo PHS::url( array( 'p' => 'admin', 'a' => 'role_edit' ), array( 'rid' => $this->context_var( 'rid' ) ) )?>" method="post">
        <input type="hidden" name="foobar" value="1" />
        <?php
        if( !empty( $back_page ) )
        {
            ?><input type="hidden" name="back_page" value="<?php echo form_str( safe_url( $back_page ) )?>" /><?php
        }
        ?>

        <div class="form_container" style="width: 650px;">

            <?php
            if( !empty( $back_page ) )
            {
                ?><i class="fa fa-chevron-left"></i> <a href="<?php echo form_str( from_safe_url( $back_page ) ) ?>"><?php echo $this->_pt( 'Back' )?></a><?php
            }
            ?>

            <section class="heading-bordered">
                <h3><?php echo $this->_pt( 'Edit Role' )?></h3>
            </section>

            <fieldset class="form-group">
                <label for="name"><?php echo $this->_pt( 'Name' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="name" name="name" class="form-control" required="required" value="<?php echo form_str( $this->context_var( 'name' ) )?>" style="width: 260px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="slug"><?php echo $this->_pt( 'Slug' )?>:</label>
                <div class="lineform_line">
                <?php echo $this->context_var( 'slug' )?>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="description"><?php echo $this->_pt( 'Description' )?>:</label>
                <div class="lineform_line">
                <input type="text" id="description" name="description" class="form-control" required="required" value="<?php echo form_str( $this->context_var( 'description' ) )?>" style="width: 400px;" autocomplete="off" />
                </div>
            </fieldset>

            <fieldset class="form-group">
                <label for="email"><?php echo $this->_pt( 'Role Units' )?>:</label>
                <div class="lineform_line">
                <?php
                foreach( $role_units_by_slug as $unit_slug => $unit_arr )
                {
                    ?>
                    <div>
                        <div style="float:left;"><input type="checkbox" id="ru_slug_<?php echo $unit_slug ?>" name="ru_slugs[]" value="<?php echo form_str( $unit_slug )?>" rel="skin_checkbox" <?php echo (in_array( $unit_slug, $ru_slugs ) ? 'checked="checked"' : '')?> /></div>
                        <label style="margin-left:5px;width: auto !important;float:left;" for="ru_slug_<?php echo $unit_slug ?>">
                            <?php echo $unit_arr['name']?>
                            <i class="fa fa-question-circle" title="<?php echo form_str( $unit_arr['description'] )?>"></i>
                        </label>
                    </div>
                    <div class="clearfix"></div>
                    <?php
                }
                ?>
                </div>
            </fieldset>

            <fieldset>
                <input type="submit" id="do_submit" name="do_submit" class="btn btn-primary submit-protection" value="<?php echo $this->_pte( 'Save changes' )?>" />
            </fieldset>

        </div>
    </form>
</div>
<div class="clearfix"></div>