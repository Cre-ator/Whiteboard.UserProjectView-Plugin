<?php
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_database_api.php';

$print_flag = false;
if ( isset( $_POST[ 'print_flag' ] ) )
{
   $print_flag = true;
}

$project_id = gpc_get_int ( 'project_id', helper_get_current_project () );
if ( ( ALL_PROJECTS == $project_id || project_exists ( $project_id ) )
   && $project_id != helper_get_current_project ()
)
{
   helper_set_current_project ( $project_id );
   print_header_redirect ( $_SERVER[ 'REQUEST_URI' ], true, false, true );
}

$stat_cols = array ();
$issue_amount_thresholds = array ();
$issue_age_thresholds = array ();
for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
{
   $stat_cols[ $stat_index ] = plugin_config_get ( 'CStatSelect' . $stat_index );
   $issue_amount_thresholds[ $stat_index ] = plugin_config_get ( 'IAMThreshold' . $stat_index );
   $issue_age_thresholds[ $stat_index ] = plugin_config_get ( 'IAGThreshold' . $stat_index );
}

$matchcode = calc_matchcodes ( $stat_cols );
$result = process_match_codes ( $matchcode, $stat_cols );
$data_array = $result[ 0 ];
$matchcode_row_index = $result[ 1 ];

if ( plugin_config_get ( 'ShowZIU' ) )
{
   $data_array = process_zero_issue_users ( $data_array, $matchcode_row_index, $project_id, $stat_cols );
}

html_page_top1 ( plugin_lang_get ( 'menu_userprojecttitle' ) );
echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>';
echo '<script type="text/javascript" src="plugins/UserProjectView/javascript/table.js"></script>';
echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css"/>';
if ( !$print_flag )
{
   html_page_top2 ();
   if ( plugin_is_installed ( 'WhiteboardMenu' ) && file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' ) )
   {
      require_once WHITEBOARDMENU_CORE_URI . 'whiteboard_print_api.php';
      $whiteboard_print_api = new whiteboard_print_api();
      $whiteboard_print_api->printWhiteboardMenu ();
   }
}

echo '<div id="manage-user-div" class="form-container">';
print_table_head ();
print_thead ( $stat_cols, $print_flag );
print_tbody ( $data_array, $project_id, $stat_cols, $issue_amount_thresholds, $issue_age_thresholds, $print_flag );
echo '</table>';
echo '</div>';
if ( !$print_flag )
{
   html_page_bottom1 ();
}

/**
 * Get the amount of status columns for the plugin
 *
 * @return int|string
 */
function get_stat_count ()
{
   $stat_count = plugin_config_get ( 'CAmount' );
   if ( $stat_count > PLUGINS_USERPROJECTVIEW_MAX_COLUMNS )
   {
      $stat_count = PLUGINS_USERPROJECTVIEW_MAX_COLUMNS;
   }

   return $stat_count;
}

/**
 * Calculate an array which contains a matchcode for each data row
 *
 * @param $stat_cols
 * @return array
 */
function calc_matchcodes ( $stat_cols )
{
   $userprojectview_database_api = new userprojectview_database_api();

   $matchcode = array ();
   $per_page = 10000;
   $page_count = null;
   $bug_count = null;

   $rows = filter_get_bug_rows ( gpc_get_int ( 'page_number', 1 ), $per_page, $page_count, $bug_count, unserialize ( '' ), null, null, true );
   for ( $row_index = 0; $row_index < count ( $rows ); $row_index++ )
   {
      // bug information
      $bug_id = $rows[ $row_index ]->id;
      $target_version = bug_get_field ( $bug_id, 'target_version' );
      $assigned_project_id = bug_get_field ( $bug_id, 'project_id' );
      $assigned_user_id = bug_get_field ( $bug_id, 'handler_id' );

      // filter config specific bug status
      $irrelevantFlag = setIrrelevantFlag ( bug_get_field ( $bug_id, 'status' ), $stat_cols );
      if ( !in_array ( false, $irrelevantFlag ) )
      {
         continue;
      }

      // user information
      $user_name = '';
      $real_name = '';
      $user_active = true;

      // bug is assigned, etc... but not ASSIGNED TO, etc ...
      if ( $assigned_user_id != 0 )
      {
         $user_name = user_get_name ( $assigned_user_id );
         if ( user_exists ( $assigned_user_id ) )
         {
            $real_name = user_get_realname ( $assigned_user_id );
            $user_active = user_is_enabled ( $assigned_user_id );
         }
      }

      // prepare project information and target version string
      $assigned_project_name = project_get_name ( $assigned_project_id );

      $target_version_date = null;
      if ( $target_version == '' )
      {
         // no target version available -> get main project by project hierarchy
         $main_project_id = getMainProjectByHierarchy ( $assigned_project_id );

         $target_version_string = '';
         $target_version_date = '';
      }
      else
      {
         // identify main project by target version of selected issue
         $main_project_id = $userprojectview_database_api->get_project_by_version ( $target_version );

         $target_version_id = version_get_id ( $target_version, $assigned_project_id );
         $target_version_string = prepare_version_string ( $assigned_project_id, $target_version_id );
         if ( $target_version_id != null )
         {
            $target_version_date = date ( 'Y-m-d', version_get_field ( $target_version_id, 'date_order' ) );
         }
      }

      if ( $assigned_project_id == $main_project_id )
      {
         $assigned_project_id = '';
         $assigned_project_name = '';
      }

      // prepare record matchcode
      $matchcode[ $row_index ] = $assigned_user_id
         . '__' . $user_name
         . '__' . $real_name
         . '__' . $main_project_id
         . '__' . project_get_name ( $main_project_id )
         . '__' . $assigned_project_id
         . '__' . $assigned_project_name
         . '__' . $target_version
         . '__' . $target_version_date
         . '__' . $target_version_string
         . '__' . $user_active;
   }

   return $matchcode;
}

/**
 * Extract the bundled information in the matchcode array and returns it reorganized
 *
 * @param $matchcode
 * @param $stat_cols
 * @return array
 */
function process_match_codes ( $matchcode, $stat_cols )
{
   $userprojectview_database_api = new userprojectview_database_api();

   $data_rows = array ();
   $matchcode_rows = array_count_values ( $matchcode );
   $matchcode_row_count = count ( $matchcode_rows );
   for ( $matchcode_row_index = 0; $matchcode_row_index < $matchcode_row_count; $matchcode_row_index++ )
   {
      // process first entry in array
      $matchcode_row_data = key ( $matchcode_rows );

      // process data string
      $matchcode_row_data_values = explode ( '__', $matchcode_row_data );
      // fill tablerow with data
      $data_rows[ $matchcode_row_index ][ 'userId' ] = $matchcode_row_data_values[ 0 ];
      $data_rows[ $matchcode_row_index ][ 'userName' ] = $matchcode_row_data_values[ 1 ];
      $data_rows[ $matchcode_row_index ][ 'userRealname' ] = $matchcode_row_data_values[ 2 ];
      $data_rows[ $matchcode_row_index ][ 'mainProjectId' ] = $matchcode_row_data_values[ 3 ];
      $data_rows[ $matchcode_row_index ][ 'mainProjectName' ] = $matchcode_row_data_values[ 4 ];
      $data_rows[ $matchcode_row_index ][ 'bugAssignedProjectId' ] = $matchcode_row_data_values[ 5 ];
      $data_rows[ $matchcode_row_index ][ 'bugAssignedProjectName' ] = $matchcode_row_data_values[ 6 ];
      $data_rows[ $matchcode_row_index ][ 'bugTargetVersion' ] = $matchcode_row_data_values[ 7 ];
      $data_rows[ $matchcode_row_index ][ 'bugTargetVersionDate' ] = $matchcode_row_data_values[ 8 ];
      $data_rows[ $matchcode_row_index ][ 'bugTargetVersionPreparedString' ] = $matchcode_row_data_values[ 9 ];
      $data_rows[ $matchcode_row_index ][ 'inactiveUserFlag' ] = $matchcode_row_data_values[ 10 ];
      $data_rows[ $matchcode_row_index ][ 'zeroIssuesFlag' ] = false;

      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         $data_rows[ $matchcode_row_index ][ 'specColumn' . $stat_index ] = '0';
         $soec_column = 'specColumn' . $stat_index;
         if ( $stat_cols[ $stat_index ] != null )
         {
            if ( $matchcode_row_data_values[ 5 ] == '' )
            {
               $data_rows[ $matchcode_row_index ][ $soec_column ] = $userprojectview_database_api->get_amount_issues_by_user_project_version_status ( $matchcode_row_data_values[ 0 ], $matchcode_row_data_values[ 3 ], $matchcode_row_data_values[ 7 ], $stat_cols[ $stat_index ] );
            }
            else
            {
               $data_rows[ $matchcode_row_index ][ $soec_column ] = $userprojectview_database_api->get_amount_issues_by_user_project_version_status ( $matchcode_row_data_values[ 0 ], $matchcode_row_data_values[ 5 ], $matchcode_row_data_values[ 7 ], $stat_cols[ $stat_index ] );
            }
         }
      }
      array_shift ( $matchcode_rows );
   }
   $result = array ();
   $result[ 0 ] = $data_rows;
   $result[ 1 ] = $matchcode_row_index;

   return $result;
}

/**
 * Fill data array with additional users which are not beeing catched by >> calc_matchcodes <<
 *
 * @param $data_rows
 * @param $matchcode_row_index
 * @param $project_id
 * @param $stat_cols
 * @return mixed
 */
function process_zero_issue_users ( $data_rows, $matchcode_row_index, $project_id, $stat_cols )
{
   $userprojectview_database_api = new userprojectview_database_api();

   $all_users = $userprojectview_database_api->get_all_users ();
   $user_rows = array ();
   while ( $user_row = mysqli_fetch_row ( $all_users ) )
   {
      $user_rows[] = $user_row;
   }

   $user_row_count = count ( $user_rows );
   for ( $user_row_index = 0; $user_row_index < $user_row_count; $user_row_index++ )
   {
      $user_id = $user_rows[ $user_row_index ][ 0 ];
      $user_name = user_get_name ( $user_id );
      $real_name = user_get_realname ( $user_id );
      $user_active = false;
      if ( user_exists ( $user_id ) )
      {
         $user_active = user_is_enabled ( $user_id );
      }
      $user_is_assigned_to_project_hierarchy = false;

      if ( $user_active == false )
      {
         continue;
      }

      $additional_row_index = $matchcode_row_index + 1 + $user_row_index;

      $issue_count = '';
      if ( $project_id == 0 )
      {
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $issue_count .= $userprojectview_database_api->get_amount_issues_by_user_project_status ( $user_id, $project_id, $stat_cols[ $stat_index ] );
         }
      }
      else
      {
         $sub_project_ids = array ();
         array_push ( $sub_project_ids, $project_id );
         $temp_sub_project_ids = project_hierarchy_get_all_subprojects ( $project_id );
         foreach ( $temp_sub_project_ids as $temp_sub_project_id )
         {
            array_push ( $sub_project_ids, $temp_sub_project_id );
         }

         foreach ( $sub_project_ids as $sub_project_id )
         {
            $user_is_assigned_to_project = mysqli_fetch_row ( $userprojectview_database_api->check_user_project_assignment ( $user_id, $sub_project_id ) );
            if ( $user_is_assigned_to_project != null )
            {
               $user_is_assigned_to_project_hierarchy = true;
               break;
            }
         }

         if ( !$user_is_assigned_to_project_hierarchy )
         {
            continue;
         }

         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            foreach ( $sub_project_ids as $sub_project_id )
            {
               $issue_count .= $userprojectview_database_api->get_amount_issues_by_user_project_status ( $user_id, $sub_project_id, $stat_cols[ $stat_index ] );
            }
         }
      }

      if ( intval ( $issue_count ) == 0 )
      {
         $data_rows[ $additional_row_index ][ 'userId' ] = $user_id;
         $data_rows[ $additional_row_index ][ 'userName' ] = $user_name;
         $data_rows[ $additional_row_index ][ 'userRealname' ] = $real_name;
         $data_rows[ $additional_row_index ][ 'mainProjectId' ] = '';
         $data_rows[ $additional_row_index ][ 'mainProjectName' ] = '';
         $data_rows[ $additional_row_index ][ 'bugAssignedProjectId' ] = '';
         $data_rows[ $additional_row_index ][ 'bugAssignedProjectName' ] = '';
         $data_rows[ $additional_row_index ][ 'bugTargetVersion' ] = '';
         $data_rows[ $additional_row_index ][ 'bugTargetVersionDate' ] = '';
         $data_rows[ $additional_row_index ][ 'bugTargetVersionPreparedString' ] = '';
         $data_rows[ $additional_row_index ][ 'inactiveUserFlag' ] = $user_active;
         $data_rows[ $additional_row_index ][ 'zeroIssuesFlag' ] = true;

         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $data_rows[ $additional_row_index ][ 'specColumn' . $stat_index ] = '0';
         }
      }
   }
   return $data_rows;
}

/** ********************* table head area *************************************************************************** */

/**
 * Print the head of the plugin table
 *
 * @param $stat_cols
 * @param $print_flag
 */
function print_thead ( $stat_cols, $print_flag )
{
   $colspan = 8;
   if ( plugin_config_get ( 'ShowAvatar' ) )
   {
      $colspan++;
   }

   $dynamic_colspan = get_stat_count () + $colspan;
   $header_colspan = $colspan - 6;

   echo '<thead>';
   print_main_table_head_row ( $dynamic_colspan, $print_flag );
   echo '<tr>';
   print_main_table_head_col ( 'thead_username', 'userName', $header_colspan );
   print_main_table_head_col ( 'thead_realname', 'realName', null );
   print_main_table_head_col ( 'thead_project', 'mainProject', null );
   print_main_table_head_col ( 'thead_subproject', 'assignedProject', null );
   print_main_table_head_col ( 'thead_targetversion', 'targetVersion', null );

   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      echo '<th style="width:150px;" class="headrow_status" bgcolor="' . get_status_color ( $stat_cols[ $stat_index ], null, null ) . '">';
      $status = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
      echo $status [ $stat_cols[ $stat_index ] ];
      echo '</th>';
   }
   echo '<th class="headrow">' . plugin_lang_get ( 'thead_remark' ) . '</th>';
   echo '</tr></thead>';
}

/**
 * Print the row of the head of the plugin table
 *
 * @param $dynamic_colspan
 * @param $print_flag
 */
function print_main_table_head_row ( $dynamic_colspan, $print_flag )
{
   echo '<tr>';
   echo '<td class="form-title" colspan="' . ( $dynamic_colspan - 1 ) . '">' .
      plugin_lang_get ( 'menu_userprojecttitle' ) . ' - ' .
      plugin_lang_get ( 'thead_projects_title' ) .
      project_get_name ( helper_get_current_project () );
   echo '</td>';
   if ( !$print_flag )
   {
      echo '<td><form action="' . plugin_page ( 'UserProject' ) . '&amp;sortVal=userName&amp;sort=ASC' . '" method="post">';
      echo '<input type="submit" name="print_flag" class="button" value="' . lang_get ( 'print' ) . '"/>';
      echo '</form></td>';
   }
   echo '</tr>';
}

/**
 * Print a colzmn of the head of the plugin table
 *
 * @param $lang_string
 * @param $sort_val
 * @param $header_colspan
 */
function print_main_table_head_col ( $lang_string, $sort_val, $header_colspan )
{
   if ( $header_colspan != null )
   {
      echo '<th style="width:15px;" class="group_row_bg" ></th>';
      echo '<th class="headrow" colspan="' . $header_colspan . '">';
   }
   else
   {
      echo '<th class="headrow">';
   }

   echo plugin_lang_get ( $lang_string ) . '&nbsp;';
   if ( $sort_val == 'userName' || $sort_val == 'realName' )
   {
      echo '<a href="' . plugin_page ( 'UserProject' ) . '&amp;sortVal=' . $sort_val . '&amp;sort=ASC">';
      echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif" alt="sort asc" />';
      echo '</a>';
      echo '<a href="' . plugin_page ( 'UserProject' ) . '&amp;sortVal=' . $sort_val . '&amp;sort=DESC">';
      echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif" alt="sort desc" />';
      echo '</a>';
   }
   echo '</th>';
}

/** ***************************************************************************************************************** */

/** ********************* table body area *************************************************************************** */

/**
 * Print the body of the plugin table
 *
 * @param $data_rows
 * @param $project_id
 * @param $stat_cols
 * @param $issue_amount_threshold
 * @param $issue_age_threshold
 * @param $print_flag
 */
function print_tbody ( $data_rows, $project_id, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $print_flag )
{
   $get_sort_val = $_GET[ 'sortVal' ];
   $get_sort_order = $_GET[ 'sort' ];
   $sort_column = get_sort_col ( $get_sort_val, $data_rows );
   $sort_order = get_sort_order ( $get_sort_order );
   if ( $data_rows != null )
   {
      array_multisort ( $sort_column, $sort_order, SORT_NATURAL | SORT_FLAG_CASE, $data_rows );
   }

   $stat_issue_count = array ();
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_count[ $stat_index ] = '';
   }

   $groups = array ();
   $groups[ 0 ] = array ();
   $groups[ 1 ] = array ();
   $groups[ 2 ] = array ();
   $groups[ 3 ] = array ();
   $groups = assign_groups ( $groups, $data_rows );
   /** process each group */

   echo '<tbody>';
   print_group_head_row ( $groups[ 0 ], $data_rows, $stat_cols, 'headrow_user' );
   $head_rows_array = calculate_user_head_rows ( $data_rows );
   foreach ( $head_rows_array as $head_row )
   {
      $head_row_user_id = $head_row[ 0 ];
      $head_row_counter = true;
      for ( $group_index = 0; $group_index < count ( $groups[ 0 ] ); $group_index++ )
      {
         $data_row_index = $groups[ 0 ][ $group_index ];
         $user_id = $data_rows[ $data_row_index ][ 'userId' ];
         if ( $user_id == $head_row_user_id )
         {
            if ( $head_row_counter )
            {
               print_user_head_row ( $head_row, $user_id, $issue_amount_threshold, $print_flag );
               $head_row_counter = false;
            }
            $stat_issue_count = print_user_row ( $data_row_index, $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, false, $print_flag );
         }
      }
   }

   $stat_issue_count = print_group ( $groups[ 1 ], $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, 'headrow_no_issue', $print_flag );
   $stat_issue_count = print_group ( $groups[ 2 ], $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, 'headrow_del_user', $print_flag );
   $stat_issue_count = print_group ( $groups[ 3 ], $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, 'headrow_no_user', $print_flag );
   build_option_panel ( $stat_issue_count, $print_flag );
   echo '</tbody>';
}

/**
 * Assign a specific value how the table should be sorted
 *
 * @param $get_sort_val
 * @param $data_rows
 * @return array|null
 */
function get_sort_col ( $get_sort_val, $data_rows )
{
   $sort_column = null;
   $user_name = array ();
   $real_name = array ();
   $main_project = array ();
   $assigned_project = array ();
   $target_version = array ();
   foreach ( $data_rows as $key => $row )
   {
      $user_name[ $key ] = $row[ 'userName' ];
      $real_name[ $key ] = $row[ 'userRealname' ];
      $main_project[ $key ] = $row[ 'mainProjectName' ];
      $assigned_project[ $key ] = $row[ 'bugAssignedProjectName' ];
      $target_version[ $key ] = $row[ 'bugTargetVersion' ];
   }

   switch ( $get_sort_val )
   {
      case 'userName':
         $sort_column = $user_name;
         break;
      case 'realName':
         $sort_column = $real_name;
         break;
      case 'mainProject':
         $sort_column = $main_project;
         break;
      case 'assignedProject':
         $sort_column = $assigned_project;
         break;
      case 'targetVersion':
         $sort_column = $target_version;
         break;
   }
   return $sort_column;
}

/**
 * Assign ascending or descending sort order
 *
 * @param $get_sort_order
 * @return int|null
 */
function get_sort_order ( $get_sort_order )
{
   $sort_order = null;
   switch ( $get_sort_order )
   {
      case 'ASC':
         $sort_order = SORT_ASC;
         break;
      case 'DESC':
         $sort_order = SORT_DESC;
         break;
   }
   return $sort_order;
}

/**
 * Assigns each data row (from $data_rows) to one of the four specific groups
 *
 * @param $groups
 * @param $data_rows
 * @return mixed
 */
function assign_groups ( $groups, $data_rows )
{
   $group_user_with_issue = $groups[ 0 ];
   $group_user_without_issue = $groups[ 1 ];
   $group_inactive_deleted_user = $groups[ 2 ];
   $group_issues_without_user = $groups[ 3 ];
   for ( $data_row_index = 0; $data_row_index < count ( $data_rows ); $data_row_index++ )
   {
      if ( $data_rows[ $data_row_index ][ 'userId' ] > 0 )
      {
         /** user existiert */
         if ( user_exists ( $data_rows[ $data_row_index ][ 'userId' ] ) )
         {
            /** user ist aktiv */
            if ( user_is_enabled ( $data_rows[ $data_row_index ][ 'userId' ] ) )
            {
               $stat_issue_count = 0;
               for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
               {
                  $stat_issue_count += $data_rows[ $data_row_index ][ 'specColumn' . $stat_index ];
               }

               /** user hat issues */
               if ( $stat_issue_count > 0 )
               {
                  array_push ( $group_user_with_issue, $data_row_index );
               }
               /** user hat keine issues */
               else
               {
                  array_push ( $group_user_without_issue, $data_row_index );
               }
            }
            /** user ist inaktiv */
            else
            {
               array_push ( $group_inactive_deleted_user, $data_row_index );
            }
         }
         /** user existiert nicht */
         else
         {
            array_push ( $group_inactive_deleted_user, $data_row_index );
         }
      }
      /** wenn user_id = 0, gibt es keinen Nutzer */
      else
      {
         array_push ( $group_issues_without_user, $data_row_index );
      }
   }

   $groups[ 0 ] = $group_user_with_issue;
   $groups[ 1 ] = $group_user_without_issue;
   $groups[ 2 ] = $group_inactive_deleted_user;
   $groups[ 3 ] = $group_issues_without_user;
   return $groups;
}

/**
 * Get an array with the head row data for each user
 *
 * @param $data_rows
 * @return array
 */
function calculate_user_head_rows ( $data_rows )
{
   $head_rows_array = array ();
   for ( $data_row_index = 0; $data_row_index < count ( $data_rows ); $data_row_index++ )
   {
      $user_id = $data_rows[ $data_row_index ][ 'userId' ];
      if ( $user_id == 0 )
      {
         continue;
      }

      $head_row = array ();
      $stat_issue_count = array ();
      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         $stat_issue_count[ $stat_index ] = $data_rows[ $data_row_index ][ 'specColumn' . $stat_index ];
      }

      if ( $data_row_index == 0 )
      {
         /** create first headrow entry */
         $head_row[ 0 ] = $user_id;
         $head_row[ 1 ] = $stat_issue_count;

         array_push ( $head_rows_array, $head_row );
      }

      if ( $data_row_index > 0 )
      {
         /** process data of same user now || not and create next headrow */
         $last_user_id = $data_rows[ $data_row_index - 1 ][ 'userId' ];
         if ( $last_user_id == $user_id )
         {
            /** same user */
            for ( $head_rows_array_index = 0; $head_rows_array_index < count ( $head_rows_array ); $head_rows_array_index++ )
            {
               $head_row_array = $head_rows_array[ $head_rows_array_index ];
               /** find his array */
               if ( $head_row_array[ 0 ] == $user_id )
               {
                  /** get his issue counter */
                  $temp_stat_issue_count = $head_row_array[ 1 ];
                  /** add count to existing */
                  for ( $iCounter_index = 1; $iCounter_index <= get_stat_count (); $iCounter_index++ )
                  {
                     $temp_stat_issue_count[ $iCounter_index ] += $data_rows[ $data_row_index ][ 'specColumn' . $iCounter_index ];
                  }
                  /** save modified counter */
                  $head_row_array[ 1 ] = $temp_stat_issue_count;
                  $head_rows_array[ $head_rows_array_index ] = $head_row_array;
               }
            }
         }
         else
         {
            /** new user */
            $head_row[ 0 ] = $user_id;
            $head_row[ 1 ] = $stat_issue_count;

            array_push ( $head_rows_array, $head_row );
         }
      }
   }

   return $head_rows_array;
}

/**
 * Print the head row for a given group
 *
 * @param $group
 * @param $data_rows
 * @param $stat_cols
 * @param $lang_string
 */
function print_group_head_row ( $group, $data_rows, $stat_cols, $lang_string )
{
   $stat_issue_count = array ();
   foreach ( $group as $data_row_index )
   {
      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         $stat_issue_count[ $stat_index ] += $data_rows[ $data_row_index ][ 'specColumn' . $stat_index ];
      }
   }

   if ( !empty( $stat_issue_count ) )
   {
      $colspan = 6;
      if ( plugin_config_get ( 'ShowAvatar' ) )
      {
         $colspan = 7;
      }
      echo '<tr class="clickable" data-level="0" data-status="0" >';
      echo '<td class="icon" />';
      echo '<td class="group_row_bg" colspan="' . $colspan . '">' . plugin_lang_get ( $lang_string ) . '</td>';

      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         if ( $lang_string == 'headrow_no_issue' && $stat_issue_count[ $stat_index ] > 0 )
         {
            $status = $stat_cols[ $stat_index ];
            if ( $status == '10' || $status == '20' || $status == '30' || $status == '40' || $status == '50' )
            {
               echo '<td style="background-color:"' . plugin_config_get ( 'TAMHBGColor' ) . '">' . $stat_issue_count[ $stat_index ] . '</td>';
            }
            else
            {
               echo '<td class="group_row_bg">' . $stat_issue_count[ $stat_index ] . '</td>';
            }
         }
         else
         {
            echo '<td class="group_row_bg">' . $stat_issue_count[ $stat_index ] . '</td>';
         }
      }

      echo '<td class="group_row_bg"/>';
      echo '</tr>';
   }
}

/**
 * Print the head row for a given user
 *
 * @param $head_row
 * @param $user_id
 * @param $issue_amount_threshold
 * @param $print_flag
 */
function print_user_head_row ( $head_row, $user_id, $issue_amount_threshold, $print_flag )
{
   $filter_string = '<a href="search.php?handler_id=' . $user_id . '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';

   echo '<tr class="clickable" data-level="1" data-status="0">';
   echo '<td style="max-width:15px;" />';
   echo '<td class="icon" />';
   echo '<td class="group_row_bg" align="center" style="max-width:25px;">';
   if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
   {
      $avatar = user_get_avatar ( $user_id );
      if ( $print_flag )
      {
         echo '<img class="avatar" src="' . $avatar[ 0 ] . '" alt="avatar" />';
      }
      else
      {
         echo $filter_string . '<img class="avatar" src="' . $avatar[ 0 ] . '" alt="avatar" /></a>';
      }
   }
   echo '</td>';

   echo '<td class="group_row_bg">';
   if ( $print_flag )
   {
      echo user_get_name ( $user_id );
   }
   else
   {
      echo $filter_string . user_get_name ( $user_id ) . '</a>';
   }
   echo '</td>';

   echo '<td class="group_row_bg">';
   if ( $print_flag )
   {
      echo user_get_realname ( $user_id );
   }
   else
   {
      echo $filter_string . user_get_realname ( $user_id ) . '</a>';
   }
   echo '</td>';

   echo '<td class="group_row_bg" colspan="3"/>';
   $stat_issue_count = $head_row[ 1 ];
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_amount_threshold = $issue_amount_threshold[ $stat_index ];
      if ( $stat_issue_amount_threshold <= $stat_issue_count[ $stat_index ] && $stat_issue_amount_threshold > 0 )
      {
         echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">' . $stat_issue_count[ $stat_index ] . '</td>';
      }
      else
      {
         echo '<td class="group_row_bg">' . $stat_issue_count[ $stat_index ] . '</td>';
      }
   }
   echo '<td class="group_row_bg"/>';
   echo '</tr>';
}

/**
 * Prints the data of a given group
 *
 * @param $group
 * @param $lang_string
 * @param $data_rows
 * @param $stat_cols
 * @param $project_id
 * @param $print_flag
 * @param $issue_age_threshold
 * @param $issue_amount_threshold
 * @param $stat_issue_count
 * @return mixed
 */
function print_group ( $group, $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, $lang_string, $print_flag )
{
   print_group_head_row ( $group, $data_rows, $stat_cols, $lang_string );
   foreach ( $group as $data_row_index )
   {
      $stat_issue_count = print_user_row ( $data_row_index, $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, true, $print_flag );
   }

   return $stat_issue_count;
}

/**
 * Print a given user row detailed
 *
 * @param $data_rows
 * @param $data_row_index
 * @param $project_id
 * @param $stat_cols
 * @param $print_flag
 * @param $issue_age_threshold
 * @param $issue_amount_threshold
 * @param $stat_issue_count
 * @param $detailed_flag
 * @return mixed
 */
function print_user_row ( $data_row_index, $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, $detailed_flag, $print_flag )
{
   $data_row_content = $data_rows[ $data_row_index ];
   print_main_table_user_row ( $data_row_content, 2, $stat_cols, true );
   echo '<td/>';
   if ( $print_flag )
   {
      echo '<td/>';
      $link_user_id = generateLinkUserId ( $data_row_content[ 'userId' ] );
      $no_user_flag = setUserflag ( $stat_cols, $data_row_content[ 'userId' ] );
      $zero_issue_flag = $data_row_content[ 'zeroIssuesFlag' ];
      $user_assigned_to_project = checkUserAssignedToProject ( $data_row_content[ 'userId' ], $data_row_content[ 'bugAssignedProjectId' ] );
      $unreachable_issue_flag = setUnreachableIssueFlag ( $user_assigned_to_project );
      get_cell_highlighting ( $link_user_id, $no_user_flag, $zero_issue_flag, $unreachable_issue_flag );
   }
   else
   {
      if ( $detailed_flag )
      {
         build_chackbox_column ( $data_row_content, $project_id, $stat_cols );
      }
      else
      {
         echo '<td/>';
      }
      build_avatar_column ( $data_row_content, $project_id, $stat_cols, $detailed_flag );
   }
   build_user_column ( $data_row_content, $stat_cols, $print_flag, $detailed_flag );
   build_real_name_column ( $data_row_content, $stat_cols, $print_flag, $detailed_flag );
   build_main_project_column ( $data_row_content, $stat_cols, $print_flag );
   build_assigned_project_column ( $data_row_content, $stat_cols, $print_flag );
   target_version_column ( $data_row_content, $stat_cols, $print_flag );
   $stat_issue_count = build_amount_of_issues_column ( $data_row_content, $issue_amount_threshold, $stat_cols, $stat_issue_count, $print_flag );
   build_remark_column ( $data_row_content, $issue_age_threshold, $stat_cols, $print_flag );
   echo '</tr>';

   return $stat_issue_count;
}

/** ***************************************************************************************************************** */

/**
 * compares two dates and returns true if they are not equal (else false).
 *
 * @param $sortVal
 * @param $tableRow
 * @param $tableRowIndex
 * @return bool
 */
function checkout_change_row ( $sortVal, $tableRow, $tableRowIndex )
{
   $change_row_bg = false;
   switch ( $sortVal )
   {
      case 'realName':
      case 'userName':
         $userName = $tableRow[ $tableRowIndex ][ 'userName' ];
         $userNameOld = $tableRow[ $tableRowIndex - 1 ][ 'userName' ];
         $change_row_bg = ( $userName !== $userNameOld );
         break;

      case 'mainProject':
         $mainProjectName = $tableRow[ $tableRowIndex ][ 'mainProjectName' ];
         $mainProjectNameOld = $tableRow[ $tableRowIndex - 1 ][ 'mainProjectName' ];
         $change_row_bg = ( $mainProjectName !== $mainProjectNameOld );
         break;

      case 'assignedProject':
         $bugAssignedProjectName = $tableRow[ $tableRowIndex ][ 'bugAssignedProjectName' ];
         $bugAssignedProjectNameOld = $tableRow[ $tableRowIndex - 1 ][ 'bugAssignedProjectName' ];
         $change_row_bg = ( $bugAssignedProjectName !== $bugAssignedProjectNameOld );
         break;

      case 'targetVersion':
         $bugTargetVersion = $tableRow[ $tableRowIndex ][ 'bugTargetVersion' ];
         $bugTargetVersionOld = $tableRow[ $tableRowIndex - 1 ][ 'bugTargetVersion' ];
         $change_row_bg = ( $bugTargetVersion !== $bugTargetVersionOld );
         break;
   }
   return $change_row_bg;
}

function build_chackbox_column ( $table_row_content, $t_project_id, $statCols )
{
   $userId = $table_row_content[ 'userId' ];
   $mainProjectId = $table_row_content[ 'mainProjectId' ];
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $pProject = prepareParentProject ( $t_project_id, $bugAssignedProjectId, $mainProjectId );
   $noUserFlag = setUserflag ( $statCols, $userId );

   echo '<td width="15px">';
   if ( !$noUserFlag )
   {
      echo '<form action="' . plugin_page ( 'UserProject_Option' ) . '" method="post">';
      echo '<input type="checkbox" name="dataRow[]" value="' . $userId . '__' . $pProject . '" />';
      echo '</form>';
   }
   echo '</td>';
}

function build_avatar_column ( $table_row_content, $t_project_id, $statCols, $detailed_flag )
{
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $userId = $table_row_content[ 'userId' ];
   $linkUserId = generateLinkUserId ( $userId );
   $noUserFlag = setUserflag ( $statCols, $userId );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $isAssignedToProject = checkUserAssignedToProject ( $userId, $bugAssignedProjectId );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );

   $iA_background_color = plugin_config_get ( 'IAUHBGColor' );
   $uR_background_color = plugin_config_get ( 'URIUHBGColor' );
   $nU_background_color = plugin_config_get ( 'NUIHBGColor' );
   $zI_background_color = plugin_config_get ( 'ZIHBGColor' );
   if ( ( !user_exists ( $linkUserId ) && !$noUserFlag )
      || ( user_exists ( $linkUserId ) && $linkUserId != '0' && user_get_field ( $linkUserId, 'enabled' ) == '0' && plugin_config_get ( 'IAUHighlighting' ) )
   )
   {
      echo '<td align="center" width="25px" style="background-color:' . $iA_background_color . '">';
   }
   elseif ( $zeroIssuesFlag && plugin_config_get ( 'ZIHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="background-color:' . $zI_background_color . '">';
   }
   elseif ( $noUserFlag && plugin_config_get ( 'NUIHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="background-color:' . $nU_background_color . '">';
   }
   elseif ( $unreachableIssueFlag && plugin_config_get ( 'URIUHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="background-color:' . $uR_background_color . '">';
   }
   else
   {
      echo '<td class="user_row_bg" align="center" width="25px">';
   }

   if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
   {
      if ( $detailed_flag )
      {
         if ( user_exists ( $userId ) )
         {
            if ( access_has_global_level ( $userAccessLevel ) )
            {
               $filterString = '<a href="search.php?handler_id=' . $linkUserId . '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
               echo $filterString;
            }

            if ( config_get ( 'show_avatar' ) && $userAccessLevel >= config_get ( 'show_avatar_threshold' ) )
            {
               if ( $userId > 0 )
               {
                  $assocArray = user_get_avatar ( $userId );
                  echo '<img class="avatar" src="' . $assocArray [ 0 ] . '" />';
               }
            }

            if ( access_has_global_level ( $userAccessLevel ) )
            {
               echo '</a>';
            }
         }
      }
      else
      {
         $mainProjectId = $table_row_content[ 'mainProjectId' ];
         $pProject = prepareParentProject ( $t_project_id, $bugAssignedProjectId, $mainProjectId );
         if ( !$noUserFlag )
         {
            echo '<form action="' . plugin_page ( 'UserProject_Option' ) . '" method="post">';
            echo '<input type="checkbox" name="dataRow[]" value="' . $userId . '__' . $pProject . '" />';
            echo '</form>';
         }
      }
   }
   echo '</td>';
}

function get_cell_highlighting ( $user_id, $no_assigned_user, $zero_issues, $unreachable_issues )
{
   $iA_background_color = plugin_config_get ( 'IAUHBGColor' );
   $uR_background_color = plugin_config_get ( 'URIUHBGColor' );
   $nU_background_color = plugin_config_get ( 'NUIHBGColor' );
   $zI_background_color = plugin_config_get ( 'ZIHBGColor' );
   if ( ( !user_exists ( $user_id ) && !$no_assigned_user )
      || ( user_exists ( $user_id ) && $user_id != '0' && user_get_field ( $user_id, 'enabled' ) == '0' && plugin_config_get ( 'IAUHighlighting' ) )
   )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . $iA_background_color . '">';
   }
   elseif ( $zero_issues && plugin_config_get ( 'ZIHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . $zI_background_color . '">';
   }
   elseif ( $no_assigned_user && plugin_config_get ( 'NUIHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . $nU_background_color . '">';
   }
   elseif ( $unreachable_issues && plugin_config_get ( 'URIUHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . $uR_background_color . '">';
   }
   else
   {
      echo '<td class="user_row_bg" style="white-space:nowrap">';
   }
}

function build_user_column ( $table_row_content, $statCols, $print_flag, $detailed_flag )
{
   $linkUserId = generateLinkUserId ( $table_row_content[ 'userId' ] );
   $userName = $table_row_content[ 'userName' ];
   $noUserFlag = setUserflag ( $statCols, $table_row_content[ 'userId' ] );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $isAssignedToProject = checkUserAssignedToProject ( $table_row_content[ 'userId' ], $table_row_content[ 'bugAssignedProjectId' ] );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $linkUserId, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );

   if ( $detailed_flag )
   {
      if ( access_has_global_level ( $userAccessLevel ) && !$print_flag )
      {
         $filterString = '<a href="search.php?handler_id=' . $linkUserId . '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
         echo $filterString;
         if ( user_exists ( $linkUserId ) )
         {
            echo $userName;
         }
         else
         {
            echo '<s>' . $userName . '</s>';
         }
         echo '</a>';
      }
      else
      {
         if ( user_exists ( $linkUserId ) )
         {
            echo $userName;
         }
         else
         {
            echo '<s>' . $userName . '</s>';
         }
      }
   }
   echo '</td>';
}

function build_real_name_column ( $table_row_content, $statCols, $print_flag, $detailed_flag )
{
   $linkUserId = generateLinkUserId ( $table_row_content[ 'userId' ] );
   $userRealname = $table_row_content[ 'userRealname' ];
   $noUserFlag = setUserflag ( $statCols, $table_row_content[ 'userId' ] );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $isAssignedToProject = checkUserAssignedToProject ( $table_row_content[ 'userId' ], $table_row_content[ 'bugAssignedProjectId' ] );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $linkUserId, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );

   if ( $detailed_flag )
   {
      if ( access_has_global_level ( $userAccessLevel ) && !$print_flag )
      {
         $filterString = '<a href="search.php?handler_id=' . $linkUserId . '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
         echo $filterString;
         echo $userRealname;
         echo '</a>';
      }
      else
      {
         echo $userRealname;
      }
   }
   echo '</td>';
}

function build_main_project_column ( $table_row_content, $statCols, $print_flag )
{
   $linkUserId = generateLinkUserId ( $table_row_content[ 'userId' ] );
   $mainProjectId = $table_row_content[ 'mainProjectId' ];
   $mainProjectName = $table_row_content[ 'mainProjectName' ];
   $noUserFlag = setUserflag ( $statCols, $table_row_content[ 'userId' ] );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $isAssignedToProject = checkUserAssignedToProject ( $table_row_content[ 'userId' ], $table_row_content[ 'bugAssignedProjectId' ] );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $linkUserId, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
   if ( access_has_global_level ( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?project_id=' . $mainProjectId . '&amp;handler_id=' . $linkUserId .
         '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
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

function build_assigned_project_column ( $table_row_content, $statCols, $print_flag )
{
   $linkUserId = generateLinkUserId ( $table_row_content[ 'userId' ] );
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $bugAssignedProjectName = $table_row_content[ 'bugAssignedProjectName' ];
   $noUserFlag = setUserflag ( $statCols, $table_row_content[ 'userId' ] );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $isAssignedToProject = checkUserAssignedToProject ( $table_row_content[ 'userId' ], $table_row_content[ 'bugAssignedProjectId' ] );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $linkUserId, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
   if ( access_has_global_level ( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&amp;handler_id=' . $linkUserId .
         '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
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

function target_version_column ( $table_row_content, $statCols, $print_flag )
{
   $linkUserId = generateLinkUserId ( $table_row_content[ 'userId' ] );
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $bugTargetVersion = $table_row_content[ 'bugTargetVersion' ];
   $bugTargetVersionDate = $table_row_content[ 'bugTargetVersionDate' ];
   $bugTargetVersionPreparedString = $table_row_content[ 'bugTargetVersionPreparedString' ];
   $noUserFlag = setUserflag ( $statCols, $table_row_content[ 'userId' ] );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $isAssignedToProject = checkUserAssignedToProject ( $table_row_content[ 'userId' ], $table_row_content[ 'bugAssignedProjectId' ] );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $linkUserId, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
   echo $bugTargetVersionDate . ' ';
   if ( access_has_global_level ( $userAccessLevel ) && !$print_flag )
   {
      $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&amp;handler_id=' . $linkUserId .
         '&amp;sticky_issues=on&amp;target_version=' . $bugTargetVersion . '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
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

function build_amount_of_issues_column ( $table_row_content, $issueThresholds, $statCols, $specColumnIssueAmount, $print_flag )
{
   $linkUserId = generateLinkUserId ( $table_row_content[ 'userId' ] );
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $bugTargetVersion = $table_row_content[ 'bugTargetVersion' ];

   $issueCounter = array ();
   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      $issueCounter[ $statColIndex ] = $table_row_content[ 'specColumn' . $statColIndex ];
   }

   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      $issueThreshold = $issueThresholds[ $statColIndex ];
      $specStatus = $statCols[ $statColIndex ];
      $issueAmount = $issueCounter[ $statColIndex ];
      $specColumnIssueAmount[ $statColIndex ] += $issueAmount;
      if ( $issueThreshold < $issueAmount && $issueThreshold > 0 )
      {
         echo '<td style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '; width:150px;">';
      }
      else
      {
         echo '<td style="background-color:' . get_status_color ( $statCols[ $statColIndex ], null, null ) . '; width:150px;">';
      }

      if ( !$print_flag )
      {
         $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&amp;status_id=' . $specStatus .
            '&amp;handler_id=' . $linkUserId . '&amp;sticky_issues=on&amp;target_version=' . $bugTargetVersion .
            '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
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

function build_remark_column ( $table_row_content, $issueAgeThresholds, $statCols, $print_flag )
{
   $userprojectview_database_api = new userprojectview_database_api();

   $userId = $table_row_content[ 'userId' ];
   $linkUserId = generateLinkUserId ( $userId );
   $mainProjectId = $table_row_content[ 'mainProjectId' ];
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $bugTargetVersion = $table_row_content[ 'bugTargetVersion' ];
   $inactiveUserFlag = $table_row_content[ 'inactiveUserFlag' ];
   $noUserFlag = setUserflag ( $statCols, $userId );
   $zeroIssuesFlag = $table_row_content[ 'zeroIssuesFlag' ];
   $isAssignedToProject = checkUserAssignedToProject ( $userId, $bugAssignedProjectId );
   $unreachableIssueFlag = setUnreachableIssueFlag ( $isAssignedToProject );

   get_cell_highlighting ( $linkUserId, $noUserFlag, $zeroIssuesFlag, $unreachableIssueFlag );
   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      $issueAgeThreshold = $issueAgeThresholds[ $statColIndex ];
      if ( $bugAssignedProjectId == null && $mainProjectId == null )
      {
         continue;
      }

      $specStatus = $statCols[ $statColIndex ];
      if ( $specStatus == USERPROJECTVIEW_ASSIGNED_STATUS && $issueAgeThreshold > 0
         || $specStatus == USERPROJECTVIEW_FEEDBACK_STATUS && $issueAgeThreshold > 0
         || $specStatus == 40 && $issueAgeThreshold > 0
      )
      {
         $specIssueResult = $userprojectview_database_api->get_issues_by_user_project_version_status ( $userId, $bugAssignedProjectId, $bugTargetVersion, $specStatus );
         $assocArray = mysqli_fetch_row ( $specIssueResult );
         $specIssues = array ();
         while ( $specIssue = $assocArray [ 0 ] )
         {
            $specIssues[] = $specIssue;
            $assocArray = mysqli_fetch_row ( $specIssueResult );
         }

         if ( $specIssues != null )
         {
            $specTimeDifference = calculateTimeDifference ( $specIssues )[ 0 ];
            $oldestSpecIssue = calculateTimeDifference ( $specIssues )[ 1 ];

            if ( $specTimeDifference > $issueAgeThreshold && !$print_flag )
            {
               $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId . '&amp;search=' . $oldestSpecIssue .
                  '&amp;status_id=' . $specStatus . '&amp;handler_id=' . $linkUserId . '&amp;sticky_issues=on&target_version=' . $bugTargetVersion .
                  '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
               echo $filterString;
               $assocArray = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
               echo $assocArray [ $specStatus ] .
                  ' ' . plugin_lang_get ( 'remark_since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get ( 'remark_day' ) . '<br/>';
               echo '</a>';
            }
            else
            {
               $assocArray = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
               echo $assocArray [ $specStatus ] .
                  ' ' . plugin_lang_get ( 'remark_since' ) . ' ' . $specTimeDifference . ' ' . plugin_lang_get ( 'remark_day' ) . '<br/>';
            }
         }
      }
   }

   if ( $unreachableIssueFlag )
   {
      $unreachIssueStatusValue = plugin_config_get ( 'URIThreshold' );
      $unreachIssueStatusCount = count ( $unreachIssueStatusValue );
      $filterString = '<a href="search.php?project_id=' . $bugAssignedProjectId;
      $filterString .= prepareFilterString ( $unreachIssueStatusCount, $unreachIssueStatusValue );
      $filterString .= '&amp;handler_id=' . $linkUserId .
         '&amp;sticky_issues=on&amp;target_version=' . $bugTargetVersion .
         '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
      echo plugin_lang_get ( 'remark_noProject' ) . ' [';
      echo $filterString;
      echo plugin_lang_get ( 'remark_showURIssues' );
      echo '</a>]<br/>';
   }
   if ( !$inactiveUserFlag )
   {
      echo plugin_lang_get ( 'remark_IAUser' ) . '<br/>';
   }
   if ( $zeroIssuesFlag )
   {
      echo plugin_lang_get ( 'remark_ZIssues' ) . '<br/>';
   }
   if ( $noUserFlag )
   {
      echo plugin_lang_get ( 'remark_noUser' ) . '<br/>';
   }
   echo '</td>';
}

function build_option_panel ( $specColumnIssueAmount, $print_flag )
{
   $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $fixColspan = 8;
   if ( plugin_config_get ( 'ShowAvatar' ) )
   {
      $fixColspan = 9;
   }

   echo '<tr>';
   $footerColspan = $fixColspan - 1;
   echo '<td colspan="' . $footerColspan . '">';
   if ( !$print_flag )
   {
      if ( access_has_global_level ( $userAccessLevel ) )
      {
         ?>
         <form name="options" action="" method="get">
            <label for="option"></label>
            <select id="option" name="option">
               <option value="removeSingle"><?php echo plugin_lang_get ( 'remove_selectSingle' ) ?></option>
               <option value="removeAll"><?php echo plugin_lang_get ( 'remove_selectAll' ) ?></option>
            </select>
            <input type="submit" name="formSubmit" class="button" value="<?php echo lang_get ( 'ok' ); ?>"/>
         </form>
         <?php
      }
   }
   echo '</td>';
   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      echo '<td>' . $specColumnIssueAmount[ $statColIndex ] . '</td>';
   }
   echo '<td />';
   echo '</tr>';
}

function print_main_table_user_row ( $table_row_content, $row_index, $statCols, $ignore_row_color )
{
   $iA_background_color = plugin_config_get ( 'IAUHBGColor' );
   $uR_background_color = plugin_config_get ( 'URIUHBGColor' );
   $nU_background_color = plugin_config_get ( 'NUIHBGColor' );
   $zI_background_color = plugin_config_get ( 'ZIHBGColor' );

   $user_id = $table_row_content[ 'userId' ];
   $zero_issues_flag = $table_row_content[ 'zeroIssuesFlag' ];
   $bugAssignedProjectId = $table_row_content[ 'bugAssignedProjectId' ];
   $isAssignedToProject = checkUserAssignedToProject ( $user_id, $bugAssignedProjectId );
   $unreachable_issue_flag = setUnreachableIssueFlag ( $isAssignedToProject );
   $no_user_flag = setUserflag ( $statCols, $user_id );

   if ( !$ignore_row_color )
   {
      if ( user_exists ( $user_id ) && $user_id != '0' && user_get_field ( $user_id, 'enabled' ) == '0' && plugin_config_get ( 'IAUHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . $iA_background_color . '">';
      }
      elseif ( $zero_issues_flag && plugin_config_get ( 'ZIHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . $zI_background_color . '">';
      }
      elseif ( $no_user_flag && plugin_config_get ( 'NUIHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . $nU_background_color . '">';
      }
      elseif ( $unreachable_issue_flag && plugin_config_get ( 'URIUHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . $uR_background_color . '">';
      }
      else
      {
         echo '<tr class="info" data-level="2" data-status="1" "row-' . $row_index . '">';
      }
   }
   else
   {
      echo '<tr class="info" data-level="2" data-status="1">';
   }
}

function print_table_head ()
{
   if ( substr ( MANTIS_VERSION, 0, 4 ) == '1.2.' )
   {
      echo '<table class="width100" cellspacing="1">';
   }
   else
   {
      echo '<table>';
   }
}

function prepareFilterString ( $unreach_issue_status_count, $unreach_issue_status_value )
{
   $filter_string = '';
   for ( $unreachIssueStatusIndex = 0; $unreachIssueStatusIndex < $unreach_issue_status_count; $unreachIssueStatusIndex++ )
   {
      if ( $unreach_issue_status_value[ $unreachIssueStatusIndex ] != null )
      {
         if ( substr ( MANTIS_VERSION, 0, 4 ) == '1.2.' )
         {
            $filter_string = '&amp;status_id[]=' . $unreach_issue_status_value[ $unreachIssueStatusIndex ];
         }
         else
         {
            $filter_string = '&amp;status[]=' . $unreach_issue_status_value[ $unreachIssueStatusIndex ];
         }
      }
   }
   return $filter_string;
}

function setIrrelevantFlag ( $gug_status, $stat_cols )
{
   $irrelevant_flag = array ();
   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      if ( $gug_status != $stat_cols[ $statColIndex ] )
      {
         $irrelevant_flag[ $statColIndex ] = true;
      }
      else
      {
         $irrelevant_flag[ $statColIndex ] = false;
      }
   }

   return $irrelevant_flag;
}

function getMainProjectByHierarchy ( $bug_assigned_project_id )
{
   $parent_project = project_hierarchy_get_parent ( $bug_assigned_project_id, false );
   if ( project_hierarchy_is_toplevel ( $bug_assigned_project_id ) )
   {
      $bug_main_project_id = $bug_assigned_project_id;
   }
   else
   {
      // selected project is subproject
      while ( project_hierarchy_is_toplevel ( $parent_project, false ) == false )
      {
         $parent_project = project_hierarchy_get_parent ( $parent_project, false );

         if ( project_hierarchy_is_toplevel ( $parent_project ) )
         {
            break;
         }
      }
      $bug_main_project_id = $parent_project;
   }

   return $bug_main_project_id;
}

function getBugAssignedProjectId ( $bug_assigned_project_id, $main_project_id )
{
   if ( $bug_assigned_project_id == '' )
   {
      $bug_assigned_project_id = $main_project_id;
   }

   return $bug_assigned_project_id;
}

function generateLinkUserId ( $user_id )
{
   $link_user_id = $user_id;
   if ( $user_id == '0' )
   {
      $link_user_id = '-2';
   }

   return $link_user_id;
}

function checkUserAssignedToProject ( $user_id, $bug_assigned_project_id )
{
   include_once config_get_global ( 'plugin_path' ) . plugin_get_current () . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectview_database_api.php';

   $userprojectview_database_api = new userprojectview_database_api();

   $assigned_to_project = '0';
   if ( $user_id != '0' && $bug_assigned_project_id != '' && user_exists ( $user_id ) )
   {
      if ( !user_is_administrator ( $user_id ) )
      {
         $assigned_to_project = mysqli_fetch_row ( $userprojectview_database_api->check_user_project_assignment ( $user_id, $bug_assigned_project_id ) );
      }
   }

   return $assigned_to_project;
}

function setUnreachableIssueFlag ( $assigned_to_project )
{
   $unreach_issue_flag = false;
   if ( $assigned_to_project == null || $assigned_to_project == '' )
   {
      $unreach_issue_flag = true;
   }

   return $unreach_issue_flag;
}

function prepareParentProject ( $project_id, $bug_assigned_project_id, $main_project_id )
{
   $p_project = '';
   if ( $bug_assigned_project_id == '' && $main_project_id == '' )
   {
      $p_project = $project_id;
   }
   elseif ( $bug_assigned_project_id == '' && $main_project_id != '' )
   {
      $p_project = $main_project_id;
   }
   elseif ( $bug_assigned_project_id != '' )
   {
      $p_project = $bug_assigned_project_id;
   }

   return $p_project;
}

function setUserflag ( $stat_cols, $user_id )
{
   $no_user_flag = false;
   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      $spec_status = $stat_cols[ $statColIndex ];
      if ( $user_id == '0' && $spec_status == USERPROJECTVIEW_ASSIGNED_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_FEEDBACK_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_RESOLVED_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_CLOSED_STATUS
      )
      {
         $no_user_flag = true;
      }
   }

   return $no_user_flag;
}

function calculateTimeDifference ( $spec_issues )
{
   $act_time = time ();
   $oldest_spec_issue_date = time ();
   $oldest_spec_issue = null;
   foreach ( $spec_issues as $spec_issue )
   {
      $spec_issue_last_update = intval ( bug_get_field ( $spec_issue, 'last_updated' ) );
      if ( $spec_issue_last_update < $oldest_spec_issue_date )
      {
         $oldest_spec_issue_date = $spec_issue_last_update;
         $oldest_spec_issue = $spec_issue;
      }
   }
   $spec_time_difference = round ( ( ( $act_time - $oldest_spec_issue_date ) / 86400 ), 0 );

   $result = array ();
   $result[ 0 ] = $spec_time_difference;
   $result[ 1 ] = $oldest_spec_issue;

   return $result;
}