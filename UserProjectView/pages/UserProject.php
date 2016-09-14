<?php
require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'uvConst.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'databaseapi.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectapi.php' );

$print = false;
if ( isset( $_POST[ 'print' ] ) )
{
   $print = true;
}

$project_id = gpc_get_int ( 'project_id', helper_get_current_project () );
if ( ( ALL_PROJECTS == $project_id || project_exists ( $project_id ) )
   && $project_id != helper_get_current_project ()
)
{
   helper_set_current_project ( $project_id );
   print_header_redirect ( $_SERVER[ 'REQUEST_URI' ], true, false, true );
}

$matchcode = userprojectapi::calc_matchcodes ();
$result = userprojectapi::process_match_codes ( $matchcode );
$data_rows = $result[ 0 ];
$matchcode_row_index = $result[ 1 ];

if ( plugin_config_get ( 'ShowZIU' ) )
{
   $data_rows = userprojectapi::process_no_issue_users ( $data_rows, $matchcode_row_index, $project_id );
}

html_page_top1 ( plugin_lang_get ( 'menu_userprojecttitle' ) );
?>
   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
   <script type="text/javascript" src="plugins/UserProjectView/javascript/table.js"></script>
   <script type="text/javascript" src="plugins/UserProjectView/javascript/cookie.js"></script>
   <link rel="stylesheet" href="plugins/UserProjectView/files/UserProjectView.css"/>
<?php
if ( !$print )
{
   html_page_top2 ();
   if ( plugin_is_installed ( 'WhiteboardMenu' )
      && file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' )
   )
   {
      require_once ( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
         'WhiteboardMenu' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'wmApi.php' );
      echo '<link rel="stylesheet" href="plugins/WhiteboardMenu/files/whiteboardmenu.css"/>';
      wmApi::printWhiteboardMenu ();
   }
}

echo '<div id="manage-user-div" class="form-container">' . PHP_EOL;
if ( userprojectapi::is_mantis_rel () )
{
   echo '<table class="width100" cellspacing="1">' . PHP_EOL;
}
else
{
   echo '<table>' . PHP_EOL;
}
print_thead ();
print_tbody ( $data_rows );
echo '</table>' . PHP_EOL;
echo '</div>' . PHP_EOL;

if ( !$print )
{
   html_page_bottom1 ();
}

/** ********************* table head area *************************************************************************** */

/**
 * Print the head of the plugin table
 */
function print_thead ()
{
   $dynamic_colspan = userprojectapi::get_stat_count () + userprojectapi::get_project_hierarchy_spec_colspan ( 6, true );
   echo '<thead>' . PHP_EOL;
   print_main_table_head_row ( $dynamic_colspan );
   echo '<tr>' . PHP_EOL;
   print_main_table_head_col ( 'thead_username', 'userName', plugin_config_get ( 'ShowAvatar' ) ? 3 : 2 );
   print_main_table_head_col ( 'thead_realname', 'realName', null );
   echo '<th colspan="' . ( ( $dynamic_colspan - 4 ) ) . '" class="headrow"></th>' . PHP_EOL;
   echo '</tr>' . PHP_EOL;

   echo '<tr>' . PHP_EOL;
   echo '<th></th>' . PHP_EOL;
   echo '<th colspan="' . ( plugin_config_get ( 'ShowAvatar' ) ? 2 : 1 ) . '" class="headrow"></th>' . PHP_EOL;
   echo '<th colspan="3" class="headrow">' . plugin_lang_get ( userprojectapi::get_layer_one_column_name () ) . '</th>' . PHP_EOL;
   $project_hierarchy_depth = userprojectapi::get_project_hierarchy_depth ( helper_get_current_project () );
   if ( $project_hierarchy_depth > 1 )
   {
      print_main_table_head_col ( 'thead_layer_issue_project', 'assignedProject', null );
   }
   if ( $project_hierarchy_depth > 2 )
   {
      print_main_table_head_col ( 'thead_layer_version_project', 'mainProject', null );
   }
   print_main_table_head_col ( 'thead_targetversion', 'targetVersion', null );

   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      $status = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
      echo '<th style="width:50px;" class="headrow_status" bgcolor="'
         . get_status_color ( plugin_config_get ( 'CStatSelect' . $stat_index ), null, null ) . '">';
      echo $status [ plugin_config_get ( 'CStatSelect' . $stat_index ) ] . '</th>' . PHP_EOL;
   }
   echo '<th class="headrow">' . plugin_lang_get ( 'thead_remark' ) . '</th>' . PHP_EOL;
   echo '</tr>' . PHP_EOL . '</thead>' . PHP_EOL;
}

/**
 * Print the row of the head of the plugin table
 *
 * @param $dynamic_colspan
 */
function print_main_table_head_row ( $dynamic_colspan )
{
   global $print;
   echo '<tr>' . PHP_EOL;
   echo '<td class="form-title" colspan="' . $dynamic_colspan . '">';
   echo plugin_lang_get ( 'menu_userprojecttitle' ) . ' - ' . plugin_lang_get ( 'thead_projects_title' )
      . project_get_name ( helper_get_current_project () ) . PHP_EOL;
   echo '</td>' . PHP_EOL;
   if ( !$print )
   {
      echo '<td>';
      echo '<form action="' . plugin_page ( 'UserProject' ) . '&amp;sortVal=userName&amp;sort=ASC" method="post">';
      echo '<input type="submit" name="print" class="button" value="' . lang_get ( 'print' ) . '"/>';
      echo '</form>';
      echo '</td>' . PHP_EOL;
   }
   echo '</tr>' . PHP_EOL;
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
      echo '<th style="width:15px;" class="group_row_bg" ></th>' . PHP_EOL;
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
      echo '<img src="plugins/UserProjectView/files/up.gif" alt="sort asc"/>';
      echo '</a>';
      echo '<a href="' . plugin_page ( 'UserProject' ) . '&amp;sortVal=' . $sort_val . '&amp;sort=DESC">';
      echo '<img src="plugins/UserProjectView/files/down.gif" alt="sort desc"/>';
      echo '</a>';
   }
   echo '</th>' . PHP_EOL;
}

/** ***************************************************************************************************************** */

/** ********************* table body area *************************************************************************** */

/**
 * Print the body of the plugin table
 *
 * @param $data_rows
 */
function print_tbody ( $data_rows )
{
   $get_sort_val = $_GET[ 'sortVal' ];
   $get_sort_order = $_GET[ 'sort' ];
   $sort_column = userprojectapi::get_sort_col ( $get_sort_val, $data_rows );
   $sort_order = userprojectapi::get_sort_order ( $get_sort_order );
   if ( $data_rows != null )
   {
      array_multisort ( $sort_column, $sort_order, SORT_NATURAL | SORT_FLAG_CASE, $data_rows );
   }

   $stat_issue_count = array ();
   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      $stat_issue_count[ $stat_index ] = '';
   }

   $groups = array ();
   $groups[ 0 ] = array ();
   $groups[ 1 ] = array ();
   $groups[ 2 ] = array ();
   $groups[ 3 ] = array ();
   $groups = userprojectapi::assign_groups ( $groups, $data_rows );

   $group_three_data_rows = userprojectapi::process_no_user_matchcodes ( $groups[ 3 ], $data_rows );

   echo '<tbody>' . PHP_EOL . '<form action="' . plugin_page ( 'UserProject_Option' ) . '" method="post">' . PHP_EOL;

   /** GROUP 0 */
   $stat_issue_count = process_user_row_group ( $groups[ 0 ], $data_rows, $stat_issue_count, 0, true, 'headrow_user' );
   /** GROUP 1 */
   $stat_issue_count = userprojectapi::process_general_group ( $groups[ 1 ], $data_rows, $stat_issue_count, 1, 'headrow_no_issue' );
   /** GROUP 2 */
   $stat_issue_count = process_user_row_group ( $groups[ 2 ], $data_rows, $stat_issue_count, 2, false, 'headrow_del_user' );
   /** GROUP 3 */
   $stat_issue_count = userprojectapi::process_general_group ( $groups[ 3 ], $group_three_data_rows, $stat_issue_count, 3, 'headrow_no_user' );
   /** OPTION PANEL */
   print_option_panel ( $stat_issue_count );

   echo '</form>' . PHP_EOL . '</tbody>' . PHP_EOL;
}

/**
 * @param $group
 * @param $data_rows
 * @param $stat_issue_count
 * @param $group_index
 * @param $valid_flag
 * @param $group_name
 * @return mixed
 */
function process_user_row_group ( $group, $data_rows, $stat_issue_count, $group_index, $valid_flag, $group_name )
{
   print_group_head_row ( $group, $data_rows, $group_index, $group_name );
   $head_rows_array = userprojectapi::calculate_user_head_rows ( $data_rows, $valid_flag );
   foreach ( $head_rows_array as $head_row )
   {
      /** get information flag with specific user_id for each data row */
      $information_flag_array = userprojectapi::create_information_flag_array ( $group, $data_rows );
      $head_row_user_id = $head_row[ 0 ];
      $user_head_row_printed = false;
      for ( $group_index = 0; $group_index < count ( $group ); $group_index++ )
      {
         $data_row_index = $group[ $group_index ];
         $user_id = $data_rows[ $data_row_index ][ 'user_id' ];

         if ( $user_id == $head_row_user_id )
         {
            $data_row = $data_rows[ $data_row_index ];
            $assigned_project_id = $data_row[ 'assigned_project_id' ];
            /** pass data row, if user has no access level */
            if ( !userprojectapi::check_user_has_level ( $assigned_project_id ) )
            {
               continue;
            }
            else
            {
               /** information flag is true, if any data row contains information text */
               $information_flag = userprojectapi::check_information_flag_array ( $information_flag_array, $user_id );
               if ( !$user_head_row_printed )
               {
                  print_user_head_row ( $head_row, $data_row, $information_flag );
                  $user_head_row_printed = true;
               }
               $stat_issue_count = print_user_row ( $data_row, $stat_issue_count, 0 );
            }
         }
      }
   }

   return $stat_issue_count;
}

/**
 * Print the head row for a given group
 *
 * @param $group
 * @param $data_rows
 * @param $group_index
 * @param $group_name
 */
function print_group_head_row ( $group, $data_rows, $group_index, $group_name )
{
   $stat_issue_count_with_ignored = array ();
   $stat_issue_count_without_ignored = array ();
   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      $stat_issue_count_with_ignored[ $stat_index ] = 0;
      $stat_issue_count_without_ignored[ $stat_index ] = 0;
   }
   /** check permission to any relevant project/subproject */
   $user_permission = userprojectapi::check_user_permission ();
   foreach ( $group as $data_row_index )
   {
      $data_row = $data_rows[ $data_row_index ];
      /** pass data row, if user has no access level */
      $assigned_project_id = $data_row[ 'assigned_project_id' ];
      if ( userprojectapi::check_user_has_level ( $assigned_project_id ) && $group_index != 1 )
      {
         for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
         {
            $spec_stat_issue_count = $data_row[ 'stat_col' . $stat_index ];
            $stat_spec_status_ign = plugin_config_get ( 'CStatIgn' . $stat_index );
            /** Group 0 - ignore issue count for ignored status */
            if ( $group_index == 0 )
            {
               if ( $stat_spec_status_ign == ON )
               {
                  $stat_issue_count_with_ignored[ $stat_index ] += 0;
               }
               else
               {
                  $stat_issue_count_with_ignored[ $stat_index ] += $spec_stat_issue_count;
               }
            }
            /** Group 3 - ignore issue count for valid status */
            elseif ( $group_index == 3 )
            {
               if ( $stat_spec_status_ign == OFF )
               {
                  $stat_issue_count_with_ignored[ $stat_index ] += 0;
               }
               else
               {
                  $stat_issue_count_with_ignored[ $stat_index ] += $spec_stat_issue_count;
               }
            }
            /** other groups - get issue count for all status */
            else
            {
               $stat_issue_count_with_ignored[ $stat_index ] += $spec_stat_issue_count;
            }

            $stat_issue_count_without_ignored[ $stat_index ] += $spec_stat_issue_count;
         }
      }
      else
      {
         continue;
      }
   }

   if ( ( ( !empty( $group ) )
         && ( array_sum ( $stat_issue_count_without_ignored ) > 0 )
         && ( $user_permission ) )
      || ( ( !empty( $group ) )
         && ( $group_index == 1 )
         && ( $user_permission ) )
   )
   {
      echo '<tr class="clickable" data-level="0" data-status="0">' . PHP_EOL;
      echo '<td class="icon"></td>' . PHP_EOL;
      echo '<td class="group_row_bg" colspan="' . userprojectapi::get_project_hierarchy_spec_colspan ( 5, true ) . '">'
         . plugin_lang_get ( $group_name ) . '</td>' . PHP_EOL;
      for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
      {
         $stat_issue_amount_threshold = plugin_config_get ( 'IAMThreshold' . $stat_index );
         /** group 2 -> if issue count > 0 -> mark cell */
         if ( ( $group_index == 2 ) && ( $stat_issue_count_with_ignored[ $stat_index ] > 0 ) )
         {
            echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">'
               . $stat_issue_count_with_ignored[ $stat_index ] . '</td>' . PHP_EOL;
            continue;
         }

         /** threshold is active ( > 0 ) and lower or equal than counted issues */
         if ( ( $stat_issue_amount_threshold <= $stat_issue_count_with_ignored[ $stat_index ] && $stat_issue_amount_threshold > 0 )
            /** user is not valid / enabled and counted issues > 0 */
         )
         {
            echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">'
               . $stat_issue_count_with_ignored[ $stat_index ] . '</td>' . PHP_EOL;
         }
         else
         {
            echo '<td class="group_row_bg">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>' . PHP_EOL;
         }
      }
      echo '<td class="group_row_bg"></td>' . PHP_EOL;
      echo '</tr>' . PHP_EOL;
   }
}

/**
 * Print the head row for a given group
 *
 * @param $data_rows
 * @param $group_name
 */
function print_group_three_head_row ( $data_rows, $group_name )
{
   $stat_issue_count_with_ignored = array ();
   $stat_issue_count_without_ignored = array ();
   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      $stat_issue_count_with_ignored[ $stat_index ] = 0;
      $stat_issue_count_without_ignored[ $stat_index ] = 0;
   }

   foreach ( $data_rows as $data_row )
   {
      /** pass data row, if user has no access level */
      $assigned_project_id = $data_row[ 'assigned_project_id' ];
      if ( userprojectapi::check_user_has_level ( $assigned_project_id ) )
      {
         for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
         {
            $spec_stat_issue_count = $data_row[ 'stat_col' . $stat_index ];
            $stat_spec_status_ign = plugin_config_get ( 'CStatIgn' . $stat_index );
            if ( $stat_spec_status_ign == OFF )
            {
               $databaseapi = new databaseapi();
               $user_id = $data_row[ 'user_id' ];
               $target_version_id = $data_row[ 'target_version_id' ];
               $target_version = '';
               $status = plugin_config_get ( 'CStatSelect' . $stat_index );
               if ( strlen ( $target_version_id ) > 0 )
               {
                  $target_version = version_get_field ( $target_version_id, 'version' );
               }
               /** Hole die IDs der Issues, die keinem User zugewiesen sind, und nicht ignoriert werden */
               $stat_spec_issue_ids = $databaseapi->get_issues_by_user_project_version_status ( $user_id, $assigned_project_id, $target_version, $status, $stat_spec_status_ign, 3 );
               $stat_issue_count_with_ignored[ $stat_index ] += count ( $stat_spec_issue_ids );
            }
            else
            {
               $stat_issue_count_with_ignored[ $stat_index ] += $spec_stat_issue_count;
            }
            $stat_issue_count_without_ignored[ $stat_index ] += $spec_stat_issue_count;
         }
      }
      else
      {
         continue;
      }
   }

   if ( ( !empty( $data_rows ) ) && ( array_sum ( $stat_issue_count_without_ignored ) > 0 ) )
   {
      echo '<tr class="clickable" data-level="0" data-status="0">' . PHP_EOL;
      echo '<td class="icon"></td>' . PHP_EOL;
      echo '<td class="group_row_bg" colspan="' . userprojectapi::get_project_hierarchy_spec_colspan ( 5, true ) . '">'
         . plugin_lang_get ( $group_name ) . '</td>' . PHP_EOL;
      for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
      {
         $stat_issue_amount_threshold = plugin_config_get ( 'IAMThreshold' . $stat_index );
         /** threshold is active ( > 0 ) and lower or equal than counted issues */
         if (
            ( $stat_issue_amount_threshold <= $stat_issue_count_with_ignored[ $stat_index ] )
            && ( $stat_issue_amount_threshold > 0 )
            /** user is not valid / enabled and counted issues > 0 */
         )
         {
            echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">'
               . $stat_issue_count_with_ignored[ $stat_index ] . '</td>' . PHP_EOL;
         }
         else
         {
            echo '<td class="group_row_bg">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>' . PHP_EOL;
         }
      }
      echo '<td class="group_row_bg"></td>' . PHP_EOL;
      echo '</tr>' . PHP_EOL;
   }
}

/**
 * Print the head row for a given user
 *
 * @param $head_row
 * @param $data_row
 * @param $information_flag
 */
function print_user_head_row ( $head_row, $data_row, $information_flag )
{
   $user_id = $data_row[ 'user_id' ];
   $stat_issue_count = $head_row[ 1 ];
   if ( ( array_sum ( $stat_issue_count ) > 0 ) )
   {
      $filter_string = '<a href="search.php?' . userprojectapi::generate_status_link () .
         '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo '<tr class="clickable" data-level="1" data-status="0">' . PHP_EOL;
      echo '<td style="max-width:15px;"></td>' . PHP_EOL;
      echo '<td class="icon"></td>' . PHP_EOL;
      print_user_head_row_avatar ( $user_id, $filter_string );
      print_user_head_row_username ( $user_id, $filter_string );
      print_user_head_row_realname ( $user_id, $filter_string );
      echo '<td class="group_row_bg" colspan="' . userprojectapi::get_project_hierarchy_spec_colspan ( 2, false ) . '"></td>' . PHP_EOL;
      print_user_head_row_amountofissues ( $user_id, $stat_issue_count );
      echo '<td class="group_row_bg">';
      if ( $information_flag )
      {
         echo '***';
      }
      echo '</td>' . PHP_EOL;
      echo '</tr>' . PHP_EOL;
   }
}

/**
 * @param $user_id
 * @param $filter_string
 */
function print_user_head_row_avatar ( $user_id, $filter_string )
{
   global $print;
   if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
   {
      $user_global_access_level = user_get_field ( auth_get_current_user_id (), 'access_level' );
      echo '<td class="group_row_bg" align = "center" width="25px">';
      if ( userprojectapi::check_user_id_is_valid ( $user_id )
         && ( $user_global_access_level >= config_get ( 'show_avatar_threshold' ) )
      )
      {
         $avatar = user_get_avatar ( $user_id );
         if ( $print )
         {
            echo '<img class="avatar" src="' . $avatar[ 0 ] . '" alt="avatar" />';
         }
         else
         {
            echo $filter_string . '<img class="avatar" src="' . $avatar[ 0 ] . '" alt="avatar" /></a>';
         }
      }
      echo '</td>' . PHP_EOL;
   }
   else
   {
      echo '<td class="group_row_bg" align="center" width="25px"></td>';
   }
}

/**
 * @param $user_id
 * @param $filter_string
 */
function print_user_head_row_username ( $user_id, $filter_string )
{
   global $print;
   echo '<td class="group_row_bg" style="white-space: nowrap">';
   if ( $print )
   {
      if ( user_exists ( $user_id ) )
      {
         echo user_get_name ( $user_id );
      }
      else
      {
         echo '<s>' . user_get_name ( $user_id ) . '</s>';
      }
   }
   else
   {
      if ( user_exists ( $user_id ) )
      {
         echo $filter_string . user_get_name ( $user_id );
      }
      else
      {
         echo '<s>' . $filter_string . user_get_name ( $user_id ) . '</s>';
      }
   }
   echo '</td>' . PHP_EOL;
}

/**
 * @param $user_id
 * @param $filter_string
 */
function print_user_head_row_realname ( $user_id, $filter_string )
{
   global $print;
   echo '<td class="group_row_bg" style="white-space: nowrap">';
   if ( userprojectapi::check_user_id_is_valid ( $user_id ) )
   {
      if ( $print )
      {
         echo user_get_realname ( $user_id );
      }
      else
      {
         echo $filter_string . user_get_realname ( $user_id ) . '</a>';
      }
   }
   echo '</td>' . PHP_EOL;
}

/**
 * @param $user_id
 * @param $stat_issue_count
 */
function print_user_head_row_amountofissues ( $user_id, $stat_issue_count )
{
   global $print;
   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      /** Group 0 - ignore issue count for ignored status */
      if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == ON )
         && ( userprojectapi::check_user_id_is_enabled ( $user_id ) )
      )
      {
         $spec_stat_issue_count = 0;
      }
      /** Group 2 - ignore issue count for valid status */
      else
      {
         $spec_stat_issue_count = $stat_issue_count[ $stat_index ];
      }

      $stat_issue_amount_threshold = plugin_config_get ( 'IAMThreshold' . $stat_index );
      /** threshold is active ( > 0 ) and lower than counted issues */
      if ( ( $stat_issue_amount_threshold <= $spec_stat_issue_count && $stat_issue_amount_threshold > 0 )
         /** user is not valid / enabled and counted issues > 0 */
         || ( !userprojectapi::check_user_id_is_enabled ( $user_id ) && ( $spec_stat_issue_count > 0 ) )
      )
      {
         echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">';
      }
      else
      {
         echo '<td class="group_row_bg">';
      }

      if ( !$print && ( $spec_stat_issue_count > 0 ) )
      {
         echo '<a href="search.php?status_id=' . plugin_config_get ( 'CStatSelect' . $stat_index ) .
            '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
            '&amp;sticky_issues=on' .
            '&amp;sortby=last_updated' .
            '&amp;dir=DESC' .
            '&amp;hide_status_id=-2' .
            '&amp;match_type=0">';
         echo $spec_stat_issue_count;
         echo '</a>';
      }
      else
      {
         echo $spec_stat_issue_count;
      }
      echo '</td>' . PHP_EOL;
   }
}

/**
 * Print a given user row detailed
 *
 * @param $data_row
 * @param $stat_issue_count
 * @param $group_index
 * @return mixed
 */
function print_user_row ( $data_row, $stat_issue_count, $group_index )
{
   global $print;
   /** group 1 */
   if ( $group_index == 1 )
   {
      /** assigned_project_id is always null, so check current selected project and subprojects,
       * if the user has permission to see info
       */
      $user_permission = userprojectapi::check_user_permission ();
   }
   /** other groups */
   else
   {
      $assigned_project_id = $data_row[ 'assigned_project_id' ];
      $user_permission = userprojectapi::check_user_has_level ( $assigned_project_id );
   }

   if ( $user_permission )
   {
      echo '<tr class="info" data-level="2" data-status="1">' . PHP_EOL;
      echo '<td></td>' . PHP_EOL;
      if ( $print )
      {
         echo '<td></td>' . PHP_EOL;
         userprojectapi::get_cell_highlighting ( $data_row, 1, 'nowrap' );
         echo '</td>' . PHP_EOL;
      }
      else
      {
         if ( $group_index == 1 )
         {
            print_chackbox ( $data_row );
         }
         else
         {
            echo '<td></td>' . PHP_EOL;
         }
         print_user_avatar ( $data_row, $group_index );
      }

      if ( $group_index == 1 )
      {
         print_user_name ( $data_row );
         print_real_name ( $data_row );
      }

      print_layer_one_project ( $data_row, $print, $group_index );
      $project_hierarchy_depth = userprojectapi::get_project_hierarchy_depth ( helper_get_current_project () );
      if ( $group_index != 1 )
      {
         if ( $project_hierarchy_depth > 1 )
         {
            print_bug_layer_project ( $data_row, $print );
         }

         if ( $project_hierarchy_depth > 2 )
         {
            print_version_layer_project ( $data_row, $print );
         }

         print_target_version ( $data_row, $print );
      }
      $stat_issue_count = print_amount_of_issues ( $data_row, $group_index, $stat_issue_count, $print );
      print_remark ( $data_row, $group_index, $print );
      echo '</tr>' . PHP_EOL;
   }

   return $stat_issue_count;

}

/**
 * Print the checkbox in the user row of the plugin table
 *
 * @param $data_row
 */
function print_chackbox ( $data_row )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $assigned_to_project = userprojectapi::get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = userprojectapi::get_unreachable_issue ( $assigned_to_project );
   $no_user = userprojectapi::get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   echo '<td width="15px">';
   echo '<label>';
   if ( $no_issue && ( helper_get_current_project () != 0 ) )
   {
      echo '<input type="checkbox" name="dataRow[]" value="' . $user_id . ',' . helper_get_current_project () . '"/>';
   }

   if ( !$no_user && !$unreachable_issue && !$no_issue )
   {
      echo '<input type="checkbox" name="dataRow[]" value="' . $user_id . ',' . $assigned_project_id . '"/>';
   }
   echo '</label>';
   echo '</td>' . PHP_EOL;
}

/**
 * Print the avatar in the user row of the plugin table
 *
 * @param $data_row
 * @param $group_index
 */
function print_user_avatar ( $data_row, $group_index )
{
   $user_id = $data_row[ 'user_id' ];
   $user_global_access_level = user_get_field ( auth_get_current_user_id (), 'access_level' );
   $no_user = userprojectapi::get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $assigned_to_project = userprojectapi::get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = userprojectapi::get_unreachable_issue ( $assigned_to_project );

   if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
   {
      if ( $group_index > 0 )
      {
         if ( ( !user_exists ( $user_id ) && !$no_user )
            || ( userprojectapi::check_user_id_is_valid ( $user_id )
               && !userprojectapi::check_user_id_is_enabled ( $user_id )
               && plugin_config_get ( 'IAUHighlighting' )
            )
         )
         {
            echo '<td align="center" width="25px" style="background-color:' . plugin_config_get ( 'IAUHBGColor' ) . '">';
         }
         elseif ( $no_issue && plugin_config_get ( 'ZIHighlighting' ) )
         {
            echo '<td align="center" width="25px" style="background-color:' . plugin_config_get ( 'ZIHBGColor' ) . '">';
         }
         elseif ( $no_user && plugin_config_get ( 'NUIHighlighting' ) )
         {
            echo '<td align="center" width="25px" style="background-color:' . plugin_config_get ( 'NUIHBGColor' ) . '">';
         }
         elseif ( $unreachable_issue && plugin_config_get ( 'URIUHighlighting' ) )
         {
            echo '<td align="center" width="25px" style="background-color:' . plugin_config_get ( 'URIUHBGColor' ) . '">';
         }
         else
         {
            echo '<td class="user_row_bg" align="center" width="25px">';
         }
         if ( user_exists ( $user_id ) )
         {
            if ( $group_index != 1 )
            {
               echo '<a href="search.php?' . userprojectapi::generate_status_link () .
                  '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
                  '&amp;sortby=last_updated' .
                  '&amp;dir=DESC' .
                  '&amp;hide_status_id=-2' .
                  '&amp;match_type=0">';
            }

            if ( config_get ( 'show_avatar' )
               && ( $user_global_access_level >= config_get ( 'show_avatar_threshold' ) )
            )
            {
               if ( $user_id > 0 )
               {
                  $avatar = user_get_avatar ( $user_id );
                  echo '<img class="avatar" src="' . $avatar [ 0 ] . '" />';
               }
            }

            if ( $group_index != 1 )
            {
               echo '</a>';
            }
         }
         echo '</td>' . PHP_EOL;
      }
      else
      {
         $assigned_to_project = userprojectapi::get_assigned_to_project ( $user_id, $assigned_project_id );
         $unreachable_issue = userprojectapi::get_unreachable_issue ( $assigned_to_project );
         echo '<td>';
         if ( !$no_user && !$unreachable_issue )
         {
            echo '<label>';
            echo '<input type="checkbox" name="dataRow[]" value="' . $user_id . ',' . $assigned_project_id . '"/>';
            echo '</label>';
         }
         echo '</td>' . PHP_EOL;
      }
   }
   else
   {
      echo '<td width="25px"></td>';
   }
}

/**
 * Print the username in the user row of the plugin table
 *
 * @param $data_row
 */
function print_user_name ( $data_row )
{
   $user_id = $data_row[ 'user_id' ];
   $user_name = '';
   if ( $user_id > 0 )
   {
      $user_name = user_get_name ( $user_id );
   }

   userprojectapi::get_cell_highlighting ( $data_row, 1, 'nowrap' );
   if ( userprojectapi::check_user_id_is_valid ( $user_id ) )
   {
      echo $user_name;
   }
   else
   {
      echo '<s>' . $user_name . '</s>';
   }
   echo '</td>' . PHP_EOL;
}

/**
 * Print the real name in the user row of the plugin table
 *
 * @param $data_row
 */
function print_real_name ( $data_row )
{
   $user_id = $data_row[ 'user_id' ];
   $real_name = '';
   if ( userprojectapi::check_user_id_is_valid ( $user_id ) )
   {
      $real_name = user_get_realname ( $user_id );
   }

   userprojectapi::get_cell_highlighting ( $data_row, 1, 'nowrap' );
   echo $real_name;
   echo '</td>' . PHP_EOL;
}

/**
 * Print the layer one project name in the user row of the plugin table
 *
 * @param $data_row
 * @param $print
 * @param $group_index
 */
function print_layer_one_project ( $data_row, $print, $group_index )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $layer_one_project_id = '';
   if ( $assigned_project_id != '' )
   {
      $layer_one_project_id = userprojectapi::get_main_project_id ( $assigned_project_id );
   }

   if ( $layer_one_project_id != 0 )
   {
      $layer_one_project_name = project_get_name ( $layer_one_project_id );
   }
   else
   {
      $layer_one_project_name = '';
   }

   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $colspan = 1;
   if ( $group_index == 0 || $group_index == 3 )
   {
      $colspan = 3;
   }
   elseif ( $group_index == 1 )
   {
      $colspan = userprojectapi::get_project_hierarchy_spec_colspan ( 2, false );
   }

   userprojectapi::get_cell_highlighting ( $data_row, $colspan, 'normalwrap' );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . userprojectapi::generate_status_link () .
         '&amp;project_id=' . $layer_one_project_id .
         '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo $layer_one_project_name;
      echo '</a>';
   }
   else
   {
      echo $layer_one_project_name;
   }
   echo '</td>' . PHP_EOL;
}

/**
 * Print the main project in the user row of the plugin table
 *
 * @param $data_row
 * @param $print
 */
function print_version_layer_project ( $data_row, $print )
{
   $databaseapi = new databaseapi();
   $user_id = $data_row[ 'user_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $version_assigned_project_id = '';
   if ( $target_version_id != '' )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
      $version_assigned_project_id = $databaseapi->get_project_id_by_version ( $target_version );
      $version_assigned_project_name = project_get_name ( $version_assigned_project_id );
   }
   else
   {
      $version_assigned_project_name = '';
   }
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   userprojectapi::get_cell_highlighting ( $data_row, 1, 'normalwrap' );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . userprojectapi::generate_status_link () .
         '&amp;project_id=' . $version_assigned_project_id .
         '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo $version_assigned_project_name;
      echo '</a>';
   }
   else
   {
      echo $version_assigned_project_name;
   }
   echo '</td>' . PHP_EOL;
}

/**
 * Print the assigned project in the user row of the plugin table
 *
 * @param $data_row
 * @param $print
 */
function print_bug_layer_project ( $data_row, $print )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   if ( $assigned_project_id != 0 )
   {
      $assigned_project_name = project_get_name ( $assigned_project_id );
   }
   else
   {
      $assigned_project_name = '';
   }
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   userprojectapi::get_cell_highlighting ( $data_row, 1, 'normalwrap' );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . userprojectapi::generate_status_link () .
         '&amp;project_id=' . $assigned_project_id .
         '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo $assigned_project_name;
      echo '</a>';
   }
   else
   {
      echo $assigned_project_name;
   }
   echo '</td>' . PHP_EOL;
}

/**
 * Print the target version in the user row of the plugin table
 *
 * @param $data_row
 * @param $print
 */
function print_target_version ( $data_row, $print )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $target_version = '';
   $target_version_date = '';
   if ( strlen ( $target_version_id ) > 0 )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
      $target_version_date = date ( 'Y-m-d', version_get_field ( $target_version_id, 'date_order' ) );
   }
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   userprojectapi::get_cell_highlighting ( $data_row, 1, 'breakwordwrap' );
   echo $target_version_date . ' ';
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . userprojectapi::generate_status_link () .
         '&amp;project_id=' . $assigned_project_id .
         '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
         '&amp;sticky_issues=on' .
         '&amp;target_version=' . $target_version .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo $target_version;
      echo '</a>';
   }
   else
   {
      echo $target_version;
   }
   echo '</td>' . PHP_EOL;
}

/**
 * Print the amount of issues for each specified status in the user row of the plugin table
 *
 * @param $data_row
 * @param $stat_issue_count
 * @param $group_index
 * @param $print
 * @return mixed
 */
function print_amount_of_issues ( $data_row, $group_index, $stat_issue_count, $print )
{
   $user_id = $data_row[ 'user_id' ];

   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      $stat_spec_status_ign = plugin_config_get ( 'CStatIgn' . $stat_index );
      $temp_stat_issue_count = userprojectapi::calc_group_spec_amount ( $data_row, $group_index, $stat_index );
      $stat_issue_count_threshold = plugin_config_get ( 'IAMThreshold' . $stat_index );
      $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
      $stat_issue_count[ $stat_index ] += $temp_stat_issue_count;
      /** group 2 -> mark all cells where issue count > 0 */
      if ( ( !userprojectapi::check_user_id_is_enabled ( $user_id ) )
         && ( $temp_stat_issue_count > 0 )
         && ( $group_index != 3 )
      )
      {
         echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">';
      }
      /** group 0, 1, 3 -> mark cell if threshold is reached */
      else
      {
         if ( ( $stat_issue_count_threshold <= $temp_stat_issue_count ) && ( $stat_issue_count_threshold > 0 ) )
         {
            echo '<td style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">';
         }
         else
         {
            echo '<td style="background-color:' . get_status_color ( $stat_status_id, null, null ) . '">';
         }
      }

      if ( !$print && ( $temp_stat_issue_count > 0 ) )
      {
         $assigned_project_id = $data_row[ 'assigned_project_id' ];
         $target_version_id = $data_row[ 'target_version_id' ];
         $target_version = '';
         if ( strlen ( $target_version_id ) > 0 )
         {
            $target_version = version_get_field ( $target_version_id, 'version' );
         }

         $filter_string = '<a href="search.php?project_id=' . $assigned_project_id .
            '&amp;status_id=' . $stat_status_id;

         if ( ( $group_index != 3 )
            || ( ( $stat_spec_status_ign == OFF ) && ( $group_index == 3 ) )
         )
         {
            $filter_string .= '&amp;handler_id=' . userprojectapi::get_link_user_id ( $data_row[ 'user_id' ] );
         }

         $filter_string .= '&amp;sticky_issues=on' .
            '&amp;target_version=' . $target_version .
            '&amp;sortby=last_updated' .
            '&amp;dir=DESC' .
            '&amp;hide_status_id=-2' .
            '&amp;match_type=0">';

         echo $filter_string;
         echo $temp_stat_issue_count;
         echo '</a>';
      }
      else
      {
         echo $temp_stat_issue_count;
      }
      echo '</td>' . PHP_EOL;
   }
   return $stat_issue_count;
}

/**
 * Print additional information (remarks) in the user row of the plugin table
 *
 * @param $data_row
 * @param $group_index
 * @param $print
 */
function print_remark ( $data_row, $group_index, $print )
{
   $user_id = $data_row[ 'user_id' ];
   userprojectapi::get_cell_highlighting ( $data_row, 1, 'nowrap' );
   remark_old_issues ( $data_row, $print, $group_index );
   remark_unreachable_issues ( $data_row );
   remark_inactive ( $user_id );
   if ( $group_index == 1 )
   {
      remark_assigned_subprojects ( $user_id );
   }
   echo '</td>' . PHP_EOL;
}

/**
 * Print the option panel where the user manage user->project-assignments and the overall amount of issues
 * for each status under the user table
 *
 * @param $stat_issue_count
 */
function print_option_panel ( $stat_issue_count )
{
   global $print;
   $user_has_level = false;
   $project_ids = array ();
   $current_project_id = helper_get_current_project ();
   array_push ( $project_ids, $current_project_id );
   $sub_project_ids = project_hierarchy_get_all_subprojects ( $current_project_id );
   foreach ( $sub_project_ids as $sub_project_id )
   {
      array_push ( $project_ids, $sub_project_id );
   }

   foreach ( $project_ids as $project_id )
   {
      $access_level = user_get_access_level ( auth_get_current_user_id (), $project_id );
      if ( $access_level >= plugin_config_get ( 'UserProjectAccessLevel' ) )
      {
         $user_has_level = true;
      }
   }
   echo '<tr>' . PHP_EOL;
   echo '<td colspan="' . userprojectapi::get_project_hierarchy_spec_colspan ( 6, true ) . '">';
   if ( !$print )
   {
      if ( $user_has_level )
      {
         echo '<label for="option"></label>';
         echo '<select id="option" name="option">';
         echo '<option value="removeSingle">' . plugin_lang_get ( 'remove_selectSingle' ) . '</option>';
         echo '<option value="removeAll">' . plugin_lang_get ( 'remove_selectAll' ) . '</option>';
         echo '</select>';
         echo '&nbsp;<input type="submit" name="formSubmit" class="button" value="' . lang_get ( 'ok' ) . '"/>';
      }
   }
   echo '</td>' . PHP_EOL;
   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      echo '<td>' . $stat_issue_count[ $stat_index ] . '</td>' . PHP_EOL;
   }
   echo '<td></td>' . PHP_EOL;
   echo '</tr>' . PHP_EOL;
}

/**
 * remark about issues, where last update is older than configured threshold
 *
 * @param $data_row
 * @param $print
 * @param $group_index
 */
function remark_old_issues ( $data_row, $print, $group_index )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $target_version = '';
   if ( strlen ( $target_version_id ) > 0 )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
   }

   for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
   {
      $stat_issue_age_threshold = plugin_config_get ( 'IAGThreshold' . $stat_index );
      if ( $assigned_project_id == null )
      {
         continue;
      }

      $stat_ignore_status = plugin_config_get ( 'CStatIgn' . $stat_index );
      $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
      $databaseapi = new databaseapi();

      $stat_issue_ids = $databaseapi->get_issues_by_user_project_version_status ( $user_id, $assigned_project_id, $target_version, $stat_status_id, $stat_ignore_status, $group_index );

      if ( !empty( $stat_issue_ids ) )
      {
         $stat_time_difference = userprojectapi::calculate_time_difference ( $stat_issue_ids )[ 0 ];
         $stat_oldest_issue_id = userprojectapi::calculate_time_difference ( $stat_issue_ids )[ 1 ];

         if ( ( $stat_time_difference > $stat_issue_age_threshold ) && !$print )
         {
            if ( ( $stat_ignore_status == OFF ) || ( $group_index == 3 ) )
            {

               $stat_enum = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );

               $filter_string = '<a href="search.php?project_id=' . $assigned_project_id .
                  '&amp;search=' . $stat_oldest_issue_id .
                  '&amp;status_id=' . $stat_status_id;

               if ( $group_index != 3 )
               {
                  $filter_string .= '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id );
               }

               $filter_string .= '&amp;sticky_issues=on' .
                  '&amp;target_version=' . $target_version .
                  '&amp;sortby=last_updated' .
                  '&amp;dir=DESC' .
                  '&amp;hide_status_id=-2' .
                  '&amp;match_type=0">';

               echo $filter_string;
               echo '"' . $stat_enum [ $stat_status_id ] . '"' .
                  ' ' . plugin_lang_get ( 'remark_since' ) . ' ' . $stat_time_difference .
                  ' ' . plugin_lang_get ( 'remark_day' );
               echo '<br/>' . PHP_EOL;
               echo '</a>';
            }
         }
      }
   }
}

/**
 * information about unreachable issues cause of missing project assignment
 *
 * @param $data_row
 */
function remark_unreachable_issues ( $data_row )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $assigned_to_project = userprojectapi::get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = userprojectapi::get_unreachable_issue ( $assigned_to_project );

   if ( $unreachable_issue )
   {
      $target_version_id = $data_row[ 'target_version_id' ];
      $target_version = '';
      if ( strlen ( $target_version_id ) > 0 )
      {
         $target_version = version_get_field ( $target_version_id, 'version' );
      }

      echo '<a href="search.php?project_id=' . $assigned_project_id .
         userprojectapi::prepare_filter_string () .
         '&amp;handler_id=' . userprojectapi::get_link_user_id ( $user_id ) .
         '&amp;sticky_issues=on' .
         '&amp;target_version=' . $target_version .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo wordwrap ( plugin_lang_get ( 'remark_noProject' ), 30, '<br />' );
      echo '</a>';
      echo '<br/>' . PHP_EOL;
   }
}

/**
 * information about inactive / deleted users
 *
 * @param $user_id
 */
function remark_inactive ( $user_id )
{
   if ( $user_id > 0 )
   {
      if ( !user_exists ( $user_id ) || !userprojectapi::check_user_id_is_enabled ( $user_id ) )
      {
         echo plugin_lang_get ( 'remark_IAUser' ) . '<br/>' . PHP_EOL;
      }
   }
}

/**
 * information if user in group one is just assigned to subprojects
 *
 * @param $user_id
 */
function remark_assigned_subprojects ( $user_id )
{
   $databaseapi = new databaseapi();
   $user_is_assigned_to_project = $databaseapi->check_user_project_assignment ( $user_id, helper_get_current_project () );
   if ( is_null ( $user_is_assigned_to_project ) && helper_get_current_project () > 0 )
   {
      echo plugin_lang_get ( 'remark_noprojectassignment' );
   }
}

/** ***************************************************************************************************************** */