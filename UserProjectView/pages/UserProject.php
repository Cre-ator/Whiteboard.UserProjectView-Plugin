<?php
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_system_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_database_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_print_api.php';

$userprojectview_database_api = new userprojectview_database_api();
$userprojectview_system_api = new userprojectview_system_api();
$userprojectview_print_api = new userprojectview_print_api();

$print_flag = false;
if ( isset( $_POST['print_flag'] ) )
{
   $print_flag = true;
}

$unreachIssueStatusValue = plugin_config_get( 'URIThreshold' );
$unreachIssueStatusCount = count( $unreachIssueStatusValue );

$amountStatColumns = plugin_config_get( 'CAmount' );
if ( $amountStatColumns > PLUGINS_USERPROJECTVIEW_MAX_COLUMNS )
{
   $amountStatColumns = PLUGINS_USERPROJECTVIEW_MAX_COLUMNS;
}

$statCols = array();
$issueThresholds = array();
$issueAgeThresholds = array();

for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
   $statCols[$statColIndex] = plugin_config_get( 'CStatSelect' . $statColIndex );
   $issueThresholds[$statColIndex] = plugin_config_get( 'IAMThreshold' . $statColIndex );
   $issueAgeThresholds[$statColIndex] = plugin_config_get( 'IAGThreshold' . $statColIndex );
}

$t_project_id = gpc_get_int( 'project_id', helper_get_current_project() );
if ( ( ALL_PROJECTS == $t_project_id || project_exists( $t_project_id ) )
   && $t_project_id != helper_get_current_project()
)
{
   helper_set_current_project( $t_project_id );
   print_header_redirect( $_SERVER['REQUEST_URI'], true, false, true );
}

$f_page_number = gpc_get_int( 'page_number', 1 );
$t_per_page = 10000;
$t_bug_count = null;
$t_page_count = null;

$rows = filter_get_bug_rows( $f_page_number, $t_per_page, $t_page_count, $t_bug_count, unserialize( '' ), null, null, true );

$t_bugslist = array();

$t_row_count = count( $rows );

for ( $i = 0; $i < $t_row_count; $i++ )
{
   array_push( $t_bugslist, $rows[$i]->id );
}

$matchcode = array();
$issueCounter = 0;
$checkEquivalentBugs = false;

// calculate page content
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

   // initial unreachable issue information
   $uRIssueFlag = false;

   // prepare main project
   $actBugMainProjectId = '';

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

$dataRows = array_count_values( $matchcode );
$rowCount = count( $dataRows );
$amountOfShownIssues = 0;
$tableRow = array();

// process page content
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

if ( plugin_config_get( 'ShowZIU' ) )
{
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
         $tSubProjects = array();
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
}

$fixColspan = 7;
if ( plugin_config_get( 'ShowAvatar' ) )
{
   $fixColspan = 8;
}

$dynamicColspan = $amountStatColumns + $fixColspan;
$headerColspan = $fixColspan - 6;

html_page_top1( plugin_lang_get( 'menu_userprojecttitle' ) );
echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';
if ( !$print_flag )
{
   html_page_top2();
   if ( plugin_is_installed( 'WhiteboardMenu' ) )
   {
      require_once WHITEBOARDMENU_CORE_URI . 'whiteboard_print_api.php';
      $whiteboard_print_api = new whiteboard_print_api();
      $whiteboard_print_api->printWhiteboardMenu();
   }
}

echo '<div id="manage-user-div" class="form-container">';
$userprojectview_print_api->print_table_head();
print_thead( $dynamicColspan, $headerColspan, $amountStatColumns, $statCols, $print_flag );
print_tbody( $tableRow, $amountStatColumns, $t_project_id, $statCols, $issueThresholds, $issueAgeThresholds, $fixColspan, $unreachIssueStatusCount, $unreachIssueStatusValue, $print_flag );
echo '</table>';
echo '</div>';
if ( !$print_flag )
{
   html_page_bottom1();
}

function print_thead( $dynamicColspan, $headerColspan, $amountStatColumns, $statCols, $print_flag )
{
   $userprojectview_print_api = new userprojectview_print_api();

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
      echo '<th bgcolor="' . get_status_color( $statCols[$headIndex], null, null ) . '">';
      $assocArray = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
      echo $assocArray [$statCols[$headIndex]];
      echo '</th>';
   }
   echo '<th>' . plugin_lang_get( 'thead_remark' ) . '</th>';
   echo '</tr>';
   echo '</thead>';
}

function print_tbody( $tableRow, $amountStatColumns, $t_project_id, $statCols, $issueThresholds, $issueAgeThresholds, $fixColspan, $unreachIssueStatusCount, $unreachIssueStatusValue, $print_flag )
{
   $userprojectview_system_api = new userprojectview_system_api();
   $userprojectview_print_api = new userprojectview_print_api();
   $userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
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

      $rowVal = false;
      if ( $tableRowIndex > 0 )
      {
         $rowVal = get_row_val( $sortVal, $tableRow, $tableRowIndex, $userName, $mainProjectName, $bugAssignedProjectName, $bugTargetVersion );
      }

      $userprojectview_print_api->printTDRow( $userId, $rowVal, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
      if ( !$print_flag )
      {
         build_chackbox_column( $userId, $pProject );
         build_avatar_column( $userAccessLevel, $linkUserId, $userId );
      }
      build_user_column( $userAccessLevel, $linkUserId, $userName, $print_flag );
      build_real_name_column( $userAccessLevel, $linkUserId, $userRealname, $print_flag );
      build_main_project_column( $userAccessLevel, $mainProjectId, $linkUserId, $mainProjectName, $print_flag );
      build_assigned_project_column( $userAccessLevel, $bugAssignedProjectId, $linkUserId, $bugAssignedProjectName, $print_flag );
      target_version_column( $userAccessLevel, $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $bugTargetVersionDate, $bugTargetVersionPreparedString, $print_flag );
      $specColumnIssueAmount = build_amount_of_issues_column( $amountStatColumns, $issueThresholds, $statCols, $issueCounter, $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $specColumnIssueAmount, $print_flag );
      build_remark_column( $amountStatColumns, $issueAgeThresholds, $bugAssignedProjectId, $mainProjectId, $statCols, $userId, $bugTargetVersion, $linkUserId, $unreachableIssueFlag, $unreachIssueStatusCount, $unreachIssueStatusValue, $inactiveUserFlag, $zeroIssuesFlag, $noUserFlag, $print_flag );
      echo '</tr>';
   }
   build_option_panel( $userAccessLevel, $fixColspan, $amountStatColumns, $specColumnIssueAmount, $print_flag );
   echo '</tbody>';
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

function get_row_val( $sortVal, $tableRow, $tableRowIndex, $userName, $mainProjectName, $bugAssignedProjectName, $bugTargetVersion )
{
   $userprojectview_system_api = new userprojectview_system_api();
   $rowVal = false;
   switch ( $sortVal )
   {
      case 'realName':
      case 'userName':
         $userNameOld = $tableRow[$tableRowIndex - 1]['userName'];
         $rowVal = $userprojectview_system_api->compareValues( $userName, $userNameOld, $rowVal );
         break;

      case 'mainProject':
         $mainProjectNameOld = $tableRow[$tableRowIndex - 1]['mainProjectName'];
         $rowVal = $userprojectview_system_api->compareValues( $mainProjectName, $mainProjectNameOld, $rowVal );
         break;

      case 'assignedProject':
         $bugAssignedProjectNameOld = $tableRow[$tableRowIndex - 1]['bugAssignedProjectName'];
         $rowVal = $userprojectview_system_api->compareValues( $bugAssignedProjectName, $bugAssignedProjectNameOld, $rowVal );
         break;

      case 'targetVersion':
         $bugTargetVersionOld = $tableRow[$tableRowIndex - 1]['bugTargetVersion'];
         $rowVal = $userprojectview_system_api->compareValues( $bugTargetVersion, $bugTargetVersionOld, $rowVal );
         break;
   }
   return $rowVal;
}

function build_chackbox_column( $userId, $pProject )
{
   echo '<td>';
   echo '<form action="' . plugin_page( 'UserProject_Option' ) . '" method="post">';
   echo '<input type="checkbox" name="dataRow[]" value="' . $userId . '__' . $pProject . '" />';
   echo '</td>';
}

function build_avatar_column( $userAccessLevel, $linkUserId, $userId )
{
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

function build_user_column( $userAccessLevel, $linkUserId, $userName, $print_flag )
{
   echo '<td>';
   if ( access_has_global_level( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?&handler_id=' . $linkUserId . '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
      echo $filterString;
      echo $userName;
      echo '</a>';
   }
   else
   {
      echo $userName;
   }
   echo '</td>';
}

function build_real_name_column( $userAccessLevel, $linkUserId, $userRealname, $print_flag )
{
   echo '<td>';
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
   echo '</td>';
}

function build_main_project_column( $userAccessLevel, $mainProjectId, $linkUserId, $mainProjectName, $print_flag )
{
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

function build_assigned_project_column( $userAccessLevel, $bugAssignedProjectId, $linkUserId, $bugAssignedProjectName, $print_flag )
{
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

function target_version_column( $userAccessLevel, $bugAssignedProjectId, $linkUserId, $bugTargetVersion, $bugTargetVersionDate, $bugTargetVersionPreparedString, $print_flag )
{
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
      $issueAgeThresholdColor = plugin_config_get( 'TAMHBGColor' );
      if ( $issueThreshold < $issueAmount && $issueThreshold > 0 )
      {
         echo '<td style="background-color:' . $issueAgeThresholdColor . '">';
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

function build_remark_column( $amountStatColumns, $issueAgeThresholds, $bugAssignedProjectId, $mainProjectId, $statCols, $userId, $bugTargetVersion, $linkUserId, $unreachableIssueFlag, $unreachIssueStatusCount, $unreachIssueStatusValue, $inactiveUserFlag, $zeroIssuesFlag, $noUserFlag, $print_flag )
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

function build_option_panel( $userAccessLevel, $fixColspan, $amountStatColumns, $specColumnIssueAmount, $print_flag )
{
   echo '<tr class="spacer">';
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
