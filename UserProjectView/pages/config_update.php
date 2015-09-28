<?php

$reload='<script language="javascript">document.location.reload();</script>';

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
	echo $reload;
}

$statselectcol2 = gpc_get_int( 'statselectcol2', 50 );

if ( plugin_config_get( 'statselectcol2' ) != $statselectcol2 )
{
	plugin_config_set( 'statselectcol2', $statselectcol2 );
	echo $reload;
}

$statselectcol3 = gpc_get_int( 'statselectcol3', 50 );

if ( plugin_config_get( 'statselectcol3' ) != $statselectcol3 )
{
	plugin_config_set( 'statselectcol3', $statselectcol3 );
	echo $reload;
}

foreach ($_POST['UnreachableIssueThreshold'] as $unreachableIssueThresholds)
{
	$unreachableIssueThreshold = gpc_get_int_array( 'UnreachableIssueThreshold' );
	if ( plugin_config_get( 'UnreachableIssueThreshold' ) != $unreachableIssueThreshold )
	{
		plugin_config_set( 'UnreachableIssueThreshold', $unreachableIssueThreshold );
	}
}

$issueThreshold1 = gpc_get_int( 'issueThreshold1', 0 );

if ( plugin_config_get( 'issueThreshold1' ) != $issueThreshold1 )
{
	plugin_config_set( 'issueThreshold1', $issueThreshold1 );
}

$issueThreshold2 = gpc_get_int( 'issueThreshold2', 0 );

if ( plugin_config_get( 'issueThreshold2' ) != $issueThreshold2 )
{
	plugin_config_set( 'issueThreshold2', $issueThreshold2 );
}

$issueThreshold3 = gpc_get_int( 'issueThreshold3', 0 );

if ( plugin_config_get( 'issueThreshold3' ) != $issueThreshold3 )
{
	plugin_config_set( 'issueThreshold3', $issueThreshold3 );
}

$iTBGColor1 = gpc_get_string( 'ITBGColor1', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'ITBGColor1' ) != $iTBGColor1 && plugin_config_get( 'ITBGColor1' ) != '' )
{
	plugin_config_set( 'ITBGColor1', $iTBGColor1 );
}
elseif (plugin_config_get( 'ITBGColor1' ) == '' )
{
	plugin_config_set( 'ITBGColor1', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}

$iTBGColor2 = gpc_get_string( 'ITBGColor2', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'ITBGColor2' ) != $iTBGColor2 && plugin_config_get( 'ITBGColor2' ) != '' )
{
	plugin_config_set( 'ITBGColor2', $iTBGColor2 );
}
elseif (plugin_config_get( 'ITBGColor2' ) == '' )
{
	plugin_config_set( 'ITBGColor2', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}

$iTBGColor3 = gpc_get_string( 'ITBGColor3', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'ITBGColor3' ) != $iTBGColor3 && plugin_config_get( 'ITBGColor3' ) != '' )
{
	plugin_config_set( 'ITBGColor3', $iTBGColor3 );
}
elseif (plugin_config_get( 'ITBGColor3' ) == '' )
{
	plugin_config_set( 'ITBGColor3', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}

$iTTColor1 = gpc_get_string( 'ITTColor1', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'ITTColor1' ) != $iTTColor1 && plugin_config_get( 'ITTColor1' ) != '' )
{
	plugin_config_set( 'ITTColor1', $iTTColor1 );
}
elseif (plugin_config_get( 'ITTColor1' ) == '' )
{
	plugin_config_set( 'ITTColor1', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}

$iTTColor2 = gpc_get_string( 'ITTColor2', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'ITTColor2' ) != $iTTColor2 && plugin_config_get( 'ITTColor2' ) != '' )
{
	plugin_config_set( 'ITTColor2', $iTTColor2 );
}
elseif (plugin_config_get( 'ITTColor2' ) == '' )
{
	plugin_config_set( 'ITTColor2', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}

$iTTColor3 = gpc_get_string( 'ITTColor3', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );

if ( plugin_config_get( 'ITTColor3' ) != $iTTColor3 && plugin_config_get( 'ITTColor3' ) != '' )
{
	plugin_config_set( 'ITTColor3', $iTTColor3 );
}
elseif (plugin_config_get( 'ITTColor3' ) == '' )
{
	plugin_config_set( 'ITTColor3', PLUGINS_USERPROJECTVIEW_TEXT_COLOR_DEFAULT );
}

$UserProjectAccessLevel = gpc_get_int( 'UserProjectAccessLevel' );

if ( plugin_config_get( 'UserProjectAccessLevel' ) != $UserProjectAccessLevel )
{
   plugin_config_set( 'UserProjectAccessLevel', $UserProjectAccessLevel );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );