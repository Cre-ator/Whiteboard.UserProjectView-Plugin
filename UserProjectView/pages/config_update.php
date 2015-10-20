<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'UserProjectAccessLevel' ) );

form_security_validate( 'plugin_UserProjectView_config_update' );

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
   if ( is_int( $value ) )
   {
	   $actValue = gpc_get_int( $value, $constant );
   }

   if ( is_string( $value) )
   {
      $actValue = gpc_get_string( $value, $constant );
   }

	if ( plugin_config_get( $value ) != $actValue )
	{
		plugin_config_set( $value, $actValue );
	}
}

function updateDynamicValues( $value, $constant )
{
   $cAmount = plugin_config_get( 'CAmount' );

   for ( $columnIndex = 1; $columnIndex <= $cAmount; $columnIndex++ )
   {
      $actValue = $value . $columnIndex;

      updateValue( $actValue, $constant );
   }
}

updateValue( 'UserProjectAccessLevel', ADMINISTRATOR );

updateButtonConfiguration( 'ShowMenu' );
updateButtonConfiguration( 'ShowInFooter' );
updateButtonConfiguration( 'ShowAvatar' );

updateButtonConfiguration( 'IAUHighlighting' );
updateColorConfiguration ( 'IAUHBGColor', '#663300' );

updateButtonConfiguration( 'URIUHighlighting' );
updateColorConfiguration ( 'URIUHBGColor', '#663300' );

updateButtonConfiguration( 'NUIHighlighting' );
updateColorConfiguration ( 'NUIHBGColor', '#663300' );

updateButtonConfiguration( 'ShowZIU' );
updateButtonConfiguration( 'ZIHighlighting' );
updateColorConfiguration ( 'ZIHBGColor', '#663300' );

updateColorConfiguration( 'TAMHBGColor', '#663300' );

updateDynamicValues( 'CStatSelect', 50 );
updateDynamicValues( 'IAMThreshold', 5 );
updateDynamicValues( 'IAGThreshold', 30 );


$colAmount = gpc_get_string( 'CAmount', 1 );

if ( plugin_config_get( 'CAmount' ) != $colAmount && plugin_config_get( 'CAmount' ) != '' )
{
	plugin_config_set( 'CAmount', $colAmount );
}
elseif ( plugin_config_get( 'CAmount' ) == '' )
{
	plugin_config_set( 'CAmount', 1 );
}

if ( !empty( $_POST['URIThreshold'] ) )
{
	foreach ( $_POST['URIThreshold'] as $unreachableIssueThreshold )
	{
		$unreachableIssueThreshold = gpc_get_int_array( 'URIThreshold' );
		if ( plugin_config_get( 'URIThreshold' ) != $unreachableIssueThreshold )
		{
			plugin_config_set( 'URIThreshold', $unreachableIssueThreshold );
		}
	}
}

form_security_purge( 'plugin_UserProjectView_config_update' );

print_successful_redirect( plugin_page( 'config_page', true ) );
