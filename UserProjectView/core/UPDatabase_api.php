<?php

class UPDatabase_api
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

   public function getMantisVersion()
   {
      return substr( MANTIS_VERSION, 0, 4 );
   }

   public function resetPlugin()
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $config_table = db_get_table( 'mantis_config_table' );
      }
      else
      {
         $config_table = db_get_table( 'config' );
      }

      $query = "DELETE FROM $config_table
          WHERE config_id LIKE 'plugin_UserProjectView_%'";

      $this->mysqli->query( $query );
   }

   public function getAllUsers()
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $user_table = db_get_table( 'mantis_user_table' );
      }
      else
      {
         $user_table = db_get_table( 'user' );
      }

      $query = "SELECT u.id FROM $user_table u
          WHERE u.access_level < " . config_get_global( 'admin_site_threshold' );

      $all_users = $this->mysqli->query( $query );

      return $all_users;
   }

   public function getProjectV( $version )
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $project_version_table = db_get_table( 'mantis_project_version_table' );
      }
      else
      {
         $project_version_table = db_get_table( 'project_version' );
      }

      $query = "SELECT v.project_id FROM $project_version_table v
          WHERE v.version = '" . $version . "'";

      $assoc_array = mysqli_fetch_row( $this->mysqli->query( $query ) );
      $project = $assoc_array[0];

      return $project;
   }

   public function getIssuesUPTS( $user_id, $project_id, $target_version, $status )
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $bug_table = db_get_table( 'mantis_bug_table' );
      }
      else
      {
         $bug_table = db_get_table( 'bug' );
      }

      $query = "SELECT b.id FROM $bug_table b
          WHERE b.handler_id = " . $user_id . "
          AND b.status = " . $status . "
          AND b.target_version = '" . $target_version . "'";
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND b.project_id = " . $project_id;
      }

      $issues = $this->mysqli->query( $query );

      return $issues;
   }

   public function getAmountOfIssuesUPTS( $user_id, $project_id, $target_version, $status )
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $bug_table = db_get_table( 'mantis_bug_table' );
      }
      else
      {
         $bug_table = db_get_table( 'bug' );
      }

      $query = "SELECT COUNT(*) FROM $bug_table b
          WHERE b.handler_id = " . $user_id . "
          AND b.status = " . $status . "
          AND b.target_version = '" . $target_version . "'";
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND b.project_id = " . $project_id;
      }

      $assoc_array = mysqli_fetch_row( $this->mysqli->query( $query ) );
      $amount = $assoc_array[0];

      return $amount;
   }

   public function getAmountOfIssuesUPS( $user_id, $project_id, $status )
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $bug_table = db_get_table( 'mantis_bug_table' );
      }
      else
      {
         $bug_table = db_get_table( 'bug' );
      }

      $query = "SELECT COUNT(*) FROM $bug_table b
          WHERE b.handler_id = " . $user_id . "
          AND b.status = " . $status;
      if ( $project_id != '' || $project_id != 0 )
      {
         $query .= " AND b.project_id = " . $project_id;
      }

      $assoc_array = mysqli_fetch_row( $this->mysqli->query( $query ) );
      $amount = $assoc_array[0];

      return $amount;
   }

   public function checkUserIsAssignedToProject( $user_id, $project_id )
   {
      if ( $this->getMantisVersion() == '1.2.' )
      {
         $project_user_list_table = db_get_table( 'mantis_project_user_list_table' );
      }
      else
      {
         $project_user_list_table = db_get_table( 'project_user_list' );
      }

      $query = "SELECT p.user_id FROM $project_user_list_table p
          WHERE p.project_id = " . $project_id . "
          AND p.user_id = " . $user_id;

      $result = $this->mysqli->query( $query );

      return $result;
   }
}