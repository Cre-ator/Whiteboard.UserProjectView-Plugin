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
		$this->dbPath = config_get( 'hostname' );
		$this->dbUser = config_get( 'db_username' );
		$this->dbPass = config_get( 'db_password' );
   	$this->dbName = config_get( 'database_name' );

   	$this->mysqli = new mysqli( $this->dbPath, $this->dbUser, $this->dbPass, $this->dbName );
   }

	public function getActMantisVersion()
	{
		return substr( MANTIS_VERSION, 0, 4 );
	}	
	
	public function getAllUsers()
	{
		$sqlquery = ' SELECT mantis_user_table.id' .
				' FROM mantis_user_table' .
				' WHERE mantis_user_table.access_level < ' . config_get_global( 'admin_site_threshold' );
	
		$allActiveUsers = $this->mysqli->query( $sqlquery );
	
		return $allActiveUsers;
	}

	public function getMainProjectByVersion( $version )
	{
		$sqlquery = 'SELECT mantis_project_version_table.project_id' .
				' FROM mantis_project_version_table' .
				' WHERE mantis_project_version_table.version = \'' . $version . '\'';

		$assocArray = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) );
		$mainProjectByVersion = $assocArray [0];
		
		return $mainProjectByVersion;
	}
	
	public function getIssuesByIndividual( $userId, $projectId, $targetVersion, $status )
	{
		$sqlquery = ' SELECT mantis_bug_table.id' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.handler_id = ' . $userId .
				' AND mantis_bug_table.status = ' . $status .
				' AND mantis_bug_table.target_version = \'' . $targetVersion . '\'';
		if ( $projectId != '' || $projectId != 0 )
		{
			$sqlquery .= ' AND mantis_bug_table.project_id = ' . $projectId;
		}
			
		$issuesByIndividual = $this->mysqli->query( $sqlquery );
				
		return $issuesByIndividual;
	}
		
	public function getAmountOfIssuesByIndividual( $userId, $projectId, $targetVersion, $status )
	{		
		$sqlquery = ' SELECT COUNT(*)' .
			' FROM mantis_bug_table ' .
			' WHERE mantis_bug_table.handler_id = ' . $userId .
			' AND mantis_bug_table.status = ' . $status .
			' AND mantis_bug_table.target_version = \'' . $targetVersion . '\'';
			if ( $projectId != '' || $projectId != 0 )
			{
				$sqlquery .= ' AND mantis_bug_table.project_id = ' . $projectId;
			}

		$assocArray = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) );
		$amountOfIssuesByIndividual = $assocArray [0];

		return $amountOfIssuesByIndividual;
	}
	
	public function getAmountOfIssuesByIndividualWOTV( $userId, $projectId, $status )
	{
		$sqlquery = ' SELECT COUNT(*)' .
				' FROM mantis_bug_table ' .
				' WHERE mantis_bug_table.handler_id = ' . $userId .
				' AND mantis_bug_table.status = ' . $status;
		if ( $projectId != '' || $projectId != 0 )
		{
			$sqlquery .= ' AND mantis_bug_table.project_id = ' . $projectId;
		}

		$assocArray = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) );
		$amountOfIssuesByIndividual = $assocArray [0];
	
		return $amountOfIssuesByIndividual;
	}
	
	public function checkUserIsAssignedToProject( $userId, $projectId )
	{
		$sqlquery = ' SELECT mantis_project_user_list_table.user_id' .
				' FROM mantis_project_user_list_table' .
				' WHERE mantis_project_user_list_table.project_id = ' . $projectId .
				' AND mantis_project_user_list_table.user_id = ' . $userId;
		
		$result = $this->mysqli->query( $sqlquery );
		
		return $result;
	}
	
	public function buildSpecificRow( $userId, $rowVal, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag )
	{
		$iABackgroundColor = plugin_config_get( 'IAUHBGColor' );
		
		$uRBackgroundColor = plugin_config_get( 'URIUHBGColor' );
		
		$nUBackgroundColor =  plugin_config_get( 'NUIHBGColor' );
		
		$zIBackgroundColor = plugin_config_get( 'ZIHBGColor' );
		
		if ( $rowVal == true )
		{
			$rowIndex = 1;
		}
		else 
		{
			$rowIndex = 2;
		}
		
		if ( $userId != '0' && user_get_field( $userId, 'enabled' ) == '0' && plugin_config_get( 'IAUHighlighting' ) )
		{
			echo '<tr style="background-color:' . $iABackgroundColor . '">';
		}
		elseif ( $zeroIssuesFlag && plugin_config_get( 'ZIHighlighting' ) )
		{
			echo '<tr style="background-color:' . $zIBackgroundColor . '">';
		}
		elseif ( $noUserFlag && plugin_config_get( 'NUIHighlighting' ) )
		{
			echo '<tr style="background-color:' . $nUBackgroundColor . '">';
		}
		elseif ( $unreachableIssueFlag && plugin_config_get( 'URIUHighlighting' ) )
		{
			echo '<tr style="background-color:' . $uRBackgroundColor . '">';
		}
		else
		{
			if ( $this->getActMantisVersion() == '1.2.' )
			{
				echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
			}
			else
			{
				echo '<tr class="row-' . $rowIndex . '">';
			}
		}
	}
	
	public function deleteUsersFromProjects( $projectId, $userId )
	{
		project_remove_user( $projectId, $userId );
	}
	
	public function getUserHasLevel()
	{
		$projectId = helper_get_current_project();
		$userId = auth_get_current_user_id();
	
		return user_get_access_level( $userId, $projectId ) >= plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
	}

	public function printPluginMenu()
	{
		echo '<table align="center">';
			echo '<tr">';
				echo '<td>';
				echo '[ <a href="' . plugin_page( 'UserProject' ) . '&sortVal=userName&sort=ASC">';
				echo plugin_lang_get( 'menu_userprojecttitle' );
				echo '</a> ]';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}
	
	public function printUserProjectMenu()
	{
		echo '<table align="center">';
			echo '<tr">';
				echo '<td>';
				echo '[ <a href="' . plugin_page( 'UserProject_Print' ) . '&sortVal=userName&sort=ASC">';
				echo plugin_lang_get( 'menu_printbutton' );
				echo '</a> ]';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}

	public function printConfigTableRow()
	{
      if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
      {
         echo '<tr ' . helper_alternate_class() . '>';
      }
      else
      {
         echo '<tr>';
      }
	}

   public function printConfigSpacer( $colspan )
   {
      echo '<tr>';
      echo '<td class="spacer" colspan="' . $colspan . '">&nbsp;</td>';
      echo '</tr>';
   }

	public function compareValues( $valueOne, $valueTwo, $rowVal )
	{
		if ( $valueOne != $valueTwo )
		{
			return !$rowVal;
		}
		return $rowVal;
	}

   public function prepareFilterString( $unreachIssueStatusCount, $unreachIssueStatusValue, $filterString )
   {
      for ( $unreachIssueStatusIndex = 0; $unreachIssueStatusIndex < $unreachIssueStatusCount; $unreachIssueStatusIndex++ )
      {
         if ( $unreachIssueStatusValue[$unreachIssueStatusIndex] != null )
         {
            if ( $this->getActMantisVersion() == '1.2.' )
            {
               $filterString .= '&status_id[]=' . $unreachIssueStatusValue[$unreachIssueStatusIndex];
            }
            else
            {
               $filterString .= '&status[]=' . $unreachIssueStatusValue[$unreachIssueStatusIndex];
            }
         }
      }
      return $filterString;
   }

   public function setIrrelevantFlag( $amountStatColumns, $actBugStatus, $statCols )
   {
      $irrelevantFlag = array();
      for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
      {
         if ( $actBugStatus != $statCols[$statColIndex] )
         {
            $irrelevantFlag[$statColIndex] = true;
         }
         else
         {
            $irrelevantFlag[$statColIndex] = false;
         }
      }

      return $irrelevantFlag;
   }

   public function getMainProjectByHierarchy( $actBugAssignedProjectId )
   {
      $parentProject = project_hierarchy_get_parent( $actBugAssignedProjectId, false );
      if ( project_hierarchy_is_toplevel( $actBugAssignedProjectId ) )
      {
         $actBugMainProjectId = $actBugAssignedProjectId;
      }
      else
      {
         // selected project is subproject
         while ( project_hierarchy_is_toplevel( $parentProject, false ) == false )
         {
            $parentProject = project_hierarchy_get_parent( $parentProject, false );

            if ( project_hierarchy_is_toplevel( $parentProject ) )
            {
               break;
            }
         }
         $actBugMainProjectId = $parentProject;
      }

      return $actBugMainProjectId;
   }

   public function getBugAssignedProjectId( $bugAssignedProjectId, $mainProjectId )
   {
      if ( $bugAssignedProjectId == '' )
      {
         $bugAssignedProjectId = $mainProjectId;
      }

      return $bugAssignedProjectId;
   }

   public function generateLinkUserId( $userId )
   {
      $linkUserId = $userId;
      if ( $userId == '0' )
      {
         $linkUserId = '-2';
      }

      return $linkUserId;
   }

   public function checkUserAssignedToProject( $userId, $bugAssignedProjectId )
   {
      $isAssignedToProject = '0';
      if ( $userId != '0' && $bugAssignedProjectId != '' )
      {
         if ( !user_is_administrator( $userId ) )
         {
            $isAssignedToProject = mysqli_fetch_row( $this->checkUserIsAssignedToProject( $userId, $bugAssignedProjectId ) );
         }
      }

      return $isAssignedToProject;
   }

   public function setUnreachableIssueFlag( $isAssignedToProject )
   {
      $unreachableIssueFlag = false;
      if ( $isAssignedToProject == null || $isAssignedToProject == '' )
      {
         $unreachableIssueFlag = true;
      }

      return $unreachableIssueFlag;
   }

   public function prepareParentProject( $t_project_id, $bugAssignedProjectId, $mainProjectId )
   {
      $pProject = '';
      if ( $bugAssignedProjectId == '' && $mainProjectId == '' )
      {
         $pProject = $t_project_id;
      }
      elseif ( $bugAssignedProjectId == '' && $mainProjectId != '' )
      {
         $pProject = $mainProjectId;
      }
      elseif ( $bugAssignedProjectId != '' )
      {
         $pProject = $bugAssignedProjectId;
      }

      return $pProject;
   }

   public function setUserflag( $amountStatColumns, $statCols, $userId )
   {
      $noUserFlag = false;
      for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
      {
         $specStatus = $statCols[$statColIndex];
         if ( $userId == '0' && $specStatus == config_get( 'bug_assigned_status' )
            || $userId == '0' && $specStatus == config_get( 'bug_feedback_status' )
            || $userId == '0' && $specStatus == 80
            || $userId == '0' && $specStatus == 90
         )
         {
            $noUserFlag = true;
         }
      }

      return $noUserFlag;
   }

   public function calculateTimeDifference( $specIssues )
   {
      $actTime = time();
      $oldestSpecIssueDate = time();
      $oldestSpecIssue = null;
      foreach ( $specIssues as $specIssue )
      {
         $specIssueLastUpdate = intval( bug_get_field( $specIssue, 'last_updated' ) );
         if ( $specIssueLastUpdate < $oldestSpecIssueDate )
         {
            $oldestSpecIssueDate = $specIssueLastUpdate;
            $oldestSpecIssue = $specIssue;
         }
      }
      $specTimeDifference = round ( ( ( $actTime - $oldestSpecIssueDate ) / 86400 ), 0 );

      $result = array();
      $result[0] = $specTimeDifference;
      $result[1] = $oldestSpecIssue;

      return $result;
   }

   public function createTableRow()
   {

   }
}