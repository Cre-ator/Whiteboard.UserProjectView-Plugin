<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );

form_security_validate( 'plugin_UserProjectView_config_update' );

require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'UserProjectView_api.php';

// UserProjectView_api object
$upv_api = new UserProjectView_api();

$option_reset = gpc_get_bool( 'reset', false );
$option_change = gpc_get_bool( 'change', false );

if ( $option_reset )
{
   $upv_api->config_resetPlugin();
}
elseif ( $option_change )
{
   $upv_api->config_updateValue( 'UserProjectAccessLevel', ADMINISTRATOR );

   $upv_api->config_updateButton( 'ShowMenu' );
   $upv_api->config_updateButton( 'ShowInFooter' );
   $upv_api->config_updateButton( 'ShowAvatar' );

   $upv_api->config_updateButton( 'IAUHighlighting' );
   $upv_api->config_updateColor( 'IAUHBGColor', PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR );

   $upv_api->config_updateButton( 'URIUHighlighting' );
   $upv_api->config_updateColor( 'URIUHBGColor', PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR );

   $upv_api->config_updateButton( 'NUIHighlighting' );
   $upv_api->config_updateColor( 'NUIHBGColor', PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR );

   $upv_api->config_updateButton( 'ShowZIU' );
   $upv_api->config_updateButton( 'ZIHighlighting' );
   $upv_api->config_updateColor( 'ZIHBGColor', PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR );

   $upv_api->config_updateColor( 'TAMHBGColor', PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR );

   $colAmount = gpc_get_int( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT );
   if ( plugin_config_get( 'CAmount' ) != $colAmount && plugin_config_get( 'CAmount' ) != '' && $colAmount <= PLUGINS_USERPROJECTVIEW_MAX_COLUMNS ) {
      plugin_config_set( 'CAmount', $colAmount );
   }
   elseif ( plugin_config_get( 'CAmount' ) == '' ) {
      plugin_config_set( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT );
   }

   if ( !empty( $_POST['URIThreshold'] ) ) {
      foreach ( $_POST['URIThreshold'] as $unreachableIssueThreshold ) {
         $unreachableIssueThreshold = gpc_get_int_array( 'URIThreshold' );
         if ( plugin_config_get( 'URIThreshold' ) != $unreachableIssueThreshold ) {
            plugin_config_set( 'URIThreshold', $unreachableIssueThreshold );
         }
      }
   }

   $upv_api->config_updateDynamicValues( 'CStatSelect', PLUGINS_USERPROJECTVIEW_COLUMN_STAT_DEFAULT );
   $upv_api->config_updateDynamicValues( 'IAMThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAMTHRESHOLD );
   $upv_api->config_updateDynamicValues( 'IAGThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAGTHRESHOLD );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );
