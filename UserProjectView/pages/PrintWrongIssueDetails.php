<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1( plugin_lang_get( 'user_project_view' ) );
html_head_end();
html_body_begin();

// PluginManager object
$pluginManager = new PluginManager();

// User
$userId = $_GET['user_id'];

// Details about User
$userDetails = $pluginManager->getUserDetailsByUserId($userId);

// Get all projects
$allProjects = $pluginManager->getAllProjects();

echo '<table class="width100" cellspacing="1" >';
echo '<tr>';
echo '<td class="form-title" colspan="2">';
echo '<div class="center">';
echo string_display_line( config_get( 'window_title' ) ) . ' - UserProjectView';
echo '</div>';
echo '</td>';
echo '</tr>';
echo '<tr>';
echo '<td class="print-spacer" colspan="2">';
echo '<hr />';
echo '</td>';
echo '</tr>';
echo '</tr>';
echo '<tr class="print-category">';
echo '<td class="print" width="16%">' . plugin_lang_get( 'realname' ) . '</td>';
echo '<th class="print" width="16%">' . plugin_lang_get( 'wrong_issues' ) . '</th>';
echo '</tr>';

// Column User
echo '<td class="print">';
echo string_display_line( $userDetails[2] );
echo '</td>';

// Column Wrong Issues
echo '<td class="print">';

while ( $project = mysqli_fetch_array( $allProjects ) )
{
   $u_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser( $project, $userId );

   while ( $issue = mysqli_fetch_array( $u_issue ) )
   {
      echo plugin_lang_get('issue') . ' ';
      echo $issues[] = bug_format_id($issue['id']);
      echo ', ' . plugin_lang_get('project') . ' ';
      echo $issues[] = $issue['pname'] . '<br>';
   }
}

echo '</td>';
echo '</tr>';
echo '</table>';

html_body_end();
html_end();