<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );

form_security_validate( 'plugin_UserProjectView_config_update' );

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

function includeLeadingColorIdentifier( $Color )
{
   if ( "#" == $Color [0] )
      return $Color;
   else
      return "#" . $Color;
}

function updateColorConfiguration( $FieldName, $DefaultColor )
{
   $DefaultColor        = includeLeadingColorIdentifier( $DefaultColor );
   $iAbackgroundcolor   = includeLeadingColorIdentifier( gpc_get_string( $FieldName, $DefaultColor ) );
   if (  plugin_config_get( $FieldName ) != $iAbackgroundcolor
      && plugin_config_get( $FieldName ) != ''
      )
   {
      plugin_config_set( $FieldName, $iAbackgroundcolor );
   }
   elseif ( plugin_config_get( $FieldName ) == '' )
   {
      plugin_config_set( $FieldName, $DefaultColor );
   }
}

function updateButtonConfiguration( $config )
{
	$button = gpc_get_int( $config );
	
	if ( plugin_config_get( $config ) != $button )
	{
		plugin_config_set( $config, $button );
	}
}

function updateValue( $value, $constant )
{
	$actValue = gpc_get_int( $value, $constant );

	if ( plugin_config_get( $value ) != $actValue )
	{
		plugin_config_set( $value, $actValue );
	}
}

# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
updateValue( 'UserProjectAccessLevel', ADMINISTRATOR );

updateButtonConfiguration( 'ShowInFooter' );

updateButtonConfiguration( 'ShowMenu' );

updateButtonConfiguration( 'IAUserHighlighting' );
updateColorConfiguration ( 'IABGColor', '#663300' );

updateButtonConfiguration( 'URUserHighlighting' );
updateColorConfiguration ( 'URBGColor', '#663300' );

updateButtonConfiguration( 'NUIssueHighlighting' );
updateColorConfiguration ( 'NUBGColor', '#663300' );

updateButtonConfiguration( 'ShowZIUsers' );
updateButtonConfiguration( 'ZIssueHighlighting' );
updateColorConfiguration ( 'ZIBGColor', '#663300' );

updateButtonConfiguration( 'CTFHighlighting' );

updateButtonConfiguration( 'OIHighlighting' );

updateColorConfiguration ( 'ITBGColor', '#663300' );

$colAmount = gpc_get_string( 'colAmount', 1 );

if ( plugin_config_get( 'colAmount' ) != $colAmount && plugin_config_get( 'colAmount' ) != '' )
{
	plugin_config_set( 'colAmount', $colAmount );
}
elseif ( plugin_config_get( 'colAmount' ) == '' )
{
	plugin_config_set( 'colAmount', 1 );
}

for ( $columnIndex = 1; $columnIndex <= plugin_config_get( 'colAmount' ); $columnIndex++ )
{
	$stat = 'statselectcol' . $columnIndex;
	
	updateValue( $stat, 50 );
}

for ( $columnIndex = 1; $columnIndex <= plugin_config_get( 'colAmount' ); $columnIndex++ )
{
	$issueThreshold = 'issueThreshold' . $columnIndex;
	
	updateValue( $issueThreshold, 5 );
}

for ( $columnIndex = 1; $columnIndex <= plugin_config_get( 'colAmount' ); $columnIndex++ )
{
	$oldIssueThreshold = 'oldIssueThreshold' . $columnIndex;
	
	updateValue( $oldIssueThreshold, 30 );
}

if ( !empty( $_POST['UnreachableIssueThreshold'] ) )
{
	foreach ( $_POST['UnreachableIssueThreshold'] as $unreachableIssueThreshold )
	{
		$unreachableIssueThreshold = gpc_get_int_array( 'UnreachableIssueThreshold' );
		if ( plugin_config_get( 'UnreachableIssueThreshold' ) != $unreachableIssueThreshold )
		{
			plugin_config_set( 'UnreachableIssueThreshold', $unreachableIssueThreshold );
		}
	}
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );
