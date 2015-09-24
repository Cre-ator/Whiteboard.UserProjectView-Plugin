<?php
access_ensure_global_level( plugin_config_get( 'UserProjectAccessLevel' ) );
auth_reauthenticate();

html_page_top1( plugin_lang_get( 'config_title' ) );
html_page_top2();

print_manage_menu();

echo '<br/>';
echo '<form action="' . plugin_page( 'config_update' ) . '" method="post">';
echo form_security_field( 'plugin_UserProjectView_config_update' );

if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
{
   echo '<table align="center" class="width75" cellspacing="1">';
}
else
{
   echo '<div class="form-container">';
   echo '<table>';
}

	echo '<tr>';
		echo '<td class="form-title" colspan="2">';
		echo plugin_lang_get( 'config_caption' );
		echo '</td>';
	echo '</tr>';
	
	
	
   if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
   {
      echo '<tr ' . helper_alternate_class() . '>';
   }
   else
   {
      echo '<tr>';
   }
      echo '<td class="category">';
      echo plugin_lang_get( 'menu' );
      echo '</td>';
      echo '<td width="200px">';
      ?>
      <label><input type="radio" name="ShowMenu" value="1" <?php echo ( ON == plugin_config_get( 'ShowMenu' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
      <label><input type="radio" name="ShowMenu" value="0" <?php echo ( OFF == plugin_config_get( 'ShowMenu' ) ) ? 'checked="checked" ' : ''?>/>No</label>
      <?php
      echo '</td>';
   echo '</tr>';
	
   
   
	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
	   echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
	   echo '<tr>';
	}
	
		echo '<td class="category">';
		   echo plugin_lang_get( 'footer' );
		echo '</td>';
		echo '<td width="200px">';
		?>
		<label><input type="radio" name="ShowInFooter" value="1" <?php echo ( ON == plugin_config_get( 'ShowInFooter' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
		<label><input type="radio" name="ShowInFooter" value="0" <?php echo ( OFF == plugin_config_get( 'ShowInFooter' ) ) ? 'checked="checked" ' : ''?>/>No</label>
		<?php
		echo '</td>';
	echo '</tr>';
	

	
	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
		echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}

		echo '<td class="category">';
		echo plugin_lang_get( 'inactiveUserHighlighting' );
		echo '</td>';
		echo '<td width="200px">';
      ?>
		<label><input type="radio" name="IAUserHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'IAUserHighlighting' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
		<label><input type="radio" name="IAUserHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'IAUserHighlighting' ) ) ? 'checked="checked" ' : ''?>/>No</label>
		<?php
		echo '</td>';
	echo '</tr>';
	
	

	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
		echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}
	
		echo '<td class="category">';
		echo plugin_lang_get( 'backgroundcolor' );
		echo '</td>';
		echo '<td width="200px">';
		?>
		<label>
			<input type="text" name="IABGColor" value="<?php echo plugin_config_get( 'IABGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT ); ?>" />
		</label>
		<?php
		echo '</td>';
	echo '</tr>';
	
	

	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
		echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}
	
		echo '<td class="category">';
		echo plugin_lang_get( 'textcolor' );
		echo '</td>';
		echo '<td width="200px">';
		?>
		<label>
			<input type="text" name="IATColor" value="<?php echo plugin_config_get( 'IATColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT ); ?>" />
		</label>
		<?php
		echo '</td>';
	echo '</tr>';
	
	

	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
	   echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
	   echo '<tr>';
	}
	
	echo '<td class="category" width="30%">';
	   echo '<span class="required">*</span>' . plugin_lang_get( 'accesslevel' );
	echo '</td>';
		echo '<td width="200px">';
			echo '<select name="UserProjectAccessLevel">';
	         print_enum_string_option_list( 'access_levels', plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT ) );
			echo '</select>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td class="center" colspan="2">';
		  echo '<input type="submit" class="button" value="' . lang_get( 'change_configuration' ) . '"/>';
		echo '</td>';
	echo '</tr>';

echo '</table>';



if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
{

}
else
{
   echo '</div>';
}

echo '</form>';

html_page_bottom1();