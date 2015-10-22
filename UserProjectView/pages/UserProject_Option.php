<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'UserProjectView_api.php';

auth_reauthenticate();

html_page_top1( plugin_lang_get( 'menu_title' ) );
html_page_top2();

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

// UserProjectView_api object
$upv_api = new UserProjectView_api();

$selectedValues = $_POST['dataRow'];
$recordCount = count( $selectedValues );

$select = strtolower( $_POST['option'] );

switch ( $select )
{
	case 'removesingle':
		
		echo '<div align="center">';
		echo '<hr size="1" width="50%" />';
		echo plugin_lang_get( 'remove_quest' ) . '<br/><br/>';
		
		echo '<table class="width50" cellspacing="1">';
		echo '<tr class="row-category">';
		echo '<th>' . plugin_lang_get( 'thead_username' ) . '</th>';
		echo '<th>' . plugin_lang_get( 'thead_project' ) . '</th>';
		echo '</tr>';
		
		for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
		{
			$record[$recordIndex] = explode( '__', $selectedValues[$recordIndex] );
			
			$userId = $record[$recordIndex][0];
			$projectId = $record[$recordIndex][1];
			
			echo '<form action="'. plugin_page( 'UserProject_RemoveSubmit' ) . '" method="post">';
			echo '<input type="hidden" name="records[]" value="' . $selectedValues[$recordIndex] . '"/>';
			
			if ( $upv_api->getActMantisVersion() == '1.2.' )
			{
				echo '<tr ' . helper_alternate_class() . '>';
			}
			else
			{
				echo '<tr>';
			}
			
			echo '<td>';
			echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
			echo user_get_name( $userId );
			echo '</a>';
			echo '</td>';
			echo '<td>';
			echo '<a href="manage_proj_edit_page.php?project_id=' . $projectId . '">';
			echo project_get_name( $projectId );
			echo '</a>';
			echo '</td>';
			echo '</tr>';
		}
		
		echo '<tr>';
		echo '<td class="center" colspan="2">';
		?>
		<input type="submit" name="formSubmit" class="button" value="<?php echo plugin_lang_get( 'remove_selectSingle' ); ?>" />
		<?php
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		echo '<hr size="1" width="50%" /></div>';
		echo '</div>';

		break;
		
	case 'removeall':
		
		echo '<div align="center">';
		echo '<hr size="1" width="50%" />';
		echo plugin_lang_get( 'remove_quest' ) . '<br/><br/>';
		
		echo '<table class="width50" cellspacing="1">';
		echo '<tr class="row-category">';
		echo '<th>' . plugin_lang_get( 'thead_username' ) . '</th>';
		echo '<th>' . plugin_lang_get( 'thead_project' ) . '</th>';
		echo '</tr>';
		
		for ( $recordIndex = 0; $recordIndex < $recordCount; $recordIndex++ )
		{
			$record[$recordIndex] = explode( '__', $selectedValues[$recordIndex] );
			
			$userId = $record[$recordIndex][0];
			$projectId = $record[$recordIndex][1];
			
			$subProjects = array();
			array_push( $subProjects, $projectId );
			$tSubProjects = array();
			$tSubProjects = project_hierarchy_get_all_subprojects( $projectId );
			
			foreach ( $tSubProjects as $tSubProject )
			{
				array_push( $subProjects, $tSubProject );
			}
			
			foreach ( $subProjects as $subProject )
			{
				echo '<form action="'. plugin_page( 'UserProject_RemoveSubmit' ) . '" method="post">';
				echo '<input type="hidden" name="user[]" value="' . $userId . '"/>';
				echo '<input type="hidden" name="project[]" value="' . $subProject . '"/>'; 
				
				if ( $upv_api->getActMantisVersion() == '1.2.' )
				{
					echo '<tr ' . helper_alternate_class() . '>';
				}
				else 
				{
					echo '<tr>';
				}
				
				echo '<td>';
				echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
				echo user_get_name( $userId );
				echo '</a>';
				echo '</td>';
				echo '<td>';
				echo '<a href="manage_proj_edit_page.php?project_id=' . $subProject . '">';
				echo project_get_name( $subProject );
				echo '</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '<tr>';
			echo '<td class="spacer" colspan="6">&nbsp;</td>';
			echo '</tr>';
		}
		
		echo '<tr>';
		echo '<td class="center" colspan="2">';
		?>
		<input type="submit" name="formSubmit" class="button" value="<?php echo plugin_lang_get( 'remove_selectAll' ); ?>" />
		<?php
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		
		echo '<hr size="1" width="50%" /></div>';
		echo '</div>';
		
		break;
}

html_page_bottom();