<?php
   html_page_top1(plugin_lang_get('user_project_view'));
   html_head_end();
   html_body_begin();
   
   $t_window_title = string_display_line(config_get('window_title'));

   // get all active users
   $t_query = 'SELECT *';
   $t_query = $t_query .= ' FROM mantis_user_table';
   $t_query = $t_query .= ' WHERE mantis_user_table.enabled = 1';
   
   $t_result = db_query($t_query);
   
   while($t_row = db_fetch_array($t_result))
   {
      $t_users[] = $t_row;
   }
   
   $t_user_count = count($t_users);
?>

<table class="width100" cellspacing="1" >
   <tr>
      <td class="form-title" colspan="6">
         <div class="center">
         <?php echo $t_window_title . ' - UserProjectView'?>
         </div>
      </td>
   </tr>
   <tr>
      <td class="print-spacer" colspan="6">
         <hr />
      </td>
   </tr>
   <tr class="print-category">
      <td class="print" width="16%"><?php echo plugin_lang_get('username')?></td>
      <td class="print" width="16%"><?php echo plugin_lang_get('projects')?></td>
      <td class="print" width="16%"><?php echo plugin_lang_get('next_version')?></td>
      <td class="print" width="16%"><?php echo plugin_lang_get('issues')?></td>
      <td class="print" width="16%"><?php echo plugin_lang_get('wrong_issues')?></td>
   </tr>
   <tr>
      <td class="print-spacer" colspan="6">
         <hr />
      </td>
   </tr>
<?php 
	for($i=0; $i<$t_user_count; $i++)
	{
		$t_user = $t_users[$i];
	   extract($t_user, EXTR_PREFIX_ALL, 'u');
?>
   <tr>
      <td class="print">
<?php 
   echo string_display_line($u_username);
?>
      </td>
      <td class="print">
<?php 
   $t_query = 'SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"';
   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
   
   $t_all_projects = db_query($t_query);
   
   while ($row = db_fetch_array($t_all_projects))
   {
   	echo $names[] = $row['name'] . '<br>';
   }
?>
      </td>
      <td class="print">
<?php
   $t_query = 'SELECT mantis_project_table.id AS "id"';
   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
   
   $t_all_projects = db_query($t_query);
   
   while ($row = db_fetch_array($t_all_projects))
   {
      $t_query = 'SELECT (mantis_bug_table.target_version) AS ""';
      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $row['id'];
      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $row['id'];
      $t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $row['id'];
      $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
      
      $t_target_version = db_query($t_query);
      
      echo $t_target_version . '<br>';
   }
?>
      </td>
      <td class="print">
<?php 
   $t_query = 'SELECT mantis_project_table.id AS "id"';
   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
   
   $t_all_projects = db_query($t_query);

   while ($row = db_fetch_array($t_all_projects))
   {
      $t_query = 'SELECT COUNT(mantis_bug_table.id) AS ""';
      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $row['id'];
      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $row['id'];
      $t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $row['id'];
      $t_query = $t_query .= ' AND mantis_bug_table.handler_id = ' . $t_user['id'];
      $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
         
      $t_sum_issue = db_query($t_query);
      
      echo $t_sum_issue . '<br>';
   }
?>
      </td>
      <td class="print">
<?php 
	$p_query = 'SELECT mantis_project_table.id';
   $p_query = $p_query .= ' FROM mantis_project_table';
   $p_query = $p_query .= ' WHERE mantis_project_table.enabled = 1';
   
   $p_all_projects = db_query($p_query);
   
   while ($project = db_fetch_array($p_all_projects))
   {
      $t_query = 'SELECT DISTINCT mantis_bug_table.id AS "id", mantis_bug_table.project_id AS "pid"';
      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
      $t_query = $t_query .= ' AND mantis_bug_table.handler_id = ' . $t_user['id'];
      $t_query = $t_query .= ' AND NOT EXISTS (';
         $t_query = $t_query .= ' SELECT *';
         $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';      
         $t_query = $t_query .= ' WHERE mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
         $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
      $t_query = $t_query .= ' )';
      
      $t_sum_issue = db_query($t_query);
      
      while ($issue = db_fetch_array($t_sum_issue))
      {
         echo plugin_lang_get('issue') .
         " " . $issues[] = bug_format_id($issue['id']) .
         ", " . plugin_lang_get('project') .
         " " . project_format_id($issue['pid']) .
         "<br>";
      }
   }
?>
      </td>
   </tr>
   <tr>
      <td class="print-spacer" colspan="6">
         <hr />
      </td>
   </tr>
<?php 
   }
?>
</table>
<?php 
html_body_end();
html_end();