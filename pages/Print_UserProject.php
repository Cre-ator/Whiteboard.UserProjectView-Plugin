<?php
html_page_top1(plugin_lang_get('user_project_view'));
html_head_end();
html_body_begin();
   
$t_window_title = string_display_line(config_get('window_title'));

$t_query = 'SELECT *';
$t_query = $t_query .= ' FROM mantis_user_table';
$t_query = $t_query .= ' WHERE mantis_user_table.enabled = 1';
$t_query = $t_query .= ' ORDER BY mantis_user_table.username';
   
$t_all_active_users = db_query($t_query);
   
while($t_user_row = db_fetch_array($t_all_active_users))
{
   $t_users[] = $t_user_row;
}
   
$t_user_count = count($t_users);

echo '<table class="width100" cellspacing="1" >';
	echo '<tr>';
		echo '<td class="form-title" colspan="5">';
			echo '<div class="center">';
			echo $t_window_title . ' - UserProjectView';
			echo '</div>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<td class="print-spacer" colspan="5">';
		echo '<hr />';
		echo '</td>';
	echo '</tr>';
	echo '<tr class="print-category">';
		echo '<td class="print" width="16%">' . plugin_lang_get('username') . '</td>';
		echo '<td class="print" width="16%">' . plugin_lang_get('projects') . '</td>';
		echo '<td class="print" width="16%">' . plugin_lang_get('next_version') . '</td>';
		echo '<td class="print" width="16%">' . plugin_lang_get('issues') . '</td>';
		echo '<td class="print" width="16%">' . plugin_lang_get('wrong_issues') . '</td>';
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
		   echo '<td class="print">';
		   echo string_display_line($u_username);
		   echo '</td>';
		   echo '<td class="print">';
		   
		   $t_query = 'SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"';
		   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
		   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		   $t_query = $t_query .= ' AND mantis_project_table.enabled = 1';
		   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		   
		   $t_all_projects_by_user = db_query($t_query);
		   
		   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
		   {
		   	echo $names[] = $t_project_row['name'] . '<br>';
		   }
		   echo '</td>';
		   echo '<td class="print">';
		
		   $t_query = 'SELECT mantis_project_table.id AS "id"';
		   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
		   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		   $t_query = $t_query .= ' AND mantis_project_table.enabled = 1';
		   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		   
		   $t_all_projects_by_user = db_query($t_query);
		   
		   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
		   {
		      $t_query = 'SELECT DISTINCT (mantis_bug_table.target_version) AS ""';
		      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
		      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		      
		      $t_target_version = db_query($t_query);
		      
		      echo $t_target_version . '<br>';
		   }
		
		   echo '</td>';
		   echo '<td class="print">';
		
		   $t_query = 'SELECT mantis_project_table.id AS "id"';
		   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
		   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		   $t_query = $t_query .= ' AND mantis_project_table.enabled = 1';
		   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		   
		   $t_all_projects_by_user = db_query($t_query);
		
		   while ($t_project_row = db_fetch_array($t_all_projects_by_user))
		   {
		      $t_query = 'SELECT COUNT(mantis_bug_table.id) AS ""';
		      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
		      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_bug_table.handler_id = ' . $t_user['id'];
		      $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		         
		      $t_sum_issue = db_query($t_query);
		      
		      echo $t_sum_issue . '<br>';
		   }
		
		   echo '</td>';
		   echo '<td class="print">';
		
			$t_query = 'SELECT mantis_project_table.id';
		   $t_query = $t_query .= ' FROM mantis_project_table';
		   $t_query = $t_query .= ' WHERE mantis_project_table.enabled = 1';
		   
		   $t_all_projects = db_query($t_query);
		   
		   while ($t_project_row = db_fetch_array($t_all_projects))
		   {
		      $t_query = 'SELECT DISTINCT mantis_bug_table.id AS "id", mantis_project_table.name AS "pname"';
		      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
		      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $t_project_row['id'];
		      $t_query = $t_query .= ' AND mantis_bug_table.handler_id = ' . $t_user['id'];
		      $t_query = $t_query .= ' AND NOT EXISTS (';
		         $t_query = $t_query .= ' SELECT *';
		         $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';      
		         $t_query = $t_query .= ' WHERE mantis_project_user_list_table.project_id = ' . $projects[] = $t_project_row['id'];
		         $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		      $t_query = $t_query .= ' )';
		      
		      $t_issue = db_query($t_query);
		      
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