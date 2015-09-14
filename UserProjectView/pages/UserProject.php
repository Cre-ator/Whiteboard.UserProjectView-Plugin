<?php
require_once (USERPROJECTVIEW_CORE_URI . 'constant_api.php');
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1(plugin_lang_get('user_project_view'));

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

// PluginManager object
$pluginManager = new PluginManager();

// actual Project ID
$actProject = helper_get_current_project();

// user material
$allValidUsers = $pluginManager->getAllValidUsers();

while ($user = mysqli_fetch_array($allValidUsers))
{
   $users[] = $user;
}

$userCount = count($users);

echo '<div id="manage-user-div" class="form-container">';

if ($pluginManager->getActMantisVersion() == '1.2.')
{
   echo '<table class="width100" cellspacing="1">';
}
else
{
   echo '<table>';
}
echo '<thead>';
echo '<tr>';
echo '<td class="form-title" colspan="6">' . plugin_lang_get( 'accounts_title' ) . plugin_lang_get( 'projects_title' ) . project_get_name($actProject);
echo '</td>';
echo '<td class="form-title" colspan="7">';
echo '<span class="small">';
//echo '[<a href="' . plugin_page('PrintUserProject') . '">' . plugin_lang_get('print_button') . '</a>]';
echo '</span>';
echo '</td>';
echo '</tr>';
echo '<tr class="row-category">';
echo '<th>' . plugin_lang_get('username') . '</th>';
echo '<th>' . plugin_lang_get('realname') . '</th>';
echo '<th>' . plugin_lang_get('projects') . '</th>';
echo '<th>' . plugin_lang_get('subproject') . '</th>';
echo '<th>' . plugin_lang_get('next_version') . '</th>';
echo '<th>' . plugin_lang_get('issues') . '</th>';
echo '<th>' . plugin_lang_get('wrong_issues') . '</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

// for each user
// -----------------------------------------------------------------------------
for ( $userIndex = 0; $userIndex < $userCount; $userIndex++ )
{
   $user = $users[ $userIndex ];
   $userId = $user['id'];
   $userAccessLevel = $user['access_level'];
   
   $subProjectsByProject = $pluginManager->getSubprojectsByProject( $actProject );
   
   $subProjectCount = count( $subProjectsByProject );
   $filteredBugs = array();
   
   for ( $subProjectIndex = 0; $subProjectIndex < $subProjectCount; $subProjectIndex++ )
   {
   	$subProject = $subProjectsByProject[ $subProjectIndex ];

   	$filteredValidBugsByUserAndProject = $pluginManager->getValidBugsByUserAndProject( $userId, $subProject );
   	
   	while ($filteredValidBug = mysqli_fetch_row($filteredValidBugsByUserAndProject))
   	{
   		$filteredValidBugs[] = $filteredValidBug;
   	}
   }
   
   $allValidBugsByUser = array();
   
   if ( $actProject != 0 )
   {
	   for ($i = 0; $i < count($filteredValidBugs); $i++)
	   {
	   	$allValidBugsByUser[$i] = $filteredValidBugs[$i][0];
	   }
   }
   else
   {
   	$validBugsByUser = $pluginManager->getAllValidBugsByUser( $userId );
   	
   	while ($validBug = mysqli_fetch_row($validBugsByUser))
   	{
   		$allValidBugsByUser[] = $validBug;
   	}
   }
   
   // bug material
   $validBugCount = count($allValidBugsByUser);
   
   $checkEquivalentBugs = false;
   $bugCounter = 1;
   
   // for each bug
   // -----------------------------------------------------------------------
   for ($validBugIndex = 0; $validBugIndex < $validBugCount; $validBugIndex++)
   {	
   	$validBug = $allValidBugsByUser[$validBugIndex];
   	   	
   	$validBugTargetVersion = bug_get_field($validBug[0], 'target_version');
   	$validBugAssignedProject = bug_get_field($validBug[0], 'project_id');
   	$validBugAssignedProjectName = project_get_name($validBugAssignedProject);
   	
//    	if ($validBugTargetVersion == '')
   	{
   		
   	}
//    	else 
   	{
		   $bugCounter = $pluginManager->getAmountOfEqualIssuesByProjectUserTargetVersion($validBugAssignedProject, $userId, $validBugTargetVersion);		
   	}
   	
//    	echo $validBug . '--' . $bugCounter . '--' . bug_get_field($validBug[0], 'id') . ' || ';
   	
   	if ( $bugCounter > 1	&& $checkEquivalentBugs == true )
   	{
   		continue;
   	}
   	elseif ( $bugCounter == 0 )
   	{
   		continue;
   	}
   	else
   	{
	   	if ($pluginManager->getActMantisVersion() == '1.2.')
	   	{
	   		if ( $prevUserIndex != $userIndex )
	   		{
	   			$rowIndex = !$rowIndex;
	   			$prevUserIndex = $userIndex;
	   		}
	   		echo '<tr ' . helper_alternate_class($userIndex) . '>';
	   	}
	   	else
	   	{
	   		echo '<tr>';
	   	}
	   	
	   	
	   	// Column User
	   	if ( user_get_field( $userId, 'enabled' ) == '0' )
	   	{
	   		if ( plugin_config_get( 'IAUserHighlighting' ) )
	   		{
	   			$backgroundcolor = plugin_config_get( 'IABGColor' );
	   			$textcolor = plugin_config_get( 'IATColor' );
	   			echo '<td style="background-color:' . $backgroundcolor . ';color:' . $textcolor . '">';
	   		}
	   		else
	   		{
	   			echo '<td>';
	   		}
	   	}
	   	else
	   	{
	   		echo '<td>';
	   	}
	   	if (access_has_global_level($userAccessLevel))
	   	{
	   		echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
	   		echo utf8_encode(string_display_line($user['username']));
	   		echo '</a>';
	   	}
	   	else
	   	{
	   		echo utf8_encode(string_display_line($user['username']));
	   	}
	   	
	   	echo '</td>';
	   	
	   	
	   	
	   	// Column Real Name
	   	if ( user_get_field( $userId, 'enabled' ) == '0' )
	   	{
	   		if ( plugin_config_get( 'IAUserHighlighting' ) )
	   		{
	   			$backgroundcolor = plugin_config_get( 'IABGColor' );
	   			$textcolor = plugin_config_get( 'IATColor' );
	   			echo '<td style="background-color:' . $backgroundcolor . ';color:' . $textcolor . '">';
	   		}
	   		else
	   		{
	   			echo '<td>';
	   		}
	   	}
	   	else
	   	{
	   		echo '<td>';
	   	}
	   	if (access_has_global_level($userAccessLevel))
	   	{
	   		echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
	   		echo utf8_encode($user['realname']);
	   		echo '</a>';
	   	}
	   	else
	   	{
	   		echo utf8_encode($user['realname']);
	   	}
	   	
	   	echo '</td>';
	   	
	   	
	   	
	   	// Column Main Project
	   	echo '<td>';
	   	
	   	if ($validBugTargetVersion == '')
	   	{
	   		// no target version available -> get main project by project hierarchy
	   		$parent_project = project_hierarchy_get_parent( $validBugAssignedProject, false );
				if ( project_hierarchy_is_toplevel( $validBugAssignedProject ) )
				{
					// selected project is toplevel -> main project
					if (access_has_global_level($userAccessLevel))
					{
						echo '<a href="manage_proj_edit_page.php?project_id=' . $validBugAssignedProject . '">';
						echo $validBugAssignedProjectName;
						echo '</a>';
					}
					else 
					{
						echo $validBugAssignedProjectName;						
					}
				}
	   		else 
	   		{
	   			// selected project is subproject
					while ($parent_project != null)
					{
						$parent_project = project_hierarchy_get_parent($parent_project, false);
						
						if ( project_hierarchy_is_toplevel( $parent_project ) )
						{
							break;
						}
					}
					if (access_has_global_level($userAccessLevel))
					{
						echo '<a href="manage_proj_edit_page.php?project_id=' . $parent_project . '">';
						echo project_get_name($parent_project);
						echo '</a>';
					}
					else
					{
						echo project_get_name($parent_project);
					}
	   		}
	   	}
	   	else
	   	{
	   		// identify main project by target version of selected issue
	   		$mainProjectByVersion = mysqli_fetch_row($pluginManager->getMainProjectByVersion($validBugTargetVersion))[0];
	   		
	   		if (access_has_global_level($userAccessLevel))
	   		{
	   			echo '<a href="manage_proj_edit_page.php?project_id=' . $mainProjectByVersion . '">';
		   		echo project_get_name($mainProjectByVersion);
		   		echo '</a>';
	   		}
	   		else 
	   		{
	   			echo project_get_name($mainProjectByVersion);
	   		}
	   	}
	   	
	   	echo '</td>';
	   	
	   	
	   	
	   	// Column assigned Project
	   	echo '<td>';
	   		
	   	if ( project_hierarchy_is_toplevel($validBugAssignedProject) )
	   	{
	   		// assigned project is toplevel -> already shown in project column
	   	}
	   	else 
	   	{
	   		if (access_has_global_level($userAccessLevel))
	   		{
	   			echo '<a href="manage_proj_edit_page.php?project_id=' . $validBugAssignedProject . '">';
	   			echo $validBugAssignedProjectName;
	   			echo '</a>';
   			}
   			else
   			{
   				echo $validBugAssignedProjectName;
   			}
	   	}

	   	echo '</td>';
	   	
	   	
	   	
	   	// Column Target Version
	   	echo '<td>';
	   	
	   	if ( $validBugTargetVersion != '' )
	   	{
	   		$t_version_rows = version_get_all_rows( $validBugAssignedProject );
	   		$tpl_target_version_string   = '';
	   		$tpl_target_version_string   = prepare_version_string( $validBugAssignedProject, version_get_id( $validBugTargetVersion, $validBugAssignedProject) , $t_version_rows );
	   		 
	   		echo date( 'Y-m-d', version_get_field( version_get_id( $validBugTargetVersion, $validBugAssignedProject ), 'date_order' ) ) . ' ';
	   		
	   		if (access_has_global_level($userAccessLevel))
		      {
		      	echo '<a href="manage_proj_edit_page.php?project_id=' . $mainProjectByVersion . '">';
		      	echo $tpl_target_version_string;
		      	echo '</a>';
		      }
		      else
		      {
		      	echo $tpl_target_version_string;
		      }
	   	}
	   	
	   	echo '</td>';
	   	
	   	
	   	
	   	// Column Issues
	   	echo '<td>';
	   	
	   	echo '<a href="search.php?project_id=' . $validBugAssignedProject . '&status_id='. config_get( 'bug_assigned_status' ) . '&handler_id=' . $userId . '&sticky_issues=on&target_version=' . $validBugTargetVersion . '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
	   	echo $bugCounter;
	   	echo '</a>';
	   	
	   	echo '</td>';
	   	
	   	
	   	
	   	// Column Wrong Issues
	   	echo '<td>';
	   	
	   	echo '</td>';
   	}
   	$checkEquivalentBugs = true;
   }
}

echo '</tbody>';
echo '</table>';
echo '</div>';

html_page_bottom1();