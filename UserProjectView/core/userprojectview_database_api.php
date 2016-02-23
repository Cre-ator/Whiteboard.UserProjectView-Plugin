<?php

class userprojectview_database_api
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

   /**
    * Get act mantis version substring
    *
    * @return string
    */
   public function getMantisVersion()
   {
      return substr( MANTIS_VERSION, 0, 4 );
   }

   /**
    * @param $table
    * @return string
    */
   private function getMantisTable( $table )
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $mantis_table = db_get_table( 'mantis_' . $table . '_table' );
      }
      else
      {
         $mantis_table = db_get_table( $table );
      }

      return $mantis_table;
   }

   /**
    * Reset all saved plugin config data
    */
   public function resetPlugin()
   {
      $config_table = $this->getMantisTable( 'config' );

      $query = "DELETE FROM $config_table
          WHERE config_id LIKE 'plugin_UserProjectView_%'";

      $this->mysqli->query( $query );
   }

   /**
    * @return bool|mysqli_result
    */
   public function getAllUsers()
   {
      $user_table = $this->getMantisTable( 'user' );

      $query = "SELECT id FROM $user_table
          WHERE access_level < " . config_get_global( 'admin_site_threshold' );

      $all_users = $this->mysqli->query( $query );

      return $all_users;
   }

   /**
    * @param $version
    * @return mixed
    */
   public function getProjectV( $version )
   {
      $project_version_table = $this->getMantisTable( 'project_version' );

      $query = "SELECT project_id FROM $project_version_table
          WHERE version = '" . $version . "'";

      $assoc_array = mysqli_fetch_row( $this->mysqli->query( $query ) );
      $project = $assoc_array[0];

      return $project;
   }

   /**
    * @param $user_id
    * @param $project_id
    * @param $target_version
    * @param $status
    * @return bool|mysqli_result
    */
   public function getIssuesUPTS( $user_id, $project_id, $target_version, $status )
   {
      $bug_table = $this->getMantisTable( 'bug' );

      $query = "SELECT id FROM $bug_table
          WHERE handler_id = " . $user_id . "
          AND status = " . $status . "
          AND target_version = '" . $target_version . "'";
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND project_id = " . $project_id;
      }

      $issues = $this->mysqli->query( $query );

      return $issues;
   }

   /**
    * @param $user_id
    * @param $project_id
    * @param $target_version
    * @param $status
    * @return mixed
    */
   public function getAmountOfIssuesUPTS( $user_id, $project_id, $target_version, $status )
   {
      $bug_table = $this->getMantisTable( 'bug' );

      $query = "SELECT COUNT(*) FROM $bug_table
          WHERE handler_id = " . $user_id . "
          AND status = " . $status . "
          AND target_version = '" . $target_version . "'";
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND project_id = " . $project_id;
      }

      $assoc_array = mysqli_fetch_row( $this->mysqli->query( $query ) );
      $amount = $assoc_array[0];

      return $amount;
   }

   /**
    * @param $user_id
    * @param $project_id
    * @param $status
    * @return mixed
    */
   public function getAmountOfIssuesUPS( $user_id, $project_id, $status )
   {
      $bug_table = $this->getMantisTable( 'bug' );

      $query = "SELECT COUNT(*) FROM $bug_table
          WHERE handler_id = " . $user_id . "
          AND status = " . $status;
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND project_id = " . $project_id;
      }

      $assoc_array = mysqli_fetch_row( $this->mysqli->query( $query ) );
      $amount = $assoc_array[0];

      return $amount;
   }

   /**
    * @param $user_id
    * @param $project_id
    * @return bool|mysqli_result
    */
   public function checkUserIsAssignedToProject( $user_id, $project_id )
   {
      $project_user_list_table = $this->getMantisTable( 'project_user_list' );

      $query = "SELECT user_id FROM $project_user_list_table
          WHERE project_id = " . $project_id . "
          AND user_id = " . $user_id;

      $result = $this->mysqli->query( $query );

      return $result;
   }
}