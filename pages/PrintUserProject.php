<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1(plugin_lang_get('user_project_view'));
html_head_end();
html_body_begin();

// title
$t_title = string_display_line(config_get('window_title'));

// PluginManager object
$pluginManager = new PluginManager();

// All active users
$t_all_active_users = $pluginManager->getAllActiveUsers();
   
while($t_user_row = db_fetch_array($t_all_active_users))
{
   $t_users[] = $t_user_row;
}
   
$t_user_count = count($t_users);

echo '<table class="width100" cellspacing="1" >';
	echo '<tr>';
		echo '<td class="form-title" colspan="5">';
			echo '<div class="center">';
			echo $t_title . ' - UserProjectView';
			echo '</div>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td class="print-spacer" colspan="5">';
		echo '<hr />';
		echo '</td>';
	echo '</tr>';
	echo '<tr class="print-category">';
		echo '<td class="print" width="20%">' . plugin_lang_get('username') . '</td>';
		echo '<td class="print" width="20%">' . plugin_lang_get('projects') . '</td>';
		echo '<td class="print" width="20%">' . plugin_lang_get('next_version') . '</td>';
		echo '<td class="print" width="20%">' . plugin_lang_get('issues') . '</td>';
		echo '<td class="print" width="20%">' . plugin_lang_get('wrong_issues') . '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td class="print-spacer" colspan="5">';
		echo '<hr />';
		echo '</td>';
	echo '</tr>';
	
	for($i=0; $i<$t_user_count; $i++)
	{
	   $t_user = $t_users[$i];
	   extract($t_user, EXTR_PREFIX_ALL, 'u');
	   echo '<tr>';
	   	// Column User
		   echo '<td class="print">';
		   	echo string_display_line($u_username);
		   echo '</td>';
		   // Column Projects
		   echo '<td class="print">';
			   $t_all_projects_by_user = $pluginManager->getAllProjectsByUser($t_user['id']);
			   
			   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
			   {
			   	echo $names[] = $t_project_row['name'] . '<br>';
			   }
		   echo '</td>';
		   // column  Target version
		   echo '<td class="print">';
			   $t_all_projects_by_user = $pluginManager->getAllProjectsByUser($t_user['id']);
			   
			   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
			   {
			      $t_target_version = $pluginManager->getTargetVersionByProjectAndUser($t_project_row, $t_user['id']);
			      
			      echo $t_target_version . '<br>';
			   }
		   echo '</td>';
		   // Column Issues
		   echo '<td class="print">';
			   $t_all_projects_by_user = $pluginManager->getAllProjectsByUser($t_user['id']);
			
			   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
			   {
			      $t_sum_issue = $pluginManager->getAmountOfIssuesByProjectAndUser($t_project_row, $t_user['id']);
			      
			      echo $t_sum_issue . '<br>';
			   }
		   echo '</td>';
		   // Column Wrong Issues
		   echo '<td class="print">';
			   $t_all_projects = $pluginManager->getAllProjects();
			   
			   while ($t_project_row = db_fetch_array($t_all_projects))
			   {
			      $t_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser($t_project_row, $t_user['id']);
			      
			      while ($issue = db_fetch_array($t_issue))
			      {
			         echo plugin_lang_get('issue') .
			         " " . $issues[] = bug_format_id($issue['id']) .
			         ", " . plugin_lang_get('project') .
			         " " . $issue['pname'] .
			         "<br>";
			      }
			   }
		   echo '</td>';
	   echo '</tr>';
	   echo '<tr>';
		   echo '<td class="print-spacer" colspan="5">';
		   echo '<hr />';
		   echo '</td>';
	   echo '</tr>';
   }
echo '</table>';

html_body_end();
html_end();