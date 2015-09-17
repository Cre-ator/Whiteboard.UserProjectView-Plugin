<?php
require_once ( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

// PluginManager object
$pluginManager = new PluginManager();

$userAccessLevel = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );

$sortName = true;
$sortProject = false;

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
	if ( $sortName )
	{
		array_push( $t_bugslist, $rows[$i]->handler_id . '_' . $rows[$i]->id );
		sort( $t_bugslist );
	}
	elseif ( $sortProject )
	{
		array_push( $t_bugslist, $rows[$i]->project_id . '_' . $rows[$i]->id );
		sort( $t_bugslist );
	}
}

$matchcode = array();
$issueCounter = 0;
$checkEquivalentBugs = false;
   
// calculate page content
for ( $bugIndex = 0; $bugIndex < $t_row_count; $bugIndex++ )
{	
	// bug information
	$actBugId = explode( '_', $t_bugslist[$bugIndex] )[1];
	$actBugTargetVersion = bug_get_field( $actBugId, 'target_version' );
	$actBugStatus = bug_get_field( $actBugId, 'status' );
	$actBugAssignedProjectId = bug_get_field( $actBugId, 'project_id' );
	$actBugAssignedUserId = bug_get_field( $actBugId, 'handler_id' );
	
	// filter config specific bug status
	if ( $actBugStatus != config_get( 'bug_assigned_status' ) )
	{
		continue;
	}
	
	// bug is assigned, etc... but not ASSIGNED TO, etc ...
	if ( $actBugAssignedUserId == 0 )
	{
		continue;
	}

	// user information
	$aBAUIUsername  = user_get_name( $actBugAssignedUserId );
	$aBAUIRealname  = user_get_realname( $actBugAssignedUserId );
	$aBAUIActivFlag = user_is_enabled( $actBugAssignedUserId );

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
	$unreachableIssues = mysqli_fetch_row( $pluginManager->getUnreachableIssuesByBugAndUser( $actBugId, $actBugAssignedUserId ) );

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

html_page_top1( plugin_lang_get( 'user_project_view' ) );
html_head_end();
html_body_begin();

echo '<table class="width100" cellspacing="1" >';
echo '<tr>';
echo '<td class="form-title" colspan="7">';
echo '<div class="center">';
echo string_display_line( config_get( 'window_title' ) ) . ' - UserProjectView - ' . utf8_encode(plugin_lang_get( 'projects_title' ) . project_get_name(helper_get_current_project() ) );
echo '</div>';
echo '</td>';
echo '</tr>';
echo '<tr>';
echo '<td class="print-spacer" colspan="7">';
echo '<hr />';
echo '</td>';
echo '</tr>';
echo '<tr class="print-category">';
echo '<td class="print" width="14%">' . plugin_lang_get( 'username' ) . '</td>';
echo '<td class="print" width="14%">' . plugin_lang_get( 'realname' ) . '</td>';
echo '<td class="print" width="14%">' . plugin_lang_get( 'projects' ) . '</td>';
echo '<td class="print" width="14%">' . plugin_lang_get( 'subproject' ) . '</td>';
echo '<td class="print" width="14%">' . plugin_lang_get( 'next_version' ) . '</td>';
echo '<td class="print" width="14%">' . plugin_lang_get( 'issues' ) . '</td>';
echo '<td class="print" width="14%">' . plugin_lang_get( 'remark' ) . '</td>';
echo '</tr>';

// process page content
for ( $rowIndex = 0; $rowIndex < $rowCount; $rowIndex++ )
{	
	// process first entry in array
	$rowContent = key( $dataRows );
	
	// process data string
	$rowVals = explode( '__', $rowContent );
		
	// user content
	$userId = $rowVals[0];
	$userName = $rowVals[1];
	$userRealname = $rowVals[2];
	
	// project content
	$mainProjectId = $rowVals[3];
	$mainProjectName = $rowVals[4];
	
	// bug content
	$bugAssignedProjectId = $rowVals[5];
	$bugAssignedProjectName = $rowVals[6];
	$bugTargetVersion = $rowVals[7];
	$bugTargetVersionDate = $rowVals[8];
	$bugTargetVersionPreparedString = $rowVals[9];
	
	// unreachable issue content
	$unreachableIssueFlag = $rowVals[10];
	
	// (in)active user content
	$inactiveUserFlag = $rowVals[11];
	
	// get value and pop FIRST element of array
	$issueCounter = array_shift( $dataRows );
	
	// build row
 	echo '<tr>';

	// Column User
	echo '<td class="print">';
	echo $userName;
	echo '</td>';

	// Column Real Name
	echo '<td class="print">';
	echo $userRealname;
	echo '</td>';

	// Column Projects
	echo '<td class="print">';
	echo $mainProjectName;
	echo '</td>';
         
	// Column Subprojects
	echo '<td class="print">';
	if ( $bugAssignedProjectName != $mainProjectName )
	{
		echo $bugAssignedProjectName;
	}
	echo '</td>';

	// Column Target version
	echo '<td class="print">';
	echo $bugTargetVersionDate . ' ' . $bugTargetVersionPreparedString;
	echo '</td>';

	// Column Issues
	echo '<td class="print">';
	echo $issueCounter;
	echo '</td>';

	// Column remark
	echo '<td class="print">';
	if ( $unreachableIssueFlag )
	{
		echo plugin_lang_get( 'unreachableIssue' ) . ' ';
	}
	if ( $inactiveUserFlag == false )
	{
		echo plugin_lang_get( 'inactiveUser' ) . ' ';
	}
	echo '</td>';
	echo '</tr>';
	
	$amountOfShownIssues += $issueCounter;
}
echo '<tr>';
echo '<td class="print-spacer" colspan="7">';
echo '<hr />';
echo '</td>';
echo '</tr>';
echo '<tr><td/><td/><td/><td/><td/><td>' . $amountOfShownIssues . '</td><td/>';

echo '</table>';

html_body_end();
html_end();