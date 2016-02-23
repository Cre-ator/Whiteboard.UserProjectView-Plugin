<?php
require_once USERPROJECTVIEW_CORE_URI . 'constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'UPSystem_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'UPConfig_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'UPPrint_api.php';

// UserProjectView_api object
$upc_api = new UPConfig_api();
$upp_api = new UPPrint_api();
$upv_api = new UPSystem_api();

auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'UserProjectAccessLevel' ) );

html_page_top1( plugin_lang_get( 'config_title' ) );
html_page_top2();

print_manage_menu();

echo '<script type="text/javascript" src="plugins' . DIRECTORY_SEPARATOR . plugin_get_current() . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . 'jscolor' . DIRECTORY_SEPARATOR . 'jscolor.js"></script>';
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
$upp_api->printConfigTitle( 6, 'config_caption' );
$upp_api->printConfigRow();
echo '<td class="category" colspan="1">';
echo '<span class="required">*</span>' . plugin_lang_get( 'config_accesslevel' );
echo '</td>';
echo '<td width="100px" colspan="5">';
echo '<select name="UserProjectAccessLevel">';
print_enum_string_option_list( 'access_levels', plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT ) );
echo '</select>';
echo '</td>';
echo '</tr>';

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_showMenu' );
$upp_api->printRadioButton( 5, 'ShowMenu' );
echo '</tr>';

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_showFooter' );
$upp_api->printRadioButton( 5, 'ShowInFooter' );
echo '</tr>';

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_showAvatar' );
$upp_api->printRadioButton( 5, 'ShowAvatar' );
echo '</tr>';

$upp_api->printSpacer( 6 );
$upp_api->printConfigTitle( 6, 'config_highlighting' );

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_IAUHighlighting' );
$upp_api->printRadioButton( 1, 'IAUHighlighting' );
$upp_api->printConfigCategory( 1, 1, 'config_BGColor' );
$upp_api->printColorPicker( 3, 'IAUHBGColor', PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR );
echo '</tr>';

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_URIUHighlighting' );
$upp_api->printRadioButton( 1, 'URIUHighlighting' );
$upp_api->printConfigCategory( 1, 1, 'config_BGColor' );
$upp_api->printColorPicker( 3, 'URIUHBGColor', PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR );
echo '</tr>';

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_NUIHighlighting' );
$upp_api->printRadioButton( 1, 'NUIHighlighting' );
$upp_api->printConfigCategory( 1, 1, 'config_BGColor' );
$upp_api->printColorPicker( 3, 'NUIHBGColor', PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR );
echo '</tr>';

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_showZIU' );
$upp_api->printRadioButton( 5, 'ShowZIU' );
echo '</tr>';

$upp_api->printConfigRow();
echo '<td class="category" colspan="1">';
echo plugin_lang_get( 'config_ZIHighlighting' ) . '<br/>';
echo '<span class="small">' . plugin_lang_get( 'config_ZIUExpl' ) . '</span>';
echo '</td>';
$upp_api->printRadioButton( 1, 'ZIHighlighting' );
$upp_api->printConfigCategory( 1, 1, 'config_BGColor' );
$upp_api->printColorPicker( 3, 'ZIHBGColor', PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR );
echo '</tr>';

$upp_api->printSpacer( 6 );
$upp_api->printConfigTitle( 6, 'config_specColumns' );

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_CAmount' );
echo '<td width="100px" colspan="1" rowspan="1">';
?>
   <label><input type="number" name="CAmount"
                 value="<?php echo plugin_config_get( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT ); ?>" min="1"
                 max="20"/></label>
<?php
echo '</td>';

$upp_api->printConfigCategory( 1, 1, 'config_BGColor' );
$upp_api->printColorPicker( 1, 'TAMHBGColor', PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR );
echo '</tr>';

for ( $columnIndex = 1; $columnIndex <= plugin_config_get( 'CAmount' ); $columnIndex++ )
{
   $upp_api->printConfigRow();
   echo '<td class="category" colspan="1" rowspan="1">';
   echo plugin_lang_get( 'config_CStatSelect' ) . ' ' . $columnIndex . ':';
   echo '</td>';
   echo '<td valign="top" width="100px" colspan="1" rowspan="1">';
   echo '<select name="CStatSelect' . $columnIndex . '">';
   print_enum_string_option_list( 'status', plugin_config_get( 'CStatSelect' . $columnIndex ) );
   echo '</select>';
   echo '</td>';
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_IAMThreshold' ) . '<br>';
   echo '<span class="small">' . plugin_lang_get( 'config_IAGMThresholdExpl' ) . '</span>';
   echo '</td>';
   echo '<td  colspan="1">';
   ?>
   <label><input type="number" name="IAMThreshold<?php echo $columnIndex ?>"
                 value="<?php echo plugin_config_get( 'IAMThreshold' . $columnIndex, 5 ); ?>" min="0"/></label>
   <?php
   echo '</td>';
   echo '<td class="category" colspan="1">';
   echo plugin_lang_get( 'config_IAGThreshold' ) . '<br>';
   echo '<span class="small">' . plugin_lang_get( 'config_IAGMThresholdExpl' ) . '</span>';
   echo '</td>';
   echo '<td  colspan="1">';
   ?>
   <label><input type="number" name="IAGThreshold<?php echo $columnIndex ?>"
                 value="<?php echo plugin_config_get( 'IAGThreshold' . $columnIndex, 30 ); ?>" min="0"/></label>
   <?php
   echo '</td>';
   echo '</tr>';
}

$upp_api->printSpacer( 6 );
$upp_api->printConfigTitle( 6, 'config_URIFilter' );

$upp_api->printConfigRow();
$upp_api->printConfigCategory( 1, 1, 'config_URIThreshold' );
echo '<td valign="top" width="100px" colspan="7">';
echo '<select name="URIThreshold[]" multiple="multiple">';
print_enum_string_option_list( 'status', plugin_config_get( 'URIThreshold', 50 ) );
echo '</select>';
echo '</td>';
echo '</tr>';

$upp_api->printSpacer( 6 );
echo '<tr>';
echo '<td class="center" colspan="6">';
echo '<input type="submit" name="change" class="button" value="' . lang_get( 'update_prefs_button' ) . '"/>' . ' ';
echo '<input type="submit" name="reset" class="button" value="' . lang_get( 'reset_prefs_button' ) . '"/>';
echo '</td>';
echo '</tr>';

echo '</table>';

if ( substr( MANTIS_VERSION, 0, 4 ) != '1.2.' )
{
   echo '</div>';
}

echo '</form>';

html_page_bottom1();