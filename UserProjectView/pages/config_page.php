<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

// PluginManager object
$pluginManager = new PluginManager();

auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'UserProjectAccessLevel' ) );

html_page_top1( plugin_lang_get( 'config_title' ) );
html_page_top2();

print_manage_menu();

echo '<script type="text/javascript" src="plugins' . DIRECTORY_SEPARATOR . plugin_get_current () . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . 'jscolor' . DIRECTORY_SEPARATOR . 'jscolor.js"></script>';
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
		echo '<td class="form-title" colspan="6">';
		echo plugin_lang_get( 'config_caption' );
		echo '</td>';
	echo '</tr>';
	
	
	$pluginManager->printConfigTableRow();
	echo '<td class="category" colspan="1">';
	echo '<span class="required">*</span>' . plugin_lang_get( 'config_accesslevel' );
	echo '</td>';
	echo '<td width="100px" colspan="5">';
	echo '<select name="UserProjectAccessLevel">';
	print_enum_string_option_list( 'access_levels', plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT ) );
	echo '</select>';
	echo '</td>';
	echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_showMenu' );
   echo '</td>';
   echo '<td width="100px" colspan="5">';
   ?>
   <label><input type="radio" name="ShowMenu" value="1" <?php echo ( ON == plugin_config_get( 'ShowMenu' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="ShowMenu" value="0" <?php echo ( OFF == plugin_config_get( 'ShowMenu' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
   echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
      echo plugin_lang_get( 'config_showFooter' );
   echo '</td>';
   echo '<td width="100px" colspan="5">';
   ?>
   <label><input type="radio" name="ShowInFooter" value="1" <?php echo ( ON == plugin_config_get( 'ShowInFooter' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="ShowInFooter" value="0" <?php echo ( OFF == plugin_config_get( 'ShowInFooter' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
	echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_showAvatar' );
   echo '</td>';
   echo '<td width="100px" colspan="5">';
   ?>
   <label><input type="radio" name="ShowAvatar" value="1" <?php echo ( ON == plugin_config_get( 'ShowAvatar' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="ShowAvatar" value="0" <?php echo ( OFF == plugin_config_get( 'ShowAvatar' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
   echo '</tr>';

	$pluginManager->printConfigSpacer( 6 );
	
	# ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

	
	echo '<tr>';
	echo '<td class="form-title" colspan="6">';
	echo plugin_lang_get( 'config_highlighting' );
	echo '</td>';
	echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_IAUHighlighting' );
   echo '</td>';
   echo '<td width="100px" colspan="1">';
   ?>
   <label><input type="radio" name="IAUHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'IAUHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="IAUHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'IAUHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_BGColor' );
   echo '</td>';
   echo '<td width="100px" colspan="3">';
   ?>
   <label><input class="color {pickerFace:4,pickerClosable:true}" type="text" name="IAUHBGColor" value="<?php echo plugin_config_get( 'IAUHBGColor', '#663300' ); ?>" /></label>
   <?php
   echo '</td>';
	echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_URIUHighlighting' );
   echo '</td>';
   echo '<td width="100px" colspan="1">';
   ?>
   <label><input type="radio" name="URIUHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'URIUHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="URIUHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'URIUHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_BGColor' );
   echo '</td>';
   echo '<td width="100px" colspan="3">';
   ?>
   <label><input class="color {pickerFace:4,pickerClosable:true}" type="text" name="URIUHBGColor" value="<?php echo plugin_config_get( 'URIUHBGColor', '#663300' ); ?>" /></label>
   <?php
   echo '</td>';
	echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_NUIHighlighting' );
   echo '</td>';
   echo '<td width="100px" colspan="1">';
   ?>
   <label><input type="radio" name="NUIHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'NUIHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="NUIHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'NUIHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_BGColor' );
   echo '</td>';
   echo '<td width="100px" colspan="3">';
   ?>
   <label><input class="color {pickerFace:4,pickerClosable:true}" type="text" name="NUIHBGColor" value="<?php echo plugin_config_get( 'NUIHBGColor', '#663300' ); ?>" /></label>
   <?php
   echo '</td>';
	echo '</tr>';



   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_showZIU' );
   echo '</td>';
   echo '<td width="100px" colspan="5">';
   ?>
   <label><input type="radio" name="ShowZIU" value="1" <?php echo ( ON == plugin_config_get( 'ShowZIU' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="ShowZIU" value="0" <?php echo ( OFF == plugin_config_get( 'ShowZIU' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
	echo '</tr>';

   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_ZIHighlighting' ) . '<br/>';
   echo '<span class="small">' . plugin_lang_get( 'config_ZIUExpl' ) . '</span>';
   echo '</td>';
   echo '<td width="100px" colspan="1">';
   ?>
   <label><input type="radio" name="ZIHighlighting" value="1" <?php echo ( ON == plugin_config_get( 'ZIHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_y' ) ?></label>
   <label><input type="radio" name="ZIHighlighting" value="0" <?php echo ( OFF == plugin_config_get( 'ZIHighlighting' ) ) ? 'checked="checked" ' : ''?>/><?php echo plugin_lang_get( 'config_n' ) ?></label>
   <?php
   echo '</td>';
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_BGColor' );
   echo '</td>';
   echo '<td width="100px" colspan="3">';
   ?>
   <label><input class="color {pickerFace:4,pickerClosable:true}" type="text" name="ZIHBGColor" value="<?php echo plugin_config_get( 'ZIHBGColor', '#663300' ); ?>" /></label>
   <?php
   echo '</td>';
	echo '</tr>';

   $pluginManager->printConfigSpacer( 6 );

	# ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	
	echo '<tr>';
	echo '<td class="form-title" colspan="8">';
	echo plugin_lang_get( 'config_specColumns' );
	echo '</td>';
	echo '</tr>';


   $pluginManager->printConfigTableRow();
   echo '<td class="category" colspan="1" rowspan="1">';
   echo plugin_lang_get( 'config_CAmount' );
   echo '</td>';
   echo '<td width="100px" colspan="1" rowspan="1">';
   ?>
   <label><input type="number" name="CAmount" value="<?php echo plugin_config_get( 'CAmount', 1 ); ?>" min="1" max="20"/></label>
   <?php
   echo '</td>';


   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_BGColor' );
   echo '</td>';
   echo '<td width="100px" colspan="1">';
   ?>
      <label><input class="color {pickerFace:4,pickerClosable:true}" type="text" name="TAMHBGColor" value="<?php echo plugin_config_get( 'TAMHBGColor', '#663300' ); ?>" /></label>
   <?php
   echo '</td>';

   echo '</tr>';

	for ( $columnIndex = 1; $columnIndex <= plugin_config_get( 'CAmount' ); $columnIndex++ )
	{
      $pluginManager->printConfigTableRow();
      echo '<td class="category" colspan="1" rowspan="1">';
      echo '<span class="required">*</span>' . plugin_lang_get( 'config_CStatSelect' ) . ' ' . $columnIndex . ':';
      echo '</td>';
      echo '<td valign="top" width="100px" colspan="1" rowspan="1">';
      echo '<select name="CStatSelect' . $columnIndex .'">';
      print_enum_string_option_list( 'status', plugin_config_get( 'CStatSelect' . $columnIndex ) );
      echo '</select>';
      echo '</td>';
      echo '<td class="category" colspan="1">';
      echo plugin_lang_get('config_IAMThreshold') . '<br>';
      echo '<span class="small">' . plugin_lang_get( 'config_IAGMThresholdExpl' ) . '</span>';
      echo '</td>';
      echo '<td  colspan="1">';
      ?>
      <label><input type="number" name="IAMThreshold<?php echo $columnIndex ?>" value="<?php echo plugin_config_get( 'IAMThreshold' . $columnIndex , 5 ); ?>" min="0"/></label>
      <?php
      echo '</td>';
      echo '<td class="category" colspan="1">';
      echo plugin_lang_get('config_IAGThreshold') . '<br>';
      echo '<span class="small">' . plugin_lang_get( 'config_IAGMThresholdExpl' ) . '</span>';
      echo '</td>';
      echo '<td  colspan="1">';
      ?>
      <label><input type="number" name="IAGThreshold<?php echo $columnIndex ?>" value="<?php echo plugin_config_get( 'IAGThreshold' . $columnIndex , 30 ); ?>" min="0"/></label>
      <?php
      echo '</td>';
		echo '</tr>';
	}

	# ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
   $pluginManager->printConfigSpacer( 6 );

	echo '<tr>';
	echo '<td class="form-title" colspan="8">';
	echo plugin_lang_get( 'config_URIFilter' );
	echo '</td>';
	echo '</tr>';

   $pluginManager->printConfigTableRow();
	echo '<td class="category" width="30%" colspan="1">';
	echo plugin_lang_get( 'config_URIThreshold' );
	echo '</td>';
	echo '<td valign="top" width="100px" colspan="7">';
	echo '<select name="URIThreshold[]" multiple="multiple">';
	print_enum_string_option_list( 'status', plugin_config_get( 'URIThreshold', 50 ) );
	echo '</select>';
	echo '</td>';
	echo '</tr>';

   $pluginManager->printConfigSpacer( 6 );

	echo '<tr>';
		echo '<td class="center" colspan="6">';
      echo '<label><input type="checkbox" name="change" checked/>' . plugin_lang_get( 'config_change' ) . '</label>';
      echo '<label><input type="checkbox" name="reset"/>' . plugin_lang_get( 'config_reset' ) . '</label>';
      echo '</td>';
	echo '</tr>';
   echo '<tr>';
      echo '<td class="center" colspan="6">';
      echo '<input type="submit" class="button" value="' . lang_get( 'change_configuration' ) . '"/>';
      echo '</td>';
   echo '</tr>';

echo '</table>';

if ( substr( MANTIS_VERSION, 0, 4 ) != '1.2.' )
{
   echo '</div>';
}

echo '</form>';

html_page_bottom1();