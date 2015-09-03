<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1(plugin_lang_get('user_project_view'));
html_page_top2();

// PluginManager object
$pluginManager = new PluginManager();

// User
$userId = $_GET['user_id'];

// Details about User
$userDetails = $pluginManager->getUserDetailsByUserId($userId);

// Get all projects
$allProjects = $pluginManager->getAllProjects();

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">' . "\n";
echo '<div id="manage-user-div" class="form-container">';

if ($pluginManager->getActMantisVersion() == '1.2.')
{
   echo '<table class="width100" cellspacing="1">';
}
else
{
   echo '<table>';
}
echo '<thead>';
echo '<tr>';
echo '<td class="form-title" colspan="1">' . plugin_lang_get( 'wrong_issues_title' ) . string_display_line( $userDetails[1] ) . '</td>';
echo '<td class="form-title" colspan="2">';
echo '<span class="small">';
echo '[<a href="' . plugin_page( 'PrintWrongIssueDetails' ) . '&user_id=' . $userId . '">' . plugin_lang_get( 'print_button' ) . '</a>]';
echo '</span>';
echo '</td>';
echo '</tr>';
echo '<tr class="row-category">';
echo '<th>' . plugin_lang_get('realname') . '</th>';
echo '<th>' . plugin_lang_get('wrong_issues') . '</th>';
echo '</tr>';

echo '</thead>';

echo '<tbody>';

if ($pluginManager->getActMantisVersion() == '1.2.')
{
   echo '<tr ' . helper_alternate_class($i) . '>';
}
else
{
   echo '<tr>';
}

// Column User
echo '<td>';
echo '<a href="manage_user_edit_page.php?user_id=' . $userId . '">';
echo string_display_line( $userDetails[2] );
echo '</a>';
echo '</td>';

// Column Wrong Issues
echo '<td>';

while ( $project = mysqli_fetch_array( $allProjects ) )
{
   $u_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser( $project['id'], $userId );

   while ( $issue = mysqli_fetch_array( $u_issue ) )
   {
      echo plugin_lang_get('issue') . ' ';
      echo '<a href="view.php?id=' . $issues[] = $issue['id'] . '">';
      echo $issues[] = bug_format_id($issue['id']);
      echo '</a>';
      echo ', ' . plugin_lang_get('project') . ' ';
      echo '<a href="manage_proj_edit_page.php?project_id=' . $issues[] = $issue['pid'] . '">';
      echo $issues[] = $issue['pname'] . '<br>';
      echo '</a>';
   }
}

echo '</td>';

echo '</tr>';
echo '</tbody>';
echo '</table>';

html_page_bottom1();