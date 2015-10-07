<?php

$records = $_POST['records'];

$recordCount = count( $records );

for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
{
	$record[$recordIndex] = explode( '__', $records[$recordIndex] );
}

for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
{
	project_remove_user( $record[$recordIndex][1], $record[$recordIndex][0] );
}

$redirectUrl = 'plugin.php?page=UserProjectView/UserProject&sortVal=userName&sort=ASC';

html_page_top( null, $redirectUrl );

echo '<div align="center">';
echo plugin_lang_get( 'remove_confirm' );
echo '</div>';