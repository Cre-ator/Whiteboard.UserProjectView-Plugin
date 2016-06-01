<?php

require_once USERPROJECTVIEW_CORE_URI . 'constantapi.php';
require_once USERPROJECTVIEW_CORE_URI . 'databaseapi.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectapi.php';


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

$matchcode = calc_matchcodes ();
$result = process_match_codes ( $matchcode );
$data_rows = $result[ 0 ];
$matchcode_row_index = $result[ 1 ];

if ( plugin_config_get ( 'ShowZIU' ) )
{
   $data_rows = process_no_issue_users ( $data_rows, $matchcode_row_index, $project_id );
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
   if ( plugin_is_installed ( 'WhiteboardMenu' ) && file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' ) )
   {
      require_once WHITEBOARDMENU_CORE_URI . 'whiteboard_print_api.php';
      $whiteboard_print_api = new whiteboard_print_api();
      $whiteboard_print_api->printWhiteboardMenu ();
   }
}

echo '<div id="manage-user-div" class="form-container">';
if ( is_mantis_rel () )
{
   echo '<table class="width100" cellspacing="1">';
}
else
{
   echo '<table>';
}
print_thead ();
print_tbody ( $data_rows );
echo '</table>';
echo '</div>';

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
   $dynamic_colspan = get_stat_count () + get_project_hierarchy_spec_colspan ( 6, true );
   echo '<thead>';
   print_main_table_head_row ( $dynamic_colspan );
   echo '<tr>';
   print_main_table_head_col ( 'thead_username', 'userName', plugin_config_get ( 'ShowAvatar' ) ? 3 : 2 );
   print_main_table_head_col ( 'thead_realname', 'realName', null );
   echo '<th colspan="' . ( ( $dynamic_colspan - 4 ) ) . '" class="headrow"></th>';
   echo '</tr>';

   echo '<tr>';
   echo '<th></th>';
   echo '<th colspan="' . ( plugin_config_get ( 'ShowAvatar' ) ? 2 : 1 ) . '" class="headrow"></th>';
   echo '<th colspan="3" class="headrow">' . plugin_lang_get ( get_layer_one_column_name () ) . '</th>';
   $project_hierarchy_depth = get_project_hierarchy_depth ( helper_get_current_project () );
   if ( $project_hierarchy_depth > 1 )
   {
      print_main_table_head_col ( 'thead_layer_issue_project', 'assignedProject', null );
   }
   if ( $project_hierarchy_depth > 2 )
   {
      print_main_table_head_col ( 'thead_layer_version_project', 'mainProject', null );
   }
   print_main_table_head_col ( 'thead_targetversion', 'targetVersion', null );

   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      ?>
      <th style="width:50px;" class="headrow_status"
          bgcolor="<?php echo get_status_color ( plugin_config_get ( 'CStatSelect' . $stat_index ), null, null ); ?>">
         <?php $status = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
         echo $status [ plugin_config_get ( 'CStatSelect' . $stat_index ) ]; ?>
      </th>
      <?php
   }
   echo '<th class="headrow">' . plugin_lang_get ( 'thead_remark' ) . '</th>';
   echo '</tr></thead>';
}

/**
 * Print the row of the head of the plugin table
 *
 * @param $dynamic_colspan
 */
function print_main_table_head_row ( $dynamic_colspan )
{
   global $print;
   ?>
   <tr>
      <td class="form-title" colspan="<?php echo ( $dynamic_colspan ); ?>">
         <?php
         echo plugin_lang_get ( 'menu_userprojecttitle' ) . ' - ' . plugin_lang_get ( 'thead_projects_title' ) .
            project_get_name ( helper_get_current_project () );
         ?>
      </td>
      <?php
      if ( !$print )
      {
         ?>
         <td>
            <form action="<?php echo plugin_page ( 'UserProject' ); ?>&amp;sortVal=userName&amp;sort=ASC"
                  method="post">
               <input type="submit" name="print" class="button" value="<?php echo lang_get ( 'print' ); ?>"/>
            </form>
         </td>
         <?php
      }
      ?>
   </tr>
   <?php
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
      ?>
      <a href="<?php echo plugin_page ( 'UserProject' ); ?>&amp;sortVal=<?php echo $sort_val; ?>&amp;sort=ASC">
         <img src="plugins/UserProjectView/files/up.gif" alt="sort asc"/>
      </a>
      <a href="<?php echo plugin_page ( 'UserProject' ); ?>&amp;sortVal=<?php echo $sort_val; ?>&amp;sort=DESC">
         <img src="plugins/UserProjectView/files/down.gif" alt="sort desc"/>
      </a>
      <?php
   }
   echo '</th>';
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

   $group_three_data_rows = process_no_user_matchcodes ( $groups[ 3 ], $data_rows );

   echo '<tbody><form action="' . plugin_page ( 'UserProject_Option' ) . '" method="post">';

   /** GROUP 0 */
   $stat_issue_count = process_user_row_group ( $groups[ 0 ], $data_rows, $stat_issue_count, 0, true, 'headrow_user' );
   /** GROUP 1 */
   $stat_issue_count = process_general_group ( $groups[ 1 ], $data_rows, $stat_issue_count, 1, 'headrow_no_issue' );
   /** GROUP 2 */
   $stat_issue_count = process_user_row_group ( $groups[ 2 ], $data_rows, $stat_issue_count, 2, false, 'headrow_del_user' );
   /** GROUP 3 */
   $stat_issue_count = process_general_group ( $groups[ 3 ], $group_three_data_rows, $stat_issue_count, 3, 'headrow_no_user' );
   /** OPTION PANEL */
   print_option_panel ( $stat_issue_count );

   echo '</form></tbody>';
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
   $head_rows_array = calculate_user_head_rows ( $data_rows, $valid_flag );
   foreach ( $head_rows_array as $head_row )
   {
      $head_row_user_id = $head_row[ 0 ];
      $counter = true;
      for ( $group_index = 0; $group_index < count ( $group ); $group_index++ )
      {
         $data_row_index = $group[ $group_index ];
         $user_id = $data_rows[ $data_row_index ][ 'user_id' ];
         if ( $user_id == $head_row_user_id )
         {
            $data_row = $data_rows[ $data_row_index ];
            if ( $counter )
            {
               print_user_head_row ( $head_row, $data_row );
               $counter = false;
            }
            $stat_issue_count = print_user_row ( $data_row, $stat_issue_count, 0 );
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
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_count_with_ignored[ $stat_index ] = 0;
      $stat_issue_count_without_ignored[ $stat_index ] = 0;
   }

   foreach ( $group as $data_row_index )
   {
      $data_row = $data_rows[ $data_row_index ];
      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         $spec_stat_issue_count = $data_row[ 'stat_col' . $stat_index ];
         /** Group 0 - ignore issue count for ignored status */
         if ( $group_index == 0 )
         {
            if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == ON ) )
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
            if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == OFF ) )
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

   if ( ( ( !empty( $group ) ) && ( array_sum ( $stat_issue_count_without_ignored ) > 0 ) )
      || ( ( !empty( $group ) ) && ( $group_index == 1 ) )
   )
   {
      ?>
      <tr class="clickable" data-level="0" data-status="0">
         <td class="icon"></td>
         <td class="group_row_bg"
             colspan="<?php echo get_project_hierarchy_spec_colspan ( 5, true ); ?>"><?php echo plugin_lang_get ( $group_name ); ?></td>
         <?php
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $stat_issue_amount_threshold = plugin_config_get ( 'IAMThreshold' . $stat_index );
            /** group 2 -> if issue count > 0 -> mark cell */
            if ( ( $group_index == 2 ) && ( $stat_issue_count_with_ignored[ $stat_index ] > 0 ) )
            {
               echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>';
               continue;
            }

            /** threshold is active ( > 0 ) and lower or equal than counted issues */
            if ( ( $stat_issue_amount_threshold <= $stat_issue_count_with_ignored[ $stat_index ] && $stat_issue_amount_threshold > 0 )
               /** user is not valid / enabled and counted issues > 0 */
            )
            {
               echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>';
            }
            else
            {
               echo '<td class="group_row_bg">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>';
            }
         }
         ?>
         <td class="group_row_bg"></td>
      </tr>
      <?php
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
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_count_with_ignored[ $stat_index ] = 0;
      $stat_issue_count_without_ignored[ $stat_index ] = 0;
   }

   foreach ( $data_rows as $data_row )
   {
      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         $spec_stat_issue_count = $data_row[ 'stat_col' . $stat_index ];
         if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == OFF ) )
         {
            $stat_issue_count_with_ignored[ $stat_index ] += 0;
         }
         else
         {
            $stat_issue_count_with_ignored[ $stat_index ] += $spec_stat_issue_count;
         }
         $stat_issue_count_without_ignored[ $stat_index ] += $spec_stat_issue_count;
      }
   }

   if ( ( !empty( $data_rows ) ) && ( array_sum ( $stat_issue_count_without_ignored ) > 0 ) )
   {
      ?>
      <tr class="clickable" data-level="0" data-status="0">
         <td class="icon"></td>
         <td class="group_row_bg"
             colspan="<?php echo get_project_hierarchy_spec_colspan ( 5, true ); ?>"><?php echo plugin_lang_get ( $group_name ); ?></td>
         <?php
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            $stat_issue_amount_threshold = plugin_config_get ( 'IAMThreshold' . $stat_index );
            /** threshold is active ( > 0 ) and lower or equal than counted issues */
            if ( ( $stat_issue_amount_threshold <= $stat_issue_count_with_ignored[ $stat_index ] && $stat_issue_amount_threshold > 0 )
               /** user is not valid / enabled and counted issues > 0 */
            )
            {
               echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>';
            }
            else
            {
               echo '<td class="group_row_bg">' . $stat_issue_count_with_ignored[ $stat_index ] . '</td>';
            }
         }
         ?>
         <td class="group_row_bg"></td>
      </tr>
      <?php
   }
}

/**
 * Print the head row for a given user
 *
 * @param $head_row
 * @param $data_row
 */
function print_user_head_row ( $head_row, $data_row )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $stat_issue_count = $head_row[ 1 ];
   if ( ( array_sum ( $stat_issue_count ) > 0 ) && ( check_user_has_level ( $assigned_project_id ) ) )
   {
      $filter_string = '<a href="search.php?' . generate_status_link () .
         '&amp;handler_id=' . get_link_user_id ( $user_id ) .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo '<tr class="clickable" data-level="1" data-status="0">';
      echo '<td style="max-width:15px;"></td>';
      echo '<td class="icon"></td>';
      print_user_head_row_avatar ( $user_id, $filter_string );
      print_user_head_row_username ( $user_id, $filter_string );
      print_user_head_row_realname ( $user_id, $filter_string );
      echo '<td class="group_row_bg" colspan="' . get_project_hierarchy_spec_colspan ( 2, false ) . '"></td>';
      print_user_head_row_amountofissues ( $user_id, $stat_issue_count );
      echo '<td class="group_row_bg"></td>';
      echo '</tr>';
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
      echo '<td class="group_row_bg" align = "center" style = "max-width:25px;" >';
      if ( check_user_id_is_valid ( $user_id ) )
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
      echo '</td>';
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
         echo '<s>' . $filter_string . user_get_name ( $user_id ) . '</s></a>';
      }
   }
   echo '</td>';
}

/**
 * @param $user_id
 * @param $filter_string
 */
function print_user_head_row_realname ( $user_id, $filter_string )
{
   global $print;
   echo '<td class="group_row_bg" style="white-space: nowrap">';
   if ( check_user_id_is_valid ( $user_id ) )
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
   echo '</td>';
}

/**
 * @param $user_id
 * @param $stat_issue_count
 */
function print_user_head_row_amountofissues ( $user_id, $stat_issue_count )
{
   global $print;
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      /** Group 0 - ignore issue count for ignored status */
      if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == ON )
         && ( check_user_id_is_enabled ( $user_id ) )
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
         || ( !check_user_id_is_enabled ( $user_id ) && ( $spec_stat_issue_count > 0 ) )
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
            '&amp;handler_id=' . get_link_user_id ( $user_id ) .
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
      echo '</td>';
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
   $continue_flag = true;
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   if ( $group_index == 1 )
   {
      $databaseapi = new databaseapi();
      $sub_project_ids = project_hierarchy_get_all_subprojects ( helper_get_current_project () );
      if ( helper_get_current_project () > 0 )
      {
         array_push ( $sub_project_ids, helper_get_current_project () );
      }
      foreach ( $sub_project_ids as $sub_project_id )
      {
         $user_is_assigned_to_project = $databaseapi->check_user_project_assignment ( $user_id, $sub_project_id );
         if ( ( !is_null ( $user_is_assigned_to_project ) )
            && ( check_user_has_level ( $sub_project_id ) )
         )
         {
            $continue_flag = false;
         }
      }
      if ( $continue_flag )
      {
         return $stat_issue_count;
      }
   }
   else
   {
      if ( !check_user_has_level ( $assigned_project_id ) )
      {
         return $stat_issue_count;
      }
   }

   echo '<tr class="info" data-level="2" data-status="1">';
   echo '<td></td>';
   if ( $print )
   {
      echo '<td></td>';

      $no_user = get_no_user ( $user_id );
      $no_issue = $data_row[ 'no_issue' ];
      $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
      $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
      get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'nowrap' );
      echo '</td>';
   }
   else
   {
      if ( $group_index == 1 )
      {
         print_chackbox ( $data_row );
      }
      else
      {
         echo '<td></td>';
      }
      print_user_avatar ( $data_row, $group_index );
   }

   if ( $group_index == 1 )
   {
      print_user_name ( $data_row );
      print_real_name ( $data_row );
   }

   print_layer_one_project ( $data_row, $print, $group_index );
   $project_hierarchy_depth = get_project_hierarchy_depth ( helper_get_current_project () );
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
   echo '</tr>';

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
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $no_user = get_no_user ( $user_id );
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
   echo '</td>';
}

/**
 * Print the avatar in the user row of the plugin table
 *
 * @param $data_row
 * @param $group_index
 */
function print_user_avatar ( $data_row, $group_index )
{
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $user_id = $data_row[ 'user_id' ];
   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );

   if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
   {
      if ( $group_index > 0 )
      {
         if ( ( !user_exists ( $user_id ) && !$no_user )
            || ( check_user_id_is_valid ( $user_id ) && !check_user_id_is_enabled ( $user_id ) && plugin_config_get ( 'IAUHighlighting' ) )
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
            if ( access_has_global_level ( $access_level ) && $group_index != 1 )
            {
               echo '<a href="search.php?' . generate_status_link () .
                  '&amp;handler_id=' . get_link_user_id ( $user_id ) .
                  '&amp;sortby=last_updated' .
                  '&amp;dir=DESC' .
                  '&amp;hide_status_id=-2' .
                  '&amp;match_type=0">';
            }

            if ( config_get ( 'show_avatar' ) && $access_level >= config_get ( 'show_avatar_threshold' ) )
            {
               if ( $user_id > 0 )
               {
                  $avatar = user_get_avatar ( $user_id );
                  echo '<img class="avatar" src="' . $avatar [ 0 ] . '" />';
               }
            }

            if ( access_has_global_level ( $access_level ) && $group_index != 1 )
            {
               echo '</a>';
            }
         }
         echo '</td>';
      }
      else
      {
         $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
         $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
         echo '<td>';
         if ( !$no_user && !$unreachable_issue )
         {
            echo '<label>';
            echo '<input type="checkbox" name="dataRow[]" value="' . $user_id . ',' . $assigned_project_id . '"/>';
            echo '</label>';
         }
         echo '</td>';
      }
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
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   if ( $user_id > 0 )
   {
      $user_name = user_get_name ( $user_id );
   }
   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'nowrap' );
   if ( check_user_id_is_valid ( $user_id ) )
   {
      echo $user_name;
   }
   else
   {
      echo '<s>' . $user_name . '</s>';
   }
   echo '</td>';
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
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   if ( check_user_id_is_valid ( $user_id ) )
   {
      $real_name = user_get_realname ( $user_id );
   }
   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'nowrap' );
   echo $real_name;
   echo '</td>';
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
      $layer_one_project_id = get_main_project_id ( $assigned_project_id );
   }

   if ( $layer_one_project_id != 0 )
   {
      $layer_one_project_name = project_get_name ( $layer_one_project_id );
   }
   else
   {
      $layer_one_project_name = '';
   }

   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $colspan = 1;
   if ( $group_index == 0 || $group_index == 3 )
   {
      $colspan = 3;
   }
   elseif ( $group_index == 1 )
   {
      $colspan = get_project_hierarchy_spec_colspan ( 2, false );
   }

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, $colspan, 'normalwrap' );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . generate_status_link () .
         '&amp;project_id=' . $layer_one_project_id .
         '&amp;handler_id=' . get_link_user_id ( $user_id ) .
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
   echo '</td>';
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
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
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

   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'normalwrap' );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . generate_status_link () .
         '&amp;project_id=' . $version_assigned_project_id .
         '&amp;handler_id=' . get_link_user_id ( $user_id ) .
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
   echo '</td>';
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

   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'normalwrap' );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . generate_status_link () .
         '&amp;project_id=' . $assigned_project_id .
         '&amp;handler_id=' . get_link_user_id ( $user_id ) .
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
   echo '</td>';
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
   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'breakwordwrap' );
   echo $target_version_date . ' ';
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      echo '<a href="search.php?' . generate_status_link () .
         '&amp;project_id=' . $assigned_project_id .
         '&amp;handler_id=' . get_link_user_id ( $user_id ) .
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
   echo '</td>';
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
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $target_version = '';
   if ( strlen ( $target_version_id ) > 0 )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
   }

   $stat_issue_count_array = array ();
   $issue_amount_thresholds = array ();
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_count_array[ $stat_index ] = $data_row[ 'stat_col' . $stat_index ];
      $issue_amount_thresholds[ $stat_index ] = plugin_config_get ( 'IAMThreshold' . $stat_index );
   }

   $user_id = $data_row[ 'user_id' ];
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      if ( $group_index == 0 )
      {
         /** Group 0 - ignore issue count for ignored status */
         if ( ( plugin_config_get ( 'CStatIgn' . $stat_index ) == ON )
            && ( check_user_id_is_enabled ( $user_id ) )
         )
         {
            $temp_stat_issue_count = 0;
         }
         /** Group 2 - ignore issue count for valid status */
         else
         {
            $temp_stat_issue_count = $stat_issue_count_array[ $stat_index ];
         }
      }
      /** Group 3 - ignore issue count for valid status */
      elseif ( $group_index == 3 )
      {
         if ( plugin_config_get ( 'CStatIgn' . $stat_index ) == OFF )
         {
            $temp_stat_issue_count = 0;
         }
         else
         {
            $temp_stat_issue_count = $stat_issue_count_array[ $stat_index ];
         }
      }
      /** other groups - get issue count for all status */
      else
      {
         $temp_stat_issue_count = $stat_issue_count_array[ $stat_index ];
      }

      $stat_issue_amount_threshold = $issue_amount_thresholds[ $stat_index ];
      $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
      $stat_issue_count[ $stat_index ] += $temp_stat_issue_count;
      /** group 2 -> mark all cells where issue count > 0 */
      if ( ( !check_user_id_is_enabled ( $user_id ) )
         && ( $temp_stat_issue_count > 0 )
         && ( $group_index != 3 )
      )
      {
         echo '<td class="group_row_bg" style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">';
      }
      /** group 0, 1, 3 -> mark cell if threshold is reached */
      else
      {
         if ( ( $stat_issue_amount_threshold <= $temp_stat_issue_count ) && ( $stat_issue_amount_threshold > 0 ) )
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
         echo '<a href="search.php?project_id=' . $assigned_project_id .
            '&amp;status_id=' . $stat_status_id .
            '&amp;handler_id=' . get_link_user_id ( $data_row[ 'user_id' ] ) .
            '&amp;sticky_issues=on' .
            '&amp;target_version=' . $target_version .
            '&amp;sortby=last_updated' .
            '&amp;dir=DESC' .
            '&amp;hide_status_id=-2' .
            '&amp;match_type=0">';
         echo $temp_stat_issue_count;
         echo '</a>';
      }
      else
      {
         echo $temp_stat_issue_count;
      }
      echo '</td>';
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
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $target_version = '';
   if ( strlen ( $target_version_id ) > 0 )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
   }
   $no_user = get_no_user ( $user_id );
   $no_issue = $data_row[ 'no_issue' ];

   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue, 1, 'nowrap' );
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_age_threshold = plugin_config_get ( 'IAGThreshold' . $stat_index );
      if ( $assigned_project_id == null )
      {
         continue;
      }

      $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
      $databaseapi = new databaseapi();
      $stat_issue_id_assoc_array = $databaseapi->get_issues_by_user_project_version_status ( $user_id, $assigned_project_id, $target_version, $stat_status_id );
      $stat_issue_id_db_result = mysqli_fetch_row ( $stat_issue_id_assoc_array );
      $stat_issue_ids = array ();
      while ( $stat_issue_id = $stat_issue_id_db_result [ 0 ] )
      {
         $stat_issue_ids[] = $stat_issue_id;
         $stat_issue_id_db_result = mysqli_fetch_row ( $stat_issue_id_assoc_array );
      }

      if ( $stat_issue_ids != null )
      {
         $stat_time_difference = calculate_time_difference ( $stat_issue_ids )[ 0 ];
         $stat_oldest_issue_id = calculate_time_difference ( $stat_issue_ids )[ 1 ];

         if ( $stat_time_difference > $stat_issue_age_threshold && !$print )
         {
            $stat_issue_id_db_result = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
            echo '<a href="search.php?project_id=' . $assigned_project_id .
               '&amp;search=' . $stat_oldest_issue_id .
               '&amp;status_id=' . $stat_status_id .
               '&amp;handler_id=' . get_link_user_id ( $user_id ) .
               '&amp;sticky_issues=on' .
               '&amp;target_version=' . $target_version .
               '&amp;sortby=last_updated' .
               '&amp;dir=DESC' .
               '&amp;hide_status_id=-2' .
               '&amp;match_type=0">';
            echo $stat_issue_id_db_result [ $stat_status_id ] .
               ' ' . plugin_lang_get ( 'remark_since' ) . ' ' . $stat_time_difference .
               ' ' . plugin_lang_get ( 'remark_day' );
            echo '<br/>';
            echo '</a>';
         }
      }
   }

   if ( $unreachable_issue )
   {
      echo '<a href="search.php?project_id=' . $assigned_project_id .
         prepare_filter_string ( $data_row, $group_index ) .
         '&amp;handler_id=' . get_link_user_id ( $user_id ) .
         '&amp;sticky_issues=on' .
         '&amp;target_version=' . $target_version .
         '&amp;sortby=last_updated' .
         '&amp;dir=DESC' .
         '&amp;hide_status_id=-2' .
         '&amp;match_type=0">';
      echo wordwrap ( plugin_lang_get ( 'remark_noProject' ), 30, '<br />' );
      echo '</a>';
      echo '<br/>';
   }
   if ( $user_id > 0 )
   {
      if ( !user_exists ( $user_id ) || !check_user_id_is_enabled ( $user_id ) )
      {
         echo plugin_lang_get ( 'remark_IAUser' ) . '<br/>';
      }
   }
   if ( $group_index == 1 )
   {
      $databaseapi = new databaseapi();
      $user_is_assigned_to_project = $databaseapi->check_user_project_assignment ( $user_id, helper_get_current_project () );
      if ( is_null ( $user_is_assigned_to_project ) && helper_get_current_project () > 0 )
      {
         echo plugin_lang_get ( 'remark_noprojectassignment' );
      }
   }
   echo '</td>';
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
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   ?>
   <tr>
      <td colspan="<?php echo get_project_hierarchy_spec_colspan ( 6, true ); ?>">
         <?php
         if ( !$print )
         {
            if ( $access_level >= plugin_config_get ( 'UserProjectAccessLevel' ) )
            {
               ?>
               <label for="option"></label>
               <select id="option" name="option">
                  <option value="removeSingle"><?php echo plugin_lang_get ( 'remove_selectSingle' ) ?></option>
                  <option value="removeAll"><?php echo plugin_lang_get ( 'remove_selectAll' ) ?></option>
               </select>
               <input type="submit" name="formSubmit" class="button" value="<?php echo lang_get ( 'ok' ); ?>"/>
               <?php
            }
         }
         ?>
      </td>
      <?php
      for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
      {
         ?>
         <td><?php echo $stat_issue_count[ $stat_index ]; ?></td>
         <?php
      }
      ?>
      <td></td>
   </tr>
   <?php
}

/** ***************************************************************************************************************** */