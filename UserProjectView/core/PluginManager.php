<?php

class PluginManager
{
   private $mysqli;

   public function __construct()
   {
      $this->mysqli = new mysqli("localhost", "root", "", "mantisdb");
   }

	public function getActMantisVersion()
	{
		return substr( MANTIS_VERSION, 0, 4 );
	}	
	
	public function getAllActiveUsers()
	{
		$sqlquery = ' SELECT *' .
                  ' FROM mantis_user_table' .
						' WHERE mantis_user_table.enabled = 1' .
						' ORDER BY mantis_user_table.username';
		
		$allActiveUsers = $this->mysqli->query( $sqlquery );
		
		return $allActiveUsers;
	}
	
	public function getAllProjects()
	{
		$sqlquery = ' SELECT mantis_project_table.id' .
						' FROM mantis_project_table' .
						' WHERE mantis_project_table.enabled = 1' .
						' ORDER BY mantis_project_table.id';
		 
		$allProjects = $this->mysqli->query( $sqlquery );

		return $allProjects;
	}
	
	public function getAllProjectsByUser( $userId )
	{
		$sqlquery = ' SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"' .
						' FROM mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id' .
						' AND mantis_project_table.enabled = 1' .
						' AND mantis_project_user_list_table.user_id = ' . $userId .
						' ORDER BY mantis_project_table.id';
		
		$allProjectsByUser = $this->mysqli->query( $sqlquery );

		return $allProjectsByUser;
	}

   public function getAllIssuesByUser( $userId )
   {
      $sqlquery = ' SELECT mantis_bug_table.id AS ""' .
                  ' FROM mantis_bug_table, mantis_user_table' .
                  ' WHERE mantis_bug_table.handler_id = mantis_user_table.id' .
                  ' AND mantis_user_table.id = ' . $userId .
                  ' ORDER BY mantis_bug_table.id';

      $allIssuesByUser = $this->mysqli->query( $sqlquery )->fetch_row()[0];

      return $allIssuesByUser;
   }

	public function getTargetVersionByProjectAndUser( $project, $userId )
	{
		$sqlquery = ' SELECT DISTINCT (mantis_bug_table.target_version) AS ""' .
						' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'] .
						' AND mantis_project_table.id = ' . $projects[] = $project['id'] .
						' AND mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'] .
						' AND mantis_project_user_list_table.user_id = ' . $userId;
		
		$targetVersion = $this->mysqli->query( $sqlquery )->fetch_all()[1][0];

		return $targetVersion;
	}

	public function getValidTargetVersionByProjectAndUser( $project, $userId )
	{

	}
	
	public function getAmountOfIssuesByProjectAndUser( $project, $userId )
	{
		$sqlquery = ' SELECT COUNT(mantis_bug_table.id) AS ""' .
						' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'] .
						' AND mantis_project_table.id = ' . $projects[] = $project['id'] .
						' AND mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'] .
						' AND mantis_bug_table.handler_id = ' . $userId .
						' AND mantis_project_user_list_table.user_id = ' . $userId;

      $amountIssues = $this->mysqli->query( $sqlquery )->fetch_row()[0];

	   return $amountIssues;
	}
	
	public function getIssuesWithoutProjectByProjectAndUser( $project, $userId )
	{
		$sqlquery = ' SELECT DISTINCT mantis_bug_table.id AS "id", ' .
								' mantis_bug_table.project_id AS "pid", mantis_project_table.name AS "pname"' .
						' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_bug_table.project_id = ' . $projects[] = $project['id'] .
						' AND mantis_project_table.id = ' . $projects[] = $project['id'] .
						' AND mantis_bug_table.handler_id = ' . $userId .
						' AND NOT EXISTS (' .
							' SELECT *' .
							' FROM mantis_project_table, mantis_project_user_list_table' .
							' WHERE mantis_project_user_list_table.project_id = ' . $projects[] = $project['id'] .
							' AND mantis_project_user_list_table.user_id = ' . $userId .
						' )' .
						' ORDER BY mantis_bug_table.id';
      
      $issueWithoutProjectByProjectAndUser = $this->mysqli->query( $sqlquery );
      
      return $issueWithoutProjectByProjectAndUser;
	}

	public function getUserDetailsByUserId( $userId )
	{
		$sqlquery = ' SELECT * ' .
						' FROM mantis_user_table' .
						' WHERE mantis_user_table.id =' . $userId;

		$userDetailsByUserId = $this->mysqli->query( $sqlquery )->fetch_row();

		return $userDetailsByUserId;
	}
}