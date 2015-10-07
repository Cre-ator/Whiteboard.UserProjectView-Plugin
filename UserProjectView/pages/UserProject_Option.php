<?php
auth_reauthenticate();

html_page_top1( plugin_lang_get( 'userProject_title' ) );
html_page_top2();


$selectedValues = $_POST['dataRow'];
$recordCount = count( $selectedValues );

$select = strtolower( $_POST['option'] );

switch ( $select )
{
	case 'remove':
		
		for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
		{
			$record[$recordIndex] = explode( '__', $selectedValues[$recordIndex] );
			echo '<form action="'. plugin_page( 'UserProject_RemoveSubmit' ) . '" method="post">';
			echo '<input type="hidden" name="records[]" value="' . $selectedValues[$recordIndex] . '"/>';
		}
		
		echo '<div align="center">';
		echo '<hr size="1" width="50%" />';
		echo plugin_lang_get( 'remove_quest' ) . '<br><br>';
		
		for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
		{
			echo plugin_lang_get( 'username' ) . ': ' . user_get_name( $record[$recordIndex][0] ) . ' - ';
			echo plugin_lang_get( 'project' ) . ': ' . project_get_name( $record[$recordIndex][1] ) . '<br>';
		}
		
		?>
		<input type="submit" name="formSubmit" class="button" value="<?php echo plugin_lang_get( 'remove_submit' ); ?>" />
		<?php
		
		echo '<hr size="1" width="50%" /></div>';
		echo '</div>';
		
		break;
}

html_page_bottom();