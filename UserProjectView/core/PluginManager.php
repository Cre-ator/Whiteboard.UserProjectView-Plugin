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
	
	public function getUnreachableIssuesByBugAndUser( $bugId, $userId )
	{
		$sqlquery = ' SELECT mantis_bug_table.id AS \'bid\',' .
				' mantis_bug_table.project_id AS \'pid\'' .
				' FROM mantis_bug_table' .
				' WHERE mantis_bug_table.id = ' . $bugId .
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
		
		return $unreachableIssuesByBugAndUser;
	}
	
	public function buildSpecificRow( $userId, $rowFlag )
	{
		// inaktive user marks
		$iABackgroundColor = plugin_config_get( 'IABGColor' );
		$iATextColor = plugin_config_get( 'IATColor' );
		
		$uRBackgroundColor;
		$uRTextColor;
		
		if ( $rowFlag )
		{
			$rowIndex = 2;		
		}
		else
		{
			$rowIndex = 1;
		}
			
		if ( user_get_field( $userId, 'enabled' ) == '0' )
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
		else
		{
			echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
		}
	}
}