<?php

class PluginManager
{
	public function getActMantisVersion()
	{
		return substr( MANTIS_VERSION, 0, 4 );
	}	
	
	public function getAllActiveUsers()
	{
		$sqlquery = 'SELECT *';
		$sqlquery = $sqlquery .= ' FROM mantis_user_table';
		$sqlquery = $sqlquery .= ' WHERE mantis_user_table.enabled = 1';
		$sqlquery = $sqlquery .= ' ORDER BY mantis_user_table.username';
		
		$allActiveUsers = db_query( $sqlquery );
		
		return $allActiveUsers;
	}
	
	public function getAllProjects()
	{
		$sqlquery = 'SELECT mantis_project_table.id';
		$sqlquery = $sqlquery .= ' FROM mantis_project_table';
		$sqlquery = $sqlquery .= ' WHERE mantis_project_table.enabled = 1';
		$sqlquery = $sqlquery .= ' ORDER BY mantis_project_table.id';
		 
		$allProjects = db_query( $sqlquery );
		
		return $allProjects;
	}
	
	public function getAllProjectsByUser( $userId )
	{
		$sqlquery = 'SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"';
		$sqlquery = $sqlquery .= ' FROM mantis_project_table, mantis_project_user_list_table';
		$sqlquery = $sqlquery .= ' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id';
		$sqlquery = $sqlquery .= ' AND mantis_project_table.enabled = 1';
		$sqlquery = $sqlquery .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
		$sqlquery = $sqlquery .= ' ORDER BY mantis_project_table.id';
		
		$allProjectsByUser = db_query( $sqlquery );
		
		return $allProjectsByUser;
	}
	
	public function getTargetVersionByProjectAndUser( $project, $userId )
	{
		$sqlquery = 'SELECT DISTINCT (mantis_bug_table.target_version) AS ""';
		$sqlquery = $sqlquery .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
		$sqlquery = $sqlquery .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
		$sqlquery = $sqlquery .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
		$sqlquery = $sqlquery .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
		$sqlquery = $sqlquery .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
		
		$targetVersion = db_query( $sqlquery );
		
		return $targetVersion;
	}
	
	public function getAmountOfIssuesByProjectAndUser( $project, $userId )
	{
		$sqlquery = 'SELECT COUNT(mantis_bug_table.id) AS ""';
	   $sqlquery = $sqlquery .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
	   $sqlquery = $sqlquery .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
	   $sqlquery = $sqlquery .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
	   $sqlquery = $sqlquery .= ' AND mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
	   $sqlquery = $sqlquery .= ' AND mantis_bug_table.handler_id = ' . $userId;
	   $sqlquery = $sqlquery .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
			
	   $amountIssues = db_query( $sqlquery );
	   
	   return $amountIssues;
	}
	
	public function getIssuesWithoutProjectByProjectAndUser( $project, $userId ) {
		$sqlquery = 'SELECT DISTINCT mantis_bug_table.id AS "id", mantis_bug_table.project_id AS "pid", mantis_project_table.name AS "pname"';
      $sqlquery = $sqlquery .= ' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table';
      $sqlquery = $sqlquery .= ' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'];
      $sqlquery = $sqlquery .= ' AND mantis_project_table.id = ' . $projects[] = $project['id'];
      $sqlquery = $sqlquery .= ' AND mantis_bug_table.handler_id = ' . $userId;
      $sqlquery = $sqlquery .= ' AND NOT EXISTS (';
         $sqlquery = $sqlquery .= ' SELECT *';
         $sqlquery = $sqlquery .= ' FROM mantis_project_table, mantis_project_user_list_table';   	
         $sqlquery = $sqlquery .= ' WHERE mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'];
         $sqlquery = $sqlquery .= ' AND mantis_project_user_list_table.user_id = ' . $userId;
      $sqlquery = $sqlquery .= ' )';
      $sqlquery = $sqlquery .= ' ORDER BY mantis_bug_table.id';
      
      $IssueWithoutProjectByProjectAndUser = db_query( $sqlquery );
      
      return $IssueWithoutProjectByProjectAndUser;		      
	}
}