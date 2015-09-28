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
		echo '<td class="form-title" colspan="8">';
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
      echo '<td class="category" colspan="1">';
      echo plugin_lang_get( 'menu' );
      echo '</td>';
      echo '<td width="100px" colspan="7">';
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
		echo '<td class="category" colspan="1">';
		   echo plugin_lang_get( 'footer' );
		echo '</td>';
		echo '<td width="100px" colspan="7">';
		?>
		<label><input type="radio" name="ShowInFooter" value="1" <?php echo ( ON == plugin_config_get( 'ShowInFooter' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
		<label><input type="radio" name="ShowInFooter" value="0" <?php echo ( OFF == plugin_config_get( 'ShowInFooter' ) ) ? 'checked="checked" ' : ''?>/>No</label>
		<?php
		echo '</td>';
	echo '</tr>';
	
	
	echo '<tr>';
		echo '<td class="spacer" colspan="8">&nbsp;</td>';
	echo '</tr>';

	
	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
		echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'inactiveUserHighlighting' );
		echo '</td>';
		echo '<td width="100px" colspan="1">';
      ?>
		<label><input type="radio" name="IAUserHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'IAUserHighlighting' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
		<label><input type="radio" name="IAUserHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'IAUserHighlighting' ) ) ? 'checked="checked" ' : ''?>/>No</label>
		<?php
		echo '</td>';
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'backgroundcolor' );
		echo '</td>';
		echo '<td width="100px" colspan="1">';
		?>
		<label>
			<input type="text" name="IABGColor" value="<?php echo plugin_config_get( 'IABGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT ); ?>" />
		</label>
		<?php
		echo '</td>';
				
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'textcolor' );
		echo '</td>';
		echo '<td width="100px" colspan="3">';
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
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'unreachableIssueUserHighlighting' );
		echo '</td>';
		echo '<td width="100px" colspan="1">';
		?>
		<label><input type="radio" name="URUserHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'URUserHighlighting' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
		<label><input type="radio" name="URUserHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'URUserHighlighting' ) ) ? 'checked="checked" ' : ''?>/>No</label>
		<?php
		echo '</td>';
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'backgroundcolor' );
		echo '</td>';
		echo '<td width="100px" colspan="1">';
		?>
		<label>
			<input type="text" name="URBGColor" value="<?php echo plugin_config_get( 'URBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT ); ?>" />
		</label>
		<?php
		echo '</td>';
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'textcolor' );
		echo '</td>';
		echo '<td width="100px" colspan="3">';
		?>
		<label>
			<input type="text" name="URTColor" value="<?php echo plugin_config_get( 'URTColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT ); ?>" />
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
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'noUserIssueHighlighting' );
		echo '</td>';
		echo '<td width="100px" colspan="1">';
		?>
		<label><input type="radio" name="NUIssueHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'NUIssueHighlighting' ) ) ? 'checked="checked" ' : ''?>/>Yes</label>
		<label><input type="radio" name="NUIssueHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'NUIssueHighlighting' ) ) ? 'checked="checked" ' : ''?>/>No</label>
		<?php
		echo '</td>';
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'backgroundcolor' );
		echo '</td>';
		echo '<td width="100px" colspan="1">';
		?>
		<label>
			<input type="text" name="NUBGColor" value="<?php echo plugin_config_get( 'NUBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT ); ?>" />
		</label>
		<?php
		echo '</td>';
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'textcolor' );
		echo '</td>';
		echo '<td width="100px" colspan="3">';
		?>
		<label>
			<input type="text" name="NUTColor" value="<?php echo plugin_config_get( 'NUTColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT ); ?>" />
		</label>
		<?php
		echo '</td>';
	echo '</tr>';
	
	
	echo '<tr>';
	echo '<td class="spacer" colspan="8">&nbsp;</td>';
	echo '</tr>';

	
	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
		echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}
		echo '<td class="category" colspan="1">';
		echo plugin_lang_get( 'columnAmount' );
		echo '</td>';
		echo '<td width="100px" colspan="7">';
		?>
		<label>
			<input type="number" name="colAmount" value="<?php echo plugin_config_get( 'colAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT_DEFAULT ); ?>" min="1" max="3"/>
		</label>
		<?php
		echo '</td>';
	echo '</tr>';
	
	
	
	for ( $columnIndex = 1; $columnIndex <= plugin_config_get( 'colAmount' ); $columnIndex++ )
	{
		if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
		{
			echo '<tr ' . helper_alternate_class() . '>';
		}
		else
		{
			echo '<tr>';
		}
			echo '<td class="category" colspan="1">';
			echo plugin_lang_get( 'columnStatSelect' ) . ' ' . $columnIndex . ':';
			echo '</td>';
			echo '<td valign="top" width="100px" colspan="1">';
			echo '<select name="statselectcol' . $columnIndex .'">';
			echo PLUGINS_USERPROJECTVIEW_COL_STAT_DEFAULT;
			print_enum_string_option_list( 'status', plugin_config_get( 'statselectcol' . $columnIndex , PLUGINS_USERPROJECTVIEW_COL_STAT_DEFAULT ) );
			echo '</select>';
			echo '</td>';
			echo '<td class="category" colspan="1">';
			echo plugin_lang_get('issueThreshold');
			echo '</td>';
			echo '<td  colspan="1">';
			?>
			<label>
				<input type="number" name="issueThreshold<?php echo $columnIndex ?>" value="<?php echo plugin_config_get( 'issueThreshold' . $columnIndex , PLUGINS_USERPROJECTVIEW_ISSUE_THRESHOLD_DEFAULT ); ?>" min="0"/>
			</label>
			<?php
			echo '</td>';
			echo '<td class="category" colspan="1">';
			echo plugin_lang_get( 'backgroundcolor' );
			echo '</td>';
			echo '<td width="100px" colspan="1">';
			?>
			<label>
				<input type="text" name="ITBGColor<?php echo $columnIndex ?>" value="<?php echo plugin_config_get( 'ITBGColor' . $columnIndex , PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT ); ?>" />
			</label>
			<?php
			echo '</td>';
			echo '<td class="category" colspan="1">';
			echo plugin_lang_get( 'textcolor' );
			echo '</td>';
			echo '<td width="100px" colspan="1">';
			?>
			<label>
				<input type="text" name="ITTColor<?php echo $columnIndex ?>" value="<?php echo plugin_config_get( 'ITTColor' . $columnIndex , PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT ); ?>" />
			</label>
			<?php
			echo '</td>';
		echo '</tr>';
	}

	
	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
		echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}
	echo '<td class="category" width="30%" colspan="1">';
	echo plugin_lang_get( 'unreachable_issue_threshold' );
	echo '</td>';
	echo '<td valign="top" width="100px" colspan="7">';
	echo '<select name="UnreachableIssueThreshold[]" multiple="multiple">';
	print_enum_string_option_list( 'status', plugin_config_get( 'UnreachableIssueThreshold', PLUGINS_USERPROJECTVIEW_UNREACH_ISSUE_THRESHOLD_DEFAULT ) );
	echo '</select>';
	echo '</td>';
	echo '</tr>';
	
	
	echo '<tr>';
	echo '<td class="spacer" colspan="8">&nbsp;</td>';
	echo '</tr>';

	
	if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
	{
	   echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
	   echo '<tr>';
	}
	echo '<td class="category" width="30%" colspan="1">';
	   echo '<span class="required">*</span>' . plugin_lang_get( 'accesslevel' );
	echo '</td>';
		echo '<td width="100px" colspan="7">';
			echo '<select name="UserProjectAccessLevel">';
	         print_enum_string_option_list( 'access_levels', plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT ) );
			echo '</select>';
		echo '</td>';
	echo '</tr>';
	
	
	echo '<tr>';
		echo '<td class="center" colspan="8">';
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