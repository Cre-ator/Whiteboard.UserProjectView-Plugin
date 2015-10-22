<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'UserProjectView_api.php';

// UserProjectView_api object
$upv_api = new UserProjectView_api();

$userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );

$unreachIssueStatusValue = plugin_config_get( 'URIThreshold' );
$unreachIssueStatusCount = count( $unreachIssueStatusValue );

$amountStatColumns = plugin_config_get( 'CAmount' );
if ( $amountStatColumns > PLUGINS_USERPROJECTVIEW_MAX_COLUMNS )
{
	$amountStatColumns = PLUGINS_USERPROJECTVIEW_MAX_COLUMNS;
}

$statCols = array();

for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	$statCols[$statColIndex] = '';
}

$issueThresholds = array();
$issueAgeThresholds = array();

for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	$statCols[$statColIndex]                = plugin_config_get( 'CStatSelect' . $statColIndex );
	$issueThresholds[$statColIndex]         = plugin_config_get( 'IAMThreshold' . $statColIndex );
   $issueAgeThresholds[$statColIndex]      = plugin_config_get( 'IAGThreshold' . $statColIndex );
}

# Get Project Id and set it as current
$t_project_id = gpc_get_int( 'project_id', helper_get_current_project() );
if ( ( ALL_PROJECTS == $t_project_id || project_exists( $t_project_id ) )
	&& $t_project_id != helper_get_current_project()
	)
{
	helper_set_current_project( $t_project_id );
	# Reloading the page is required so that the project browser
	# reflects the new current project
	print_header_redirect( $_SERVER['REQUEST_URI'], true, false, true );
}

$f_page_number	= gpc_get_int( 'page_number', 1 );
$t_per_page    = 10000;
$t_bug_count   = null;
$t_page_count  = null;

$rows = filter_get_bug_rows( $f_page_number, $t_per_page, $t_page_count, $t_bug_count, unserialize( '' ), null, null, true );

$t_bugslist = array();

$t_row_count = count( $rows );

for( $i = 0; $i < $t_row_count; $i++ )
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
	$actBugId                = $t_bugslist[$bugIndex];
	$actBugTargetVersion     = bug_get_field( $actBugId, 'target_version' );
	$actBugStatus            = bug_get_field( $actBugId, 'status' );
	$actBugAssignedProjectId = bug_get_field( $actBugId, 'project_id' );
	$actBugAssignedUserId    = bug_get_field( $actBugId, 'handler_id' );
	
	// user information
	$aBAUIUsername  = '';
	$aBAUIRealname  = '';
	$aBAUIActivFlag = true;
	
	// filter config specific bug status
   $irrelevantFlag = $upv_api->setIrrelevantFlag( $amountStatColumns, $actBugStatus, $statCols );
	if ( !in_array( false, $irrelevantFlag ) )
	{
		continue;
	}
	
	// bug is assigned, etc... but not ASSIGNED TO, etc ...
	if ( $actBugAssignedUserId != 0 )
	{
		$aBAUIUsername  = user_get_name( $actBugAssignedUserId );
		$aBAUIRealname  = user_get_realname( $actBugAssignedUserId );
		$aBAUIActivFlag = user_is_enabled( $actBugAssignedUserId );
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
      $actBugMainProjectId = $upv_api->getMainProjectByHierarchy( $actBugAssignedProjectId );
	}
	else
	{
		// identify main project by target version of selected issue
		$actBugMainProjectId = $upv_api->getMainProjectByVersion( $actBugTargetVersion );
	}
	
	$actBugMainProjectName = project_get_name( $actBugMainProjectId );
	
	// prepare target version string
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
				$tableRow[$rowIndex][$specColumnValue] = $upv_api->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[$statColIndex] );
			}
			else
			{
				$tableRow[$rowIndex][$specColumnValue] = $upv_api->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[$statColIndex] );
			}
		}
	}
	array_shift( $dataRows );
}

if ( plugin_config_get( 'ShowZIU' ) )
{
	$allUsers = $upv_api->getAllUsers();
	
	$userRows = array();
	while ( $userRow = mysqli_fetch_row( $allUsers ) )
	{
		$userRows[] = $userRow;
	}
	
	$rowCount = count( $userRows );
	for ( $userRowIndex = 0; $userRowIndex < $rowCount; $userRowIndex++ )
	{
		$userId       = $userRows[$userRowIndex][0];
		$userName     = user_get_name( $userId );
		$userRealname = user_get_realname( $userId );
		$userIsActive = user_is_enabled( $userId );
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
				$amountOfIssues .= $upv_api->getAmountOfIssuesByIndividualWOTV( $userId, $t_project_id, $statCols[$statColIndex] );
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
				$userIsAssignedToProject = mysqli_fetch_row( $upv_api->checkUserIsAssignedToProject( $userId, $subProject ) );
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
					$amountOfIssues .= $upv_api->getAmountOfIssuesByIndividualWOTV( $userId, $subProject, $statCols[$statColIndex] );
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

$sortUserName = array();
$sortUserRealname = array();
$sortMainProject = array();
$sortAssignedProject = array();
$sortTargetVersion = array();

foreach ( $tableRow as $key => $row )
{
	$sortUserName[$key]        = $row['userName'];
	$sortUserRealname[$key]    = $row['userRealname'];
	$sortMainProject[$key]     = $row['mainProjectName'];
	$sortAssignedProject[$key] = $row['bugAssignedProjectName'];
	$sortTargetVersion[$key]   = $row['bugTargetVersion'];
}

html_page_top1( plugin_lang_get( 'menu_userprojecttitle' ) );

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

// user configuration area ++++++++++++++++++++++++++++++++++++++++++++++++++++
if ( $upv_api->getUserHasLevel() )
{
	$upv_api->printPluginMenu();
	$upv_api->printUserProjectMenu();
}
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

$fixColspan = 7;

if ( plugin_config_get( 'ShowAvatar' ) )
{
   $fixColspan = 8;
}

$dynamicColspan = $amountStatColumns + $fixColspan;
$headerColspan = $fixColspan - 6;

$sortVal = $sortUserName;
$sortOrder = 'ASC';

echo '<div id="manage-user-div" class="form-container">';

if ( $upv_api->getActMantisVersion() == '1.2.' )
{
	echo '<table class="width100" cellspacing="1">';
}
else
{
	echo '<table>';
}
echo '<thead>';
echo '<tr>';
echo '<td class="form-title" colspan="' . $dynamicColspan . '">' . 
	plugin_lang_get( 'thead_accounts_title' ) .
	plugin_lang_get( 'thead_projects_title' ) .
	project_get_name( helper_get_current_project() );
echo '</td>';
echo '</tr>';
echo '<tr class="row-category">';
	echo '<th />';
	echo '<th colspan="' . $headerColspan . '">';
	echo plugin_lang_get( 'thead_username' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=userName&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=userName&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';		
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'thead_realname' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=realName&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=realName&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'thead_project' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=mainProject&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=mainProject&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'thead_subproject' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=assignedProject&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=assignedProject&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'thead_targetversion' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=targetVersion&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=targetVersion&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';

for ( $headIndex = 1; $headIndex <= $amountStatColumns; $headIndex++ )
{
	echo '<th bgcolor="' . get_status_color( $statCols[$headIndex], null, null ) .'">';
   $assocArray = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
   echo $assocArray [$statCols[$headIndex]];
	echo '</th>';
}
echo '<th>' . plugin_lang_get( 'thead_remark' ) . '</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$sortVal = $_GET['sortVal'];
$sortOrder = $_GET['sort'];

switch ( $sortVal )
{
	case 'userName':
		$sortVal = $sortUserName;
		break;
	case 'realName':
		$sortVal = $sortUserRealname;
		break;
	case 'mainProject':
		$sortVal = $sortMainProject;
		break;
	case 'assignedProject':
		$sortVal = $sortAssignedProject;
		break;
	case 'targetVersion':
		$sortVal = $sortTargetVersion;
		break;
}

switch ( $sortOrder )
{
	case 'ASC':
		$sortOrder = SORT_ASC;
		break;
	case 'DESC':
		$sortOrder = SORT_DESC;
		break;
}

if ( $tableRow != null )
{
	array_multisort( $sortVal, $sortOrder, SORT_NATURAL|SORT_FLAG_CASE, $tableRow );
}
$rowVal = false;
$tableRowCount = count( $tableRow );
$specColumnIssueAmount = array();
for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	$specColumnIssueAmount[$statColIndex] = '';
}

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

	$bugAssignedProjectId = $upv_api->getBugAssignedProjectId( $bugAssignedProjectId, $mainProjectId );
	$linkUserId           = $upv_api->generateLinkUserId( $userId );
   $isAssignedToProject  = $upv_api->checkUserAssignedToProject( $userId, $bugAssignedProjectId );
	$unreachableIssueFlag = $upv_api->setUnreachableIssueFlag( $isAssignedToProject );
   $pProject             = $upv_api->prepareParentProject( $t_project_id, $bugAssignedProjectId, $mainProjectId );
   $noUserFlag           = $upv_api->setUserflag( $amountStatColumns, $statCols, $userId );

	$sortVal = $_GET['sortVal'];
	if ( $tableRowIndex > 0 )
	{
      switch ( $sortVal )
      {
         case 'realName':
         case 'userName':
            $userNameOld = $tableRow[$tableRowIndex-1]['userName'];
            $rowVal = $upv_api->compareValues( $userName, $userNameOld, $rowVal );
            break;

         case 'mainProject':
            $mainProjectNameOld = $tableRow[$tableRowIndex-1]['mainProjectName'];
            $rowVal = $upv_api->compareValues( $mainProjectName, $mainProjectNameOld, $rowVal );
            break;

         case 'assignedProject':
            $bugAssignedProjectNameOld = $tableRow[$tableRowIndex-1]['bugAssignedProjectName'];
            $rowVal = $upv_api->compareValues( $bugAssignedProjectName, $bugAssignedProjectNameOld, $rowVal );
            break;

         case 'targetVersion':
            $bugTargetVersionOld = $tableRow[$tableRowIndex-1]['bugTargetVersion'];
            $rowVal = $upv_api->compareValues( $bugTargetVersion, $bugTargetVersionOld, $rowVal );
            break;
      }
	}

	// build row
   $upv_api->buildSpecificRow( $userId, $rowVal, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );

	// column checkbox
	echo '<td>';
	echo '<form action="' . plugin_page( 'UserProject_Option' ) . '" method="post">';
	echo '<input type="checkbox" name="dataRow[]" value="' . $userId . '__' . $pProject . '" />';
	echo '</td>';


   // column avatar
   if ( plugin_config_get( 'ShowAvatar' ) )
   {
      echo '<td align="center" width="25px">';
      if ( access_has_global_level( $userAccessLevel ) )
      {
         echo '<a href="search.php?&handler_id=' . $linkUserId .
            '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
      }

      if ( config_get( 'show_avatar' ) && $userAccessLevel >= config_get( 'show_avatar_threshold' ) )
      {
         if ($userId > 0)
         {
            $assocArray = user_get_avatar( $userId );
            echo '<img class="avatar" src="' . $assocArray [0] . '" />';
         }
      }

      if ( access_has_global_level( $userAccessLevel ) )
      {
         echo '</a>';
      }
      echo '</td>';
   }

	// column user
	echo '<td>';
	if ( access_has_global_level( $userAccessLevel ) )
   {
		echo '<a href="search.php?&handler_id=' . $linkUserId .
		   '&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		echo $userName;
		echo '</a>';
	}
	else
	{
		echo $userName;
	}
	echo '</td>';
	
	
	// column real name
	echo '<td>';
	if ( access_has_global_level( $userAccessLevel ) )
	{
		echo '<a href="search.php?&handler_id=' . $linkUserId .
		'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		echo $userRealname;
		echo '</a>';
	}
	else
	{
		echo $userRealname;
	}
	echo '</td>';
	
	
	// column main project
	echo '<td>';
	if ( access_has_global_level( $userAccessLevel ) )
	{
		echo '<a href="search.php?project_id=' . $mainProjectId .
			'&handler_id=' . $linkUserId .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		echo $mainProjectName;
		echo '</a>';
	}
	else
	{
		echo $mainProjectName;
	}
	echo '</td>';
	
	
	// column assigned project
	echo '<td>';
	if ( access_has_global_level( $userAccessLevel ) )
	{
		echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
			'&handler_id=' . $linkUserId .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		echo $bugAssignedProjectName;
		echo '</a>';
	}
	else
	{
		echo $bugAssignedProjectName;
	}
	echo '</td>';
	
	
	// column target version
	echo '<td>';
	echo $bugTargetVersionDate . ' ';
	if ( access_has_global_level( $userAccessLevel ) )
	{
		echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
			'&handler_id=' . $linkUserId .
			'&sticky_issues=on&target_version=' . $bugTargetVersion .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		echo $bugTargetVersionPreparedString;
		echo '</a>';
	}
	else
	{
		echo $bugTargetVersionPreparedString;
	}
	echo '</td>';
	
	
	// Column(s) amount of issues
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
		$issueThreshold         = $issueThresholds[$statColIndex];
		$specStatus             = $statCols[$statColIndex];
		$issueAmount            = $issueCounter[$statColIndex];
      $issueAgeThresholdColor = plugin_config_get( 'TAMHBGColor' );
		
		if ( $issueThreshold < $issueAmount && $issueThreshold > 0 )
		{
			echo '<td style="background-color:' . $issueAgeThresholdColor . '">';
		}
		else
		{
			echo '<td bgcolor="' . get_status_color( $statCols[$statColIndex], null, null ) . '">';
		}
		echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
			'&status_id='. $specStatus .
			'&handler_id=' . $linkUserId .
			'&sticky_issues=on&target_version=' . $bugTargetVersion .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		$specColumnIssueAmount[$statColIndex] += $issueAmount;
		echo $issueAmount;
		echo '</a>';
		echo '</td>';
	}

	
	// column remark
	echo '<td style="white-space:nowrap">';
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
      $issueAgeThreshold = $issueAgeThresholds[$statColIndex];

		if ( $bugAssignedProjectId == null && $mainProjectId == null )
		{
			continue;
		}

		$specStatus = $statCols[$statColIndex];
		if ( $specStatus == config_get( 'bug_assigned_status') && $issueAgeThreshold > 0
			|| $specStatus == config_get( 'bug_feedback_status' ) && $issueAgeThreshold > 0
			|| $specStatus == 40 && $issueAgeThreshold > 0
			)
		{
			$specIssueResult = $upv_api->getIssuesByIndividual( $userId, $bugAssignedProjectId, $bugTargetVersion, $specStatus );
         $assocArray = mysqli_fetch_row( $specIssueResult );
			$specIssues = array();

         while ( $specIssue = $assocArray [0] )
			{
            $specIssues[] = $specIssue;
            $assocArray = mysqli_fetch_row( $specIssueResult );
			}
			
			if ( $specIssues != null )
			{
				$specTimeDifference = $upv_api->calculateTimeDifference( $specIssues )[0];
            $oldestSpecIssue    = $upv_api->calculateTimeDifference( $specIssues )[1];

				if ( $specTimeDifference > $issueAgeThreshold )
				{
					echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
					'&search=' . $oldestSpecIssue .
					'&status_id='. $specStatus .
					'&handler_id=' . $linkUserId .
					'&sticky_issues=on&target_version=' . $bugTargetVersion .
					'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
               $assocArray = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
               echo $assocArray [$specStatus] .
					' ' . plugin_lang_get( 'remark_since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get( 'remark_day' ) . '<br/>';
					echo '</a>';
				}
			}
		}
	}
	
	if ( $unreachableIssueFlag )
	{		
		$filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId;

      $filterString = $upv_api->prepareFilterString( $unreachIssueStatusCount, $unreachIssueStatusValue, $filterString );

      $filterString .= '&handler_id=' . $linkUserId .
			'&sticky_issues=on&target_version=' . $bugTargetVersion .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		
		echo plugin_lang_get( 'remark_noProject' ) . ' [';
		echo $filterString;
		echo plugin_lang_get( 'remark_showURIssues');
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
	
	echo '</tr>';
}

echo '<tr class="spacer">';
$footerColspan = $fixColspan - 1;
echo '<td colspan="' . $footerColspan . '">';

if ( access_has_global_level( $userAccessLevel ) )
{
?>

<form name="options" action="" method="get">
<label for="option"></label>
<select id="option" name="option">
	<option value="removeSingle"><?php echo plugin_lang_get( 'remove_selectSingle' ) ?></option>
	<option value="removeAll"><?php echo plugin_lang_get( 'remove_selectAll' ) ?></option>
</select>
<input type="submit" name="formSubmit" class="button" value="<?php echo lang_get( 'ok' ); ?>" />
</form>

<?php
}

echo '</td>';
for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	echo '<td>' . $specColumnIssueAmount[$statColIndex] . '</td>';
}
echo '<td />';
echo '</tr>';
echo '</tbody>';
echo '</table>';
echo '</div>';

html_page_bottom();