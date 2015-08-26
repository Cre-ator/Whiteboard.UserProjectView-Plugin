<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1(plugin_lang_get('user_project_view'));
html_head_end();
html_body_begin();

// PluginManager object
$pluginManager = new PluginManager();

// All active users
$allActiveUsers = $pluginManager->getAllActiveUsers();
   
while($user = db_fetch_array($allActiveUsers))
{
   $users[] = $user;
}
   
$userCount = count($users);

echo '<table class="width100" cellspacing="1" >';
	echo '<tr>';
		echo '<td class="form-title" colspan="5">';
			echo '<div class="center">';
			echo string_display_line(config_get('window_title')) . ' - UserProjectView';
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
	
	for($i=0; $i<$userCount; $i++)
	{
	   $user = $users[$i];
	   extract($user, EXTR_PREFIX_ALL, 'u');
	   echo '<tr>';
	   	// Column User
		   echo '<td class="print">';
		   	echo string_display_line($u_username);
		   echo '</td>';
		   // Column Projects
		   echo '<td class="print">';
			   $allProjectsByUser = $pluginManager->getAllProjectsByUser($user['id']);
			   
			   while ($project = db_fetch_array($allProjectsByUser))
			   {
			   	echo $names[] = $project['name'] . '<br>';
			   }
		   echo '</td>';
		   // column  Target version
		   echo '<td class="print">';
			   $allProjectsByUser = $pluginManager->getAllProjectsByUser($user['id']);
			   
			   while ($project = db_fetch_array($allProjectsByUser))
			   {
			      $targetVersion = $pluginManager->getTargetVersionByProjectAndUser($project, $user['id']);
			      
			      echo $targetVersion . '<br>';
			   }
		   echo '</td>';
		   // Column Issues
		   echo '<td class="print">';
			   $allProjectsByUser = $pluginManager->getAllProjectsByUser($user['id']);
			
			   while ($project = db_fetch_array($allProjectsByUser))
			   {
			      $amountIssue = $pluginManager->getAmountOfIssuesByProjectAndUser($project, $user['id']);
			      
			      echo $amountIssue . '<br>';
			   }
		   echo '</td>';
		   // Column Wrong Issues
		   echo '<td class="print">';
			   $allProjects = $pluginManager->getAllProjects();
			   
			   while ($project = db_fetch_array($allProjects))
			   {
			      $u_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser($project, $user['id']);
			      
			      while ($issue = db_fetch_array($u_issue))
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