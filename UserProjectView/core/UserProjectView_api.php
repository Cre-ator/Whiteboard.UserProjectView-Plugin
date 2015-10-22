<?php

class UserProjectView_api
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

   /* ------------------------------------------------------------------------------------------------- */
   /* Menu functions */
   public function menu_printPlugin()
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

   public function menu_printUserProject()
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

   /* ------------------------------------------------------------------------------------------------- */
   /* System functions */
   public function system_getMantisVersion()
   {
      return substr( MANTIS_VERSION, 0, 4 );
   }

   public function system_buildRow( $userId, $rowVal, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag )
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
         if ( $this->system_getMantisVersion() == '1.2.' )
         {
            echo '<tr ' . helper_alternate_class( $rowIndex ) . '">';
         }
         else
         {
            echo '<tr class="row-' . $rowIndex . '">';
         }
      }
   }

   public function system_deleteUsersFromProjects( $projectId, $userId )
   {
      project_remove_user( $projectId, $userId );
   }

   public function system_getUserHasLevel()
   {
      $projectId = helper_get_current_project();
      $userId = auth_get_current_user_id();

      return user_get_access_level( $userId, $projectId ) >= plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
   }

   public function system_compareValues( $valueOne, $valueTwo, $rowVal )
   {
      if ( $valueOne != $valueTwo )
      {
         return !$rowVal;
      }
      return $rowVal;
   }

   public function system_prepareFilterString( $unreachIssueStatusCount, $unreachIssueStatusValue, $filterString )
   {
      for ( $unreachIssueStatusIndex = 0; $unreachIssueStatusIndex < $unreachIssueStatusCount; $unreachIssueStatusIndex++ )
      {
         if ( $unreachIssueStatusValue[$unreachIssueStatusIndex] != null )
         {
            if ( $this->system_getMantisVersion() == '1.2.' )
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

   public function system_setIrrelevantFlag( $amountStatColumns, $actBugStatus, $statCols )
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

   public function system_getMainProjectByHierarchy( $actBugAssignedProjectId )
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

   public function system_getBugAssignedProjectId( $bugAssignedProjectId, $mainProjectId )
   {
      if ( $bugAssignedProjectId == '' )
      {
         $bugAssignedProjectId = $mainProjectId;
      }

      return $bugAssignedProjectId;
   }

   public function system_generateLinkUserId( $userId )
   {
      $linkUserId = $userId;
      if ( $userId == '0' )
      {
         $linkUserId = '-2';
      }

      return $linkUserId;
   }

   public function system_checkUserAssignedToProject( $userId, $bugAssignedProjectId )
   {
      $isAssignedToProject = '0';
      if ( $userId != '0' && $bugAssignedProjectId != '' )
      {
         if ( !user_is_administrator( $userId ) )
         {
            $isAssignedToProject = mysqli_fetch_row( $this->database_checkUserIsAssignedToProject( $userId, $bugAssignedProjectId ) );
         }
      }

      return $isAssignedToProject;
   }

   public function system_setUnreachableIssueFlag( $isAssignedToProject )
   {
      $unreachableIssueFlag = false;
      if ( $isAssignedToProject == null || $isAssignedToProject == '' )
      {
         $unreachableIssueFlag = true;
      }

      return $unreachableIssueFlag;
   }

   public function system_prepareParentProject( $t_project_id, $bugAssignedProjectId, $mainProjectId )
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

   public function system_setUserflag( $amountStatColumns, $statCols, $userId )
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

   public function system_calculateTimeDifference( $specIssues )
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

   /* ------------------------------------------------------------------------------------------------- */
   /* Database functions */
   public function database_getAllUsers()
   {
      $sqlquery = ' SELECT mantis_user_table.id' .
            ' FROM mantis_user_table' .
            ' WHERE mantis_user_table.access_level < ' . config_get_global( 'admin_site_threshold' );

      $allActiveUsers = $this->mysqli->query( $sqlquery );

      return $allActiveUsers;
   }

   public function database_getProjectByVersion( $version )
   {
      $sqlquery = ' SELECT mantis_project_version_table.project_id' .
            ' FROM mantis_project_version_table' .
            ' WHERE mantis_project_version_table.version = \'' . $version . '\'';

      $assocArray = mysqli_fetch_row( $this->mysqli->query( $sqlquery ) );
      $projectByVersion = $assocArray [0];

      return $projectByVersion;
   }

   public function database_getIssues( $userId, $projectId, $targetVersion, $status )
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

      $issues = $this->mysqli->query( $sqlquery );

      return $issues;
   }

   public function database_getAmountOfIssues( $userId, $projectId, $targetVersion, $status )
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
      $amountOfIssues = $assocArray [0];

      return $amountOfIssues;
   }

   public function database_getAmountOfIssuesByIndividualWOTV( $userId, $projectId, $status )
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

   public function database_checkUserIsAssignedToProject( $userId, $projectId )
   {
      $sqlquery = ' SELECT mantis_project_user_list_table.user_id' .
            ' FROM mantis_project_user_list_table' .
            ' WHERE mantis_project_user_list_table.project_id = ' . $projectId .
            ' AND mantis_project_user_list_table.user_id = ' . $userId;

      $result = $this->mysqli->query( $sqlquery );

      return $result;
   }

   /* ------------------------------------------------------------------------------------------------- */
   /* Config functions */
   public function config_resetPlugin()
   {
      $sqlquery = ' DELETE FROM mantis_config_table' .
         ' WHERE config_id' .
         ' LIKE \'plugin_UserProjectView_%\' ';

      $this->mysqli->query( $sqlquery );
   }

   public function config_printTableRow()
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

   public function config_printSpacer( $colspan )
   {
      echo '<tr>';
      echo '<td class="spacer" colspan="' . $colspan . '">&nbsp;</td>';
      echo '</tr>';
   }

   public function config_printCategoryField( $colspan, $rowspan, $langString )
   {
      echo '<td class="category" colspan="' . $colspan . '" rowspan="' . $rowspan . '">';
      echo plugin_lang_get( $langString );
      echo '</td>';
   }

   public function config_printFormTitle( $colspan, $langString )
   {
      echo '<tr>';
      echo '<td class="form-title" colspan="' . $colspan . '">';
      echo plugin_lang_get( $langString );
      echo '</td>';
      echo '</tr>';
   }

   public function config_printRadioButton( $colspan, $name )
   {
      echo '<td width="100px" colspan="' . $colspan . '">';
      echo '<label>';
      echo '<input type="radio" name="' . $name . '" value="1"';
      echo ( ON == plugin_config_get( $name ) ) ? 'checked="checked"' : '';
      echo '/>' . plugin_lang_get( 'config_y' );
      echo '</label>';
      echo '<label>';
      echo '<input type="radio" name="' . $name . '" value="0"';
      echo ( OFF == plugin_config_get( $name ) ) ? 'checked="checked"' : '';
      echo '/>' . plugin_lang_get( 'config_n' );
      echo '</label>';
      echo '</td>';
   }

   public function config_printColorPicker( $colspan, $name, $default )
   {
      echo '<td width="100px" colspan="' . $colspan .'">';
      echo '<label>';
      echo '<input class="color {pickerFace:4,pickerClosable:true}" type="text" name="' . $name .'" value="' . plugin_config_get( $name, $default ) . '" />';
      echo '</label>';
      echo '</td>';
   }

   public function config_includeLeadingColorIdentifier( $Color )
   {
      if ( "#" == $Color [0] )
         return $Color;
      else
         return "#" . $Color;
   }

   public function config_updateColor( $FieldName, $DefaultColor )
   {
      $DefaultColor        = $this->config_includeLeadingColorIdentifier( $DefaultColor );
      $iAbackgroundcolor   = $this->config_includeLeadingColorIdentifier( gpc_get_string( $FieldName, $DefaultColor ) );
      if (  plugin_config_get( $FieldName ) != $iAbackgroundcolor
         && plugin_config_get( $FieldName ) != ''
      )
      {
         plugin_config_set( $FieldName, $iAbackgroundcolor );
      }
      elseif ( plugin_config_get( $FieldName ) == '' )
      {
         plugin_config_set( $FieldName, $DefaultColor );
      }
   }

   public function config_updateButton( $config )
   {
      $button = gpc_get_int( $config );

      if ( plugin_config_get( $config ) != $button )
      {
         plugin_config_set( $config, $button );
      }
   }

   public function config_updateValue( $value, $constant )
   {
      $actValue = null;

      if ( is_int( $value ) )
      {
         $actValue = gpc_get_int( $value, $constant );
      }

      if ( is_string( $value ) )
      {
         $actValue = gpc_get_string( $value, $constant );
      }

      if ( plugin_config_get( $value ) != $actValue )
      {
         plugin_config_set( $value, $actValue );
      }
   }

   public function config_updateDynamicValues( $value, $constant )
   {
      $cAmount = plugin_config_get( 'CAmount' );

      for ( $columnIndex = 1; $columnIndex <= $cAmount; $columnIndex++ )
      {
         $actValue = $value . $columnIndex;

         $this->config_updateValue( $actValue, $constant );
      }
   }
}