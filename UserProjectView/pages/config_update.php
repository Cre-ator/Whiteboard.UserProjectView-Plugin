<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );
form_security_validate( 'plugin_UserProjectView_config_update' );

require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_config_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_database_api.php';

$userprojectview_config_api = new userprojectview_config_api();
$userprojectview_database_api = new userprojectview_database_api();

$option_reset = gpc_get_bool( 'reset', false );
$option_change = gpc_get_bool( 'change', false );

if ( $option_reset )
{
   $userprojectview_database_api->resetPlugin();
}

if ( $option_change )
{
   $userprojectview_config_api->updateValue( 'UserProjectAccessLevel', ADMINISTRATOR );

   $userprojectview_config_api->updateButton( 'ShowMenu' );
   $userprojectview_config_api->updateButton( 'ShowInFooter' );
   $userprojectview_config_api->updateButton( 'ShowAvatar' );
   $userprojectview_config_api->updateButton( 'showHeadRow' );

   $userprojectview_config_api->updateButton( 'IAUHighlighting' );
   $userprojectview_config_api->updateColor( 'IAUHBGColor', PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR );

   $userprojectview_config_api->updateButton( 'URIUHighlighting' );
   $userprojectview_config_api->updateColor( 'URIUHBGColor', PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR );

   $userprojectview_config_api->updateButton( 'NUIHighlighting' );
   $userprojectview_config_api->updateColor( 'NUIHBGColor', PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR );

   $userprojectview_config_api->updateButton( 'ShowZIU' );
   $userprojectview_config_api->updateButton( 'ZIHighlighting' );
   $userprojectview_config_api->updateColor( 'ZIHBGColor', PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR );

   $userprojectview_config_api->updateColor( 'TAMHBGColor', PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR );

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

   $userprojectview_config_api->updateDynamicValues( 'CStatSelect', PLUGINS_USERPROJECTVIEW_COLUMN_STAT_DEFAULT );
   $userprojectview_config_api->updateDynamicValues( 'IAMThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAMTHRESHOLD );
   $userprojectview_config_api->updateDynamicValues( 'IAGThreshold', PLUGINS_USERPROJECTVIEW_COLUMN_IAGTHRESHOLD );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );
