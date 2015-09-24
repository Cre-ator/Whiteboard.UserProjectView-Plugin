<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

// PluginManager object
$pluginManager = new PluginManager();

$userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );

$unreachIssueStatusValue = plugin_config_get( 'UnreachableIssueThreshold' );
$amountStatColumns = plugin_config_get( 'colAmount' );
$statCols = array();

for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	$statCols[$statColIndex] = plugin_config_get( 'statselectcol' . $statColIndex );
}

$f_page_number	= gpc_get_int( 'page_number', 1 );

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

$t_per_page = 10000;
$t_bug_count = null;
$t_page_count = null;

// get filter string
$t_filter_string = explode( '#', $t_filter['filter_string'], 0 );

$rows = filter_get_bug_rows( $f_page_number, $t_per_page, $t_page_count, $t_bug_count, unserialize( $t_filter_string[1] ), null, null, true );

$t_bugslist = Array();

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
	$actBugId = $t_bugslist[$bugIndex];
	$actBugTargetVersion = bug_get_field( $actBugId, 'target_version' );
	$actBugStatus = bug_get_field( $actBugId, 'status' );
	$actBugAssignedProjectId = bug_get_field( $actBugId, 'project_id' );
	$actBugAssignedUserId = bug_get_field( $actBugId, 'handler_id' );
	
	// user information
	$aBAUIUsername  = '';
	$aBAUIRealname  = '';
	$aBAUIActivFlag = true;
	
	
	// filter config specific bug status
	if ( $actBugStatus != $statCols[1]
		&& $actBugStatus != $statCols[2]
		&& $actBugStatus != $statCols[3]
		)
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
	}
	else
	{
		// identify main project by target version of selected issue
		$actBugMainProjectId = mysqli_fetch_row( $pluginManager->getMainProjectByVersion( $actBugTargetVersion ) )[0];
	}
	
	$actBugMainProjectName = project_get_name( $actBugMainProjectId );
	
	// prepare target version string
	if ( $actBugTargetVersion != '' )
	{
		$t_version_rows = version_get_all_rows( $actBugAssignedProjectId );
		$tpl_target_version_string   = '';
		$tpl_target_version_string   = prepare_version_string( $actBugAssignedProjectId, version_get_id( $actBugTargetVersion, $actBugAssignedProjectId) , $t_version_rows );
		
		$versionDate = date( 'Y-m-d', version_get_field( version_get_id( $actBugTargetVersion, $actBugAssignedProjectId ), 'date_order' ) );
	}
	else
	{
		$tpl_target_version_string = '';
		$versionDate = '';
	}
	
	// prepare unreachable issues
	$unreachableIssues = mysqli_fetch_row( $pluginManager->getUnreachableIssuesByBugAndUser( $actBugId, $actBugAssignedUserId, $unreachIssueStatusValue ) );

	if ( $unreachableIssues != null && user_is_administrator( $actBugAssignedUserId ) == false )
	{
		$uRIssueFlag = true;
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
	. '__' . $tpl_target_version_string
	. '__' . $uRIssueFlag
	. '__' . $aBAUIActivFlag;
}

$dataRows = array_count_values( $matchcode );
$rowCount = count( $dataRows );
$rowFlag = false;
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
	$tableRow[$rowIndex]['unreachableIssueFlag'] = $rowVals[10];
	$tableRow[$rowIndex]['inactiveUserFlag'] = $rowVals[11];
	$tableRow[$rowIndex]['zeroIssuesFlag'] = false;
	if ( $rowVals[5] == '' )
	{
		$tableRow[$rowIndex]['specColumn1'] = mysqli_fetch_row( $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[1] ) )[0];
	}
	else
	{
		$tableRow[$rowIndex]['specColumn1'] = mysqli_fetch_row( $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[1] ) )[0];		
	}
	$tableRow[$rowIndex]['specColumn2'] = '0';
	$tableRow[$rowIndex]['specColumn3'] = '0';
	
	if ( $statCols[2] != null )
	{
		if ( $rowVals[5] == '' )
		{
			$tableRow[$rowIndex]['specColumn2'] = mysqli_fetch_row( $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[2] ) )[0];
		}
		else
		{
			$tableRow[$rowIndex]['specColumn2'] = mysqli_fetch_row( $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[2] ) )[0];		
		}
	}
	if ( $statCols[3] != null )
	{
		if ( $rowVals[5] == '' )
		{
			$tableRow[$rowIndex]['specColumn3'] = mysqli_fetch_row( $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[3] ) )[0];
		}
		else
		{
			$tableRow[$rowIndex]['specColumn3'] = mysqli_fetch_row( $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[3] ) )[0];		
		}
	}
	
	if ( $tableRow[$rowIndex]['specColumn1'] == '0'
		&& $tableRow[$rowIndex]['specColumn2'] == '0'
		&& $tableRow[$rowIndex]['specColumn3'] == '0'
		)
	{
		$tableRow[$rowIndex]['zeroIssuesFlag'] = true;
	}
	
	array_shift( $dataRows );
}

$allUsers = $pluginManager->getAllActiveUsers();

$userRows = array();

while ( $userRow = mysqli_fetch_row( $allUsers ) )
{
	$userRows[] = $userRow;
}

$rowCount = count( $userRows );

// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
for ( $rowIndexT = 0; $rowIndexT < $rowCount; $rowIndexT++ )
{
	$userId = $userRows[$rowIndexT][0];
	$userName = user_get_name( $userId );
	$userRealname = user_get_realname( $userId );
	$userIsActive = user_is_enabled( $userId );
	
	$addRow = $rowIndex + 1 + $rowIndexT;
	
	$amountOfIssues = 0;
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
		$amountOfIssues += $pluginManager->getAmountOfIssuesByIndividual($userId, '', '', $statCols[$statColIndex] );
	}
	
	// build row
	if ( $amountOfIssues == '0' && $t_project_id == 0 )
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
		$tableRow[$addRow]['unreachableIssueFlag'] = '';
		$tableRow[$addRow]['inactiveUserFlag'] = $userIsActive;
		$tableRow[$addRow]['zeroIssuesFlag'] = true;
		$tableRow[$addRow]['specColumn1'] = '0';
		$tableRow[$addRow]['specColumn2'] = '0';
		$tableRow[$addRow]['specColumn3'] = '0';
	}
}

// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

foreach ( $tableRow as $key => $row )
{
	$userNameS[$key] = $row['userName'];
	$userRealnameS[$key] = $row['userRealname'];
	$mainProjectNameS[$key] = $row['mainProjectName'];
}

array_multisort($userNameS, SORT_ASC, SORT_STRING, $tableRow);

$tableRowCount = count( $tableRow );
$specColumnIssueAmount = array();

html_page_top1( plugin_lang_get( 'userProject_title' ) );

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

// user configuration area ++++++++++++++++++++++++++++++++++++++++++++++++++++
if ( $pluginManager->getUserHasLevel() )
{
	$pluginManager->printPluginMenu();
	$pluginManager->printUserProjectMenu();
}
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

$dynamicColspan = $amountStatColumns + 6;

echo '<div id="manage-user-div" class="form-container">';

if ( $pluginManager->getActMantisVersion() == '1.2.' )
{
	echo '<table class="width100" cellspacing="1">';
}
else
{
	echo '<table>';
}
echo '<thead>';
echo '<tr>';
echo '<td class="form-title" colspan="' . $dynamicColspan .
	'">' . plugin_lang_get( 'accounts_title' ) .
	plugin_lang_get( 'projects_title' ) .
	project_get_name(helper_get_current_project());
echo '</td>';
echo '</tr>';
echo '<tr class="row-category">';
echo '<th>' . plugin_lang_get( 'username' ) . '</th>';
echo '<th>' . plugin_lang_get( 'realname' ) . '</th>';
echo '<th>' . plugin_lang_get( 'projects' ) . '</th>';
echo '<th>' . plugin_lang_get( 'subproject' ) . '</th>';
echo '<th>' . plugin_lang_get( 'next_version' ) . '</th>';
for ( $headIndex = 1; $headIndex <= $amountStatColumns; $headIndex++ )
{
	echo '<th bgcolor="' . get_status_color( $statCols[$headIndex], null, null ) .
		'">' . MantisEnum::getAssocArrayIndexedByValues( lang_get('status_enum_string' ) )[$statCols[$headIndex]] .
		'</th>';
}
echo '<th>' . plugin_lang_get( 'remark' ) . '</th>';
echo '</tr>';
echo '</thead>';
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
	$unreachableIssueFlag = $tableRow[$tableRowIndex]['unreachableIssueFlag'];
	$inactiveUserFlag = $tableRow[$tableRowIndex]['inactiveUserFlag'];
	$zeroIssuesFlag = $tableRow[$tableRowIndex]['zeroIssuesFlag'];
	$issueCounter = array();
	$issueCounter[1] = $tableRow[$tableRowIndex]['specColumn1'];
	$issueCounter[2] = $tableRow[$tableRowIndex]['specColumn2'];
	$issueCounter[3] = $tableRow[$tableRowIndex]['specColumn3'];
	$noUserFlag = false;
	
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
		$specStatus = $statCols[$statColIndex];
		if ( $userId == '0'
			&& $specStatus == config_get( 'bug_assigned_status' )
			|| $specStatus == config_get( 'bug_feedback_status' )
// 			|| $specStatus == config_get( 'bug_resolved_status' )
// 			|| $specStatus == config_get( 'bug_closed_status' )
			)
		{
			$noUserFlag = true;
		}
	}
	
	// build row
	$pluginManager->buildSpecificRow( $userId, $rowFlag, $unreachableIssueFlag, $noUserFlag );
	
	// column user
	echo '<td>';
	if ( $userId != '0' )
	{	
		if ( access_has_global_level( $userAccessLevel ) )
		{
			echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
			echo $userName;
			echo '</a>';
		}
		else
		{
			echo $userName;
		}
	}
	echo '</td>';
	
	
	// column real name
	echo '<td>';
	if ( $userId != '0' )
	{
		if ( access_has_global_level( $userAccessLevel ) )
		{
			echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
			echo $userRealname;
			echo '</a>';
		}
		else
		{
			echo $userRealname;
		}
	}
	echo '</td>';
	
	
	// column main project
	echo '<td>';
	if ( access_has_global_level( $userAccessLevel ) )
	{
		echo '<a href="manage_proj_edit_page.php?project_id=' . $mainProjectId . '">';
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
	if ( $bugAssignedProjectName != $mainProjectName )
	{
		if ( access_has_global_level( $userAccessLevel ) )
		{
			echo '<a href="manage_proj_edit_page.php?project_id=' . $bugAssignedProjectId . '">';
			echo $bugAssignedProjectName;
			echo '</a>';
		}
		else
		{
			echo $bugAssignedProjectName;
		}
	}
	echo '</td>';
	
	
	// column target version
	echo '<td>';
	echo $bugTargetVersionDate . ' ';
	if ( access_has_global_level( $userAccessLevel ) )
	{
		echo '<a href="manage_proj_edit_page.php?project_id=' . $mainProjectId . '">';
		echo $bugTargetVersionPreparedString;
		echo '</a>';
	}
	else
	{
		echo $bugTargetVersionPreparedString;
	}
	echo '</td>';
	
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
		$specStatus = $statCols[$statColIndex];
		echo '<td bgcolor="' . get_status_color( $statCols[$statColIndex], null, null ) . '">';
		echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
			'&status_id='. $specStatus .
			'&handler_id=' . $userId .
			'&sticky_issues=on&target_version=' . $bugTargetVersion .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		$specColumnIssueAmount[$statColIndex] += $issueCounter[$statColIndex];
		echo $issueCounter[$statColIndex];
		echo '</a>';
		echo '</td>';
	}

	
	// column remark
	echo '<td>';
	if ( $unreachableIssueFlag )
	{
		echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
			'&status_id='. config_get( 'bug_assigned_status' ) .
			'&handler_id=' . $userId .
			'&sticky_issues=on&target_version=' . $bugTargetVersion .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		echo plugin_lang_get( 'unreachableIssue' ) . '<br>';
		echo '</a>';
	}
	if ( $inactiveUserFlag == false )
	{
		echo plugin_lang_get( 'inactiveUser' ) . '<br>';
	}
	if ( $zeroIssuesFlag )
	{
		echo plugin_lang_get( 'zeroIssues' ) . '<br>';
	}
	if ( $noUserFlag )
	{
		echo plugin_lang_get( 'noUser' ) . '<br>';
	}
	echo '</td>';
	
	echo '</tr>';
	
	if ( $rowFlag == true )
	{
		$rowFlag = false;
	}
	else
	{
		$rowFlag = true;
	}
}

echo '<tr><td/><td/><td/><td/><td/>';
for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	echo '<td>' . $specColumnIssueAmount[$statColIndex] . '</td>';
}
echo '<td/>';
echo '</tbody>';
echo '</table>';
echo '</div>';

html_page_bottom();