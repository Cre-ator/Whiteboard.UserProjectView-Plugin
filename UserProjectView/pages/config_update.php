<?php

$reload='<script language="javascript">document.location.reload();</script>';

form_security_validate( 'plugin_UserProjectView_config_update' );

access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );
auth_reauthenticate();

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$ShowInFooter = gpc_get_int( 'ShowInFooter', ON );

if ( plugin_config_get( 'ShowInFooter' ) != $ShowInFooter )
{
   plugin_config_set( 'ShowInFooter', $ShowInFooter );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$ShowMenu = gpc_get_int( 'ShowMenu', ON );

if ( plugin_config_get( 'ShowMenu' ) != $ShowMenu )
{
   plugin_config_set( 'ShowMenu', $ShowMenu );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$zIssueHighlighting = gpc_get_int( 'ZIssueHighlighting', ON );

if ( plugin_config_get( 'ZIssueHighlighting' ) != $zIssueHighlighting )
{
	plugin_config_set( 'ZIssueHighlighting', $zIssueHighlighting );
}


$zIBackgroundColor = gpc_get_string( 'ZIBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'ZIBGColor' ) != $zIBackgroundColor && plugin_config_get( 'ZIBGColor' ) != '' )
{
	plugin_config_set( 'ZIBGColor', $zIBackgroundColor );
}
elseif (plugin_config_get( 'ZIBGColor' ) == '' )
{
	plugin_config_set( 'ZIBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$colAmount = gpc_get_string( 'colAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT_DEFAULT );

if ( plugin_config_get( 'colAmount' ) != $colAmount && plugin_config_get( 'colAmount' ) != '' )
{
	plugin_config_set( 'colAmount', $colAmount );
}
elseif (plugin_config_get( 'colAmount' ) == '' )
{
	plugin_config_set( 'colAmount', PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT_DEFAULT );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$cTFHighlighting = gpc_get_int( 'CTFHighlighting', ON );

if ( plugin_config_get( 'CTFHighlighting' ) != $cTFHighlighting )
{
	plugin_config_set( 'CTFHighlighting', $cTFHighlighting );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$oIHighlighting = gpc_get_int( 'OIHighlighting', ON );

if ( plugin_config_get( 'OIHighlighting' ) != $oIHighlighting )
{
	plugin_config_set( 'OIHighlighting', $oIHighlighting );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
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

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$iTBGColor = gpc_get_string( 'ITBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );

if ( plugin_config_get( 'ITBGColor' ) != $iTBGColor && plugin_config_get( 'ITBGColor' ) != '' )
{
	plugin_config_set( 'ITBGColor', $iTBGColor );
}
elseif (plugin_config_get( 'ITBGColor' ) == '' )
{
	plugin_config_set( 'ITBGColor', PLUGINS_USERPROJECTVIEW_BACKGROUND_COLOR_DEFAULT );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$oldIssueThreshold1 = gpc_get_int( 'oldIssueThreshold1', 0 );

if ( plugin_config_get( 'oldIssueThreshold1' ) != $oldIssueThreshold1 )
{
	plugin_config_set( 'oldIssueThreshold1', $oldIssueThreshold1 );
}

$oldIssueThreshold2 = gpc_get_int( 'oldIssueThreshold2', 0 );

if ( plugin_config_get( 'oldIssueThreshold2' ) != $oldIssueThreshold2 )
{
	plugin_config_set( 'oldIssueThreshold2', $oldIssueThreshold2 );
}

$oldIssueThreshold3 = gpc_get_int( 'oldIssueThreshold3', 0 );

if ( plugin_config_get( 'oldIssueThreshold3' ) != $oldIssueThreshold3 )
{
	plugin_config_set( 'oldIssueThreshold3', $oldIssueThreshold3 );
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$UserProjectAccessLevel = gpc_get_int( 'UserProjectAccessLevel' );

if ( plugin_config_get( 'UserProjectAccessLevel' ) != $UserProjectAccessLevel )
{
   plugin_config_set( 'UserProjectAccessLevel', $UserProjectAccessLevel );
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );