<?php
require_once (USERPROJECTVIEW_CORE_URI . 'constant_api.php');


include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';



html_page_top1(plugin_lang_get('user_project_view'));

echo '<link rel="stylesheet" type="text/css" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectViewCSS.php">';

html_page_top2();

// PluginManager object
$pluginManager = new PluginManager();

// actual Project ID
$actProject = helper_get_current_project();

// actual Project Details
$projectDetails = $pluginManager->getProjectDetailsByProjectId($actProject);

// All active users
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
echo '<td class="form-title" colspan="5">' . plugin_lang_get( 'accounts_title' ) . plugin_lang_get( 'projects_title' );
if ( $actProject == 0 )
{
   echo utf8_encode(plugin_lang_get( 'project_selector_all' ));
}
else
{
   echo $projectDetails[1];
}
echo '</td>';
echo '<td class="form-title" colspan="6">';
echo '<span class="small">';
echo '[<a href="' . plugin_page('PrintUserProject') . '">' . plugin_lang_get('print_button') . '</a>]';
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

   $pluginManager->checkUserIsActive( $user['id'] );

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

      $mainProjectVersionsByProject = version_get_all_rows($mainProject['id']);
            
      $mainProjectVerionCount = count($mainProjectVersionsByProject);
      
      for ($mainProjectVersionIndex = 0; $mainProjectVersionIndex < $mainProjectVerionCount; $mainProjectVersionIndex++)
      {
			$mainProjectVersion = $mainProjectVersionsByProject[$mainProjectVersionIndex];
			
			
			var_dump($mainProjectVersion);
      
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
	      if ( $pluginManager->checkUserIsActive( $user['id'] ) == '0' )
	      {
	      	if ( plugin_config_get( 'IAUserHighlighting' ) )
	      	{
	      		echo '<td class="attention">';
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
	      	echo ' [[u: ' . $user['id'] . ' || p: ' . $mainProject['id'] . ']]';
	      }
	      else
	      {
	      	echo utf8_encode(string_display_line($u_username));
	      }
	      echo '</td>';
	      
	      
	      
	      // Column Real Name
	      if ( $pluginManager->checkUserIsActive( $user['id'] ) == '0')
	      {
	      	if ( plugin_config_get( 'IAUserHighlighting' ) )
	      	{
	      		echo '<td class="attention">';
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
	      echo utf8_encode($pluginManager->getNearestTargetVersionByProject( $mainProject['id'] ));
	      echo '</td>';
	      
	      
	      
	      // Column Issues
	      echo '<td>';
	      echo '<a href="search.php?project_id=' . $mainProject['id'] . '&status_id='. config_get( 'bug_assigned_status' ) . '&handler_id=' . $user['id'] . '&sticky_issues=off&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
	      echo $pluginManager->getAmountOfAssignedIssuesByProjectAndUser($mainProject['id'], $user['id']) . '</a>';
	      echo '</td>';
	      
	      
	      
	      // Column Wrong Issues
	      echo '<td>';
	      
	      echo '</td>';
	      
	      
	      
	      
	      
	      
	      
	      
	      
	      
	      
	      
	      
	      
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
		      	
		      	if ($pluginManager->getAmountOfAssignedIssuesByProjectAndUser($subProjectRow['id'], $user['id']) == 0)
		      	{
		     			continue;
		      	}
		      	
		      	
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
		         if ( $pluginManager->checkUserIsActive( $user['id'] ) == '0' )
		         {
		         	if ( plugin_config_get( 'inactiveUserHighlighting' ) )
		         	{
		         		echo '<td class="attention">';
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
		         	echo ' [[u: ' . $user['id'] . ' || p: ' . $subProjectRow['id'] . ']]';
		         }
		         else
		         {
		         	echo utf8_encode(string_display_line($u_username));
		         }
		         echo '</td>';
		         
		         
		         
		         // Column Real Name
		         if ( $pluginManager->checkUserIsActive( $user['id'] ) == '0')
		         {
		         	if ( plugin_config_get( 'inactiveUserHighlighting' ) )
		         	{
		         		echo '<td class="attention">';
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
					echo utf8_encode($pluginManager->getNearestTargetVersionByProject( $mainProject['id'] ));
					echo '</td>';
		         
		         
		         
					// Column Issues
					echo '<td>';
					echo '<a href="search.php?project_id=' . $subProjectRow['id'] . '&status_id='. config_get( 'bug_assigned_status' ) . '&handler_id=' . $user['id'] . '&sticky_issues=off&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
					echo $pluginManager->getAmountOfAssignedIssuesByProjectAndUser($subProjectRow['id'], $user['id']) . '</a>';
					echo '</td>';
		         
		         
		         
					// Column Wrong Issues
					echo '<td>';
					
					echo '</td>';
					
					
					echo '</tr>';
		      } 
	      }
      }
   }
}