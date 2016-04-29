<?php

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
   $databaseapi = new databaseapi();

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
      $irrelevant = set_irrelevant ( bug_get_field ( $bug_id, 'status' ), $stat_cols );
      if ( !in_array ( false, $irrelevant ) )
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
         $main_project_id = get_main_project_id ( $assigned_project_id );

         $target_version_string = '';
         $target_version_date = '';
      }
      else
      {
         // identify main project by target version of selected issue
         $main_project_id = $databaseapi->get_project_by_version ( $target_version );

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
   $databaseapi = new databaseapi();

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
               $data_rows[ $matchcode_row_index ][ $soec_column ] = $databaseapi->get_amount_issues_by_user_project_version_status ( $matchcode_row_data_values[ 0 ], $matchcode_row_data_values[ 3 ], $matchcode_row_data_values[ 7 ], $stat_cols[ $stat_index ] );
            }
            else
            {
               $data_rows[ $matchcode_row_index ][ $soec_column ] = $databaseapi->get_amount_issues_by_user_project_version_status ( $matchcode_row_data_values[ 0 ], $matchcode_row_data_values[ 5 ], $matchcode_row_data_values[ 7 ], $stat_cols[ $stat_index ] );
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
function process_no_issue_users ( $data_rows, $matchcode_row_index, $project_id, $stat_cols )
{
   $databaseapi = new databaseapi();

   $all_users = $databaseapi->get_all_users ();
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
            $issue_count .= $databaseapi->get_amount_issues_by_user_project_status ( $user_id, $project_id, $stat_cols[ $stat_index ] );
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
            $user_is_assigned_to_project = mysqli_fetch_row ( $databaseapi->check_user_project_assignment ( $user_id, $sub_project_id ) );
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
               $issue_count .= $databaseapi->get_amount_issues_by_user_project_status ( $user_id, $sub_project_id, $stat_cols[ $stat_index ] );
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

/**
 * @param $user_id
 * @return string
 */
function get_link_user_id ( $user_id )
{
   $link_user_id = $user_id;
   if ( $user_id == '0' )
   {
      $link_user_id = '-2';
   }

   return $link_user_id;
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
               /** TODO user ohne projektzuweisung werden angezeigt
                */
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
function process_group ( $group, $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, $lang_string, $print_flag )
{
   print_group_head_row ( $group, $data_rows, $stat_cols, $lang_string );
   foreach ( $group as $data_row_index )
   {
      $stat_issue_count = print_user_row ( $data_row_index, $data_rows, $stat_cols, $project_id, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, true, $print_flag );
   }

   return $stat_issue_count;
}

/**
 * Get the specific cell colour  for each situation (no issues, etc.. )
 *
 * @param $user_id
 * @param $no_user
 * @param $no_issue
 * @param $unreachable_issue
 */
function get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue )
{
   if ( ( !user_exists ( $user_id ) && !$no_user )
      || ( user_exists ( $user_id ) && $user_id != '0' && user_get_field ( $user_id, 'enabled' ) == '0' && plugin_config_get ( 'IAUHighlighting' ) )
   )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . plugin_config_get ( 'IAUHBGColor' ) . '">';
   }
   elseif ( $no_issue && plugin_config_get ( 'ZIHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . plugin_config_get ( 'ZIHBGColor' ) . '">';
   }
   elseif ( $no_user && plugin_config_get ( 'NUIHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . plugin_config_get ( 'NUIHBGColor' ) . '">';
   }
   elseif ( $unreachable_issue && plugin_config_get ( 'URIUHighlighting' ) )
   {
      echo '<td align="center" width="25px" style="white-space:nowrap; background-color:' . plugin_config_get ( 'URIUHBGColor' ) . '">';
   }
   else
   {
      echo '<td class="user_row_bg" style="white-space:nowrap">';
   }
}

/**
 * Print a row for a given user in the plugin table
 *
 * @param $data_row
 * @param $stat_cols
 * @param $ignore_row_color
 */
function get_user_row_cell_highlighting ( $data_row, $stat_cols, $ignore_row_color )
{
   $user_id = $data_row[ 'userId' ];
   $no_issue = $data_row[ 'zeroIssuesFlag' ];
   $assigned_project_id = $data_row[ 'bugAssignedProjectId' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $no_user = get_no_user ( $stat_cols, $user_id );

   if ( !$ignore_row_color )
   {
      if ( user_exists ( $user_id ) && $user_id != '0' && user_get_field ( $user_id, 'enabled' ) == '0' && plugin_config_get ( 'IAUHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . plugin_config_get ( 'IAUHBGColor' ) . '">';
      }
      elseif ( $no_issue && plugin_config_get ( 'ZIHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . plugin_config_get ( 'ZIHBGColor' ) . '">';
      }
      elseif ( $no_user && plugin_config_get ( 'NUIHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . plugin_config_get ( 'NUIHBGColor' ) . '">';
      }
      elseif ( $unreachable_issue && plugin_config_get ( 'URIUHighlighting' ) )
      {
         echo '<tr class="info" data-level="2" data-status="1" style="background-color:' . plugin_config_get ( 'URIUHBGColor' ) . '">';
      }
      else
      {
         echo '<tr class="info" data-level="2" data-status="1">';
      }
   }
   else
   {
      echo '<tr class="info" data-level="2" data-status="1">';
   }
}

/**
 * Prepare a filter string which depends on the mantis version
 *
 * @param $unreachable_issue_status_count
 * @param $unreach_issue_status
 * @return string
 */
function prepare_filter_string ( $unreachable_issue_status_count, $unreach_issue_status )
{
   $filter_string = '';
   for ( $unreachable_issue_status_index = 0; $unreachable_issue_status_index < $unreachable_issue_status_count; $unreachable_issue_status_index++ )
   {
      if ( $unreach_issue_status[ $unreachable_issue_status_index ] != null )
      {
         if ( substr ( MANTIS_VERSION, 0, 4 ) == '1.2.' )
         {
            $filter_string = '&amp;status_id[]=' . $unreach_issue_status[ $unreachable_issue_status_index ];
         }
         else
         {
            $filter_string = '&amp;status[]=' . $unreach_issue_status[ $unreachable_issue_status_index ];
         }
      }
   }
   return $filter_string;
}

/**
 * Tag a bug based on his status with a flag. cause of the flag a bug can be ignored by the plugin table
 *
 * @param $bug_status
 * @param $stat_cols
 * @return array
 */
function set_irrelevant ( $bug_status, $stat_cols )
{
   $irrelevant = array ();
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      if ( $bug_status != $stat_cols[ $stat_index ] )
      {
         $irrelevant[ $stat_index ] = true;
      }
      else
      {
         $irrelevant[ $stat_index ] = false;
      }
   }

   return $irrelevant;
}

/**
 * Get the main project id for a given project id
 *
 * @param $assigned_project_id
 * @return int
 */
function get_main_project_id ( $assigned_project_id )
{
   $parent_project = project_hierarchy_get_parent ( $assigned_project_id, false );
   if ( project_hierarchy_is_toplevel ( $assigned_project_id ) )
   {
      $bug_main_project_id = $assigned_project_id;
   }
   else
   {
      /** selected project is subproject */
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

/**
 * Get the assigned project id (main project id, if empty)
 *
 * @param $assigned_project_id
 * @param $main_project_id
 * @return mixed
 */
function get_assigned_project_id ( $assigned_project_id, $main_project_id )
{
   if ( $assigned_project_id == '' )
   {
      $assigned_project_id = $main_project_id;
   }

   return $assigned_project_id;
}

/**
 * Return if the given user is assigned to a given project id
 *
 * @param $user_id
 * @param $assigned_project_id
 * @return array|null|string
 */
function get_assigned_to_project ( $user_id, $assigned_project_id )
{
   include_once config_get_global ( 'plugin_path' ) . plugin_get_current () . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'databaseapi.php';
   $databaseapi = new databaseapi();

   $assigned_to_project = '0';
   if ( $user_id != '0' && $assigned_project_id != '' && user_exists ( $user_id ) )
   {
      if ( !user_is_administrator ( $user_id ) )
      {
         $assigned_to_project = mysqli_fetch_row ( $databaseapi->check_user_project_assignment ( $user_id, $assigned_project_id ) );
      }
   }

   return $assigned_to_project;
}

/**
 * Returns true, if the user has unreachable issues
 *
 * @param $assigned_to_project
 * @return bool
 */
function get_unreachable_issue ( $assigned_to_project )
{
   $unreachable_issue = false;
   if ( $assigned_to_project == null || $assigned_to_project == '' )
   {
      $unreachable_issue = true;
   }

   return $unreachable_issue;
}

/**
 * Return the parent project id to a given project id
 *
 * @param $project_id
 * @param $assigned_project_id
 * @param $main_project_id
 * @return string
 */
function get_parent_project_id ( $project_id, $assigned_project_id, $main_project_id )
{
   $parent_project_id = '';
   if ( $assigned_project_id == '' && $main_project_id == '' )
   {
      $parent_project_id = $project_id;
   }
   elseif ( $assigned_project_id == '' && $main_project_id != '' )
   {
      $parent_project_id = $main_project_id;
   }
   elseif ( $assigned_project_id != '' )
   {
      $parent_project_id = $assigned_project_id;
   }

   return $parent_project_id;
}

/**
 * Return true if the given user is not assigned to the issues of each status column
 *
 * @param $stat_cols
 * @param $user_id
 * @return bool
 */
function get_no_user ( $stat_cols, $user_id )
{
   $no_user = false;
   for ( $statColIndex = 1; $statColIndex <= get_stat_count (); $statColIndex++ )
   {
      $spec_status = $stat_cols[ $statColIndex ];
      if ( $user_id == '0' && $spec_status == USERPROJECTVIEW_ASSIGNED_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_FEEDBACK_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_RESOLVED_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_CLOSED_STATUS
      )
      {
         $no_user = true;
      }
   }

   return $no_user;
}

/**
 * Return the passed time and issue id for the OLDEST issue of a group
 *
 * @param $stat_issue_ids
 * @return array
 */
function calculate_time_difference ( $stat_issue_ids )
{
   $act_time = time ();
   $oldest_stat_issue_date = time ();
   $oldest_stat_issue_id = null;
   foreach ( $stat_issue_ids as $stat_issue_id )
   {
      $stat_issue_last_update = intval ( bug_get_field ( $stat_issue_id, 'last_updated' ) );
      if ( $stat_issue_last_update < $oldest_stat_issue_date )
      {
         $oldest_stat_issue_date = $stat_issue_last_update;
         $oldest_stat_issue_id = $stat_issue_id;
      }
   }
   $stat_issue_time_difference = round ( ( ( $act_time - $oldest_stat_issue_date ) / 86400 ), 0 );

   $result = array ();
   $result[ 0 ] = $stat_issue_time_difference;
   $result[ 1 ] = $oldest_stat_issue_id;

   return $result;
}
