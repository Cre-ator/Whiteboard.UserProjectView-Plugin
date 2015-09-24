<?php
form_security_validate( 'plugin_UserProjectView_config_update' );

access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );
auth_reauthenticate();



$ShowInFooter = gpc_get_int( 'ShowInFooter', ON );

if ( plugin_config_get( 'ShowInFooter' ) != $ShowInFooter )
{
   plugin_config_set( 'ShowInFooter', $ShowInFooter );
}



$ShowMenu = gpc_get_int( 'ShowMenu', ON );

if ( plugin_config_get( 'ShowMenu' ) != $ShowMenu )
{
   plugin_config_set( 'ShowMenu', $ShowMenu );
}



$ShowInactiveUserHighlighting = gpc_get_int( 'IAUserHighlighting', ON );

if ( plugin_config_get( 'IAUserHighlighting' ) != $ShowInactiveUserHighlighting )
{
	plugin_config_set( 'IAUserHighlighting', $ShowInactiveUserHighlighting );
}



$backgroundcolor = gpc_get_string( 'IABGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'IABGColor' ) != $backgroundcolor && plugin_config_get( 'IABGColor' ) != '' )
{
	plugin_config_set( 'IABGColor', $backgroundcolor );
}
elseif (plugin_config_get( 'IABGColor' ) == '' )
{
	plugin_config_set( 'IABGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}


$textcolor = gpc_get_string( 'IATColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'IATColor' ) != $textcolor && plugin_config_get( 'IATColor' ) != '' )
{
	plugin_config_set( 'IATColor', $textcolor );
}
elseif (plugin_config_get( 'IATColor' ) == '' )
{
	plugin_config_set( 'IATColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}


$UserProjectAccessLevel = gpc_get_int( 'UserProjectAccessLevel' );

if ( plugin_config_get( 'UserProjectAccessLevel' ) != $UserProjectAccessLevel )
{
   plugin_config_set( 'UserProjectAccessLevel', $UserProjectAccessLevel );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );