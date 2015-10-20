<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

// PluginManager object
$pluginManager = new PluginManager();

$userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );

$unreachIssueStatusValue = plugin_config_get( 'URIThreshold' );
$unreachIssueStatusCount = count( $unreachIssueStatusValue );

$amountStatColumns = plugin_config_get( 'CAmount' );
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
$t_per_page = 10000;
$t_bug_count = null;
$t_page_count = null;

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
	$irrelevantFlag = $pluginManager->setIrrelevantFlag( $amountStatColumns, $actBugStatus, $statCols );
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
		$actBugMainProjectId = $pluginManager->getMainProjectByHierarchy( $actBugAssignedProjectId );
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
	
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
		$tableRow[$rowIndex]['specColumn' . $statColIndex] = '0';
		
		$specColumnValue = 'specColumn' . $statColIndex;
		
		if ( $statCols[$statColIndex] != null )
		{
			if ( $rowVals[5] == '' )
			{
				$tableRow[$rowIndex][$specColumnValue] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[3], $rowVals[7], $statCols[$statColIndex] );
			}
			else
			{
				$tableRow[$rowIndex][$specColumnValue] = $pluginManager->getAmountOfIssuesByIndividual( $rowVals[0], $rowVals[5], $rowVals[7], $statCols[$statColIndex] );
			}
		}
	}
	
	array_shift( $dataRows );
}

if ( plugin_config_get( 'ShowZIU' ) )
{
	$allUsers = $pluginManager->getAllUsers();
	
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
				$amountOfIssues .= $pluginManager->getAmountOfIssuesByIndividualWOTV( $userId, $t_project_id, $statCols[$statColIndex] );
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
				$userIsAssignedToProject = mysqli_fetch_row( $pluginManager->checkUserIsAssignedToProject( $userId, $subProject ) );
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
					$amountOfIssues .= $pluginManager->getAmountOfIssuesByIndividualWOTV( $userId, $subProject, $statCols[$statColIndex] );
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

foreach ( $tableRow as $key => $row )
{
	$sortUserName[$key]        = $row['userName'];
	$sortUserRealname[$key]    = $row['userRealname'];
	$sortMainProject[$key]     = $row['mainProjectName'];
	$sortAssignedProject[$key] = $row['bugAssignedProjectName'];
	$sortTargetVersion[$key]   = $row['bugTargetVersion'];
}

html_page_top1( plugin_lang_get( 'menu_userprojecttitle' ) );
html_head_end();

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';


html_body_begin();

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
echo '<td class="form-title" colspan="' . $dynamicColspan . '">' . 
	plugin_lang_get( 'thead_accounts_title' ) .
	plugin_lang_get( 'thead_projects_title' ) .
	project_get_name( helper_get_current_project() );
echo '</td>';
echo '</tr>';
echo '<tr class="spacer">';
	echo '<td class="print">';
	echo plugin_lang_get( 'thead_username' ) . ' ';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=userName&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=userName&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';		
	echo '</td>';
	echo '<td class="print">';
	echo plugin_lang_get( 'thead_realname' ) . ' ';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=realName&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=realName&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</td>';
	echo '<td class="print">';
	echo plugin_lang_get( 'thead_project' ) . ' ';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=mainProject&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=mainProject&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</td>';
	echo '<td class="print">';
	echo plugin_lang_get( 'thead_subproject' ) . ' ';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=assignedProject&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=assignedProject&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</td>';
	echo '<td class="print">';
	echo plugin_lang_get( 'thead_targetversion' ) . ' ';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=targetVersion&sort=ASC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
	echo '</a>';
	echo '<a href="' . plugin_page( 'PrintUserProject' ) . '&sortVal=targetVersion&sort=DESC">';
	echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
	echo '</a>';
	echo '</td>';

for ( $headIndex = 1; $headIndex <= $amountStatColumns; $headIndex++ )
{
	echo '<td class="print">';
	echo MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) )[$statCols[$headIndex]];
	echo '</td>';
}
echo '<td class="print">' . plugin_lang_get( 'thead_remark' ) . '</td>';
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

	$bugAssignedProjectId = $pluginManager->getBugAssignedProjectId( $bugAssignedProjectId, $mainProjectId );
	$linkUserId           = $pluginManager->generateLinkUserId( $userId );
	$isAssignedToProject  = $pluginManager->checkUserAssignedToProject( $userId, $bugAssignedProjectId );
	$unreachableIssueFlag = $pluginManager->setUnreachableIssueFlag( $isAssignedToProject );
	$pProject             = $pluginManager->prepareParentProject( $t_project_id, $bugAssignedProjectId, $mainProjectId );
	$noUserFlag           = $pluginManager->setUserflag( $amountStatColumns, $statCols, $userId );

   $sortVal = $_GET['sortVal'];
   if ( $tableRowIndex > 0 )
   {
      switch ( $sortVal )
      {
         case 'realName':
         case 'userName':
            $userNameOld = $tableRow[$tableRowIndex-1]['userName'];
            $rowVal = $pluginManager->compareValues( $userName, $userNameOld, $rowVal );
            break;

         case 'mainProject':
            $mainProjectNameOld = $tableRow[$tableRowIndex-1]['mainProjectName'];
            $rowVal = $pluginManager->compareValues( $mainProjectName, $mainProjectNameOld, $rowVal );
            break;

         case 'assignedProject':
            $bugAssignedProjectNameOld = $tableRow[$tableRowIndex-1]['bugAssignedProjectName'];
            $rowVal = $pluginManager->compareValues( $bugAssignedProjectName, $bugAssignedProjectNameOld, $rowVal );
            break;

         case 'targetVersion':
            $bugTargetVersionOld = $tableRow[$tableRowIndex-1]['bugTargetVersion'];
            $rowVal = $pluginManager->compareValues( $bugTargetVersion, $bugTargetVersionOld, $rowVal );
            break;
      }
   }

   // build row
   $pluginManager->buildSpecificRow( $userId, $rowVal, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );

   // column user
	echo '<td>';
	echo $userName;
	echo '</td>';
	
	
	// column real name
	echo '<td>';
	echo $userRealname;
	echo '</td>';
	
	
	// column main project
	echo '<td>';
	echo $mainProjectName;
	echo '</td>';
	
	
	// column assigned project
	echo '<td>';
	echo $bugAssignedProjectName;
	echo '</td>';
	
	
	// column target version
	echo '<td>';
	echo $bugTargetVersionDate . ' '. $bugTargetVersionPreparedString;
	echo '</td>';
	
	
	// Column(s) amount of issues
	for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
	{
      $issueThreshold = $issueThresholds[$statColIndex];
      $specStatus     = $statCols[$statColIndex];
      $issueAmount    = $issueCounter[$statColIndex];

      if ( $issueThreshold < $issueAmount && $issueThreshold > 0 )
      {
			echo '<td style="background-color:#555555">';
		}
		else
		{
			echo '<td>';
		}
		$specColumnIssueAmount[$statColIndex] += $issueAmount;
		echo $issueAmount;
		echo '</td>';
	}

	
	// column remark
	echo '<td>';
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
         $specIssueResult = $pluginManager->getIssuesByIndividual( $userId, $bugAssignedProjectId, $bugTargetVersion, $specStatus );
         $assocArray = mysqli_fetch_row( $specIssueResult );
         $specIssues = array();

         while ( $specIssue = $assocArray [0] )
         {
            $specIssues[] = $specIssue;
            $assocArray = mysqli_fetch_row( $specIssueResult );
         }

         if ( $specIssues != null )
         {
            $specTimeDifference = $pluginManager->calculateTimeDifference( $specIssues )[0];
            $oldestSpecIssue    = $pluginManager->calculateTimeDifference( $specIssues )[1];

            if ( $specTimeDifference > $issueAgeThreshold )
				{
					echo MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) )[$specStatus] .
					' ' . plugin_lang_get( 'remark_since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get( 'remark_day' ) . '<br/>';
				}
			}
		}
	}
	
	if ( $unreachableIssueFlag )
	{		
		echo plugin_lang_get( 'remark_noProject' ) . '<br/>';
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

echo '<tr class="spacer"><td colspan="5">';
for ( $statColIndex = 1; $statColIndex <= $amountStatColumns; $statColIndex++ )
{
	echo '<td>' . $specColumnIssueAmount[$statColIndex] . '</td>';
}
echo '<td/>';
echo '</tbody>';
echo '</table>';
echo '</div>';

html_body_end();
html_end();