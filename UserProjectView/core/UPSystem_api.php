<?php

class UPSystem_api
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

   public function userHasLevel()
   {
      $project_id = helper_get_current_project();
      $user_id = auth_get_current_user_id();

      return user_get_access_level( $user_id, $project_id ) >= plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
   }

   public function compareValues( $value_one, $value_two, $row_val )
   {
      if ( $value_one != $value_two )
      {
         return !$row_val;
      }
      return $row_val;
   }

   public function prepareFilterString( $unreach_issue_status_count, $unreach_issue_status_value, $filter_string )
   {
      for ( $unreachIssueStatusIndex = 0; $unreachIssueStatusIndex < $unreach_issue_status_count; $unreachIssueStatusIndex++ )
      {
         if ( $unreach_issue_status_value[$unreachIssueStatusIndex] != null )
         {
            if ( $this->getMantisVersion() == '1.2.' )
            {
               $filter_string .= '&status_id[]=' . $unreach_issue_status_value[$unreachIssueStatusIndex];
            }
            else
            {
               $filter_string .= '&status[]=' . $unreach_issue_status_value[$unreachIssueStatusIndex];
            }
         }
      }
      return $filter_string;
   }

   public function setIrrelevantFlag( $amount_stat_columns, $gug_status, $stat_cols )
   {
      $irrelevant_flag = array();
      for ( $statColIndex = 1; $statColIndex <= $amount_stat_columns; $statColIndex++ )
      {
         if ( $gug_status != $stat_cols[$statColIndex] )
         {
            $irrelevant_flag[$statColIndex] = true;
         }
         else
         {
            $irrelevant_flag[$statColIndex] = false;
         }
      }

      return $irrelevant_flag;
   }

   public function getMainProjectByHierarchy( $bug_assigned_project_id )
   {
      $parent_project = project_hierarchy_get_parent( $bug_assigned_project_id, false );
      if ( project_hierarchy_is_toplevel( $bug_assigned_project_id ) )
      {
         $bug_main_project_id = $bug_assigned_project_id;
      }
      else
      {
         // selected project is subproject
         while ( project_hierarchy_is_toplevel( $parent_project, false ) == false )
         {
            $parent_project = project_hierarchy_get_parent( $parent_project, false );

            if ( project_hierarchy_is_toplevel( $parent_project ) )
            {
               break;
            }
         }
         $bug_main_project_id = $parent_project;
      }

      return $bug_main_project_id;
   }

   public function getBugAssignedProjectId( $bug_assigned_project_id, $main_project_id )
   {
      if ( $bug_assigned_project_id == '' )
      {
         $bug_assigned_project_id = $main_project_id;
      }

      return $bug_assigned_project_id;
   }

   public function generateLinkUserId( $user_id )
   {
      $link_user_id = $user_id;
      if ( $user_id == '0' )
      {
         $link_user_id = '-2';
      }

      return $link_user_id;
   }

   public function checkUserAssignedToProject( $user_id, $bug_assigned_project_id )
   {
      include_once config_get_global( 'plugin_path' ) . plugin_get_current() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'UPDatabase_api.php';

      $upd_api = new UPDatabase_api();

      $assigned_to_project = '0';
      if ( $user_id != '0' && $bug_assigned_project_id != '' )
      {
         if ( !user_is_administrator( $user_id ) )
         {
            $assigned_to_project = mysqli_fetch_row( $upd_api->checkUserIsAssignedToProject( $user_id, $bug_assigned_project_id ) );
         }
      }

      return $assigned_to_project;
   }

   public function setUnreachableIssueFlag( $assigned_to_project )
   {
      $unreach_issue_flag = false;
      if ( $assigned_to_project == null || $assigned_to_project == '' )
      {
         $unreach_issue_flag = true;
      }

      return $unreach_issue_flag;
   }

   public function prepareParentProject( $project_id, $bug_assigned_project_id, $main_project_id )
   {
      $p_project = '';
      if ( $bug_assigned_project_id == '' && $main_project_id == '' )
      {
         $p_project = $project_id;
      }
      elseif ( $bug_assigned_project_id == '' && $main_project_id != '' )
      {
         $p_project = $main_project_id;
      }
      elseif ( $bug_assigned_project_id != '' )
      {
         $p_project = $bug_assigned_project_id;
      }

      return $p_project;
   }

   public function setUserflag( $amount_stat_columns, $stat_cols, $user_id )
   {
      $no_user_flag = false;
      for ( $statColIndex = 1; $statColIndex <= $amount_stat_columns; $statColIndex++ )
      {
         $spec_status = $stat_cols[$statColIndex];
         if ( $user_id == '0' && $spec_status == config_get( 'bug_assigned_status' )
            || $user_id == '0' && $spec_status == config_get( 'bug_feedback_status' )
            || $user_id == '0' && $spec_status == 80
            || $user_id == '0' && $spec_status == 90
         )
         {
            $no_user_flag = true;
         }
      }

      return $no_user_flag;
   }

   public function calculateTimeDifference( $spec_issues )
   {
      $act_time = time();
      $oldest_spec_issue_date = time();
      $oldest_spec_issue = null;
      foreach ( $spec_issues as $spec_issue )
      {
         $spec_issue_last_update = intval( bug_get_field( $spec_issue, 'last_updated' ) );
         if ( $spec_issue_last_update < $oldest_spec_issue_date )
         {
            $oldest_spec_issue_date = $spec_issue_last_update;
            $oldest_spec_issue = $spec_issue;
         }
      }
      $spec_time_difference = round( ( ( $act_time - $oldest_spec_issue_date ) / 86400 ), 0 );

      $result = array();
      $result[0] = $spec_time_difference;
      $result[1] = $oldest_spec_issue;

      return $result;
   }

   public function removeProjectUserSet( $record_set )
   {
      $record = array();
      $record_count = count( $record_set );

      for ( $recordIndex = 0; $recordIndex < $record_count; $recordIndex++ )
      {
         $record[$recordIndex] = explode( '__', $record_set[$recordIndex] );
      }

      for ( $recordIndex = 0; $recordIndex < $record_count; $recordIndex++ )
      {
         project_remove_user( $record[$recordIndex][1], $record[$recordIndex][0] );
      }
   }

   public function removeProjectUser( $user_id, $project_id )
   {
      $user_count = count( $user_id );
      $project_count = count( $project_id );

      if ( $user_count == $project_count )
      {
         for ( $dIndex = 0; $dIndex < $user_count; $dIndex++ )
         {
            project_remove_user( $project_id[$dIndex], $user_id[$dIndex] );
         }
      }
      else
      {
         echo plugin_lang_get( 'remove_failure' );
      }
   }
}