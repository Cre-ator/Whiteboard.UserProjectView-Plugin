<?php
	html_page_top1(plugin_lang_get('user_project_view'));
	html_page_top2();
	
	// get mantis version
	$mantis_version = substr(MANTIS_VERSION, 0, 4);
	
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

<div id="manage-user-div" class="form-container">
<?php 
   /*
    * Compability for mantisbt 1.2.x
    */
   if ($mantis_version == '1.2.')
   {
   echo '<table class="width100" cellspacing="1">';
   }
   else
   {
      echo '<table>';
   }
?>
      <thead>
         <tr class="nav">
            <td class="form-title" colspan="4"><?php echo plugin_lang_get('accounts_title');?></td>
            <td class="right alternate-views-links" colspan="5">
               <span class="small">
                  <?php echo '[ <a href="' . plugin_page('Print_UserProject') . '">' . plugin_lang_get('print_button') . '</a> ]';?>
               </span>
            </td>
         </tr>
         <tr class="row-category">
				<th><?php echo plugin_lang_get('username')?></th>
				<th><?php echo plugin_lang_get('projects')?></th>
				<th><?php echo plugin_lang_get('next_version')?></th>
				<th><?php echo plugin_lang_get('issues')?></th>
				<th><?php echo plugin_lang_get('wrong_issues')?></th>
         </tr>
      </thead>
      <tbody>
<?php
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

      /*
       * Compability for mantisbt 1.2.x
       */
		if ($mantis_version == '1.2.')
		{
		   echo '<tr ' . helper_alternate_class($i) . '>';
		}
		else
		{
		   echo '<tr>';
		}
?>        
         
<!-- Column User -->
            <td>
<?php
		if(access_has_global_level($u_access_level))
		{
?>
               <a href="manage_user_edit_page.php?user_id=<?php echo $u_id ?>">
<?php
      echo string_display_line($u_username)
?>
               </a>
<?php
	   } else {
	      echo string_display_line($u_username);
	   }
?>
            </td>

<!-- Column Projects -->
            <td>
<?php
		$t_query = 'SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"';
		$t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
		$t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		$t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		$t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		
		$t_all_projects = db_query($t_query);
		
		while ($row = db_fetch_array($t_all_projects))
		{
			if(access_has_global_level($u_access_level))
	      {
?>
	           <a href="manage_proj_edit_page.php?project_id=<?php echo $ids[] = $row['id'] ?>">
<?php
      echo $names[] = $row['name'] . "<br>"
?>
	           </a>
<?php
	      } else {
	         echo $names[] = $row['name'] . "<br>";
	      }
		}
?>
            </td>

<!-- Column Target version -->
            <td>
<?php 
	   $t_query = 'SELECT mantis_project_table.id AS "id"';
	   $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
	   $t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
	   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
	   $t_query = $t_query .= ' ORDER BY mantis_project_table.id';
	   
	   $t_all_projects = db_query($t_query);
	   // check each project according to target version
	   while ($row = db_fetch_array($t_all_projects))
	   {
	      $t_query = 'SELECT (mantis_bug_table.target_version) AS ""';
	      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
	      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $row['id'];
	      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $row['id'];
	      $t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $row['id'];
	      $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
	      
	      $t_target_version = db_query($t_query);
	      
	      echo $t_target_version . "<br>";
	   }  
?>
            </td>

<!-- Column Issues -->
            <td>
<?php
		$t_query = 'SELECT mantis_project_table.id AS "id"';
		$t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
		$t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		$t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $t_user['id'];
		$t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		
		$t_all_projects = db_query($t_query);
		// check each project and sum issues
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
    
<!-- Column wrong issues -->            
            <td>
<?php 
	   $p_query = 'SELECT mantis_project_table.id';
	   $p_query = $p_query .= ' FROM mantis_project_table';
	   $p_query = $p_query .= ' WHERE mantis_project_table.enabled = 1';
	   $p_query = $p_query .= ' ORDER BY mantis_project_table.id';
	   
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
	      $t_query = $t_query .= ' ORDER BY mantis_bug_table.id';
	      
	      $t_sum_issue = db_query($t_query);
	      
	      while ($issue = db_fetch_array($t_sum_issue))
	      {
	         echo plugin_lang_get('issue')
?>
      	<a href="view.php?id=<?php echo $issues[] = $issue['id'] ?>">
<?php
      echo $issues[] = bug_format_id($issue['id']);
?>
         </a>
         <?php echo ", " . plugin_lang_get('project')?>
         <a href="manage_proj_edit_page.php?project_id=<?php echo $issues[] = $issue['pid'] ?>">
<?php
      echo $issues[] = project_format_id($issue['pid']) . "<br>";
?>         
         </a>      
<?php
	      }
	   }
?>            
            </td>
         </tr>
<?php
   }
?>
      </tbody>
   </table>
</div>
<?php
   html_page_bottom1( __FILE__ );