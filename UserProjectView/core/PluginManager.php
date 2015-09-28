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

	public function getMainProjectByVersion( $version )
	{
		$sqlquery = 'SELECT mantis_project_version_table.project_id' .
				' FROM mantis_project_version_table' .
				' WHERE mantis_project_version_table.version = \'' . $version . '\'';
				
		$mainProjectByVersion = $this->mysqli->query( $sqlquery );
		
		return $mainProjectByVersion;
	}
	
	public function getUnreachableIssuesByBugAndUser( $bugId, $userId, $status )
	{
		foreach ( $status as $state)
		{
			$sqlquery = ' SELECT mantis_bug_table.id AS \'bid\',' .
					' mantis_bug_table.project_id AS \'pid\'' .
					' FROM mantis_bug_table' .
					' WHERE mantis_bug_table.id = ' . $bugId .
					' AND mantis_bug_table.status = ' . $state .
					' AND mantis_bug_table.handler_id = ' . $userId .
					' AND NOT EXISTS (' .
						' SELECT *' .
						' FROM mantis_project_user_list_table, mantis_bug_table' .
						' WHERE mantis_project_user_list_table.project_id = mantis_bug_table.project_id' .
						' AND mantis_project_user_list_table.user_id = ' . $userId .
						' AND mantis_bug_table.id = ' . $bugId .
					' )' .
					' ORDER BY mantis_bug_table.id';
			
			$unreachableIssuesByBugAndUser = $this->mysqli->query( $sqlquery );
		}
		
		return $unreachableIssuesByBugAndUser;
	}
	
	public function getAllActiveUsers()
	{
		$sqlquery = ' SELECT mantis_user_table.id' .
				' FROM mantis_user_table' .
				' WHERE mantis_user_table.access_level < ' . config_get_global( 'admin_site_threshold' );
		//		' WHERE mantis_user_table.enabled = 1';
		
		$allActiveUsers = $this->mysqli->query( $sqlquery );
		
		return $allActiveUsers;
	}
	
	public function getAmountOfIssuesByUser( $userId, $status )
	{
		$sqlquery = ' SELECT COUNT(*)' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.status = ' . $status .
				' AND mantis_bug_table.handler_id =' . $userId;
		
		$amountOfIssuesByUser = $this->mysqli->query( $sqlquery );
		
		return $amountOfIssuesByUser;
	}
	
	public function getAmountOfIssuesByIndividual( $userId, $projectId, $targetVersion, $status )
	{		
		$sqlquery = 'SELECT COUNT(*)' .
			' FROM mantis_bug_table ' .
			' WHERE mantis_bug_table.handler_id = ' . $userId;
			if ( $projectId != '' )
			{
				$sqlquery .= ' AND mantis_bug_table.project_id = ' . $projectId;
			}
			$sqlquery .= ' AND mantis_bug_table.target_version = \'' . $targetVersion . '\'' .
			' AND mantis_bug_table.status = ' . $status;
		
		$amountOfIssuesByIndividual = $this->mysqli->query( $sqlquery );

		return $amountOfIssuesByIndividual;
	}
	
	public function buildSpecificRow( $userId, $rowFlag, $unreachableIssueFlag, $noUserFlag )
	{
		// inaktive user marks
		$iABackgroundColor = plugin_config_get( 'IABGColor' );
		$iATextColor = plugin_config_get( 'IATColor' );
		
		$uRBackgroundColor = plugin_config_get( 'URBGColor' );
		$uRTextColor = plugin_config_get( 'URTColor' );
		
		$nUBackgroundColor =  plugin_config_get( 'NUBGColor' );
		$nUTextColor = plugin_config_get( 'NUTColor' );
		
		if ( $rowFlag )
		{
			$rowIndex = 2;		
		}
		else
		{
			$rowIndex = 1;
		}
		if ( $userId != '0' && user_get_field( $userId, 'enabled' ) == '0' )
		{
			if ( plugin_config_get( 'IAUserHighlighting' ) )
			{
				echo '<tr style="background-color:' . $iABackgroundColor . ';color:' . $iATextColor . '">';
			}
			else
			{
				echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
			}
		}
		elseif ( $noUserFlag )
		{
			if ( plugin_config_get( 'NUIssueHighlighting' ) )
			{
				echo '<tr style="background-color:' . $nUBackgroundColor . ';color:' . $nUTextColor . '">';
			}
			else
			{
				echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
			}
		}
		elseif ( $unreachableIssueFlag )
		{
			if ( plugin_config_get( 'URUserHighlighting' ) )
			{
				echo '<tr style="background-color:' . $uRBackgroundColor . ';color:' . $uRTextColor . '">';
			}
			else
			{
				echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
			}
		}
		else
		{
			echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
		}
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
				echo '[ <a href="' . plugin_page('UserProject') . '&sortVal=userName&sort=ASC">';
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
				echo '[ <a href="' . plugin_page('PrintUserProject') . '">';
				echo plugin_lang_get( 'print_button' );
				echo '</a> ]';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}
}