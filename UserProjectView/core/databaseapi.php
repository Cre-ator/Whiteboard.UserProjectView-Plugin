<?php

/**
 * Class databaseapi
 *
 * Provides several functions to process data in the mantis- and plugin-database
 */
class databaseapi
{
   private $mysqli;
   private $dbPath;
   private $dbUser;
   private $dbPass;
   private $dbName;

   public function __construct ()
   {
      $this->dbPath = config_get ( 'hostname' );
      $this->dbUser = config_get ( 'db_username' );
      $this->dbPass = config_get ( 'db_password' );
      $this->dbName = config_get ( 'database_name' );

      $this->mysqli = new mysqli( $this->dbPath, $this->dbUser, $this->dbPass, $this->dbName );
   }

   /**
    * Gets act mantis version substring
    *
    * @return string
    */
   public function get_mantis_version ()
   {
      return substr ( MANTIS_VERSION, 0, 4 );
   }

   /**
    * Gets a specific mantis database table
    *
    * @param $table
    * @return string
    */
   private function get_mantis_table ( $table )
   {
      if ( $this->get_mantis_version () == '1.2.' )
      {
         $mantis_table = db_get_table ( 'mantis_' . $table . '_table' );
      }
      else
      {
         $mantis_table = db_get_table ( $table );
      }

      return $mantis_table;
   }

   /**
    * Reset all saved plugin config data
    */
   public function reset_plugin ()
   {
      $config_table = $this->get_mantis_table ( 'config' );

      $query = "DELETE FROM $config_table
          WHERE config_id LIKE 'plugin_UserProjectView_%'";

      $this->mysqli->query ( $query );
   }

   /**
    * Gets all users in the mantis database
    *
    * @return bool|mysqli_result
    */
   public function get_all_users ()
   {
      $user_table = $this->get_mantis_table ( 'user' );

      $query = "SELECT id FROM $user_table
          WHERE access_level < " . config_get_global ( 'admin_site_threshold' );

      $all_users = $this->mysqli->query ( $query );

      return $all_users;
   }

   /**
    * Gets the project id assigned to a specific version
    *
    * @param $version
    * @return mixed
    */
   public function get_project_id_by_version ( $version )
   {
      $project_version_table = $this->get_mantis_table ( 'project_version' );

      $query = "SELECT project_id FROM $project_version_table
          WHERE version = '" . $version . "'";

      $assoc_array = mysqli_fetch_row ( $this->mysqli->query ( $query ) );
      $project = $assoc_array[ 0 ];

      return $project;
   }

   /**
    * Gets the issue ids by a specific filter
    *
    * @param $user_id
    * @param $project_id
    * @param $target_version
    * @param $status
    * @return mixed
    */
   public function get_issues_by_user_project_version_status ( $user_id, $project_id, $target_version, $status )
   {
      $bug_table = $this->get_mantis_table ( 'bug' );

      $query = "SELECT id FROM $bug_table
          WHERE handler_id = " . $user_id . "
          AND status = " . $status . "
          AND target_version = '" . $target_version . "'";
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND project_id = " . $project_id;
      }

      $issues = $this->mysqli->query ( $query );

      return $issues;
   }

   /**
    * Gets the amount of issue ids by a specific filter
    *
    * @param $user_id
    * @param $project_id
    * @param $target_version
    * @param $status
    * @return mixed
    */
   public function get_amount_issues_by_user_project_version_status ( $user_id, $project_id, $target_version, $status )
   {
      $bug_table = $this->get_mantis_table ( 'bug' );

      $query = "SELECT COUNT(*) FROM $bug_table
          WHERE handler_id = " . $user_id . "
          AND status = " . $status . "
          AND target_version = '" . $target_version . "'";
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND project_id = " . $project_id;
      }

      $assoc_array = mysqli_fetch_row ( $this->mysqli->query ( $query ) );
      $amount = $assoc_array[ 0 ];

      return $amount;
   }

   /**
    * Gets the amount of issue ids by a specific filter
    *
    * @param $user_id
    * @param $project_id
    * @param $status
    * @return mixed
    */
   public function get_amount_issues_by_user_project_status ( $user_id, $project_id, $status )
   {
      $bug_table = $this->get_mantis_table ( 'bug' );

      $query = "SELECT COUNT(*) FROM $bug_table
          WHERE handler_id = " . $user_id . "
          AND status = " . $status;
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND project_id = " . $project_id;
      }

      $assoc_array = mysqli_fetch_row ( $this->mysqli->query ( $query ) );
      $amount = $assoc_array[ 0 ];

      return $amount;
   }

   /**
    * Checks if a given user is assigned to a given project
    *
    * @param $user_id
    * @param $project_id
    * @return bool
    */
   public function check_user_project_assignment ( $user_id, $project_id )
   {
      $project_user_list_table = $this->get_mantis_table ( 'project_user_list' );

      $query = "SELECT user_id FROM $project_user_list_table
          WHERE project_id = " . $project_id . "
          AND user_id = " . $user_id;

      $assoc_array = mysqli_fetch_row ( $this->mysqli->query ( $query ) );
      $assigned_user_id = $assoc_array[ 0 ];

      return $assigned_user_id;
   }
}