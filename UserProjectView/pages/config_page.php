<?php
require_once USERPROJECTVIEW_CORE_URI . 'constantapi.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectapi.php';

auth_reauthenticate ();
access_ensure_global_level ( plugin_config_get ( 'UserProjectAccessLevel' ) );

html_page_top1 ( plugin_lang_get ( 'config_title' ) );
html_page_top2 ();

print_manage_menu ();

echo '<script type="text/javascript" src="plugins/UserProjectView/javascript/jscolor/jscolor.js"></script>';
echo '<br/>';
echo '<form action="' . plugin_page ( 'config_update' ) . '" method="post">';
echo form_security_field ( 'plugin_UserProjectView_config_update' );

if ( is_mantis_rel () )
{
   echo '<table align="center" class="width75" cellspacing="1">';
}
else
{
   echo '<div class="form-container">';
   echo '<table>';
}
print_config_table_title_row ( 5, 'config_caption' );
print_config_table_row ();
echo '<td class="category" colspan="2">';
echo '<span class="required">*</span>' . plugin_lang_get ( 'config_accesslevel' );
echo '</td>';
echo '<td width="100px" colspan="5">';
echo '<select name="UserProjectAccessLevel">';
print_enum_string_option_list ( 'access_levels', plugin_config_get ( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT ) );
echo '</select>';
echo '</td>';
echo '</tr>';

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_showMenu' );
print_config_table_radio_button_col ( 3, 'ShowMenu' );
echo '</tr>';

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_showFooter' );
print_config_table_radio_button_col ( 3, 'ShowInFooter' );
echo '</tr>';

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_showAvatar' );
print_config_table_radio_button_col ( 3, 'ShowAvatar' );
echo '</tr>';

print_config_table_title_row ( 5, 'config_highlighting' );

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_IAUHighlighting' );
print_config_table_radio_button_col ( 1, 'IAUHighlighting' );
print_config_table_category_col ( 1, 1, 'config_BGColor' );
print_config_table_color_picker_row ( 1, 'IAUHBGColor', PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR );
echo '</tr>';

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_URIUHighlighting' );
print_config_table_radio_button_col ( 1, 'URIUHighlighting' );
print_config_table_category_col ( 1, 1, 'config_BGColor' );
print_config_table_color_picker_row ( 1, 'URIUHBGColor', PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR );
echo '</tr>';

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_NUIHighlighting' );
print_config_table_radio_button_col ( 1, 'NUIHighlighting' );
print_config_table_category_col ( 1, 1, 'config_BGColor' );
print_config_table_color_picker_row ( 1, 'NUIHBGColor', PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR );
echo '</tr>';

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_showZIU' );
print_config_table_radio_button_col ( 3, 'ShowZIU' );
echo '</tr>';

print_config_table_row ();
echo '<td class="category" colspan="2">';
echo plugin_lang_get ( 'config_ZIHighlighting' ) . '<br/>';
echo '<span class="small">' . plugin_lang_get ( 'config_ZIUExpl' ) . '</span>';
echo '</td>';
print_config_table_radio_button_col ( 1, 'ZIHighlighting' );
print_config_table_category_col ( 1, 1, 'config_BGColor' );
print_config_table_color_picker_row ( 3, 'ZIHBGColor', PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR );
echo '</tr>';

print_config_table_title_row ( 5, 'config_layer_one_column' );
print_config_table_row ();
echo '<td class="category" colspan="2">';
echo plugin_lang_get ( 'config_layer_one_column_name' );
echo '</td>';
echo '<td width="100px" colspan="3">';
echo '<input type="radio" name="layer_one_name" value="0"';
echo ( 0 == plugin_config_get ( 'layer_one_name' ) ) ? 'checked="checked"' : '';
echo '/>' . plugin_lang_get ( 'config_layer_one_name_one' );
echo '<input type="radio" name="layer_one_name" value="1"';
echo ( 1 == plugin_config_get ( 'layer_one_name' ) ) ? 'checked="checked"' : '';
echo '/>' . plugin_lang_get ( 'config_layer_one_name_two' );
echo '<input type="radio" name="layer_one_name" value="2"';
echo ( 2 == plugin_config_get ( 'layer_one_name' ) ) ? 'checked="checked"' : '';
echo '/>' . plugin_lang_get ( 'config_layer_one_name_three' );
echo '</td>';
echo '</tr>';

print_config_table_title_row ( 5, 'config_specColumns' );

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_CAmount' );
echo '<td width="100px" colspan="1" rowspan="1">';
?>
   <label><input type="number" name="CAmount"
                 value="<?php echo plugin_config_get ( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT ); ?>" min="1"
                 max="20"/></label>
<?php
echo '</td>';

print_config_table_category_col ( 1, 1, 'config_BGColor' );
print_config_table_color_picker_row ( 1, 'TAMHBGColor', PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR );
echo '</tr>';

if ( plugin_config_get ( 'CAmount' ) > 0 )
{
   echo '<tr>';
   echo '<td class="category">' . plugin_lang_get ( 'config_CStat_Col' ) . '</td>';
   echo '<td class="category">' . plugin_lang_get ( 'config_CStat_Stat' ) . '</td>';
   echo '<td class="category">' . plugin_lang_get ( 'config_IAMThreshold' ) . '<br><span class="small">' . plugin_lang_get ( 'config_IAGMThresholdExpl' ) . '</span></td>';
   echo '<td class="category">' . plugin_lang_get ( 'config_IAGThreshold' ) . '<br><span class="small">' . plugin_lang_get ( 'config_IAGMThresholdExpl' ) . '</span></td>';
   echo '<td class="category">' . plugin_lang_get ( 'config_CStat_Ign' ) . '</td>';
   echo '</tr>';
}

for ( $columnIndex = 1; $columnIndex <= plugin_config_get ( 'CAmount' ); $columnIndex++ )
{
   print_config_table_row ();
   echo '<td class="category" colspan="1" rowspan="1">';
   echo $columnIndex;
   echo '</td>';
   echo '<td valign="top" width="100px" colspan="1" rowspan="1">';
   echo '<select name="CStatSelect' . $columnIndex . '">';
   print_enum_string_option_list ( 'status', plugin_config_get ( 'CStatSelect' . $columnIndex ) );
   echo '</select>';
   echo '</td>';
   echo '<td  colspan="1">';
   ?>
   <label><input type="number" name="IAMThreshold<?php echo $columnIndex ?>"
                 value="<?php echo plugin_config_get ( 'IAMThreshold' . $columnIndex, 5 ); ?>" min="0"/></label>
   <?php
   echo '</td>';
   echo '<td  colspan="1">';
   ?>
   <label><input type="number" name="IAGThreshold<?php echo $columnIndex ?>"
                 value="<?php echo plugin_config_get ( 'IAGThreshold' . $columnIndex, 30 ); ?>" min="0"/></label>
   <?php
   echo '</td>';
   print_config_table_radio_button_col ( 1, 'CStatIgn' . $columnIndex );

   echo '</tr>';
}

print_config_table_title_row ( 5, 'config_URIFilter' );

print_config_table_row ();
print_config_table_category_col ( 2, 1, 'config_URIThreshold' );
echo '<td valign="top" width="100px" colspan="3">';
echo '<select name="URIThreshold[]" multiple="multiple">';
print_enum_string_option_list ( 'status', plugin_config_get ( 'URIThreshold', 50 ) );
echo '</select>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td class="center" colspan="5">';
echo '<input type="submit" name="change" class="button" value="' . lang_get ( 'update_prefs_button' ) . '"/>' . ' ';
echo '<input type="submit" name="reset" class="button" value="' . lang_get ( 'reset_prefs_button' ) . '"/>';
echo '</td>';
echo '</tr>';

echo '</table>';

if ( !is_mantis_rel () )
{
   echo '</div>';
}

echo '</form>';

html_page_bottom1 ();

/**
 * Prints a table row in the plugin config area
 */
function print_config_table_row ()
{
   if ( is_mantis_rel () )
   {
      echo '<tr ' . helper_alternate_class () . '>';
   }
   else
   {
      echo '<tr>';
   }
}

/**
 * Prints a category column in the plugin config area
 *
 * @param $colspan
 * @param $rowspan
 * @param $lang_string
 */
function print_config_table_category_col ( $colspan, $rowspan, $lang_string )
{
   echo '<td class="category" colspan="' . $colspan . '" rowspan="' . $rowspan . '">';
   echo plugin_lang_get ( $lang_string );
   echo '</td>';
}

/**
 * Prints a title row in the plugin config area
 *
 * @param $colspan
 * @param $lang_string
 */
function print_config_table_title_row ( $colspan, $lang_string )
{
   echo '<tr>';
   echo '<td class="form-title" colspan="' . $colspan . '">';
   echo plugin_lang_get ( $lang_string );
   echo '</td>';
   echo '</tr>';
}

/**
 * Prints a radio button element in the plugin config area
 *
 * @param $colspan
 * @param $name
 */
function print_config_table_radio_button_col ( $colspan, $name )
{
   echo '<td width="100px" colspan="' . $colspan . '">';
   echo '<label>';
   echo '<input type="radio" name="' . $name . '" value="1"';
   echo ( ON == plugin_config_get ( $name ) ) ? 'checked="checked"' : '';
   echo '/>' . lang_get ( 'yes' );
   echo '</label>';
   echo '<label>';
   echo '<input type="radio" name="' . $name . '" value="0"';
   echo ( OFF == plugin_config_get ( $name ) ) ? 'checked="checked"' : '';
   echo '/>' . lang_get ( 'no' );
   echo '</label>';
   echo '</td>';
}

/**
 * Prints a color picker element in the plugin config area
 *
 * @param $colspan
 * @param $name
 * @param $default
 */
function print_config_table_color_picker_row ( $colspan, $name, $default )
{
   echo '<td width="100px" colspan="' . $colspan . '">';
   echo '<label>';
   echo '<input class="color {pickerFace:4,pickerClosable:true}" type="text" name="' . $name . '" value="' . plugin_config_get ( $name, $default ) . '" />';
   echo '</label>';
   echo '</td>';
}