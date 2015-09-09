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
      if (file_exists(USERPROJECTVIEW_CORE_URI . 'config.xml'))
      {
         $xml = simplexml_load_file(USERPROJECTVIEW_CORE_URI . 'config.xml');

         $this->dbPath = $xml->dbpath[0];
         $this->dbUser = $xml->dbuser[0];
         $this->dbPass = $xml->dbpass[0];
         $this->dbName = $xml->dbname[0];

         $this->mysqli = new mysqli($this->dbPath, $this->dbUser, $this->dbPass, $this->dbName);
      }
      else
      {
         exit('Configuration file not found!');
      }
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

   public function checkUserIsActive( $userId )
   {
      $sqlquery = ' SELECT mantis_user_table.enabled' .
                  ' FROM mantis_user_table' .
                  ' WHERE mantis_user_table.id= ' . $userId;

      $userIsActive = $this->mysqli->query( $sqlquery )->fetch_row()[0];

      return $userIsActive;
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
	
	
	
	
	
	
	
	
	
	public function getAllMainProjectByProjectAndUser( $projectId, $userId )
	{
		$sqlquery = ' SELECT DISTINCT mantis_project_table.id, mantis_project_table.name' .
				' FROM mantis_project_table, mantis_project_hierarchy_table, mantis_project_user_list_table' .
				' WHERE mantis_project_table.enabled = 1' .
				' AND mantis_project_table.id = mantis_project_user_list_table.project_id';
		if ( $projectId != 0 )
		{
			$sqlquery .= ' AND mantis_project_table.id = ' . $projectId;
		}
		$sqlquery .= ' AND mantis_project_user_list_table.user_id = ' . $userId .
				' AND NOT EXISTS (' .
					' SELECT mantis_project_hierarchy_table.child_id' .
					' FROM mantis_project_hierarchy_table' .
					' WHERE mantis_project_hierarchy_table.child_id = mantis_project_table.id' .
				' )';
		
		$allMainProjectsByProjectAndUser = $this->mysqli->query( $sqlquery );
		
		return $allMainProjectsByProjectAndUser;
	}
	
	public function getAllSubProjectsByProjectAndUser ( $projectId, $userId )
	{
		$sqlquery = ' SELECT DISTINCT mantis_project_table.id, mantis_project_table.name' .
				' FROM mantis_project_table, mantis_project_hierarchy_table, mantis_project_user_list_table' .
				' WHERE mantis_project_hierarchy_table.child_id = mantis_project_table.id' .
				' AND mantis_project_table.id = mantis_project_user_list_table.project_id' .
				' AND mantis_project_user_list_table.user_id = ' . $userId;
		if ( $projectId != 0 )
		{
			$sqlquery .= ' AND mantis_project_hierarchy_table.inherit_parent =' . $projectId;
		}
		$sqlquery .= ' ORDER BY mantis_project_table.id';
	
		$allSubProjectsByProjectAndUser = $this->mysqli->query( $sqlquery );
		echo 'user: ' . $userId . ' project: ' . $projectId . '<br>';
		
		return $allSubProjectsByProjectAndUser;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function getAllProjectsByProjectAndUser( $projectId, $userId )
	{
		$sqlquery = ' SELECT mantis_project_table.id AS "id", mantis_project_table.name AS "name"' .
						' FROM mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_project_table.id = mantis_project_user_list_table.project_id' .
						' AND mantis_project_table.enabled = 1' .
						' AND mantis_project_user_list_table.user_id = ' . $userId;
      if ( $projectId != 0 )
      {
         $sqlquery .= ' AND mantis_project_user_list_table.project_id =' . $projectId;
      }
		$sqlquery .= ' ORDER BY mantis_project_table.id';
		
		$allProjectsByProjectAndUser = $this->mysqli->query( $sqlquery );
		
		return $allProjectsByProjectAndUser;
	}
	
		
	public function getAllValidBugsByProjectAndUser( $projectId, $userId )
	{
		$sqlquery = ' SELECT mantis_bug_table.id' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.project_id =' . $projectId . 
				' AND mantis_bug_table.handler_id =' . $userId .
				' AND mantis_bug_table.status = ' . config_get( 'bug_assigned_status' );
		
		$allValidBugsByProjectAndUser = $this->mysqli->query( $sqlquery );
		
		return $allValidBugsByProjectAndUser;
	}

	public function getAllAssignedIssuesByUser( $userId )
   {
      $sqlquery = ' SELECT mantis_bug_table.id AS ""' .
                  ' FROM mantis_bug_table' .
                  ' WHERE mantis_bug_table.status = ' . config_get( 'bug_assigned_status' ) .
                  ' AND mantis_bug_table.handler_id = ' . $userId .
                  ' ORDER BY mantis_bug_table.id';

      $allAssignedIssuesByUser = $this->mysqli->query( $sqlquery )->fetch_row()[0];

      return $allAssignedIssuesByUser;
   }

	public function getTargetVersionByProjectAndUser( $projectId, $userId )
	{
		$sqlquery = ' SELECT DISTINCT (mantis_bug_table.target_version) AS ""' .
						' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_bug_table.project_id = ' . $projectId .
						' AND mantis_project_table.id = ' . $projectId .
						' AND mantis_project_user_list_table.project_id = ' . $projectId .
						' AND mantis_project_user_list_table.user_id = ' . $userId;

      if ( $this->getActMantisVersion() == '1.2.')
      {
         $targetVersion = $this->mysqli->query( $sqlquery )->fetch_assoc()[1][0];
      }
      else
      {
         $targetVersion = $this->mysqli->query( $sqlquery )->fetch_all()[1][0];
      }

		return $targetVersion;
	}

   public function getValidTargetVersionsByProject( $projectId )
	{
      $sqlquery = ' SELECT *' .
         ' FROM mantis_project_version_table, mantis_project_table' .
         ' WHERE mantis_project_table.id =' . $projectId .
         ' AND mantis_project_version_table.project_id = ' . $projectId .
         ' AND mantis_project_table.enabled = 1' .
         ' AND mantis_project_version_table.obsolete = false';

      $validTargetVersionsByProject = $this->mysqli->query( $sqlquery )->fetch_all();

      return $validTargetVersionsByProject;
	}

   public function getValidTargetVersionsByUser( $userId )
   {
      $assignedIssuesByUser = $this->getAssignedIssuesByUser( $userId );
      $loopCount = count( $assignedIssuesByUser );

      $sqlquery = '';

      for ( $i = 0; $i < $loopCount; $i++ )
      {
         $sqlquery .= ' SELECT *' .
            ' FROM mantis_project_version_table, mantis_bug_table' .
            ' WHERE mantis_project_version_table.project_id = mantis_bug_table.project_id' .
            ' AND mantis_bug_table.handler_id = ' . $userId .
            ' AND mantis_bug_table.id = ' . $assignedIssuesByUser[$i][0];
      }
   }

   public function getNearestTargetVersionByProject( $projectId )
   {
      $actTimeStamp = time();
      $validTargetVersionsByProject = $this->getValidTargetVersionsByProject( $projectId );

      $loopCount = count( $validTargetVersionsByProject );
      $finalTimeDifference = $actTimeStamp;
      $finalTargetVersion = '';

      for ( $i = 0; $i < $loopCount; $i++ )
      {
         $targetVersionTimeStamp = $validTargetVersionsByProject[$i][6];
         if ( $targetVersionTimeStamp >= $actTimeStamp )
         {
            $timeDifference = $targetVersionTimeStamp - $actTimeStamp;
            if ( $timeDifference < $finalTimeDifference )
            {
               $finalTimeDifference = $timeDifference;
               $finalTargetVersion = $validTargetVersionsByProject[$i][2];
            }
         }
      }
      return $finalTargetVersion;
   }

	public function getAmountOfAssignedIssuesByProjectAndUser( $projectId, $userId )
	{
		$sqlquery = ' SELECT COUNT(mantis_bug_table.id) AS ""' .
						' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_bug_table.project_id = ' . $projectId .
						' AND mantis_project_table.id = ' . $projectId .
						' AND mantis_project_user_list_table.project_id = ' . $projectId .
                  ' AND mantis_bug_table.status = ' . config_get( 'bug_assigned_status' ) .
						' AND mantis_bug_table.handler_id = ' . $userId .
						' AND mantis_project_user_list_table.user_id = ' . $userId;

      $amountOfAssignedIssues = $this->mysqli->query( $sqlquery )->fetch_row()[0];

	   return $amountOfAssignedIssues;
	}

   public function getAssignedIssuesByUser( $userId )
   {
      $sqlquery = ' SELECT *' .
         ' FROM mantis_bug_table' .
         ' WHERE mantis_bug_table.status = 50' .
         ' AND mantis_bug_table.handler_id = ' . $userId;

      $assignedIssuesByUser = $this->mysqli->query( $sqlquery )->fetch_all();

      return $assignedIssuesByUser;
   }
	
	public function getIssuesWithoutProjectByProjectAndUser( $projectId, $userId )
	{
		$sqlquery = ' SELECT DISTINCT mantis_bug_table.id AS "id", ' .
								' mantis_bug_table.project_id AS "pid", mantis_project_table.name AS "pname"' .
						' FROM mantis_bug_table, mantis_project_table, mantis_project_user_list_table' .
						' WHERE mantis_bug_table.project_id = ' . $projectId .
						' AND mantis_project_table.id = ' . $projectId .
						' AND mantis_bug_table.handler_id = ' . $userId .
						' AND NOT EXISTS (' .
							' SELECT *' .
							' FROM mantis_project_table, mantis_project_user_list_table' .
							' WHERE mantis_project_user_list_table.project_id = ' . $projectId .
							' AND mantis_project_user_list_table.user_id = ' . $userId .
						' )' .
						' ORDER BY mantis_bug_table.id';
      
      $issueWithoutProjectByProjectAndUser = $this->mysqli->query( $sqlquery );
      
      return $issueWithoutProjectByProjectAndUser;
	}

	public function getUserDetailsByUserId( $userId )
	{
		$sqlquery = ' SELECT *' .
						' FROM mantis_user_table' .
						' WHERE mantis_user_table.id =' . $userId;

		$userDetailsByUserId = $this->mysqli->query( $sqlquery )->fetch_row();

		return $userDetailsByUserId;
	}

   public function getProjectDetailsByProjectId( $projectId )
   {
      $sqlquery = ' SELECT *' .
                  ' FROM mantis_project_table' .
                  ' WHERE mantis_project_table.id =' . $projectId;

      $projectDetailsByProjectId = $this->mysqli->query( $sqlquery )->fetch_row();

      return $projectDetailsByProjectId;
   }
   
   /**
    * Get a list of projects the specified user is assigned to.
    * @param integer $p_user_id A valid user identifier.
    * @return array An array of projects by project id the specified user is assigned to.
    *		The array contains the id, name, view state, and project access level for the user.
    */
   public function getAssignedProjectsByUserId( $userId ) {

   	$sqlquery = ' SELECT DISTINCT mantis_project_table.id' .
     		' FROM mantis_project_table' .
     		' LEFT JOIN mantis_project_user_list_table' .
     		' ON mantis_project_table.id = mantis_project_user_list_table.project_id' .
     		' WHERE mantis_project_table.enabled = \'1\' AND ' .
     		' mantis_project_user_list_table.user_id = ' . $userId .
     		' ORDER BY mantis_project_table.name';
   	
   	$t_result = $this->mysqli->query( $sqlquery );

   	$t_projects = array();
   	
   	while( $t_row = mysqli_fetch_array( $t_result ) ) {
   		$t_project_id = $t_row['id'];
   		$t_projects[$t_project_id] = $t_row;
   	}
   	return $t_projects;
   }
}