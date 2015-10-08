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
				
		$mainProjectByVersion = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) )[0];
		
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
			
		$amountOfIssuesByIndividual = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) )[0];

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
			
		$amountOfIssuesByIndividual = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) )[0];
	
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
		$iABackgroundColor = plugin_config_get( 'IABGColor' );
		
		$uRBackgroundColor = plugin_config_get( 'URBGColor' );
		
		$nUBackgroundColor =  plugin_config_get( 'NUBGColor' );
		
		$zIBackgroundColor = plugin_config_get( 'ZIBGColor' );
		
		if ( $rowVal == true )
		{
			$rowIndex = 1;
		}
		else 
		{
			$rowIndex = 2;
		}
		
		if ( $userId != '0' && user_get_field( $userId, 'enabled' ) == '0' && plugin_config_get( 'IAUserHighlighting' ) )
		{
			echo '<tr style="background-color:' . $iABackgroundColor . '">';
		}
		elseif ( $zeroIssuesFlag && plugin_config_get( 'ZIssueHighlighting' ) )
		{
			echo '<tr style="background-color:' . $zIBackgroundColor . '">';
		}
		elseif ( $noUserFlag && plugin_config_get( 'NUIssueHighlighting' ) )
		{
			echo '<tr style="background-color:' . $nUBackgroundColor . '">';
		}
		elseif ( $unreachableIssueFlag && plugin_config_get( 'URUserHighlighting' ) )
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
				echo plugin_lang_get( 'userProject_title' );
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
				echo plugin_lang_get( 'print_button' );
				echo '</a> ]';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}
}