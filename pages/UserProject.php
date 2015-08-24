<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1(plugin_lang_get('user_project_view'));
html_page_top2();

// actual Mantis version
$mantis_version = substr(MANTIS_VERSION, 0, 4);

// PluginManager object
$pluginManager = new PluginManager();

// All active users
$t_all_active_users = $pluginManager->getAllActiveUsers();
	
while($t_row = db_fetch_array($t_all_active_users))
{
   $t_users[] = $t_row;
}
	
$t_user_count = count($t_users);

echo '<div id="manage-user-div" class="form-container">';

if ($mantis_version == '1.2.')
{
   echo '<table class="width100" cellspacing="1">';
}
else
{
   echo '<table>';
}
      echo '<thead>';
         echo '<tr>';
            echo '<td class="form-title" colspan="4">' . plugin_lang_get('accounts_title') . '</td>';
            echo '<td class="right alternate-views-links" colspan="5">';
               echo '<span class="small">';
                  echo '[ <a href="' . plugin_page('PrintUserProject') . '">' . plugin_lang_get('print_button') . '</a> ]';
               echo '</span>';
            echo '</td>';
         echo '</tr>';
         echo '<tr class="row-category">';
				echo '<th>' . plugin_lang_get('username') . '</th>';
				echo '<th>' . plugin_lang_get('projects') . '</th>';
				echo '<th>' . plugin_lang_get('next_version') . '</th>';
				echo '<th>' . plugin_lang_get('issues') . '</th>';
				echo '<th>' . plugin_lang_get('wrong_issues') . '</th>';
         echo '</tr>';
      echo '</thead>';
      
      echo '<tbody>';
   
		$t_access_level = array();
		for($i=0; $i<$t_user_count; $i++)
		{
		   # prefix user data with u_
		   $t_user = $t_users[$i];
		   extract($t_user, EXTR_PREFIX_ALL, 'u');
		
		   if(!isset($t_access_level[$u_access_level]))
		   {
		      $t_access_level[$u_access_level] = get_enum_element('access_levels', $u_access_level);
		   } 
	
			if ($mantis_version == '1.2.')
			{
			   echo '<tr ' . helper_alternate_class($i) . '>';
			}
			else
			{
			   echo '<tr>';
			}

				// Column User
	         echo '<td>';
					if(access_has_global_level($u_access_level))
					{
		            echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
		            echo string_display_line($u_username);
		            echo '</a>';
				   }
				   else
				   {
				      echo string_display_line($u_username);
				   }
	         echo '</td>';
	
	         // Column Projects
	         echo '<td>';
					$t_all_projects_by_user = $pluginManager->getAllProjectsByUser($t_user['id']);
					
					while ($t_project_row = db_fetch_array($t_all_projects_by_user))
					{
						if(access_has_global_level($u_access_level))
				      {
		               echo '<a href="manage_proj_edit_page.php?project_id=' . $ids[] = $t_project_row['id'] . '">';
		               echo $names[] = $t_project_row['name'] . '<br>';
		               echo '</a>';
		            }
		            else
		            {
							echo $names[] = $t_project_row['name'] . "<br>";
		            }
		         }
	         echo '</td>';
	
	         // Column Target version
	         echo '<td>';
				   $t_all_projects_by_user = $pluginManager->getAllProjectsByUser($t_user['id']);
			
				   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
				   {
				   	$t_target_version = $pluginManager->getTargetVersionByProjectAndUser($t_project_row, $t_user['id']);
				      
				      echo $t_target_version . "<br>";
				   }  
	         echo '</td>';
	
	         // Column Issues
	         echo '<td>';
					$t_all_projects_by_user = $pluginManager->getAllProjectsByUser($t_user['id']);
			
					while ($t_project_row = db_fetch_array($t_all_projects_by_user))
					{
					   $t_sum_issue = $pluginManager->getAmountOfIssuesByProjectAndUser($t_project_row, $t_user['id']);
					   
					   echo $t_sum_issue . '<br>';
					}
	         echo '</td>';
	
	         // Column Wrong Issues
	         echo '<td>';
				   $t_all_projects = $pluginManager->getAllProjects();
				   
				   while ($t_project_row = db_fetch_array($t_all_projects))
				   {
				      $t_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser($t_project_row, $t_user['id']);
				      
				      while ($issue = db_fetch_array($t_issue))
				      {
				      	if(access_has_global_level($u_access_level))
				      	{
					         echo plugin_lang_get('issue') . ' ';
			               echo '<a href="view.php?id=' . $issues[] = $issue['id'] . '">';
			               echo $issues[] = bug_format_id($issue['id']);
			               echo '</a>';
			               echo ', ' . plugin_lang_get('project') . ' ';
			               echo '<a href="manage_proj_edit_page.php?project_id=' . $issues[] = $issue['pid'] . '">';
			               echo $issues[] = $issue['pname'] . '<br>';       
			               echo '</a>';
				      	}
				      	else
				      	{
				      		echo plugin_lang_get('issue') . ' ' . $issues[] = bug_format_id($issue['id']);
				      		echo ', ' . plugin_lang_get('project') . ' ' . $issues[] = $issue['pname'] . '<br>';
				      	}
		            }
		         }
	         echo '</td>';
	      echo '</tr>';
	      }
	   echo '</tbody>';
	echo '</table>';
echo '</div>';

html_page_bottom1();