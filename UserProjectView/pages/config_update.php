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


$iAbackgroundcolor = gpc_get_string( 'IABGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'IABGColor' ) != $iAbackgroundcolor && plugin_config_get( 'IABGColor' ) != '' )
{
	plugin_config_set( 'IABGColor', $iAbackgroundcolor );
}
elseif (plugin_config_get( 'IABGColor' ) == '' )
{
	plugin_config_set( 'IABGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}


$iAtextcolor = gpc_get_string( 'IATColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'IATColor' ) != $iAtextcolor && plugin_config_get( 'IATColor' ) != '' )
{
	plugin_config_set( 'IATColor', $iAtextcolor );
}
elseif (plugin_config_get( 'IATColor' ) == '' )
{
	plugin_config_set( 'IATColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}


$ShowUnreachableIssuesHighlighting = gpc_get_int( 'URUserHighlighting', ON );

if ( plugin_config_get( 'URUserHighlighting' ) != $ShowUnreachableIssuesHighlighting )
{
	plugin_config_set( 'URUserHighlighting', $ShowUnreachableIssuesHighlighting );
}


$uRbackgroundcolor = gpc_get_string( 'URBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'URBGColor' ) != $uRbackgroundcolor && plugin_config_get( 'URBGColor' ) != '' )
{
	plugin_config_set( 'URBGColor', $uRbackgroundcolor );
}
elseif (plugin_config_get( 'URBGColor' ) == '' )
{
	plugin_config_set( 'URBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}


$uRtextcolor = gpc_get_string( 'URTColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'URTColor' ) != $uRtextcolor && plugin_config_get( 'URTColor' ) != '' )
{
	plugin_config_set( 'URTColor', $uRtextcolor );
}
elseif (plugin_config_get( 'URTColor' ) == '' )
{
	plugin_config_set( 'URTColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}


$noUserIssueHighlighting = gpc_get_int( 'NUIssueHighlighting', ON );

if ( plugin_config_get( 'NUIssueHighlighting' ) != $noUserIssueHighlighting )
{
	plugin_config_set( 'NUIssueHighlighting', $noUserIssueHighlighting );
}


$nUBackgroundColor = gpc_get_string( 'NUBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'NUBGColor' ) != $nUBackgroundColor && plugin_config_get( 'NUBGColor' ) != '' )
{
	plugin_config_set( 'NUBGColor', $nUBackgroundColor );
}
elseif (plugin_config_get( 'NUBGColor' ) == '' )
{
	plugin_config_set( 'NUBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}


$nUTextColor = gpc_get_string( 'NUTColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'NUTColor' ) != $nUTextColor && plugin_config_get( 'NUTColor' ) != '' )
{
	plugin_config_set( 'NUTColor', $nUTextColor );
}
elseif (plugin_config_get( 'NUTColor' ) == '' )
{
	plugin_config_set( 'NUTColor', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}


$colAmount = gpc_get_string( 'colAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT_DEFAULT );

if ( plugin_config_get( 'colAmount' ) != $colAmount && plugin_config_get( 'colAmount' ) != '' )
{
	plugin_config_set( 'colAmount', $colAmount );
}
elseif (plugin_config_get( 'colAmount' ) == '' )
{
	plugin_config_set( 'colAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT_DEFAULT );
}


$statselectcol1 = gpc_get_int( 'statselectcol1', 50 );

if ( plugin_config_get( 'statselectcol1' ) != $statselectcol1 )
{
	plugin_config_set( 'statselectcol1', $statselectcol1 );
}

$statselectcol2 = gpc_get_int( 'statselectcol2', 50 );

if ( plugin_config_get( 'statselectcol2' ) != $statselectcol2 )
{
	plugin_config_set( 'statselectcol2', $statselectcol2 );
}

$statselectcol3 = gpc_get_int( 'statselectcol3', 50 );

if ( plugin_config_get( 'statselectcol3' ) != $statselectcol3 )
{
	plugin_config_set( 'statselectcol3', $statselectcol3 );
}


$UnreachableIssueThreshold = gpc_get_int( 'UnreachableIssueThreshold' );

if ( plugin_config_get( 'UnreachableIssueThreshold' ) != $UnreachableIssueThreshold )
{
	plugin_config_set( 'UnreachableIssueThreshold', $UnreachableIssueThreshold );
}


$UserProjectAccessLevel = gpc_get_int( 'UserProjectAccessLevel' );

if ( plugin_config_get( 'UserProjectAccessLevel' ) != $UserProjectAccessLevel )
{
   plugin_config_set( 'UserProjectAccessLevel', $UserProjectAccessLevel );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );