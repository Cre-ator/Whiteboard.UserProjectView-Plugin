<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

html_page_top1(plugin_lang_get('user_project_view'));
html_page_top2();

// PluginManager object
$pluginManager = new PluginManager();

$rowIndex = 0;
$prevUserIndex = 0;



// actual Project ID
$actProject = helper_get_current_project();

// actual Project Details
$projectDetails = $pluginManager->getProjectDetailsByProjectId($actProject);

// All active users
$allValidUsers = $pluginManager->getAllValidUsers();

while ($user = mysqli_fetch_array($allValidUsers))
{
   $users[] = $user;
}

$t_user_count = count($users);

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
echo '<td class="form-title" colspan="5">' . plugin_lang_get( 'accounts_title' ) . plugin_lang_get( 'projects_title' );
if ( $actProject == 0 )
{
   echo plugin_lang_get( 'project_selector_all' );
}
else
{
   echo $projectDetails[1];
}
echo '</td>';
echo '<td class="form-title" colspan="6">';
echo '<span class="small">';
echo '[<a href="' . plugin_page('PrintUserProject') . '">' . plugin_lang_get('print_button') . '</a>]';
echo '</span>';
echo '</td>';
echo '</tr>';
echo '<tr class="row-category">';
echo '<th>' . plugin_lang_get('username') . '</th>';
echo '<th>' . plugin_lang_get('realname') . '</th>';
echo '<th>' . plugin_lang_get('projects') . '</th>';
echo '<th>' . plugin_lang_get('next_version') . '</th>';
echo '<th>' . plugin_lang_get('issues') . '</th>';
echo '<th>' . plugin_lang_get('wrong_issues') . '</th>';
echo '</tr>';

echo '</thead>';

echo '<tbody>';

for ($userIndex = 0; $userIndex < $t_user_count; $userIndex++)
{
   # prefix user data with u_
   $user = $users[$userIndex];
   extract($user, EXTR_PREFIX_ALL, 'u');

   $pluginManager->checkUserIsActive( $user['id'] );

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
         if ($pluginManager->getActMantisVersion() == '1.2.')
         {
            if ( $prevUserIndex != $userIndex )
            {
               $rowIndex = !$rowIndex;
               $prevUserIndex = $userIndex;
            }
            echo '<tr ' . helper_alternate_class( $rowIndex ) . '>';
         }
         else
         {
            echo '<tr>';
         }
         // Column User
         if ( $pluginManager->checkUserIsActive( $user['id'] ) == '0')
         {
            echo '<td class="attention">';
         }
         else
         {
            echo '<td>';
         }
         if (access_has_global_level($u_access_level))
         {
            echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
            echo utf8_encode(string_display_line($u_username));
            echo '</a>';
         }
         else
         {
            echo utf8_encode(string_display_line($u_username));
         }
         echo '</td>';

         // Column Real Name
         if ( $pluginManager->checkUserIsActive( $user['id'] ) == '0')
         {
            echo '<td class="attention">';
         }
         else
         {
            echo '<td>';
         }
         if (access_has_global_level($u_access_level))
         {
            echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
            echo utf8_encode($user['realname']);
            echo '</a>';
         }
         else
         {
            echo utf8_encode($user['realname']);
         }
         echo '</td>';

         // Column Projects
         echo '<td>';

         if (access_has_global_level($u_access_level))
         {
            echo '<a href="manage_proj_edit_page.php?project_id=' . $project['id'] . '">';
            echo utf8_encode($project['name']) . '<br>';
            echo '</a>';
         }
         else
         {
            echo utf8_encode($project['name']) . "<br>";
         }
         echo '</td>';

         // Column Target version
         echo '<td>';
         echo utf8_encode($pluginManager->getNearestTargetVersionByProject( $project['id'] )) . "<br>";
         echo '</td>';

         // Column Issues
         echo '<td>';
         echo '<a href="search.php?project_id=' . $project['id'] . '&status_id=50&handler_id=' . $user['id'] . '&sticky_issues=off&sortby=last_updated&dir=DESC&hide_status_id=-2&match_type=0">';
         echo $pluginManager->getAmountOfAssignedIssuesByProjectAndUser($project['id'], $user['id']) . '</a><br>';
         echo '</td>';

         // Column Wrong Issues
         echo '<td>';

         $t_all_projects = $pluginManager->getAllProjects();

         while ($project = mysqli_fetch_array($t_all_projects))
         {
            $u_issue = $pluginManager->getIssuesWithoutProjectByProjectAndUser($project['id'], $user['id']);
            if ($u_issue->fetch_row() != null)
            {
               echo plugin_lang_get('issueswithoutproject');
               echo ' [' . '<a href="' . plugin_page( 'WrongIssueDetails' ) . '&user_id=' . $user['id'] . '">';
               echo plugin_lang_get('detaillink');
               echo '</a>]';
               break; // projects for this user exists => end of search
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
         if ( $pluginManager->getAllAssignedIssuesByUser( $user['id'] ) == '' )
         {
            // User has no project and no issues -> do nothing!
         }
         else
         {
            // User has no project, but issues!
            if ($pluginManager->getActMantisVersion() == '1.2.')
            {
               echo '<tr ' . helper_alternate_class($userIndex) . '>';
            }
            else
            {
               echo '<tr>';
            }
            // Column User
            echo '<td>';
            if (access_has_global_level($u_access_level))
            {
               echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
               echo utf8_encode(string_display_line($u_username));
               echo '</a>';
            }
            else
            {
               echo utf8_encode(string_display_line($u_username));
            }
            echo '</td>';

            // Column Real Name
            echo '<td>';
            if (access_has_global_level($u_access_level))
            {
               echo '<a href="manage_user_edit_page.php?user_id=' . $u_id . '">';
               echo utf8_encode($user['realname']);
               echo '</a>';
            }
            else
            {
               echo utf8_encode($user['realname']);
            }
            echo '</td>';

            // Column Projects (in this case irrelevant)
            echo '<td />';

            // Column Target Version (in this case irrelevant)
            echo '<td />';

            // Column Issues (in this case irrelevant)
            echo '<td />';

            // Column Wrong Issues
            echo '<td>';

            echo plugin_lang_get('issueswithoutproject');

            echo ' [' . '<a href="' . plugin_page( 'WrongIssueDetails' ) . '&user_id=' . $user['id'] . '">';
            echo plugin_lang_get('detaillink');
            echo '</a>]';

            echo '</td>';
            echo '</tr>';
         }
      }
   }
}
echo '</tbody>';
echo '</table>';
echo '</div>';

html_page_bottom1();