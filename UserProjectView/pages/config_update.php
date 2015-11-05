<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );

form_security_validate( 'plugin_UserProjectView_config_update' );

require_once( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'UPSystem_api.php';
include USERPROJECTVIEW_CORE_URI . 'UPConfig_api.php';
include USERPROJECTVIEW_CORE_URI . 'UPDatabase_api.php';

// UserProjectView_api object
$upc_api = new UPConfig_api();
$upv_api = new UPSystem_api();
$upd_api = new UPDatabase_api();

$option_reset = gpc_get_bool( 'reset', false );
$option_change = gpc_get_bool( 'change', false );

if ( $option_reset )
{
   $upd_api->resetPlugin();
}
elseif ( $option_change )
{
   $upc_api->updateValue( 'UserProjectAccessLevel', ADMINISTRATOR );

   $upc_api->updateButton( 'ShowMenu' );
   $upc_api->updateButton( 'ShowInFooter' );
   $upc_api->updateButton( 'ShowAvatar' );

   $upc_api->updateButton( 'IAUHighlighting' );
   $upc_api->updateColor( 'IAUHBGColor', PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR );

   $upc_api->updateButton( 'URIUHighlighting' );
   $upc_api->updateColor( 'URIUHBGColor', PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR );

   $upc_api->updateButton( 'NUIHighlighting' );
   $upc_api->updateColor( 'NUIHBGColor', PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR );

   $upc_api->updateButton( 'ShowZIU' );
   $upc_api->updateButton( 'ZIHighlighting' );
   $upc_api->updateColor( 'ZIHBGColor', PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR );

   $upc_api->updateColor( 'TAMHBGColor', PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR );

   $col_amount = gpc_get_int( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT );
   if ( plugin_config_get( 'CAmount' ) != $col_amount && plugin_config_get( 'CAmount' ) != '' && $col_amount <= PLUGINS_USERPROJECTVIEW_MAX_COLUMNS )
   {
      plugin_config_set( 'CAmount', $col_amount );
   }
   elseif ( plugin_config_get( 'CAmount' ) == '' )
   {
      plugin_config_set( 'CAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT );
   }

   if ( !empty( $_POST['URIThreshold'] ) )
   {
      foreach ( $_POST['URIThreshold'] as $unreach_issue_threshold )
      {
         $unreach_issue_threshold = gpc_get_int_array( 'URIThreshold' );
         if ( plugin_config_get( 'URIThreshold' ) != $unreach_issue_threshold )
         {
            plugin_config_set( 'URIThreshold', $unreach_issue_threshold );
         }
      }
   }

   $upc_api->updateDynamicValues( 'CStatSelect', PLUGINS_USERPROJECTVIEW_COLUMN_STAT_DEFAULT );
   $upc_api->updateDynamicValues( 'IAMThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAMTHRESHOLD );
   $upc_api->updateDynamicValues( 'IAGThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAGTHRESHOLD );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );
