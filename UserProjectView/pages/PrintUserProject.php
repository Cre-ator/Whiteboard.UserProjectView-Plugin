<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1( plugin_lang_get( 'user_project_view' ) );
html_head_end();
html_body_begin();

// PluginManager object
$pluginManager = new PluginManager();

// actual Project ID
$actProject = helper_get_current_project();

// actual Project Details
$projectDetails = $pluginManager->getProjectDetailsByProjectId($actProject);

// All active users
$allValidUsers = $pluginManager->getAllValidUsers();

while ( $user = mysqli_fetch_array( $allValidUsers ) )
{
	$users[] = $user;
}

$t_user_count = count( $users );

echo '<table class="width100" cellspacing="1" >';
echo '<tr>';
echo '<td class="form-title" colspan="6">';
echo '<div class="center">';
echo string_display_line( config_get( 'window_title' ) ) . ' - UserProjectView - ' . plugin_lang_get( 'projects_title' );
if ( $actProject == 0 )
{
   echo plugin_lang_get( 'project_selector_all' );
}
else
{
   echo $projectDetails[1];
}
echo '</div>';
echo '</td>';
echo '</tr>';
echo '<tr>';
echo '<td class="print-spacer" colspan="6">';
echo '<hr />';
echo '</td>';
echo '</tr>';
echo '<tr class="print-category">';
echo '<td class="print" width="16%">' . plugin_lang_get( 'username' ) . '</td>';
echo '<td class="print" width="16%">' . plugin_lang_get( 'realname' ) . '</td>';
echo '<td class="print" width="16%">' . plugin_lang_get( 'projects' ) . '</td>';
echo '<td class="print" width="16%">' . plugin_lang_get( 'next_version' ) . '</td>';
echo '<td class="print" width="16%">' . plugin_lang_get( 'issues' ) . '</td>';
echo '<td class="print" width="16%">' . plugin_lang_get( 'wrong_issues' ) . '</td>';
echo '</tr>';

for ($i = 0; $i < $t_user_count; $i++)
{
	# prefix user data with u_
	$user = $users[$i];
	extract($user, EXTR_PREFIX_ALL, 'u');

	$projects = array();
	$allProjectsByUser = $pluginManager->getAllProjectsByProjectAndUser($actProject, $user['id']);

	while ($project = mysqli_fetch_array($allProjectsByUser))
	{
		$projects[] = $project;
	}
	$project_count = count($projects);

	for ($j = 0; $j < $project_count; $j++)
   {
      $project = $projects[$j];

      $amount = $pluginManager->getAmountOfAssignedIssuesByProjectAndUser($project['id'], $user['id']);
      if ($amount == '0')
      {
         // User has project, but zero issues - do nothing!
      }
      else
      {
         echo '<tr>';

         // Column User
         echo '<td class="print">';
         echo utf8_encode(string_display_line($u_username));
         echo '</td>';

         // Column Real Name
         echo '<td class="print">';
         echo utf8_encode($user['realname']);
         echo '</td>';

         // Column Projects
         echo '<td class="print">';
         echo utf8_encode($project['name']) . "<br>";
         echo '</td>';

         // Column Target version
         echo '<td class="print">';
         echo utf8_encode($pluginManager->getTargetVersionByProjectAndUser($project['id'], $user['id'])) . "<br>";
         echo '</td>';

         // Column Issues
         echo '<td class="print">';
         echo $pluginManager->getAmountOfAssignedIssuesByProjectAndUser($project['id'], $user['id']) . '<br>';
         echo '</td>';

         // Column Wrong Issues
         echo '<td class="print">';
         $t_all_projects = $pluginManager->getAllProjects();

         while ($project = mysqli_fetch_array($t_all_projects))
         {
            $u_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser($project['id'], $user['id']);
            if ($u_issue->fetch_row() != null)
            {
               echo plugin_lang_get('issueswithoutproject');
            }
         }
         echo '</td>';
         echo '</tr>';
      }
   }

   // This section is only relevant for non-filtered project-overview
   if ($actProject == 0)
   {
      if ($project_count == 0)
      {
         if ($pluginManager->getAllAssignedIssuesByUser($user['id']) == '')
         {
            // User has no project and no issues -> do nothing!
         }
         else
         {
            // User has no project, but issues!
            echo '<tr>';

            // Column User
            echo '<td class="print">';
            echo utf8_encode(string_display_line($u_username));
            echo '</td>';

            // Column Real Name
            echo '<td class="print">';
            echo utf8_encode($user['realname']);
            echo '</td>';

            // Column Projects (in this case irrelevant)
            echo '<td class="print" />';

            // Column Target Version (in this case irrelevant)
            echo '<td class="print" />';

            // Column Issues (in this case irrelevant)
            echo '<td class="print" />';

            // Column Wrong Issues
            echo '<td class="print">';
            echo plugin_lang_get('issueswithoutproject');
            echo '</td>';
            echo '</tr>';
         }
      }
   }
}
echo '</table>';

html_body_end();
html_end();