<?php
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_system_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_database_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_print_api.php';

$userprojectview_print_api = new userprojectview_print_api();

$print_flag = false;
if ( isset( $_POST['print_flag'] ) )
{
   $print_flag = true;
}

$t_project_id = gpc_get_int( 'project_id', helper_get_current_project() );
if ( ( ALL_PROJECTS == $t_project_id || project_exists( $t_project_id ) )
   && $t_project_id != helper_get_current_project()
)
{
   helper_set_current_project( $t_project_id );
   print_header_redirect( $_SERVER['REQUEST_URI'], true, false, true );
}

$statCols = array();
$issueThresholds = array();
$issueAgeThresholds = array();
$amountStatColumns = get_amount_stat_columns();
for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
   $statCols[$statColIndex] = plugin_config_get( 'CStatSelect' . $statColIndex );
   $issueThresholds[$statColIndex] = plugin_config_get( 'IAMThreshold' . $statColIndex );
   $issueAgeThresholds[$statColIndex] = plugin_config_get( 'IAGThreshold' . $statColIndex );
}

$matchcode = calc_matchcodes( $statCols );
$amountOfShownIssues = 0;
$result = process_match_codes( $matchcode, $statCols );
$data_array = $result[0];
$rowIndex = $result[1];

if ( plugin_config_get( 'ShowZIU' ) )
{
   $data_array = process_zero_issue_users( $data_array, $rowIndex, $t_project_id, $statCols );
}

html_page_top1( plugin_lang_get( 'menu_userprojecttitle' ) );
echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>';
echo '<script type="text/javascript" src="plugins' . DIRECTORY_SEPARATOR . plugin_get_current() . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . 'table.js"></script>';
echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';
if ( !$print_flag )
{
   html_page_top2();
   if ( plugin_is_installed( 'WhiteboardMenu' ) && file_exists( config_get_global( 'plugin_path' ) . 'WhiteboardMenu' ) )
   {
      require_once WHITEBOARDMENU_CORE_URI . 'whiteboard_print_api.php';
      $whiteboard_print_api = new whiteboard_print_api();
      $whiteboard_print_api->printWhiteboardMenu();
   }
}

echo '<div id="manage-user-div" class="form-container">';
$userprojectview_print_api->print_table_head();
print_thead( $statCols, $print_flag );
print_tbody( $data_array, $t_project_id, $statCols, $issueThresholds, $issueAgeThresholds, $print_flag );
echo '</table>';
echo '</div>';
if ( !$print_flag )
{
   html_page_bottom1();
}

function get_amount_stat_columns()
{
   $amountStatColumns = plugin_config_get( 'CAmount' );
   if ( $amountStatColumns > PLUGINS_USERPROJECTVIEW_MAX_COLUMNS )
   {
      $amountStatColumns = PLUGINS_USERPROJECTVIEW_MAX_COLUMNS;
   }

   return $amountStatColumns;
}

function calc_matchcodes( $statCols )
{
   $userprojectview_database_api = new userprojectview_database_api();
   $userprojectview_system_api = new userprojectview_system_api();

   $amountStatColumns = get_amount_stat_columns();
   $matchcode = array();
   $f_page_number = gpc_get_int( 'page_number', 1 );
   $t_per_page = 10000;
   $t_page_count = null;
   $t_bug_count = null;

   $rows = filter_get_bug_rows( $f_page_number, $t_per_page, $t_page_count, $t_bug_count, unserialize( '' ), null, null, true );

   $t_bugslist = array();
   $t_row_count = count( $rows );

   for ( $i = 0; $i < $t_row_count; $i++ )
   {
      array_push( $t_bugslist, $rows[$i]->id );
   }

   for ( $bugIndex = 0; $bugIndex < $t_row_count; $bugIndex++ )
   {
      // bug information
      $actBugId = $t_bugslist[$bugIndex];
      $actBugTargetVersion = bug_get_field( $actBugId, 'target_version' );
      $actBugStatus = bug_get_field( $actBugId, 'status' );
      $actBugAssignedProjectId = bug_get_field( $actBugId, 'project_id' );
      $actBugAssignedUserId = bug_get_field( $actBugId, 'handler_id' );

      // user information
      $aBAUIUsername = '';
      $aBAUIRealname = '';
      $aBAUIActivFlag = true;

      // filter config specific bug status
      $irrelevantFlag = $userprojectview_system_api->setIrrelevantFlag( $amountStatColumns, $actBugStatus, $statCols );
      if ( !in_array( false, $irrelevantFlag ) )
      {
         continue;
      }

      // bug is assigned, etc... but not ASSIGNED TO, etc ...
      if ( $actBugAssignedUserId != 0 )
      {
         $aBAUIUsername = user_get_name( $actBugAssignedUserId );
         if ( user_exists( $actBugAssignedUserId ) )
         {
            $aBAUIRealname = user_get_realname( $actBugAssignedUserId );
            $aBAUIActivFlag = user_is_enabled( $actBugAssignedUserId );
         }
      }

      // project information
      $aBAPIname = project_get_name( $actBugAssignedProjectId );

      if ( $actBugTargetVersion == '' )
      {
         // no target version available -> get main project by project hierarchy
         $actBugMainProjectId = $userprojectview_system_api->getMainProjectByHierarchy( $actBugAssignedProjectId );
      }
      else
      {
         // identify main project by target version of selected issue
         $actBugMainProjectId = $userprojectview_database_api->getProjectV( $actBugTargetVersion );
      }

      $actBugMainProjectName = project_get_name( $actBugMainProjectId );

      // prepare target version string
      $versionDate = null;
      if ( $actBugTargetVersion != '' )
      {
         $versionId = version_get_id( $actBugTargetVersion, $actBugAssignedProjectId );
         $targetVersionString = prepare_version_string( $actBugAssignedProjectId, $versionId );
         if ( $versionId != null )
         {
            $versionDate = date( 'Y-m-d', version_get_field( $versionId, 'date_order' ) );
         }
      }
      else
      {
         $targetVersionString = '';
         $versionDate = '';
      }

      if ( $actBugAssignedProjectId == $actBugMainProjectId )
      {
         $actBugAssignedProjectId = '';
         $aBAPIname = '';
      }

      // prepare record matchcode
      $matchcode[$bugIndex] = $actBugAssignedUserId
         . '__' . $aBAUIUsername
         . '__' . $aBAUIRealname
         . '__' . $actBugMainProjectId
         . '__' . $actBugMainProjectName
         . '__' . $actBugAssignedProjectId
         . '__' . $aBAPIname
         . '__' . $actBugTargetVersion
         . '__' . $versionDate
         . '__' . $targetVersionString
         . '__' . $aBAUIActivFlag;
   }

   return $matchcode;
}

function process_match_codes( $matchcode, $statCols )
{
   $userprojectview_database_api = new userprojectview_database_api();

   $amountStatColumns = get_amount_stat_columns();
   $tableRow = array();
   $dataRows = array_count_values( $matchcode );
   $rowCount = count( $dataRows );

   for ( $rowIndex = 0; $rowIndex < $rowCount; $rowIndex++ )
   {
      // process first entry in array
      $rowContent = key( $dataRows );

      // process data string
      $rowVals = explode( '__', $rowContent );
      // fill tablerow with data
      $tableRow[$rowIndex]['userId'] = $rowVals[0];
      $tableRow[$rowIndex]['userName'] = $rowVals[1];
      $tableRow[$rowIndex]['userRealname'] = $rowVals[2];
      $tableRow[$rowIndex]['mainProjectId'] = $rowVals[3];
      $tableRow[$rowIndex]['mainProjectName'] = $rowVals[4];
      $tableRow[$rowIndex]['bugAssignedProjectId'] = $rowVals[5];
      $tableRow[$rowIndex]['bugAssignedProjectName'] = $rowVals[6];
      $tableRow[$rowIndex]['bugTargetVersion'] = $rowVals[7];
      $tableRow[$rowIndex]['bugTargetVersionDate'] = $rowVals[8];
      $tableRow[$rowIndex]['bugTargetVersionPreparedString'] = $rowVals[9];
      $tableRow[$rowIndex]['inactiveUserFlag'] = $rowVals[10];

      $tableRow[$rowIndex]['zeroIssuesFlag'] = false;

      for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
      {
         $tableRow[$rowIndex]['specColumn' . $statColIndex] = '0';

         $specColumnValue = 'specColumn' . $statColIndex;

         if ( $statCols[$statColIndex] != null )
         {
            if ( $rowVals[5] == '' )
            {
               $tableRow[$rowIndex][$specColumnValue] = $userprojectview_database_api->getAmountOfIssuesUPTS( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[$statColIndex] );
            }
            else
            {
               $tableRow[$rowIndex][$specColumnValue] = $userprojectview_database_api->getAmountOfIssuesUPTS( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[$statColIndex] );
            }
         }
      }
      array_shift( $dataRows );
   }
   $result = array();
   $result[0] = $tableRow;
   $result[1] = $rowIndex;

   return $result;
}

function process_zero_issue_users( $tableRow, $rowIndex, $t_project_id, $statCols )
{
   $userprojectview_database_api = new userprojectview_database_api();

   $amountStatColumns = get_amount_stat_columns();
   $allUsers = $userprojectview_database_api->getAllUsers();
   $userRows = array();
   while ( $userRow = mysqli_fetch_row( $allUsers ) )
   {
      $userRows[] = $userRow;
   }

   $rowCount = count( $userRows );
   for ( $userRowIndex = 0; $userRowIndex < $rowCount; $userRowIndex++ )
   {
      $userId = $userRows[$userRowIndex][0];
      $userName = user_get_name( $userId );
      $userRealname = user_get_realname( $userId );
      $userIsActive = false;
      if ( user_exists( $userId ) )
      {
         $userIsActive = user_is_enabled( $userId );
      }
      $userIsAssignedToProjectHierarchy = false;

      if ( $userIsActive == false )
      {
         continue;
      }

      $addRow = $rowIndex + 1 + $userRowIndex;

      $amountOfIssues = '';
      if ( $t_project_id == 0 )
      {
         for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
         {
            $amountOfIssues .= $userprojectview_database_api->getAmountOfIssuesUPS( $userId, $t_project_id, $statCols[$statColIndex] );
         }
      }
      else
      {
         $subProjects = array();
         array_push( $subProjects, $t_project_id );
         $tSubProjects = project_hierarchy_get_all_subprojects( $t_project_id );

         foreach ( $tSubProjects as $tSubProject )
         {
            array_push( $subProjects, $tSubProject );
         }

         foreach ( $subProjects as $subProject )
         {
            $userIsAssignedToProject = mysqli_fetch_row( $userprojectview_database_api->checkUserIsAssignedToProject( $userId, $subProject ) );
            if ( $userIsAssignedToProject != null )
            {
               $userIsAssignedToProjectHierarchy = true;
               break;
            }
         }

         if ( !$userIsAssignedToProjectHierarchy )
         {
            continue;
         }

         for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
         {
            foreach ( $subProjects as $subProject )
            {
               $amountOfIssues .= $userprojectview_database_api->getAmountOfIssuesUPS( $userId, $subProject, $statCols[$statColIndex] );
            }
         }
      }

      // build row
      if ( intval( $amountOfIssues ) == 0 )
      {
         // fill tablerow with data
         $tableRow[$addRow]['userId'] = $userId;
         $tableRow[$addRow]['userName'] = $userName;
         $tableRow[$addRow]['userRealname'] = $userRealname;
         $tableRow[$addRow]['mainProjectId'] = '';
         $tableRow[$addRow]['mainProjectName'] = '';
         $tableRow[$addRow]['bugAssignedProjectId'] = '';
         $tableRow[$addRow]['bugAssignedProjectName'] = '';
         $tableRow[$addRow]['bugTargetVersion'] = '';
         $tableRow[$addRow]['bugTargetVersionDate'] = '';
         $tableRow[$addRow]['bugTargetVersionPreparedString'] = '';
         $tableRow[$addRow]['inactiveUserFlag'] = $userIsActive;
         $tableRow[$addRow]['zeroIssuesFlag'] = true;

         for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
         {
            $tableRow[$addRow]['specColumn' . $statColIndex] = '0';
         }
      }
   }
   return $tableRow;
}

function print_thead( $statCols, $print_flag )
{
   $userprojectview_print_api = new userprojectview_print_api();

   $fixColspan = 7;
   if ( plugin_config_get( 'showHeadRow' ) )
   {
      $fixColspan++;
   }

   if ( plugin_config_get( 'ShowAvatar' ) )
   {
      $fixColspan++;
   }

   $amountStatColumns = get_amount_stat_columns();
   $dynamicColspan = $amountStatColumns + $fixColspan;
   $headerColspan = $fixColspan - 6;

   echo '<thead>';
   $userprojectview_print_api->printTHRow( $dynamicColspan, $print_flag );
   echo '<tr class="row-category">';
   if ( $print_flag )
   {
      $headerColspan = null;
   }
   $userprojectview_print_api->printTH( 'thead_username', 'userName', $headerColspan );
   $userprojectview_print_api->printTH( 'thead_realname', 'realName', null );
   $userprojectview_print_api->printTH( 'thead_project', 'mainProject', null );
   $userprojectview_print_api->printTH( 'thead_subproject', 'assignedProject', null );
   $userprojectview_print_api->printTH( 'thead_targetversion', 'targetVersion', null );

   for ( $headIndex = 1; $headIndex <= $amountStatColumns; $headIndex++ )
   {
      echo '<th class="headrow" bgcolor="' . get_status_color( $statCols[$headIndex], null, null ) . '">';
      $assocArray = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
      echo $assocArray [$statCols[$headIndex]];
      echo '</th>';
   }
   echo '<th class="headrow">' . plugin_lang_get( 'thead_remark' ) . '</th>';
   echo '</tr>';
   echo '</thead>';
}

function print_tbody( $tableRow, $t_project_id, $statCols, $issueThresholds, $issueAgeThresholds, $print_flag )
{
   $userprojectview_system_api = new userprojectview_system_api();
   $userprojectview_print_api = new userprojectview_print_api();
   $amountStatColumns = get_amount_stat_columns();
   $sortVal = $_GET['sortVal'];
   $sortOrder = $_GET['sort'];
   $sortCol = get_sort_col( $sortVal, $tableRow );
   $sortOrd = get_sort_order( $sortOrder );

   if ( $tableRow != null )
   {
      array_multisort( $sortCol, $sortOrd, SORT_NATURAL | SORT_FLAG_CASE, $tableRow );
   }
   $tableRowCount = count( $tableRow );
   $specColumnIssueAmount = array();
   for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
   {
      $specColumnIssueAmount[$statColIndex] = '';
   }

   echo '<tbody>';

   if ( plugin_config_get( 'showHeadRow' ) && !$print_flag && ( $sortVal == 'userName' || $sortVal == 'realName' ) )
   {
      /** initialize and prepare groups */
      $group_user_with_issue = array();
      $group_user_without_issue = array();
      $group_inactive_deleted_user = array();
      $group_issues_without_user = array();

      $groups = array();
      $groups[0] = $group_user_with_issue;
      $groups[1] = $group_user_without_issue;
      $groups[2] = $group_inactive_deleted_user;
      $groups[3] = $group_issues_without_user;
      $groups = assign_groups( $groups, $tableRow, $amountStatColumns );

      /** process each group */
      $group_user_with_issue = $groups[0];
      $head_rows_array = calculate_head_rows( $tableRow, $amountStatColumns );
      print_group_head_row( 0, 'headrow_user', $amountStatColumns, $group_user_with_issue, $tableRow, $statCols );
      foreach ( $head_rows_array as $head_row )
      {
         $head_row_user_id = $head_row[0];
         $head_row_counter = true;
         for ( $group_index = 0; $group_index < count( $group_user_with_issue ); $group_index++ )
         {
            $tableRowIndex = $group_user_with_issue[$group_index];
            $user_id = $tableRow[$tableRowIndex]['userId'];
            if ( $user_id == $head_row_user_id )
            {
               if ( $head_row_counter )
               {
                  print_user_head_row( $user_id, $head_row, $amountStatColumns, $issueThresholds );
                  $head_row_counter = false;
               }
               $specColumnIssueAmount = print_row( $tableRow, $tableRowIndex, $amountStatColumns, $t_project_id, $statCols, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount, false );
            }
         }
      }

      print_category_block( $groups[1], '100002', 'headrow_no_issue', $amountStatColumns, $tableRow, $statCols, $t_project_id, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount );
      print_category_block( $groups[2], '100003', 'headrow_del_user', $amountStatColumns, $tableRow, $statCols, $t_project_id, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount );
      print_category_block( $groups[3], '100004', 'headrow_no_user', $amountStatColumns, $tableRow, $statCols, $t_project_id, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount );
      build_option_panel( $amountStatColumns, $specColumnIssueAmount, $print_flag );
   }
   else
   {
      $row_index = 1;
      for ( $tableRowIndex = 0; $tableRowIndex < $tableRowCount; $tableRowIndex++ )
      {
         $userId = $tableRow[$tableRowIndex]['userId'];
         $userName = $tableRow[$tableRowIndex]['userName'];
         $userRealname = $tableRow[$tableRowIndex]['userRealname'];
         $mainProjectId = $tableRow[$tableRowIndex]['mainProjectId'];
         $mainProjectName = $tableRow[$tableRowIndex]['mainProjectName'];
         $bugAssignedProjectId = $tableRow[$tableRowIndex]['bugAssignedProjectId'];
         $bugAssignedProjectName = $tableRow[$tableRowIndex]['bugAssignedProjectName'];
         $bugTargetVersion = $tableRow[$tableRowIndex]['bugTargetVersion'];
         $bugTargetVersionDate = $tableRow[$tableRowIndex]['bugTargetVersionDate'];
         $bugTargetVersionPreparedString = $tableRow[$tableRowIndex]['bugTargetVersionPreparedString'];
         $inactiveUserFlag = $tableRow[$tableRowIndex]['inactiveUserFlag'];
         $zeroIssuesFlag = $tableRow[$tableRowIndex]['zeroIssuesFlag'];
         $issueCounter = array();
         for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
         {
            $issueCounter[$statColIndex] = $tableRow[$tableRowIndex]['specColumn' . $statColIndex];
         }

         $bugAssignedProjectId = $userprojectview_system_api->getBugAssignedProjectId( $bugAssignedProjectId, $mainProjectId );
         $linkUserId = $userprojectview_system_api->generateLinkUserId( $userId );
         $isAssignedToProject = $userprojectview_system_api->checkUserAssignedToProject( $userId, $bugAssignedProjectId );
         $unreachableIssueFlag = $userprojectview_system_api->setUnreachableIssueFlag( $isAssignedToProject );
         $pProject = $userprojectview_system_api->prepareParentProject( $t_project_id, $bugAssignedProjectId, $mainProjectId );
         $noUserFlag = $userprojectview_system_api->setUserflag( $amountStatColumns, $statCols, $userId );

         /** @var $change_row_bg := true, if value has changed, false if not */
         $change_row_bg = false;
         if ( $tableRowIndex > 0 )
         {
            $change_row_bg = checkout_change_row( $sortVal, $tableRow, $tableRowIndex );
         }

         /** @var $row_index := 1 dark grey, 2 light grey ( Mantis 1.2.x ) */
         if ( $change_row_bg )
         {
            $row_index = 3 - $row_index;
         }

         $userprojectview_print_api->printTDRow( $userId, $row_index, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
         if ( !$print_flag )
         {
            build_chackbox_column( $userId, $pProject );
            build_avatar_column( $linkUserId, $userId );
         }
         build_user_column( $linkUserId, $userName, $print_flag, true );
         build_real_name_column( $linkUserId, $userRealname, $print_flag, true );
         build_main_project_column( $mainProjectId, $linkUserId, $mainProjectName, $print_flag );
         build_assigned_project_column( $bugAssignedProjectId, $linkUserId, $bugAssignedProjectName, $print_flag );
         target_version_column( $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $bugTargetVersionDate, $bugTargetVersionPreparedString, $print_flag );
         $specColumnIssueAmount = build_amount_of_issues_column( $amountStatColumns, $issueThresholds, $statCols, $issueCounter, $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $specColumnIssueAmount, $print_flag );
         build_remark_column( $amountStatColumns, $issueAgeThresholds, $bugAssignedProjectId, $mainProjectId, $statCols, $userId, $bugTargetVersion, $linkUserId, $unreachableIssueFlag, $inactiveUserFlag, $zeroIssuesFlag, $noUserFlag, $print_flag );
         echo '</tr>';
      }

      build_option_panel( $amountStatColumns, $specColumnIssueAmount, $print_flag );
      echo '</tbody>';
   }
}

function print_category_block( $group, $category, $lang_string, $amountStatColumns, $tableRow, $statCols, $t_project_id, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount )
{
   print_group_head_row( $category, $lang_string, $amountStatColumns, $group, $tableRow, $statCols );
   for ( $group_index = 0; $group_index < count( $group ); $group_index++ )
   {
      $tableRowIndex = $group[$group_index];
      $specColumnIssueAmount = print_row( $tableRow, $tableRowIndex, $amountStatColumns, $t_project_id, $statCols, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount, true );
   }
}

function calculate_head_rows( $tableRow, $amountStatColumns )
{
   $head_rows_array = array();
   for ( $tableRowIndex = 0; $tableRowIndex < count( $tableRow ); $tableRowIndex++ )
   {
      $userId = $tableRow[$tableRowIndex]['userId'];
      if ( $userId == 0 )
      {
         continue;
      }

      $head_row = array();
      $iCounter = array();
      for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
      {
         $iCounter[$statColIndex] = $tableRow[$tableRowIndex]['specColumn' . $statColIndex];
      }

      if ( $tableRowIndex == 0 )
      {
         /** create first headrow entry */
         $head_row[0] = $userId;
         $head_row[1] = $iCounter;

         array_push( $head_rows_array, $head_row );
      }

      if ( $tableRowIndex > 0 )
      {
         /** process data of same user now || not and create next headrow */
         $last_user_id = $tableRow[$tableRowIndex - 1]['userId'];
         if ( $last_user_id == $userId )
         {
            /** same user */
            for ( $head_rows_array_index = 0; $head_rows_array_index < count( $head_rows_array ); $head_rows_array_index++ )
            {
               $head_row_array = $head_rows_array[$head_rows_array_index];
               /** find his array */
               if ( $head_row_array[0] == $userId )
               {
                  /** get his issue counter */
                  $extracted_iCounter = $head_row_array[1];
                  /** add count to existing */
                  for ( $iCounter_index = 1; $iCounter_index <= $amountStatColumns; $iCounter_index++ )
                  {
                     $extracted_iCounter[$iCounter_index] += $tableRow[$tableRowIndex]['specColumn' . $iCounter_index];
                  }
                  /** save modified counter */
                  $head_row_array[1] = $extracted_iCounter;
                  $head_rows_array[$head_rows_array_index] = $head_row_array;
               }
            }
         }
         else
         {
            /** new user */
            $head_row[0] = $userId;
            $head_row[1] = $iCounter;

            array_push( $head_rows_array, $head_row );
         }
      }
   }

   return $head_rows_array;
}

function print_user_head_row( $user_id, $head_row, $amountStatColumns, $issueThresholds )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   echo '<tr name="100001" class="clickable" data-level="1" data-status="0" background-color:' . plugin_config_get( 'HeadRowColor' ) . '">';
   echo '<td width="20px" />';
   echo '<td class="icon" />';
   if ( plugin_config_get( 'ShowAvatar' ) && config_get( 'show_avatar' ) && $userAccessLevel >= config_get( 'show_avatar_threshold' ) )
   {
      echo '<td align="center" width="25px">';
      $assocArray = user_get_avatar( $user_id );
      echo '<img class="avatar" src="' . $assocArray[0] . '" />';
      echo '</td>';
   }
   echo '<td>' . user_get_name( $user_id ) . '</td>';
   echo '<td>' . user_get_realname( $user_id ) . '</td>';
   echo '<td colspan="3"/>';
   $iCounter = $head_row[1];
   for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
   {
      $issueThreshold = $issueThresholds[$statColIndex];
      if ( $issueThreshold <= $iCounter[$statColIndex] && $issueThreshold > 0 )
      {
         echo '<td style="background-color:' . plugin_config_get( 'TAMHBGColor' ) . '">' . $iCounter[$statColIndex] . '</td>';
      }
      else
      {
         echo '<td>' . $iCounter[$statColIndex] . '</td>';
      }
   }
   echo '<td/>';
   echo '</tr>';
}

function print_group_head_row( $category, $lang_string, $amountStatColumns, $group, $tableRow, $statCols )
{
   $iCounter = array();
   foreach ( $group as $table_row_index )
   {
      for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
      {
         $iCounter[$statColIndex] += $tableRow[$table_row_index]['specColumn' . $statColIndex];
      }
   }

   if ( !empty( $iCounter ) )
   {
      $colspan = 6;
      if ( plugin_config_get( 'ShowAvatar' ) && config_get( 'show_avatar' ) )
      {
         $colspan = 7;
      }

      echo '<tr class="clickable" data-level="0" data-status="0" style="background-color:' . plugin_config_get( 'HeadRowColor' ) . '">';
      echo '<td class="icon" />';
      echo '<td colspan="' . $colspan . '">' . plugin_lang_get( $lang_string );
      echo '</td>';

      for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
      {
         if ( $category == 100002 && $iCounter[$statColIndex] > 0 )
         {
            $StatColStatus = $statCols[$statColIndex];
            if ( $StatColStatus == '10' || $StatColStatus == '20' || $StatColStatus == '30' || $StatColStatus == '40' || $StatColStatus == '50' )
            {
               echo '<td style="background-color:' . plugin_config_get( 'TAMHBGColor' ) . '">' . $iCounter[$statColIndex] . '</td>';
            }
            else
            {
               echo '<td>' . $iCounter[$statColIndex] . '</td>';
            }
         }
         else
         {
            echo '<td>' . $iCounter[$statColIndex] . '</td>';
         }
      }

      echo '<td/>';
      echo '</tr>';
   }
}

/**
 * Erzeugt einen String, der alle "row_view"-Aufrufe in table.js koordiniert.
 *
 * @param $tableRow
 * @param $amountStatColumns
 * @param $group
 * @return string
 */
function get_js_function_call_string( $tableRow, $amountStatColumns, $group )
{
   $user_id_array = array();
   $head_rows_array = calculate_head_rows( $tableRow, $amountStatColumns );
   foreach ( $head_rows_array as $head_row )
   {
      $head_row_user_id = $head_row[0];
      for ( $group_index = 0; $group_index < count( $group ); $group_index++ )
      {
         $tableRowIndex = $group[$group_index];
         $user_id = $tableRow[$tableRowIndex]['userId'];
         if ( $user_id == $head_row_user_id )
         {
            if ( !in_array( $user_id, $user_id_array, true ) )
            {
               array_push( $user_id_array, $user_id );
            }
         }
      }
   }

   $function_call_string = '';
   foreach ( $user_id_array as $user_id )
   {
      $function_call_string .= 'row_view(' . $user_id . ');';
   }

   return $function_call_string;
}

function print_row( $tableRow, $tableRowIndex, $amountStatColumns, $t_project_id, $statCols, $print_flag, $issueAgeThresholds, $issueThresholds, $specColumnIssueAmount, $detailed_flag )
{
   $userprojectview_system_api = new userprojectview_system_api();
   $userprojectview_print_api = new userprojectview_print_api();

   $userId = $tableRow[$tableRowIndex]['userId'];
   $userName = $tableRow[$tableRowIndex]['userName'];
   $userRealname = $tableRow[$tableRowIndex]['userRealname'];
   $mainProjectId = $tableRow[$tableRowIndex]['mainProjectId'];
   $mainProjectName = $tableRow[$tableRowIndex]['mainProjectName'];
   $bugAssignedProjectId = $tableRow[$tableRowIndex]['bugAssignedProjectId'];
   $bugAssignedProjectName = $tableRow[$tableRowIndex]['bugAssignedProjectName'];
   $bugTargetVersion = $tableRow[$tableRowIndex]['bugTargetVersion'];
   $bugTargetVersionDate = $tableRow[$tableRowIndex]['bugTargetVersionDate'];
   $bugTargetVersionPreparedString = $tableRow[$tableRowIndex]['bugTargetVersionPreparedString'];
   $inactiveUserFlag = $tableRow[$tableRowIndex]['inactiveUserFlag'];
   $zeroIssuesFlag = $tableRow[$tableRowIndex]['zeroIssuesFlag'];
   $issueCounter = array();
   for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
   {
      $issueCounter[$statColIndex] = $tableRow[$tableRowIndex]['specColumn' . $statColIndex];
   }

   $bugAssignedProjectId = $userprojectview_system_api->getBugAssignedProjectId( $bugAssignedProjectId, $mainProjectId );
   $linkUserId = $userprojectview_system_api->generateLinkUserId( $userId );
   $isAssignedToProject = $userprojectview_system_api->checkUserAssignedToProject( $userId, $bugAssignedProjectId );
   $unreachableIssueFlag = $userprojectview_system_api->setUnreachableIssueFlag( $isAssignedToProject );
   $pProject = $userprojectview_system_api->prepareParentProject( $t_project_id, $bugAssignedProjectId, $mainProjectId );
   $noUserFlag = $userprojectview_system_api->setUserflag( $amountStatColumns, $statCols, $userId );

   $userprojectview_print_api->printTDRow( $userId, 2, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
   echo '<td/>';
   if ( !$print_flag )
   {
      build_chackbox_column( $userId, $pProject );
      build_avatar_column( $linkUserId, $userId );
   }
   build_user_column( $linkUserId, $userName, $print_flag, $detailed_flag );
   build_real_name_column( $linkUserId, $userRealname, $print_flag, $detailed_flag );
   build_main_project_column( $mainProjectId, $linkUserId, $mainProjectName, $print_flag );
   build_assigned_project_column( $bugAssignedProjectId, $linkUserId, $bugAssignedProjectName, $print_flag );
   target_version_column( $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $bugTargetVersionDate, $bugTargetVersionPreparedString, $print_flag );
   $specColumnIssueAmount = build_amount_of_issues_column( $amountStatColumns, $issueThresholds, $statCols, $issueCounter, $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $specColumnIssueAmount, $print_flag );
   build_remark_column( $amountStatColumns, $issueAgeThresholds, $bugAssignedProjectId, $mainProjectId, $statCols, $userId, $bugTargetVersion, $linkUserId, $unreachableIssueFlag, $inactiveUserFlag, $zeroIssuesFlag, $noUserFlag, $print_flag );
   echo '</tr>';

   return $specColumnIssueAmount;
}

/**
 * Assigns each data row (from $tableRow) to one of the four specific groups
 *
 * @param $groups
 * @param $tableRow
 * @param $amountStatColumns
 * @return mixed
 */
function assign_groups( $groups, $tableRow, $amountStatColumns )
{
   $group_user_with_issue = $groups[0];
   $group_user_without_issue = $groups[1];
   $group_inactive_deleted_user = $groups[2];
   $group_issues_without_user = $groups[3];
   for ( $table_row_index = 0; $table_row_index < count( $tableRow ); $table_row_index++ )
   {
      if ( $tableRow[$table_row_index]['userId'] > 0 )
      {
         /** user existiert */
         if ( user_exists( $tableRow[$table_row_index]['userId'] ) )
         {
            /** user ist aktiv */
            if ( user_is_enabled( $tableRow[$table_row_index]['userId'] ) )
            {
               $amount_all_issues = 0;
               for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
               {
                  $amount_all_issues += $tableRow[$table_row_index]['specColumn' . $statColIndex];
               }

               /** user hat issues */
               if ( $amount_all_issues > 0 )
               {
                  array_push( $group_user_with_issue, $table_row_index );
               }
               /** user hat keine issues */
               else
               {
                  array_push( $group_user_without_issue, $table_row_index );
               }
            }
            /** user ist inaktiv */
            else
            {
               array_push( $group_inactive_deleted_user, $table_row_index );
            }
         }
         /** user existiert nicht */
         else
         {
            array_push( $group_inactive_deleted_user, $table_row_index );
         }
      }
      /** wenn user_id = 0, gibt es keinen Nutzer */
      else
      {
         array_push( $group_issues_without_user, $table_row_index );
      }
   }

   $groups[0] = $group_user_with_issue;
   $groups[1] = $group_user_without_issue;
   $groups[2] = $group_inactive_deleted_user;
   $groups[3] = $group_issues_without_user;
   return $groups;
}

function get_sort_col( $sortVal, $tableRow )
{
   $sortCol = null;
   $sortUserName = array();
   $sortUserRealname = array();
   $sortMainProject = array();
   $sortAssignedProject = array();
   $sortTargetVersion = array();
   foreach ( $tableRow as $key => $row )
   {
      $sortUserName[$key] = $row['userName'];
      $sortUserRealname[$key] = $row['userRealname'];
      $sortMainProject[$key] = $row['mainProjectName'];
      $sortAssignedProject[$key] = $row['bugAssignedProjectName'];
      $sortTargetVersion[$key] = $row['bugTargetVersion'];
   }

   switch ( $sortVal )
   {
      case 'userName':
         $sortCol = $sortUserName;
         break;
      case 'realName':
         $sortCol = $sortUserRealname;
         break;
      case 'mainProject':
         $sortCol = $sortMainProject;
         break;
      case 'assignedProject':
         $sortCol = $sortAssignedProject;
         break;
      case 'targetVersion':
         $sortCol = $sortTargetVersion;
         break;
   }
   return $sortCol;
}

function get_sort_order( $sortOrder )
{
   $sortOrd = null;
   switch ( $sortOrder )
   {
      case 'ASC':
         $sortOrd = SORT_ASC;
         break;
      case 'DESC':
         $sortOrd = SORT_DESC;
         break;
   }
   return $sortOrd;
}

/**
 * compares two dates and returns true if they are not equal (else false).
 *
 * @param $sortVal
 * @param $tableRow
 * @param $tableRowIndex
 * @return bool
 */
function checkout_change_row( $sortVal, $tableRow, $tableRowIndex )
{
   $change_row_bg = false;
   switch ( $sortVal )
   {
      case 'realName':
      case 'userName':
         $userName = $tableRow[$tableRowIndex]['userName'];
         $userNameOld = $tableRow[$tableRowIndex - 1]['userName'];
         $change_row_bg = ( $userName !== $userNameOld );
         break;

      case 'mainProject':
         $mainProjectName = $tableRow[$tableRowIndex]['mainProjectName'];
         $mainProjectNameOld = $tableRow[$tableRowIndex - 1]['mainProjectName'];
         $change_row_bg = ( $mainProjectName !== $mainProjectNameOld );
         break;

      case 'assignedProject':
         $bugAssignedProjectName = $tableRow[$tableRowIndex]['bugAssignedProjectName'];
         $bugAssignedProjectNameOld = $tableRow[$tableRowIndex - 1]['bugAssignedProjectName'];
         $change_row_bg = ( $bugAssignedProjectName !== $bugAssignedProjectNameOld );
         break;

      case 'targetVersion':
         $bugTargetVersion = $tableRow[$tableRowIndex]['bugTargetVersion'];
         $bugTargetVersionOld = $tableRow[$tableRowIndex - 1]['bugTargetVersion'];
         $change_row_bg = ( $bugTargetVersion !== $bugTargetVersionOld );
         break;
   }
   return $change_row_bg;
}

function build_chackbox_column( $userId, $pProject )
{
   echo '<td>';
   echo '<form action="' . plugin_page( 'UserProject_Option' ) . '" method="post">';
   echo '<input type="checkbox" name="dataRow[]" value="' . $userId . '__' . $pProject . '" />';
   echo '</td>';
}

function build_avatar_column( $linkUserId, $userId )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   if ( plugin_config_get( 'ShowAvatar' ) )
   {
      echo '<td align="center" width="25px">';
      if ( user_exists( $userId ) )
      {
         if ( access_has_global_level( $userAccessLevel ) )
         {
            $filterString = '<a href="search.php?&handler_id=' . $linkUserId . '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
            echo $filterString;
         }

         if ( config_get( 'show_avatar' ) && $userAccessLevel >= config_get( 'show_avatar_threshold' ) )
         {
            if ( $userId > 0 )
            {
               $assocArray = user_get_avatar( $userId );
               echo '<img class="avatar" src="' . $assocArray [0] . '" />';
            }
         }

         if ( access_has_global_level( $userAccessLevel ) )
         {
            echo '</a>';
         }
      }
      echo '</td>';
   }
}

function build_user_column( $linkUserId, $userName, $print_flag, $detailed_flag )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   echo '<td>';
   if ( $detailed_flag )
   {
      if ( access_has_global_level( $userAccessLevel ) && !$print_flag )
      {
         $filterString = '<a href="search.php?&handler_id=' . $linkUserId . '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
         echo $filterString;
         if ( user_exists( $linkUserId ) )
         {
            echo $userName;
         }
         else
         {
            echo '<s>' . $userName . '</s>';
         }
         echo '</a>';
      }
      else
      {
         if ( user_exists( $linkUserId ) )
         {
            echo $userName;
         }
         else
         {
            echo '<s>' . $userName . '</s>';
         }
      }
   }
   echo '</td>';
}

function build_real_name_column( $linkUserId, $userRealname, $print_flag, $detailed_flag )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   echo '<td>';
   if ( $detailed_flag )
   {
      if ( access_has_global_level( $userAccessLevel ) && !$print_flag )
      {
         $filterString = '<a href="search.php?&handler_id=' . $linkUserId . '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
         echo $filterString;
         echo $userRealname;
         echo '</a>';
      }
      else
      {
         echo $userRealname;
      }
   }
   echo '</td>';
}

function build_main_project_column( $mainProjectId, $linkUserId, $mainProjectName, $print_flag )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   echo '<td>';
   if ( access_has_global_level( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?project_id=' . $mainProjectId . '&handler_id=' . $linkUserId .
         '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
      echo $filterString;
      echo $mainProjectName;
      echo '</a>';
   }
   else
   {
      echo $mainProjectName;
   }
   echo '</td>';
}

function build_assigned_project_column( $bugAssignedProjectId, $linkUserId, $bugAssignedProjectName, $print_flag )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   echo '<td>';
   if ( access_has_global_level( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&handler_id=' . $linkUserId .
         '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
      echo $filterString;
      echo $bugAssignedProjectName;
      echo '</a>';
   }
   else
   {
      echo $bugAssignedProjectName;
   }
   echo '</td>';
}

function target_version_column( $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $bugTargetVersionDate, $bugTargetVersionPreparedString, $print_flag )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   echo '<td>';
   echo $bugTargetVersionDate . ' ';
   if ( access_has_global_level( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&handler_id=' . $linkUserId .
         '&sticky_issues=on&target_version=' . $bugTargetVersion . '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
      echo $filterString;
      echo $bugTargetVersionPreparedString;
      echo '</a>';
   }
   else
   {
      echo $bugTargetVersionPreparedString;
   }
   echo '</td>';
}

function build_amount_of_issues_column( $amountStatColumns, $issueThresholds, $statCols, $issueCounter, $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $specColumnIssueAmount, $print_flag )
{
   for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
   {
      $issueThreshold = $issueThresholds[$statColIndex];
      $specStatus = $statCols[$statColIndex];
      $issueAmount = $issueCounter[$statColIndex];
      $specColumnIssueAmount[$statColIndex] += $issueAmount;
      if ( $issueThreshold < $issueAmount && $issueThreshold > 0 )
      {
         echo '<td style="background-color:' . plugin_config_get( 'TAMHBGColor' ) . '">';
      }
      else
      {
         echo '<td bgcolor="' . get_status_color( $statCols[$statColIndex], null, null ) . '">';
      }

      if ( !$print_flag )
      {
         $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&status_id=' . $specStatus .
            '&handler_id=' . $linkUserId . '&sticky_issues=on&target_version=' . $bugTargetVersion .
            '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
         echo $filterString;
         echo $issueAmount;
         echo '</a>';
      }
      else
      {
         echo $issueAmount;
      }
      echo '</td>';

   }
   return $specColumnIssueAmount;
}

function build_remark_column( $amountStatColumns, $issueAgeThresholds, $bugAssignedProjectId, $mainProjectId, $statCols, $userId, $bugTargetVersion, $linkUserId, $unreachableIssueFlag, $inactiveUserFlag, $zeroIssuesFlag, $noUserFlag, $print_flag )
{
   $userprojectview_database_api = new userprojectview_database_api();
   $userprojectview_system_api = new userprojectview_system_api();

   echo '<td style="white-space:nowrap">';
   for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
   {
      $issueAgeThreshold = $issueAgeThresholds[$statColIndex];
      if ( $bugAssignedProjectId == null && $mainProjectId == null )
      {
         continue;
      }

      $specStatus = $statCols[$statColIndex];
      if ( $specStatus == USERPROJECTVIEW_ASSIGNED_STATUS && $issueAgeThreshold > 0
         || $specStatus == USERPROJECTVIEW_FEEDBACK_STATUS && $issueAgeThreshold > 0
         || $specStatus == 40 && $issueAgeThreshold > 0
      )
      {
         $specIssueResult = $userprojectview_database_api->getIssuesUPTS( $userId, $bugAssignedProjectId, $bugTargetVersion, $specStatus );
         $assocArray = mysqli_fetch_row( $specIssueResult );
         $specIssues = array();
         while ( $specIssue = $assocArray [0] )
         {
            $specIssues[] = $specIssue;
            $assocArray = mysqli_fetch_row( $specIssueResult );
         }

         if ( $specIssues != null )
         {
            $specTimeDifference = $userprojectview_system_api->calculateTimeDifference( $specIssues )[0];
            $oldestSpecIssue = $userprojectview_system_api->calculateTimeDifference( $specIssues )[1];

            if ( $specTimeDifference > $issueAgeThreshold && !$print_flag )
            {
               $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&search=' . $oldestSpecIssue .
                  '&status_id=' . $specStatus . '&handler_id=' . $linkUserId . '&sticky_issues=on&target_version=' . $bugTargetVersion .
                  '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
               echo $filterString;
               $assocArray = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
               echo $assocArray [$specStatus] .
                  ' ' . plugin_lang_get( 'remark_since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get( 'remark_day' ) . '<br/>';
               echo '</a>';
            }
            else
            {
               $assocArray = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
               echo $assocArray [$specStatus] .
                  ' ' . plugin_lang_get( 'remark_since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get( 'remark_day' ) . '<br/>';
            }
         }
      }
   }

   if ( $unreachableIssueFlag )
   {
      $unreachIssueStatusValue = plugin_config_get( 'URIThreshold' );
      $unreachIssueStatusCount = count( $unreachIssueStatusValue );
      $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId;
      $filterString = $userprojectview_system_api->prepareFilterString( $unreachIssueStatusCount, $unreachIssueStatusValue, $filterString );
      $filterString .= '&handler_id=' . $linkUserId .
         '&sticky_issues=on&target_version=' . $bugTargetVersion .
         '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
      echo plugin_lang_get( 'remark_noProject' ) . ' [';
      echo $filterString;
      echo plugin_lang_get( 'remark_showURIssues' );
      echo '</a>]<br/>';
   }
   if ( !$inactiveUserFlag )
   {
      echo plugin_lang_get( 'remark_IAUser' ) . '<br/>';
   }
   if ( $zeroIssuesFlag )
   {
      echo plugin_lang_get( 'remark_ZIssues' ) . '<br/>';
   }
   if ( $noUserFlag )
   {
      echo plugin_lang_get( 'remark_noUser' ) . '<br/>';
   }
   echo '</td>';
}

function build_option_panel( $amountStatColumns, $specColumnIssueAmount, $print_flag )
{
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
   $fixColspan = 8;
   if ( plugin_config_get( 'ShowAvatar' ) )
   {
      $fixColspan = 9;
   }

   echo '<tr>';
   $footerColspan = $fixColspan - 1;
   if ( $print_flag )
   {
      $footerColspan = $fixColspan - 3;
   }
   echo '<td colspan="' . $footerColspan . '">';
   if ( !$print_flag )
   {
      if ( access_has_global_level( $userAccessLevel ) )
      {
         ?>
         <form name="options" action="" method="get">
            <label for="option"></label>
            <select id="option" name="option">
               <option value="removeSingle"><?php echo plugin_lang_get( 'remove_selectSingle' ) ?></option>
               <option value="removeAll"><?php echo plugin_lang_get( 'remove_selectAll' ) ?></option>
            </select>
            <input type="submit" name="formSubmit" class="button" value="<?php echo lang_get( 'ok' ); ?>"/>
         </form>
         <?php
      }
   }
   echo '</td>';
   for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
   {
      echo '<td>' . $specColumnIssueAmount[$statColIndex] . '</td>';
   }
   echo '<td />';
   echo '</tr>';
}
