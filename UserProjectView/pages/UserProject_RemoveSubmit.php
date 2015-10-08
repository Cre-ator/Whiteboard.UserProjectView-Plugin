<?php

$records = $_POST['records'];
$userIds = $_POST['user']; 
$projectIds = $_POST['project'];

if ( $records != null )
{
	$recordCount = count( $records );
	
	for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
	{
		$record[$recordIndex] = explode( '__', $records[$recordIndex] );
	}
	
	for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
	{
		project_remove_user( $record[$recordIndex][1], $record[$recordIndex][0] );
	}
}
elseif ( $userIds != null && $projectIds != null )
{
	$uCount = count( $userIds );
	$pCount = count( $projectIds );
	
	if ( $uCount == $pCount )
	{
		for ( $dIndex = 0; $dIndex < $uCount; $dIndex++ )
		{
			project_remove_user( $projectIds[$dIndex], $userIds[$dIndex] );
		}
	}
	else
	{
		echo plugin_lang_get( 'remove_failure' );
	}
}

$redirectUrl = 'plugin.php?page=UserProjectView/UserProject&sortVal=userName&sort=ASC';

html_page_top( null, $redirectUrl );

echo '<div align="center">';
echo plugin_lang_get( 'remove_confirm' );
echo '</div>';