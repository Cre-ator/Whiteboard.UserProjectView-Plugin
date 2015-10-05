<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

// PluginManager object
$pluginManager = new PluginManager();

$userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );

$unreachIssueStatusValue = plugin_config_get( 'UnreachableIssueThreshold' );

$amountStatColumns = plugin_config_get( 'colAmount' );
$statCols = array();
$issueThresholds = array();

for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	$statCols[$statColIndex] = plugin_config_get( 'statselectcol' . $statColIndex );
	$issueThresholds[$statColIndex] = plugin_config_get( 'issueThreshold' . $statColIndex );
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
		$actBugMainProjectId = $pluginManager->getMainProjectByVersion( $actBugTargetVersion );
	}
	
	$actBugMainProjectName = project_get_name( $actBugMainProjectId );
	
	// prepare target version string
	if ( $actBugTargetVersion != '' )
	{
		$t_version_rows = version_get_all_rows( $actBugAssignedProjectId );
		$tpl_target_version_string = '';
		$tpl_target_version_string = prepare_version_string( $actBugAssignedProjectId, version_get_id( $actBugTargetVersion, $actBugAssignedProjectId ) , $t_version_rows );
		
		$versionDate = date( 'Y-m-d', version_get_field( version_get_id( $actBugTargetVersion, $actBugAssignedProjectId ), 'date_order' ) );
	}
	else
	{
		$tpl_target_version_string = '';
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
	. '__' . $tpl_target_version_string
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
	if ( $rowVals[5] == '' )
	{
		$tableRow[$rowIndex]['specColumn1'] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[1] );
	}
	else
	{
		$tableRow[$rowIndex]['specColumn1'] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[1] );		
	}
	$tableRow[$rowIndex]['specColumn2'] = '0';
	$tableRow[$rowIndex]['specColumn3'] = '0';
	
	if ( $statCols[2] != null )
	{
		if ( $rowVals[5] == '' )
		{
			$tableRow[$rowIndex]['specColumn2'] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[2] );
		}
		else
		{
			$tableRow[$rowIndex]['specColumn2'] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[2] );		
		}
	}
	if ( $statCols[3] != null )
	{
		if ( $rowVals[5] == '' )
		{
			$tableRow[$rowIndex]['specColumn3'] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[3] );
		}
		else
		{
			$tableRow[$rowIndex]['specColumn3'] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[3] );		
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

$allUsers = $pluginManager->getAllUsers();

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
	$userIsActive = user_is_enabled( $userId );
	
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
			$amountOfIssues .= $pluginManager->getAmountOfIssuesByIndividual( $userId, $t_project_id, '', $statCols[$statColIndex] );
		}
	}
	else
	{
		$userIsAssignedToProject = mysqli_fetch_row( $pluginManager->checkUserIsAssignedToProject( $userId, $t_project_id ) );
		if ( $userIsAssignedToProject != null )
		{
			$subProjects = array();
			array_push( $subProjects, $t_project_id );
			$tSubProjects = array();
			$tSubProjects = project_hierarchy_get_all_subprojects( $t_project_id );
			
			foreach ( $tSubProjects as $tSubProject )
			{
				array_push( $subProjects, $tSubProject );
			}
					
			for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
			{
				foreach ( $subProjects as $subProject )
				{
					$amountOfIssues .= $pluginManager->getAmountOfIssuesByIndividual( $userId, $subProject, '', $statCols[$statColIndex] );
				}
			}
		}
		else
		{
			continue;
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
		$tableRow[$addRow]['specColumn1'] = '0';
		$tableRow[$addRow]['specColumn2'] = '0';
		$tableRow[$addRow]['specColumn3'] = '0';
	}
}

foreach ( $tableRow as $key => $row )
{
	$sortUserName[$key] = $row['userName'];
	$sortUserRealname[$key] = $row['userRealname'];
	$sortMainProject[$key] = $row['mainProjectName'];
	$sortAssignedProject[$key] = $row['bugAssignedProjectName'];
	$sortTargetVersion[$key] = $row['bugTargetVersion'];
	$sortSpecColumnFst[$key] = $row['specColumn1'];
	$sortSpecColumnScd[$key] = $row['specColumn2'];
	$sortSpecColumnThd[$key] = $row['specColumn3'];
}

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

$sortVal = $sortUserName;
$sortOrder = 'ASC';

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
	project_get_name( helper_get_current_project() );
echo '</td>';
echo '</tr>';
echo '<tr class="row-category">';
	echo '<th>';
	echo plugin_lang_get( 'username' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=userName&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=userName&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';		
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'realname' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=realName&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=realName&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'projects' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=mainProject&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=mainProject&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'subproject' ) . ' ';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=assignedProject&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page('UserProject') . '&sortVal=assignedProject&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
	echo '<th>';
	echo plugin_lang_get( 'next_version' ) . ' ';
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
	echo MantisEnum::getAssocArrayIndexedByValues( lang_get('status_enum_string' ) )[$statCols[$headIndex]] . ' ';
	echo '<a href="' . plugin_page( 'UserProject' ) . '&sortVal=specColumn' . $headIndex . '&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page( 'UserProject' ) . '&sortVal=specColumn' . $headIndex . '&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</th>';
}
echo '<th>' . plugin_lang_get( 'remark' ) . '</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';



$sortVal = $_GET['sortVal'];
$sortOrder = $_GET['sort'];

if ( $sortVal == 'userName' )
{
	$sortVal = $sortUserName;
}
elseif ( $sortVal == 'realName')
{
	$sortVal = $sortUserRealname;
}
elseif ( $sortVal == 'mainProject')
{
	$sortVal = $sortMainProject;
}
elseif ( $sortVal == 'assignedProject')
{
	$sortVal = $sortAssignedProject;
}
elseif ( $sortVal == 'targetVersion')
{
	$sortVal = $sortTargetVersion;
}
elseif ( $sortVal == 'specColumn1')
{
	$sortVal = $sortSpecColumnFst;
}
elseif ( $sortVal == 'specColumn2')
{
	$sortVal = $sortSpecColumnScd;
}
elseif ( $sortVal == 'specColumn3')
{
	$sortVal = $sortSpecColumnThd;
}

if ( $sortOrder == 'ASC' )
{
	$sortOrder = SORT_ASC;
}
elseif ( $sortOrder == 'DESC' )
{
	$sortOrder = SORT_DESC;
}

if ( $tableRow != null )
{
	array_multisort( $sortVal, $sortOrder, SORT_NATURAL|SORT_FLAG_CASE, $tableRow );
}
$rowVal = false;
$tableRowCount = count( $tableRow );
$specColumnIssueAmount = array();

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
	$issueCounter[1] = $tableRow[$tableRowIndex]['specColumn1'];
	$issueCounter[2] = $tableRow[$tableRowIndex]['specColumn2'];
	$issueCounter[3] = $tableRow[$tableRowIndex]['specColumn3'];
	$noUserFlag = false;
	
	if ( $bugAssignedProjectId == '' )
	{
		$bugAssignedProjectId = $mainProjectId;
	}
	$linkUserId = $userId;
	if ( $userId == '0' )
	{
		$linkUserId = '-2';
	}
	
	$isAssignedToProject = '0';
	if ( $userId != '0' && $bugAssignedProjectId != '' )
	{
		if ( !user_is_administrator( $userId ) )
		{
			$isAssignedToProject = mysqli_fetch_row( $pluginManager->checkUserIsAssignedToProject( $userId, $bugAssignedProjectId ) );
		}
	}
	$unreachableIssueFlag = false;
	if ( $isAssignedToProject == null || $isAssignedToProject == '' )
	{
		$unreachableIssueFlag = true;
	}
	
	
	
	$sortVal = $_GET['sortVal'];
	if ( $tableRowIndex > 0 )
	{
		if ( $sortVal == 'userName'
			|| $sortVal == 'realName'
			|| $sortVal == 'specColumn1'
			|| $sortVal == 'specColumn2'
			|| $sortVal == 'specColumn3'
			)
		{
			$userNameOld = $tableRow[$tableRowIndex-1]['userName'];
			if ( $userName != $userNameOld )
			{
				$rowVal = !$rowVal;
			}
		}
		elseif ( $sortVal == 'mainProject' )
		{
			$mainProjectNameOld = $tableRow[$tableRowIndex-1]['mainProjectName'];
			if ( $mainProjectName != $mainProjectNameOld )
			{
				$rowVal = !$rowVal;
			}
		}
		elseif ( $sortVal == 'assignedProject')
		{
			$bugAssignedProjectNameOld = $tableRow[$tableRowIndex-1]['bugAssignedProjectName'];
			if ( $bugAssignedProjectName != $bugAssignedProjectNameOld )
			{
				$rowVal = !$rowVal;
			}
		}
		elseif ( $sortVal == 'targetVersion')
		{
			$bugTargetVersionOld = $tableRow[$tableRowIndex-1]['bugTargetVersion'];
			if ( $bugTargetVersion != $bugTargetVersionOld )
			{
				$rowVal = !$rowVal;
			}
		}
	}
	
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
	
	// build row
	$pluginManager->buildSpecificRow( $userId, $rowVal, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
	

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
		$issueThreshold = $issueThresholds[$statColIndex];
		$specStatus = $statCols[$statColIndex];
		$issueAmount = $issueCounter[$statColIndex];
		
		if ( $issueThreshold < $issueAmount && plugin_config_get( 'CTFHighlighting' ) )
		{
			echo '<td style="background-color:' . plugin_config_get( 'ITBGColor' ) . '">';
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
	echo '<td>';
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{ 
		if ( $bugAssignedProjectId == null && $mainProjectId == null )
		{
			continue;
		}
		
		$specStatus = $statCols[$statColIndex];
		if ( $specStatus == config_get( 'bug_assigned_status') && plugin_config_get( 'OIHighlighting' )
			|| $specStatus == config_get( 'bug_feedback_status' ) && plugin_config_get( 'OIHighlighting' )
			|| $specStatus == 40 && plugin_config_get( 'OIHighlighting' )
			)
		{
			$specIssueResult = $pluginManager->getIssuesByIndividual( $userId, $bugAssignedProjectId, $bugTargetVersion, $specStatus );
			
			$specIssues = array();
			while ( $specIssue = mysqli_fetch_row( $specIssueResult )[0] )
			{
				$specIssues[] = $specIssue;
			}
			
			if ( $specIssues != null )
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
				
				if ( $specTimeDifference > plugin_config_get( 'oldIssueThreshold' . $statColIndex ) )
				{
					echo '<a href="search.php?project_id=' . $bugAssignedProjectId .
					'&search=' . $oldestSpecIssue .
					'&status_id='. $specStatus .
					'&handler_id=' . $linkUserId .
					'&sticky_issues=on&target_version=' . $bugTargetVersion .
					'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
					echo MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) )[$specStatus] .
					' ' . plugin_lang_get( 'since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get( 'day' ) . '<br>';
					echo '</a>';
				}
			}
		}
	}
	
	if ( $unreachableIssueFlag )
	{		
		$filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId .
			'&status_id[]=' . $unreachIssueStatusValue[0];
		if ( $unreachIssueStatusValue[1] != null )
		{
			$filterString .= '&status_id[]=' . $unreachIssueStatusValue[1];
		}
		if ( $unreachIssueStatusValue[2] != null )
		{
			$filterString .= '&status_id[]=' . $unreachIssueStatusValue[2];
		}
		if ( $unreachIssueStatusValue[3] != null )
		{
			$filterString .= '&status_id[]=' . $unreachIssueStatusValue[3];
		}
		if ( $unreachIssueStatusValue[4] != null )
		{
			$filterString .= '&status_id[]=' . $unreachIssueStatusValue[4];
		}
		if ( $unreachIssueStatusValue[5] != null )
		{
			$filterString .= '&status_id[]=' . $unreachIssueStatusValue[5];
		}
		if ( $unreachIssueStatusValue[6] != null )
		{
			$filterString .= '&status_id[]=' . $unreachIssueStatusValue[6];
		}
		$filterString .= '&handler_id=' . $linkUserId .
			'&sticky_issues=on&target_version=' . $bugTargetVersion .
			'&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
		
		echo plugin_lang_get( 'noProject' ) . ' [';
		echo $filterString;
		echo plugin_lang_get( 'showUnreachIssues') . ']<br>';
		echo '</a>';
	}
	if ( !$inactiveUserFlag )
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
}

echo '<tr class="spacer"><td colspan="5">';
for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	echo '<td>' . $specColumnIssueAmount[$statColIndex] . '</td>';
}
echo '<td/>';
echo '</tbody>';
echo '</table>';
echo '</div>';

html_page_bottom();