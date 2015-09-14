<?php

class PluginManager
{
   private $mysqli;
   private $dbPath;
   private $dbUser;
   private $dbPass;
   private $dbName;

   public function __construct()
   {
		$this->dbPath = config_get('hostname');
		$this->dbUser = config_get('db_username');
		$this->dbPass = config_get('db_password');
   	$this->dbName = config_get('database_name');

   	$this->mysqli = new mysqli($this->dbPath, $this->dbUser, $this->dbPass, $this->dbName);
   }

	public function getActMantisVersion()
	{
		return substr( MANTIS_VERSION, 0, 4 );
	}	
	
	public function getAllValidUsers()
	{
		$sqlquery = ' SELECT *' .
				' FROM mantis_user_table' .
				' WHERE mantis_user_table.access_level < ' . config_get_global( 'admin_site_threshold' ) .
				' ORDER BY mantis_user_table.username';
	
		$allValidUsers = $this->mysqli->query( $sqlquery );
	
		return $allValidUsers;
	}
	
	public function getAllValidBugsByUser( $userId )
	{
		$sqlquery = ' SELECT mantis_bug_table.id' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.handler_id =' . $userId .
				' AND mantis_bug_table.status = ' . config_get( 'bug_assigned_status' );
	
		$allValidBugsByUser = $this->mysqli->query( $sqlquery );
	
		return $allValidBugsByUser;
	}
	
	public function getAmountOfEqualIssuesByProjectUserTargetVersion( $projectId, $userId, $targetVersion )
	{
		$sqlquery = ' SELECT COUNT(*)' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.project_id = ' . $projectId .
				' AND mantis_bug_table.handler_id = ' . $userId .
				' AND mantis_bug_table.target_version = \'' . $targetVersion . '\' ' .
				' AND mantis_bug_table.status = ' . config_get( 'bug_assigned_status' );
		
// 		echo $sqlquery . '<br><br>',

		$amountOfEquivalentIssues = $this->mysqli->query( $sqlquery )->fetch_row()[0];
	
		return $amountOfEquivalentIssues;
	}

	public function getMainProjectByVersion( $version )
	{
		$sqlquery = 'SELECT mantis_project_table.id' .
				' FROM mantis_project_table, mantis_project_version_table' .
				' WHERE mantis_project_version_table.version = \'' . $version . '\'' .
				' AND mantis_project_version_table.project_id = mantis_project_table.id';
				
		$mainProjectByVersion = $this->mysqli->query( $sqlquery );
		
		return $mainProjectByVersion;
	}
	
	// individual bug selection
	public function getValidBugsByUserAndProject( $userId, $projectId )
	{
		$sqlquery = ' SELECT mantis_bug_table.id' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.handler_id = ' . $userId .
				' AND mantis_bug_table.project_id = ' . $projectId .
				' AND mantis_bug_table.status = ' . config_get( 'bug_assigned_status' );
		
		$validBugsByUserAndProject = $this->mysqli->query( $sqlquery );
		
		return $validBugsByUserAndProject;
	}
	
	// prepare subprojects for bug selection
	public function getSubprojectsByProject( $projectId )
	{
		$projectArray = array();

		$projectArray = project_hierarchy_get_all_subprojects( $projectId, false );
		$projectArray[ count( $projectArray ) ] = $projectId;
		
		return $projectArray;
	}
}