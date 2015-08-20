<?php

/**
 * core functions needed in the plugin several times
 *
 * @author schwarz
 *
 */
class PluginManager
{
	public function getAllActiveUsers()
	{
		$t_query = 'SELECT *';
		$t_query = $t_query .= ' FROM mantis_user_table';
		$t_query = $t_query .= ' WHERE mantis_user_table.enabled = 1';
		$t_query = $t_query .= ' ORDER BY mantis_user_table.username';
		
		$t_all_active_users = db_query($t_query);
		
		return $t_all_active_users;
	}
	
	public function getAllProjects()
	{
		$t_query = 'SELECT mantis_project_table.id';
		$t_query = $t_query .= ' FROM mantis_project_table';
		$t_query = $t_query .= ' WHERE mantis_project_table.enabled = 1';
		$t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		 
		$t_all_projects = db_query($t_query);
		
		return $t_all_projects;
	}
	
	public function getAllProjectsByUser($userId)
	{
		$t_query = 'SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"';
		$t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';
		$t_query = $t_query .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		$t_query = $t_query .= ' AND mantis_project_table.enabled = 1';
		$t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
		$t_query = $t_query .= ' ORDER BY mantis_project_table.id';
		
		$t_all_projects_by_user = db_query($t_query);
		
		return $t_all_projects_by_user;
	}
	
	public function getTargetVersionByProjectAndUser($project, $userId)
	{
		$t_query = 'SELECT DISTINCT (mantis_bug_table.target_version) AS ""';
		$t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
		$t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
		$t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
		$t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
		$t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
		
		$t_target_version = db_query($t_query);
		
		return $t_target_version;
	}
	
	public function getAmountOfIssuesByProjectAndUser($project, $userId)
	{
		$t_query = 'SELECT COUNT(mantis_bug_table.id) AS ""';
	   $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
	   $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
	   $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
	   $t_query = $t_query .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
	   $t_query = $t_query .= ' AND mantis_bug_table.handler_id = ' . $userId;
	   $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
			
	   $t_sum_issue = db_query($t_query);
	   
	   return $t_sum_issue;
	}
	
	public function getIssuesWithoutProjectByProjectAndUser($project, $userId) {
		$t_query = 'SELECT DISTINCT mantis_bug_table.id AS "id", mantis_bug_table.project_id AS "pid", mantis_project_table.name AS "pname"';
      $t_query = $t_query .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
      $t_query = $t_query .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
      $t_query = $t_query .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
      $t_query = $t_query .= ' AND mantis_bug_table.handler_id = ' . $userId;
      $t_query = $t_query .= ' AND NOT EXISTS (';
         $t_query = $t_query .= ' SELECT *';
         $t_query = $t_query .= ' FROM mantis_project_table, mantis_project_user_list_table';   	
         $t_query = $t_query .= ' WHERE mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
         $t_query = $t_query .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
      $t_query = $t_query .= ' )';
      $t_query = $t_query .= ' ORDER BY mantis_bug_table.id';
      
      $t_issue = db_query($t_query);
      
      return $t_issue;		      
	}
}