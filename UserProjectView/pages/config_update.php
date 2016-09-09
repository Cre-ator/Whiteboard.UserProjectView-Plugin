<?php
require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'constantapi.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'databaseapi.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectapi.php' );

auth_reauthenticate ();
access_ensure_global_level ( config_get ( 'UserProjectAccessLevel' ) );
form_security_validate ( 'plugin_UserProjectView_config_update' );

$option_reset = gpc_get_bool ( 'reset', false );
$option_change = gpc_get_bool ( 'change', false );

if ( $option_reset )
{
   $databaseapi = new databaseapi();
   $databaseapi->reset_plugin ();
   userprojectapi::removePluginFromWhiteboardMenu ();
}

if ( $option_change )
{
   update_single_value ( 'UserProjectAccessLevel', ADMINISTRATOR );
   userprojectapi::editPluginInWhiteboardMenu ( 'plugin_access_level', gpc_get_int ( 'UserProjectAccessLevel', ADMINISTRATOR ) );

   update_button ( 'ShowMenu' );
   userprojectapi::editPluginInWhiteboardMenu ( 'plugin_show_menu', gpc_get_int ( 'ShowMenu' ) );
   update_button ( 'ShowInFooter' );
   update_button ( 'ShowAvatar' );

   update_button ( 'IAUHighlighting' );
   update_color ( 'IAUHBGColor', PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR );

   update_button ( 'URIUHighlighting' );
   update_color ( 'URIUHBGColor', PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR );

   update_button ( 'NUIHighlighting' );
   update_color ( 'NUIHBGColor', PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR );

   update_button ( 'ShowZIU' );
   update_button ( 'ZIHighlighting' );
   update_color ( 'ZIHBGColor', PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR );

   update_button ( 'layer_one_name' );

   update_color ( 'TAMHBGColor', PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR );

   $col_amount = gpc_get_int ( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT );
   if ( plugin_config_get ( 'CAmount' ) != $col_amount && plugin_config_get ( 'CAmount' ) != '' && $col_amount <= PLUGINS_USERPROJECTVIEW_MAX_COLUMNS )
   {
      plugin_config_set ( 'CAmount', $col_amount );
   }
   elseif ( plugin_config_get ( 'CAmount' ) == '' )
   {
      plugin_config_set ( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT );
   }

   if ( !empty( $_POST[ 'URIThreshold' ] ) )
   {
      foreach ( $_POST[ 'URIThreshold' ] as $unreach_issue_threshold )
      {
         $unreach_issue_threshold = gpc_get_int_array ( 'URIThreshold' );
         if ( plugin_config_get ( 'URIThreshold' ) != $unreach_issue_threshold )
         {
            plugin_config_set ( 'URIThreshold', $unreach_issue_threshold );
         }
      }
   }

   update_multiple_values ( 'CStatSelect', PLUGINS_USERPROJECTVIEW_COLUMN_STAT_DEFAULT );
   update_multiple_values ( 'IAMThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAMTHRESHOLD );
   update_multiple_values ( 'IAGThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAGTHRESHOLD );
   update_multiple_values ( 'CStatIgn', PLUGINS_USERPROJECTVIEW_COLUMN_CSTATIGN );
}

form_security_purge ( 'plugin_UserProjectView_config_update' );

print_successful_redirect ( plugin_page ( 'config_page', true ) );


/**
 * Adds the "#"-Tag if necessary
 *
 * @param $color
 * @return string
 */
function include_leading_color_identifier ( $color )
{
   if ( "#" == $color[ 0 ] )
   {
      return $color;
   }
   else
   {
      return "#" . $color;
   }
}

/**
 * Updates a specific color value in the plugin
 *
 * @param $field_name
 * @param $default_color
 */
function update_color ( $field_name, $default_color )
{
   $default_color = include_leading_color_identifier ( $default_color );
   $iA_background_color = include_leading_color_identifier ( gpc_get_string ( $field_name, $default_color ) );

   if ( plugin_config_get ( $field_name ) != $iA_background_color && plugin_config_get ( $field_name ) != '' )
   {
      plugin_config_set ( $field_name, $iA_background_color );
   }
   elseif ( plugin_config_get ( $field_name ) == '' )
   {
      plugin_config_set ( $field_name, $default_color );
   }
}

/**
 * Updates the value set by a button
 *
 * @param $config
 */
function update_button ( $config )
{
   $button = gpc_get_int ( $config );

   if ( plugin_config_get ( $config ) != $button )
   {
      plugin_config_set ( $config, $button );
   }
}

/**
 * Updates the value set by an input text field
 *
 * @param $value
 * @param $constant
 */
function update_single_value ( $value, $constant )
{
   $act_value = null;

   if ( is_int ( $value ) )
   {
      $act_value = gpc_get_int ( $value, $constant );
   }

   if ( is_string ( $value ) )
   {
      $act_value = gpc_get_string ( $value, $constant );
   }

   if ( plugin_config_get ( $value ) != $act_value )
   {
      plugin_config_set ( $value, $act_value );
   }
}

/**
 * Iterates through a specific amount and updates each value
 *
 * @param $value
 * @param $constant
 */
function update_multiple_values ( $value, $constant )
{
   $column_amount = plugin_config_get ( 'CAmount' );

   for ( $columnIndex = 1; $columnIndex <= $column_amount; $columnIndex++ )
   {
      $act_value = $value . $columnIndex;

      update_single_value ( $act_value, $constant );
   }
}
