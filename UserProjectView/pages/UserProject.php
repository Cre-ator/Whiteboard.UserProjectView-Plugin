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

// actual Project Details
$projectDetails = $pluginManager->getProjectDetailsByProjectId($actProject);

// user material
$allValidUsers = $pluginManager->getAllValidUsers();

while ($user = mysqli_fetch_array($allValidUsers))
{
   $users[] = $user;
}

$t_user_count = count($users);


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
echo '<td class="form-title" colspan="6">' . plugin_lang_get( 'accounts_title' ) . plugin_lang_get( 'projects_title' );
if ( $actProject == 0 )
{
   echo utf8_encode(plugin_lang_get( 'project_selector_all' ));
}
else
{
   echo $projectDetails[1];
}
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
for ($userIndex = 0; $userIndex < $t_user_count; $userIndex++)
{
   # prefix user data with u_
   $user = $users[$userIndex];
   extract($user, EXTR_PREFIX_ALL, 'u');

   // project material
   $mainProjects = array();
   $mainProjectsByUser = $pluginManager->getAllMainProjectByProjectAndUser($actProject, $user['id']);
	
   while ($mainProject = mysqli_fetch_array($mainProjectsByUser))
   {
      $mainProjects[] = $mainProject;
   }

   $mainProjectCount = count($mainProjects);

   // for each project
   // --------------------------------------------------------------------------
   for ($mainProjectIndex = 0; $mainProjectIndex < $mainProjectCount; $mainProjectIndex++)
   {
      $mainProject = $mainProjects[$mainProjectIndex];

		// bug material
      $validBugs = array();
      $allValidBugsByProjectAndUser = $pluginManager->getAllValidBugsByProjectAndUser($mainProject['id'], $user['id']);
            
      while ($validBug = mysqli_fetch_array($allValidBugsByProjectAndUser))
      {
      	$validBugs[] = $validBug;
      }
      
      $validBugCount = count($validBugs);
      
      $checkEquivalentBugs = false;
      $bugCounter = 1;
      
      // for each bug
      // -----------------------------------------------------------------------
      for ($validBugIndex = 0; $validBugIndex < $validBugCount; $validBugIndex++)
      {  
      	$validBug = $validBugs[$validBugIndex];
      	
      	$project_id = $mainProject['id'];
      	$user_id = $user['id'];
      	$target_version = bug_get_field($validBug['id'], 'target_version');
      	
      	$bugCounter = $pluginManager->getAmountOfEqualIssuesByProjectUserTargetVersion($project_id, $user_id, $target_version);
      	
      	if ( $bugCounter > 1	&& $checkEquivalentBugs == true )
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
		      if ( user_get_field( $user['id'], 'enabled' ) == '0' )
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
		      if (access_has_global_level($u_access_level))
		      {
		      	echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
		      	echo utf8_encode(string_display_line($u_username));
		      	echo '</a>';
		      }
		      else
		      {
		      	echo utf8_encode(string_display_line($u_username));
		      }
		      echo '</td>';
		      
		      
		      
		      // Column Real Name
		      if ( user_get_field( $user['id'], 'enabled' ) == '0' )
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
		      if (access_has_global_level($u_access_level))
		      {
		      	echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
		      	echo utf8_encode($user['realname']);
		      	echo '</a>';
		      }
		      else
		      {
		      	echo utf8_encode($user['realname']);
		      }
		      echo '</td>';
		      
		      
		      
		      // Column Projects
		      echo '<td>';
		      
		      if (access_has_global_level($u_access_level))
		      {
		      	echo '<a href="manage_proj_edit_page.php?project_id=' . $mainProject['id'] . '">';
		      	echo utf8_encode($mainProject['name']);
		      	echo '</a>';
		      }
		      else
		      {
		      	echo utf8_encode($mainProject['name']);
		      }
		      echo '</td>';
		      
		      
		      
		      // Column subproject
		      echo '<td>';
		
		      echo '</td>';
		      
		      
		      
		      // Column Target version
		      echo '<td>';
		      if ( bug_get_field($validBug['id'], 'target_version') != null )
		      {
		      	echo '[' . date( 'Y-m-d', version_get_field( version_get_id( bug_get_field($validBug['id'], 'target_version'), $mainProject['id'] ), 'date_order' ) ) . '] [' . $mainProject['name'] . '] ' . bug_get_field($validBug['id'], 'target_version');
		      }
		      echo '</td>';
		      
		      
		      
		      // Column Issues
		      echo '<td>';
		      echo '<a href="search.php?project_id=' . $mainProject['id'] . '&status_id='. config_get( 'bug_assigned_status' ) . '&handler_id=' . $user['id'] . '&sticky_issues=off&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		      echo $bugCounter . '</a>';
		      echo '</td>';
		      
		      
		      
		      // Column Wrong Issues
		      echo '<td>';
		      
		      echo '</td>';
      	}
      	$checkEquivalentBugs = true;
   
      }
	      
	      
	      
	      

      $SubProjects = array();
      $SubProjects = project_hierarchy_get_all_subprojects($mainProject['id']);
      
      $subProjectCount = count($SubProjects);
      
      // subprojects available
      if ($subProjectCount > 0)
      {
	      // for each subproject
	      // --------------------------------------------------------------------
	      for ($subProjectIndex = 0; $subProjectIndex < $subProjectCount; $subProjectIndex++)
	      {
	      	$subProject = $SubProjects[$subProjectIndex];
	      	$subProjectRow = project_get_row($subProject);
	      	
	      	// bug material
	      	$subValidBugs = array();
	      	$subAllValidBugsByProjectAndUser = $pluginManager->getAllValidBugsByProjectAndUser($subProjectRow['id'], $user['id']);
	      	
	      	while ($subValidBug = mysqli_fetch_array($subAllValidBugsByProjectAndUser))
	      	{
	      		$subValidBugs[] = $subValidBug;
	      	}
	      	
	      	$subValidBugCount = count($subValidBugs);
	      	
	      	$subCheckEquivalentBugs = false;
	      	$subBugCounter = 1;
	      	
	      	// for each bug
	      	// -----------------------------------------------------------------------
	      	for ($validBugIndex = 0; $validBugIndex < $subValidBugCount; $validBugIndex++)
	      	{
	      		$subValidBug = $subValidBugs[$validBugIndex];
	      		 	      		
	      		$sub_project_id = $subProjectRow['id'];
	      		$user_id = $user['id'];
	      		$sub_target_version = bug_get_field($subValidBug['id'], 'target_version');
	      		 
	      		$subBugCounter = $pluginManager->getAmountOfEqualIssuesByProjectUserTargetVersion($sub_project_id, $user_id, $sub_target_version);
	      		 
	      		if ( $subBugCounter > 1	&& $subCheckEquivalentBugs == true )
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
						if ( user_get_field( $user['id'], 'enabled' ) == '0' )
						{
							if ( plugin_config_get( 'inactiveUserHighlighting' ) )
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
			         if (access_has_global_level($u_access_level))
			         {
			         	echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
			         	echo utf8_encode(string_display_line($u_username));
			         	echo '</a>';
			         }
			         else
			         {
			         	echo utf8_encode(string_display_line($u_username));
			         }
			         echo '</td>';
				         
				         
				         
						// Column Real Name
						if ( user_get_field( $user['id'], 'enabled' ) == '0' )
						{
							if ( plugin_config_get( 'inactiveUserHighlighting' ) )
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
			         if (access_has_global_level($u_access_level))
			         {
			         	echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
			         	echo utf8_encode($user['realname']);
			         	echo '</a>';
			         }
			         else
			         {
			         	echo utf8_encode($user['realname']);
			         }
			         echo '</td>';
				         
				         
				         
			         // Column Projects
			         echo '<td>';
			         
						if (access_has_global_level($u_access_level))
						{
							echo '<a href="manage_proj_edit_page.php?project_id=' . $mainProject['id'] . '">';
							echo utf8_encode($mainProject['name']);
							echo '</a>';
						}
						else
						{
							echo utf8_encode($mainProject['name']);
						}
						echo '</td>';
			         
			         
			         
						// Column subproject
						echo '<td>';
						
						$parent_project = project_hierarchy_get_parent( $subProjectRow['id'], false );

						$arrayIndex = 0;
						$parent_project_array = array();
						
						while ($parent_project != null)
						{
							$parent_project = project_hierarchy_get_parent($parent_project, false);
							
							$parent_project_array[$arrayIndex] = $parent_project;
							$arrayIndex ++;
						}
						
						$parent_projects_count = count($parent_project_array);
						for ($project_index = 0; $project_index < $parent_projects_count; $project_index++)
						{
							$p_project_id = $parent_project_array[$parent_projects_count - $project_index - 1];
							
							if ($p_project_id != 0)
							{
								$p_project_name = project_get_field($p_project_id, 'name');
								if (access_has_global_level($u_access_level))
								{
									echo '<a href="manage_proj_edit_page.php?project_id=' . $p_project_id . '">';
									echo $p_project_name;
									echo '</a>';
								}
								else
								{
									echo $p_project_name;
								}
								echo ' >> ';
							}
						}

						if (access_has_global_level($u_access_level))
						{
							echo '<a href="manage_proj_edit_page.php?project_id=' . $subProjectRow['id'] . '">';
							echo utf8_encode($subProjectRow['name']);
							echo '</a>';
						}
						else
						{
							echo utf8_encode($subProjectRow['name']);
						}
						echo '</td>';
				        
						
						
						// Column Target version
				      echo '<td>';
				      
				      $tpl_bug = bug_get( $subValidBug['id'], true );
				      $t_version_rows = version_get_all_rows( $tpl_bug->project_id );
				      
				      if ($subValidBug['target_version'] != null)
				      {
					      $tpl_target_version_string   = '';
					      $tpl_target_version_string   = prepare_version_string( $tpl_bug->project_id, version_get_id( $tpl_bug->target_version, $tpl_bug->project_id) , $t_version_rows );
					      
					      echo '[' . date( 'Y-m-d', version_get_field( version_get_id( bug_get_field($tpl_bug->id, 'target_version'), $subProjectRow['id'] ), 'date_order' ) ) . '] ' . $tpl_target_version_string;
				      }
				      echo '</td>';
			         
			         
			         
						// Column Issues
						echo '<td>';
						echo '<a href="search.php?project_id=' . $subProjectRow['id'] . '&status_id='. config_get( 'bug_assigned_status' ) . '&handler_id=' . $user['id'] . '&sticky_issues=off&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
						echo $subBugCounter . '</a>';
						echo '</td>';
			         
			         
			         
						// Column Wrong Issues
						echo '<td>';
						
						echo '</td>';
						
						
						echo '</tr>';
	      		}
	      		$subCheckEquivalentBugs = true;
	      	}
	      }
      }
   }
}

echo '</tbody>';
echo '</table>';
echo '</div>';

html_page_bottom1();