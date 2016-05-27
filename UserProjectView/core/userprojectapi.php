<?php

/**
 * Returns true, if the used mantis version is a 1.2.x release
 *
 * @return bool
 */
function is_mantis_rel ()
{
   return substr ( MANTIS_VERSION, 0, 4 ) == '1.2.';
}

/**
 * Returns true, if the user has access level
 *
 * @param $project_id
 * @return bool
 */
function check_user_has_level ( $project_id )
{
   $user_id = auth_get_current_user_id ();

   return user_get_access_level ( $user_id, $project_id ) >= plugin_config_get ( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
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
 * @return array
 */
function calc_matchcodes ()
{
   $matchcode = array ();
   $per_page = 10000;
   $page_count = null;
   $bug_count = null;

   $rows = filter_get_bug_rows ( gpc_get_int ( 'page_number', 1 ), $per_page, $page_count, $bug_count, unserialize ( '' ), null, null, true );
   for ( $row_index = 0; $row_index < count ( $rows ); $row_index++ )
   {
      $bug_id = $rows[ $row_index ]->id;
      $target_version = bug_get_field ( $bug_id, 'target_version' );
      $assigned_project_id = bug_get_field ( $bug_id, 'project_id' );
      $status = bug_get_field ( $bug_id, 'status' );
      $user_id = bug_get_field ( $bug_id, 'handler_id' );

      /** filter config specific bug status */
      if ( !in_array ( false, set_irrelevant ( $status ) ) )
      {
         continue;
      }

      /** target version id */
      $target_version_id = get_target_version_id ( $target_version, $assigned_project_id );

      /** final user active status */
      $no_user = get_user_active ( $user_id );

      /** prepare record matchcode */
      $matchcode[ $row_index ] =
         $user_id . ',' .
         $assigned_project_id . ',' .
         $target_version_id . ',' .
         $no_user;
   }

   return $matchcode;
}

/**
 * Get the layer one column name which is defined in the plugin config area
 *
 * @return string
 */
function get_layer_one_column_name ()
{
   $thead_layer_one = '';
   $layer_one_name = plugin_config_get ( 'layer_one_name' );

   switch ( $layer_one_name )
   {
      case '0':
         $thead_layer_one = 'config_layer_one_name_one';
         break;
      case '1':
         $thead_layer_one = 'config_layer_one_name_two';
         break;
      case '2':
         $thead_layer_one = 'config_layer_one_name_three';
         break;
   }

   return $thead_layer_one;
}

/**
 * Get the target version id by a given version and project id
 *
 * @param $target_version
 * @param $project_id
 * @return int
 */
function get_target_version_id ( $target_version, $project_id )
{
   if ( $target_version != '' )
   {
      return version_get_id ( $target_version, $project_id );
   }
   else
   {
      return '';
   }
}

/**
 * get the active status of a given user id
 *
 * @param $user_id
 * @return bool|int
 */
function get_user_active ( $user_id )
{
   if ( check_user_id_is_valid ( $user_id ) )
   {
      return check_user_id_is_enabled ( $user_id );
   }
   else
   {
      return null;
   }
}

/**
 * return true is the user account is enabled, false otherwise
 *
 * @param integer $user_id A valid user identifier.
 * @return boolean
 */
function check_user_id_is_enabled ( $user_id )
{
   if ( !check_user_id_is_valid ( $user_id ) )
   {
      return false;
   }
   else
   {
      if ( ON == user_get_field ( $user_id, 'enabled' ) )
      {
         return true;
      }
      else
      {
         return false;
      }
   }
}

/**
 * Check if a given user id is valid
 *
 * @param $user_id
 * @return bool|int
 */
function check_user_id_is_valid ( $user_id )
{
   return ( ( $user_id > 0 ) && ( user_exists ( $user_id ) ) );
}

/**
 * Extract the bundled information in the matchcode array and returns it reorganized
 *
 * @param $matchcode
 * @return array
 */
function process_match_codes ( $matchcode )
{
   $data_rows = array ();
   $matchcode_rows = array_count_values ( $matchcode );
   $matchcode_row_count = count ( $matchcode_rows );
   for ( $matchcode_row_index = 0; $matchcode_row_index < $matchcode_row_count; $matchcode_row_index++ )
   {
      /** process first entry in array */
      $matchcode_row_data = key ( $matchcode_rows );

      /** process data string */
      $matchcode_row_data_values = explode ( ',', $matchcode_row_data );

      $user_id = $matchcode_row_data_values[ 0 ];
      $assigned_project_id = $matchcode_row_data_values[ 1 ];
      $target_version_id = $matchcode_row_data_values[ 2 ];
      $user_active = $matchcode_row_data_values[ 3 ];
      $target_version = get_target_version ( $target_version_id );

      /** fill tablerow with data */
      $data_rows[ $matchcode_row_index ][ 'user_id' ] = $user_id;
      $data_rows[ $matchcode_row_index ][ 'assigned_project_id' ] = $assigned_project_id;
      $data_rows[ $matchcode_row_index ][ 'target_version_id' ] = $target_version_id;
      $data_rows[ $matchcode_row_index ][ 'no_user' ] = $user_active;
      $data_rows[ $matchcode_row_index ][ 'no_issue' ] = false;

      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         $data_rows[ $matchcode_row_index ][ 'stat_col' . $stat_index ] = '0';
         $stat_column = 'stat_col' . $stat_index;
         $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
         if ( !is_null ( $stat_status_id ) )
         {
            $databaseapi = new databaseapi();
            $data_rows[ $matchcode_row_index ][ $stat_column ] = $databaseapi->get_amount_issues_by_user_project_version_status ( $user_id, $assigned_project_id, $target_version, $stat_status_id );
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
 * get the target version by a given target version id
 *
 * @param $target_version_id
 * @return string
 */
function get_target_version ( $target_version_id )
{
   if ( $target_version_id != '' )
   {
      return version_get_field ( $target_version_id, 'version' );
   }
   else
   {
      return '';
   }
}

/**
 * Fill data array with additional users which are not beeing catched by >> calc_matchcodes <<
 *
 * @param $data_rows
 * @param $matchcode_row_index
 * @param $project_id
 * @return mixed
 */
function process_no_issue_users ( $data_rows, $matchcode_row_index, $project_id )
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
      if ( !get_user_active ( $user_id ) )
      {
         continue;
      }

      $issue_count = 0;
      if ( $project_id == 0 )
      {
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
            $issue_count += $databaseapi->get_amount_issues_by_user_project_status ( $user_id, $project_id, $stat_status_id );
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

         if ( !check_user_assigned_to_project_hierarchy ( $sub_project_ids, $user_id ) )
         {
            continue;
         }

         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
            foreach ( $sub_project_ids as $sub_project_id )
            {
               $issue_count += $databaseapi->get_amount_issues_by_user_project_status ( $user_id, $sub_project_id, $stat_status_id );
            }
         }
      }

      $additional_row_index = $matchcode_row_index + 1 + $user_row_index;
      if ( intval ( $issue_count ) == 0 )
      {
         $data_rows[ $additional_row_index ][ 'user_id' ] = $user_id;
         $data_rows[ $additional_row_index ][ 'assigned_project_id' ] = '';
         $data_rows[ $additional_row_index ][ 'target_version_id' ] = '';
         $data_rows[ $additional_row_index ][ 'no_user' ] = get_user_active ( $user_id );
         $data_rows[ $additional_row_index ][ 'no_issue' ] = true;

         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $data_rows[ $additional_row_index ][ 'stat_col' . $stat_index ] = '0';
         }
      }
   }
   return $data_rows;
}

/**
 * check if a given user is assigned to a sub project
 *
 * @param $sub_project_ids
 * @param $user_id
 * @return bool
 */
function check_user_assigned_to_project_hierarchy ( $sub_project_ids, $user_id )
{
   $databaseapi = new databaseapi();
   $user_assigned_to_project_hierarchy = false;
   foreach ( $sub_project_ids as $sub_project_id )
   {
      $user_is_assigned_to_project = $databaseapi->check_user_project_assignment ( $user_id, $sub_project_id );
      if ( !is_null ( $user_is_assigned_to_project ) )
      {
         $user_assigned_to_project_hierarchy = true;
         break;
      }
   }

   return $user_assigned_to_project_hierarchy;
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
   $user_realname = array ();
   $main_project = array ();
   $assigned_project = array ();
   $target_version = array ();
   foreach ( $data_rows as $key => $row )
   {
      $user_id[ $key ] = $row[ 'user_id' ];
      if ( check_user_id_is_valid ( $user_id[ $key ] ) )
      {
         $user_name[ $key ] = user_get_name ( $user_id[ $key ] );
         $user_realname[ $key ] = user_get_realname ( $user_id[ $key ] );
      }
      else
      {
         $user_name[ $key ] = '';
         $user_realname[ $key ] = '';
      }
   }

   switch ( $get_sort_val )
   {
      case 'userName':
         $sort_column = $user_name;
         break;
      case 'realName':
         $sort_column = $user_realname;
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
      if ( $data_rows[ $data_row_index ][ 'user_id' ] > 0 )
      {
         /** user existiert */
         if ( user_exists ( $data_rows[ $data_row_index ][ 'user_id' ] ) )
         {
            /** user ist aktiv */
            if ( check_user_id_is_enabled ( $data_rows[ $data_row_index ][ 'user_id' ] ) )
            {
               $valid_stat_issue_count = 0;
               $ignored_stat_issue_count = 0;
               for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
               {
                  $spec_stat_issue_count = $data_rows[ $data_row_index ][ 'stat_col' . $stat_index ];
                  if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == ON ) )
                  {
                     $ignored_stat_issue_count += $spec_stat_issue_count;
                  }
                  else
                  {
                     $valid_stat_issue_count += $spec_stat_issue_count;
                  }
               }

               /** user hat ausschließlich berücksichtigte issues */
               if ( $valid_stat_issue_count > 0 && $ignored_stat_issue_count == 0 )
               {
                  array_push ( $group_user_with_issue, $data_row_index );
               }
               /** user hat ausschließlich ignorierte issues */
               elseif ( $valid_stat_issue_count == 0 && $ignored_stat_issue_count > 0 )
               {
                  array_push ( $group_issues_without_user, $data_row_index );
               }
               /** user hat sowohl berücksichtigte, als auch ignorierte issues */
               elseif ( $valid_stat_issue_count > 0 && $ignored_stat_issue_count > 0 )
               {
                  array_push ( $group_user_with_issue, $data_row_index );
                  array_push ( $group_issues_without_user, $data_row_index );
               }
               /** user hat keine issues */
               elseif ( $valid_stat_issue_count == 0 && $ignored_stat_issue_count == 0 )
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
 * @param $valid_flag
 * @return array
 */
function calculate_user_head_rows ( $data_rows, $valid_flag )
{
   $head_rows_array = array ();
   for ( $data_row_index = 0; $data_row_index < count ( $data_rows ); $data_row_index++ )
   {
      $user_id = $data_rows[ $data_row_index ][ 'user_id' ];
      if ( $user_id == 0 )
      {
         continue;
      }

      if ( $valid_flag )
      {
         if ( !user_exists ( $user_id ) || !check_user_id_is_enabled ( $user_id ) )
         {
            continue;
         }
      }

      $head_row = array ();
      $stat_issue_count = array ();
      $assigned_project_id = $data_rows[ $data_row_index ][ 'assigned_project_id' ];
      if ( check_user_has_level ( $assigned_project_id ) )
      {
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $stat_issue_count[ $stat_index ] = $data_rows[ $data_row_index ][ 'stat_col' . $stat_index ];
         }
      }
      else
      {
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $stat_issue_count[ $stat_index ] = 0;
         }
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
         $last_user_id = $data_rows[ $data_row_index - 1 ][ 'user_id' ];
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
                  if ( check_user_has_level ( $assigned_project_id ) )
                  {
                     for ( $iCounter_index = 1; $iCounter_index <= get_stat_count (); $iCounter_index++ )
                     {
                        $temp_stat_issue_count[ $iCounter_index ] += $data_rows[ $data_row_index ][ 'stat_col' . $iCounter_index ];
                     }
                  }
                  else
                  {
                     for ( $iCounter_index = 1; $iCounter_index <= get_stat_count (); $iCounter_index++ )
                     {
                        $temp_stat_issue_count[ $iCounter_index ] += 0;
                     }
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
 * @param $data_rows
 * @param $stat_issue_count
 * @param $group_index
 * @param $group_name
 * @param $print_flag
 * @return mixed
 */
function process_general_group ( $group, $data_rows, $stat_issue_count, $group_index, $group_name, $print_flag )
{
   print_group_head_row ( $group, $data_rows, $group_index, $group_name );
   foreach ( $group as $data_row_index )
   {
      $data_row = $data_rows[ $data_row_index ];
      $stat_issue_count = print_user_row ( $data_row, $stat_issue_count, $group_index, $print_flag );
   }

   return $stat_issue_count;
}

/**
 * generates a string which can be used for status specified links
 *
 * @return string
 */
function generate_status_link ()
{
   $status_link = '';
   if ( get_stat_count () == 1 )
   {
      $status_link .= 'status_id=' . plugin_config_get ( 'CStatSelect1' );
   }
   else
   {
      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         if ( $stat_index < 2 )
         {
            $status_link .= 'status_id[]=' . plugin_config_get ( 'CStatSelect' . $stat_index );
         }
         else
         {
            $status_link .= '&amp;status_id[]=' . plugin_config_get ( 'CStatSelect' . $stat_index );
         }
      }
   }

   return $status_link;
}

/**
 * Get the depth level of the project hierarchy
 *
 * @param $project_id
 * @return int
 */
function get_project_hierarchy_depth ( $project_id )
{
   $project_hierarchy_depth = 1;
   if ( $project_id == 0 )
   {
      $project_hierarchy_depth = 3;
   }
   else
   {
      $top_level_project = get_main_project_id ( $project_id );
      $sub_project_ids = project_hierarchy_get_subprojects ( $top_level_project );
      if ( !empty( $sub_project_ids ) )
      {
         $project_hierarchy_depth++;
         foreach ( $sub_project_ids as $sub_project_id )
         {
            if ( !empty( project_hierarchy_get_subprojects ( $sub_project_id ) ) )
            {
               $project_hierarchy_depth++;
               break;
            }
         }
      }
   }

   return $project_hierarchy_depth;
}

/**
 * Checks the project hierarchy and returns the nessecary colspan.
 *
 * @param $colspan
 * @param $avatar_flag
 * @return mixed
 */
function get_project_hierarchy_spec_colspan ( $colspan, $avatar_flag )
{
   $project_hierarchy_depth = get_project_hierarchy_depth ( helper_get_current_project () );
   if ( $avatar_flag && plugin_config_get ( 'ShowAvatar' ) )
   {
      $colspan++;
   }
   if ( $project_hierarchy_depth > 1 )
   {
      $colspan++;
   }
   if ( $project_hierarchy_depth > 2 )
   {
      $colspan++;
   }

   return $colspan;
}

function prepare_user_project_remove_group ( $selected_values )
{
   $record_count = count ( $selected_values );
   $user_group = array ();
   for ( $record_index = 0; $record_index < $record_count; $record_index++ )
   {
      $user_hash = array ();
      $record[ $record_index ] = explode ( ',', $selected_values[ $record_index ] );
      $act_user_id = $record[ $record_index ][ 0 ];
      $act_project_id = $record[ $record_index ][ 1 ];

      if ( $record_index > 0 )
      {
         if ( $user_group[ $record_index - 1 ][ 0 ] == $act_user_id )
         {
            $tmp_project_ids = $user_group[ $record_index - 1 ][ 1 ];
            $tmp_project_ids .= ',' . $act_project_id;
            $user_group[ $record_index - 1 ][ 1 ] = $tmp_project_ids;
         }
         else
         {
            $user_hash[ 0 ] = $act_user_id;
            $user_hash[ 1 ] = $act_project_id;
         }
      }
      else
      {
         $user_hash[ 0 ] = $act_user_id;
         $user_hash[ 1 ] = $act_project_id;
      }

      if ( !empty( $user_hash ) )
      {
         array_push ( $user_group, $user_hash );
      }
   }

   return $user_group;
}

/**
 * Get the specific cell colour  for each situation (no issues, etc.. )
 *
 * @param $group_index
 * @param $user_id
 * @param $no_user
 * @param $no_issue
 * @param $unreachable_issue
 * @param $colspan
 * @param $class
 */
function get_cell_highlighting ( $group_index, $user_id, $no_user, $no_issue, $unreachable_issue, $colspan, $class )
{
   if (
      ( !user_exists ( $user_id ) && !$no_user ) ||
      ( check_user_id_is_valid ( $user_id ) && !check_user_id_is_enabled ( $user_id ) && plugin_config_get ( 'IAUHighlighting' ) )
   )
   {
      echo '<td class="' . $class . '" colspan="' . $colspan .
         '" style="background-color:' . plugin_config_get ( 'IAUHBGColor' ) . '">';
   }
   elseif ( $no_issue && plugin_config_get ( 'ZIHighlighting' ) )
   {
      echo '<td class="' . $class . '" colspan="' . $colspan .
         '" style="background-color:' . plugin_config_get ( 'ZIHBGColor' ) . '">';
   }
   elseif ( $no_user && plugin_config_get ( 'NUIHighlighting' ) )
   {
      echo '<td class="' . $class . '" colspan="' . $colspan .
         '" style="background-color:' . plugin_config_get ( 'NUIHBGColor' ) . '">';
   }
   elseif ( $unreachable_issue && plugin_config_get ( 'URIUHighlighting' ) )
   {
      echo '<td class="' . $class . '" colspan="' . $colspan .
         '" style="background-color:' . plugin_config_get ( 'URIUHBGColor' ) . '">';
   }
   elseif ( $group_index == 3 )
   {
      echo '<td class="' . $class . '" colspan="' . $colspan .
         '" style="background-color:' . plugin_config_get ( 'IgnIssBGColor' ) . '">';
   }
   else
   {
      echo '<td class="' . $class . '" colspan="' . $colspan .
         '" style="background-color: #e0e0e0">';
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
      if ( !is_null ( $unreach_issue_status[ $unreachable_issue_status_index ] ) )
      {
         if ( is_mantis_rel () )
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
 * @return array
 */
function set_irrelevant ( $bug_status )
{
   $irrelevant = array ();
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      if ( $bug_status != plugin_config_get ( 'CStatSelect' . $stat_index ) )
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
   if ( project_hierarchy_is_toplevel ( $assigned_project_id ) )
   {
      $bug_main_project_id = $assigned_project_id;
   }
   else
   {
      $parent_project = project_hierarchy_get_parent ( $assigned_project_id, false );
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
   $databaseapi = new databaseapi();
   $assigned_user_id = '0';
   if ( check_user_id_is_valid ( $user_id ) && $assigned_project_id != '' )
   {
      if ( !user_is_administrator ( $user_id ) )
      {
         $assigned_user_id = $databaseapi->check_user_project_assignment ( $user_id, $assigned_project_id );
      }
   }

   return $assigned_user_id;
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
   if ( is_null ( $assigned_to_project ) || $assigned_to_project == '' )
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
 * @param $user_id
 * @return bool
 */
function get_no_user ( $user_id )
{
   $no_user = false;
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $spec_status = plugin_config_get ( 'CStatSelect' . $stat_index );
      if ( $user_id == '0' && $spec_status == USERPROJECTVIEW_ASSIGNED_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_FEEDBACK_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_RESOLVED_STATUS
         || $user_id == '0' && $spec_status == USERPROJECTVIEW_CLOSED_STATUS
         || $user_id == '0'
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

/**
 * @param $main_project_id
 * @param $assigned_project_id
 * @return mixed
 */
function validate_assigned_project_id ( $main_project_id, $assigned_project_id )
{
   if ( strlen ( $assigned_project_id ) == 0 && !is_null ( $main_project_id ) )
   {
      $assigned_project_id = $main_project_id;
   }

   return $assigned_project_id;
}
