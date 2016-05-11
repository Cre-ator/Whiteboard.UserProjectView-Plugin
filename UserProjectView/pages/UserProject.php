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
$data_rows = $result[ 0 ];
$matchcode_row_index = $result[ 1 ];

if ( plugin_config_get ( 'ShowZIU' ) )
{
   $data_rows = process_no_issue_users ( $data_rows, $matchcode_row_index, $project_id, $stat_cols );
}

html_page_top1 ( plugin_lang_get ( 'menu_userprojecttitle' ) );
?>
   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
   <script type="text/javascript" src="plugins/UserProjectView/javascript/table.js"></script>
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
print_thead ( $stat_cols, $print );
print_tbody ( $data_rows, $stat_cols, $issue_amount_thresholds, $issue_age_thresholds, $print );
echo '</table>';
echo '</div>';
if ( !$print )
{
   html_page_bottom1 ();
}

/** ********************* table head area *************************************************************************** */

/**
 * Print the head of the plugin table
 *
 * @param $stat_cols
 * @param $print
 */
function print_thead ( $stat_cols, $print )
{
   $colspan = 8;
   if ( plugin_config_get ( 'ShowAvatar' ) )
   {
      $colspan++;
   }

   $dynamic_colspan = get_stat_count () + $colspan;
   $header_colspan = $colspan - 6;

   echo '<thead>';
   print_main_table_head_row ( $dynamic_colspan, $print );
   echo '<tr>';
   print_main_table_head_col ( 'thead_username', 'userName', $header_colspan );
   print_main_table_head_col ( 'thead_realname', 'realName', null );
   print_main_table_head_col ( get_layer_one_column_name (), 'mainProject', null );
   print_main_table_head_col ( 'thead_layer_version_project', 'mainProject', null );
   print_main_table_head_col ( 'thead_layer_issue_project', 'assignedProject', null );
   print_main_table_head_col ( 'thead_targetversion', 'targetVersion', null );

   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      ?>
      <th style="width:150px;" class="headrow_status"
          bgcolor="<?php echo get_status_color ( $stat_cols[ $stat_index ], null, null ); ?>">
         <?php $status = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
         echo $status [ $stat_cols[ $stat_index ] ]; ?>
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
 * @param $print
 */
function print_main_table_head_row ( $dynamic_colspan, $print )
{
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
            <form action="<?php echo plugin_page ( 'UserProject' ); ?>&amp;sortVal=userName&amp;sort=ASC" method="post">
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
 * @param $stat_cols
 * @param $issue_amount_threshold
 * @param $issue_age_threshold
 * @param $print
 */
function print_tbody ( $data_rows, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $print )
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
   echo '<form action="' . plugin_page ( 'UserProject_Option' ) . '" method="post">';
   print_group_head_row ( $groups[ 0 ], $data_rows, $stat_cols, 'headrow_user' );
   $head_rows_array = calculate_user_head_rows ( $data_rows );
   foreach ( $head_rows_array as $head_row )
   {
      $head_row_user_id = $head_row[ 0 ];
      $head_row_counter = true;
      for ( $group_index = 0; $group_index < count ( $groups[ 0 ] ); $group_index++ )
      {
         $data_row_index = $groups[ 0 ][ $group_index ];
         $user_id = $data_rows[ $data_row_index ][ 'user_id' ];
         if ( $user_id == $head_row_user_id )
         {
            if ( $head_row_counter )
            {
               print_user_head_row ( $head_row, $user_id, $issue_amount_threshold, $print );
               $head_row_counter = false;
            }
            $stat_issue_count = print_user_row ( $data_row_index, $data_rows, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, false, $print );
         }
      }
   }

   $stat_issue_count = process_group ( $groups[ 1 ], $data_rows, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, 'headrow_no_issue', $print );
   $stat_issue_count = process_group ( $groups[ 2 ], $data_rows, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, 'headrow_del_user', $print );
   $stat_issue_count = process_group ( $groups[ 3 ], $data_rows, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, 'headrow_no_user', $print );
   print_option_panel ( $stat_issue_count, $print );
   echo '</form>';
   echo '</tbody>';
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
         $stat_issue_count[ $stat_index ] += $data_rows[ $data_row_index ][ 'stat_col' . $stat_index ];
      }
   }

   if ( !empty( $stat_issue_count ) )
   {
      $colspan = 7;
      if ( plugin_config_get ( 'ShowAvatar' ) )
      {
         $colspan = 8;
      }
      ?>
      <tr class="clickable" data-level="0" data-status="0">
         <td class="icon"></td>
         <td class="group_row_bg" colspan="<?php echo $colspan; ?>"><?php echo plugin_lang_get ( $lang_string ); ?></td>
         <?php
         for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
         {
            if ( $lang_string == 'headrow_del_user' && $stat_issue_count[ $stat_index ] > 0 )
            {
               $status = $stat_cols[ $stat_index ];
               if ( $status == '10' || $status == '20' || $status == '30' || $status == '40' || $status == '50' )
               {
                  echo '<td style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '">' . $stat_issue_count[ $stat_index ] . '</td>';
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
 * @param $user_id
 * @param $issue_amount_threshold
 * @param $print
 */
function print_user_head_row ( $head_row, $user_id, $issue_amount_threshold, $print )
{
   $filter_string = '<a href="search.php?handler_id=' . $user_id . '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
   ?>
   <tr class="clickable" data-level="1" data-status="0">
      <td style="max-width:15px;"></td>
      <td class="icon"></td>
      <td class="group_row_bg" align="center" style="max-width:25px;">
         <?php
         if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
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

         echo '<td class="group_row_bg">';
         if ( $print )
         {
            echo user_get_name ( $user_id );
         }
         else
         {
            echo $filter_string . user_get_name ( $user_id ) . '</a>';
         }
         echo '</td>';

         echo '<td class="group_row_bg">';
         if ( $print )
         {
            echo user_get_realname ( $user_id );
         }
         else
         {
            echo $filter_string . user_get_realname ( $user_id ) . '</a>';
         }
         echo '</td>';

         echo '<td class="group_row_bg" colspan="4"/>';
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
         ?>
      <td class="group_row_bg"></td>
   </tr>
   <?php
}

/**
 * Print a given user row detailed
 *
 * @param $data_rows
 * @param $data_row_index
 * @param $stat_cols
 * @param $print
 * @param $issue_age_threshold
 * @param $issue_amount_threshold
 * @param $stat_issue_count
 * @param $detailed
 * @return mixed
 */
function print_user_row ( $data_row_index, $data_rows, $stat_cols, $issue_amount_threshold, $issue_age_threshold, $stat_issue_count, $detailed, $print )
{
   $data_row = $data_rows[ $data_row_index ];
   get_user_row_cell_highlighting ( $data_row, $stat_cols, true );
   echo '<td/>';
   if ( $print )
   {
      echo '<td/>';
      $main_project_id = $data_row[ 'main_project_id' ];
      $assigned_project_id = validate_assigned_project_id ( $main_project_id, $data_row[ 'assigned_project_id' ] );
      $user_id = get_link_user_id ( $data_row[ 'user_id' ] );
      $no_user = get_no_user ( $stat_cols, $user_id );
      $no_issue = $data_row[ 'no_issue' ];
      $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
      $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
      get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );
   }
   else
   {
      if ( $detailed )
      {
         print_chackbox ( $data_row, $stat_cols );
      }
      else
      {
         echo '<td/>';
      }
      print_user_avatar ( $data_row, $stat_cols, $detailed );
   }
   print_user_name ( $data_row, $stat_cols, $print, $detailed );
   print_real_name ( $data_row, $stat_cols, $print, $detailed );
   print_layer_one_project ( $data_row, $stat_cols, $print );
   print_version_layer_project ( $data_row, $stat_cols, $print );
   print_bug_layer_project ( $data_row, $stat_cols, $print );
   print_target_version ( $data_row, $stat_cols, $print );
   $stat_issue_count = print_amount_of_issues ( $data_row, $issue_amount_threshold, $stat_cols, $stat_issue_count, $print );
   print_remark ( $data_row, $issue_age_threshold, $stat_cols, $print );
   echo '</tr>';

   return $stat_issue_count;
}

/**
 * Print the checkbox in the user row of the plugin table
 *
 * @param $data_row
 * @param $stat_cols
 */
function print_chackbox ( $data_row, $stat_cols )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $no_user = get_no_user ( $stat_cols, $user_id );

   echo '<td width="15px">';
   if ( !$no_user && !$unreachable_issue )
   {
      ?>
      <label>
         <input type="checkbox" name="dataRow[]" value="<?php echo $user_id . '_' . $assigned_project_id; ?>"/>
      </label>
      <?php
   }
   echo '</td>';
}

/**
 * Print the avatar in the user row of the plugin table
 *
 * @param $data_row
 * @param $stat_cols
 * @param $detailed
 */
function print_user_avatar ( $data_row, $stat_cols, $detailed )
{
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $user_id = $data_row[ 'user_id' ];
   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );

   if ( plugin_config_get ( 'ShowAvatar' ) && config_get ( 'show_avatar' ) )
   {
      if ( $detailed )
      {
         if ( ( !user_exists ( $user_id ) && !$no_user )
            || ( check_user_id_is_valid ( $user_id ) && user_get_field ( $user_id, 'enabled' ) == '0' && plugin_config_get ( 'IAUHighlighting' ) )
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
            if ( access_has_global_level ( $access_level ) )
            {
               $link_user_id = get_link_user_id ( $user_id );
               echo '<a href="search.php?handler_id=' . $link_user_id .
                  '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
            }

            if ( config_get ( 'show_avatar' ) && $access_level >= config_get ( 'show_avatar_threshold' ) )
            {
               if ( $user_id > 0 )
               {
                  $avatar = user_get_avatar ( $user_id );
                  echo '<img class="avatar" src="' . $avatar [ 0 ] . '" />';
               }
            }

            if ( access_has_global_level ( $access_level ) )
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
            ?>
            <label>
               <input type="checkbox" name="dataRow[]" value="<?php echo $user_id . '_' . $assigned_project_id; ?>">
            </label>
            <?php
         }
         echo '</td>';
      }
   }
}

/**
 * Print the username in the user row of the plugin table
 *
 * @param $data_row
 * @param $stat_cols
 * @param $print
 * @param $detailed
 */
function print_user_name ( $data_row, $stat_cols, $print, $detailed )
{
   $user_id = $data_row[ 'user_id' ];
   $user_name = '';
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   if ( $user_id > 0 )
   {
      $user_name = user_get_name ( $user_id );
   }
   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );

   if ( $detailed )
   {
      if ( access_has_global_level ( $access_level ) && !$print )
      {
         echo '<a href="search.php?handler_id=' . get_link_user_id ( $user_id ) .
            '&amp;sortby=last_updated&amp;dir=DESC&amp;hide_status_id=-2&amp;match_type=0">';
         if ( check_user_id_is_valid ( $user_id ) )
         {
            echo $user_name;
         }
         else
         {
            echo '<s>' . $user_name . '</s>';
         }
         echo '</a>';
      }
      else
      {
         if ( check_user_id_is_valid ( $user_id ) )
         {
            echo $user_name;
         }
         else
         {
            echo '<s>' . $user_name . '</s>';
         }
      }
   }
   echo '</td>';
}

/**
 * Print the real name in the user row of the plugin table
 *
 * @param $data_row
 * @param $stat_cols
 * @param $print
 * @param $detailed
 */
function print_real_name ( $data_row, $stat_cols, $print, $detailed )
{
   $user_id = $data_row[ 'user_id' ];
   $real_name = '';
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   if ( check_user_id_is_valid ( $user_id ) )
   {
      $real_name = user_get_realname ( $user_id );
   }
   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );

   if ( $detailed )
   {
      if ( access_has_global_level ( $access_level ) && !$print )
      {
         ?>
         <a href="search.php?handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                     &amp;sortby=last_updated
                     &amp;dir=DESC
                     &amp;hide_status_id=-2
                     &amp;match_type=0">
            <?php echo $real_name; ?>
         </a>
         <?php
      }
      else
      {
         echo $real_name;
      }
   }
   echo '</td>';
}

/**
 * Print the layer one project name in the user row of the plugin table
 *
 * @param $data_row
 * @param $stat_cols
 * @param $print
 */
function print_layer_one_project ( $data_row, $stat_cols, $print )
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

   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      ?>
      <a href="search.php?project_id=<?php echo $layer_one_project_id; ?>
                  &amp;handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                  &amp;sortby=last_updated
                  &amp;dir=DESC
                  &amp;hide_status_id=-2
                  &amp;match_type=0">
         <?php echo $layer_one_project_name; ?>
      </a>
      <?php
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
 * @param $stat_cols
 * @param $print
 */
function print_version_layer_project ( $data_row, $stat_cols, $print )
{
   $databaseapi = new databaseapi();
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $no_user = get_no_user ( $stat_cols, $user_id );
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

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      ?>
      <a href="search.php?project_id=<?php echo $version_assigned_project_id; ?>
                  &amp;handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                  &amp;sortby=last_updated
                  &amp;dir=DESC
                  &amp;hide_status_id=-2
                  &amp;match_type=0">
         <?php echo $version_assigned_project_name; ?>
      </a>
      <?php
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
 * @param $stat_cols
 * @param $print
 */
function print_bug_layer_project ( $data_row, $stat_cols, $print )
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

   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      ?>
      <a href="search.php?project_id=<?php echo $assigned_project_id; ?>
                  &amp;handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                  &amp;sortby=last_updated
                  &amp;dir=DESC
                  &amp;hide_status_id=-2
                  &amp;match_type=0">
         <?php echo $assigned_project_name; ?>
      </a>
      <?php
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
 * @param $stat_cols
 * @param $print
 */
function print_target_version ( $data_row, $stat_cols, $print )
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
   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];
   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );
   echo $target_version_date . ' ';
   if ( access_has_global_level ( $access_level ) && !$print )
   {
      ?>
      <a href="search.php?project_id=<?php echo $assigned_project_id; ?>
                  &amp;handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                  &amp;sticky_issues=on
                  &amp;target_version=<?php echo $target_version; ?>
                  &amp;sortby=last_updated
                  &amp;dir=DESC
                  &amp;hide_status_id=-2
                  &amp;match_type=0">
         <?php echo $target_version; ?>
      </a>
      <?php
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
 * @param $issue_amount_threshold
 * @param $stat_cols
 * @param $stat_issue_count
 * @param $print
 * @return mixed
 */
function print_amount_of_issues ( $data_row, $issue_amount_threshold, $stat_cols, $stat_issue_count, $print )
{
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $target_version = '';
   if ( strlen ( $target_version_id ) > 0 )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
   }

   $stat_issue_count_array = array ();
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_count_array[ $stat_index ] = $data_row[ 'stat_col' . $stat_index ];
   }

   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_amount_threshold = $issue_amount_threshold[ $stat_index ];
      $stat_status_id = $stat_cols[ $stat_index ];
      $temp_stat_issue_count = $stat_issue_count_array[ $stat_index ];
      $stat_issue_count[ $stat_index ] += $temp_stat_issue_count;
      if ( $stat_issue_amount_threshold < $temp_stat_issue_count && $stat_issue_amount_threshold > 0 )
      {
         echo '<td style="background-color:' . plugin_config_get ( 'TAMHBGColor' ) . '; width:150px;">';
      }
      else
      {
         echo '<td style="background-color:' . get_status_color ( $stat_cols[ $stat_index ], null, null ) . '; width:150px;">';
      }

      if ( !$print )
      {
         ?>
         <a href="search.php?project_id=<?php echo $assigned_project_id; ?>
                     &amp;status_id=<?php echo $stat_status_id; ?>
                     &amp;handler_id=<?php echo get_link_user_id ( $data_row[ 'user_id' ] ); ?>
                     &amp;sticky_issues=on
                     &amp;target_version=<?php echo $target_version; ?>
                     &amp;sortby=last_updated
                     &amp;dir=DESC
                     &amp;hide_status_id=-2
                     &amp;match_type=0">
            <?php echo $temp_stat_issue_count; ?>
         </a>
         <?php
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
 * @param $issue_age_threshold
 * @param $stat_cols
 * @param $print
 */
function print_remark ( $data_row, $issue_age_threshold, $stat_cols, $print )
{
   $user_id = $data_row[ 'user_id' ];
   $assigned_project_id = $data_row[ 'assigned_project_id' ];
   $target_version_id = $data_row[ 'target_version_id' ];
   $target_version = '';
   if ( strlen ( $target_version_id ) > 0 )
   {
      $target_version = version_get_field ( $target_version_id, 'version' );
   }
   $no_user = get_no_user ( $stat_cols, $user_id );
   $no_issue = $data_row[ 'no_issue' ];

   $assigned_to_project = get_assigned_to_project ( $user_id, $assigned_project_id );
   $unreachable_issue = get_unreachable_issue ( $assigned_to_project );

   get_cell_highlighting ( $user_id, $no_user, $no_issue, $unreachable_issue );
   for ( $stat_index = 1; $stat_index <= get_stat_count (); $stat_index++ )
   {
      $stat_issue_age_threshold = $issue_age_threshold[ $stat_index ];
      if ( $assigned_project_id == null )
      {
         continue;
      }

      $stat_status_id = $stat_cols[ $stat_index ];
      if ( $stat_status_id == USERPROJECTVIEW_ASSIGNED_STATUS && $stat_issue_age_threshold > 0
         || $stat_status_id == USERPROJECTVIEW_FEEDBACK_STATUS && $stat_issue_age_threshold > 0
         || $stat_status_id == 40 && $stat_issue_age_threshold > 0
      )
      {
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
               ?>
               <a href="search.php?project_id=<?php echo $assigned_project_id; ?>
                           &amp;search=<?php echo $stat_oldest_issue_id; ?>
                           &amp;status_id=<?php echo $stat_status_id; ?>
                           &amp;handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                           &amp;sticky_issues=on&target_version=<?php echo $target_version; ?>
                           &amp;sortby=last_updated
                           &amp;dir=DESC
                           &amp;hide_status_id=-2
                           &amp;match_type=0">
                  <?php $stat_issue_id_db_result = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
                  echo $stat_issue_id_db_result [ $stat_status_id ] .
                     ' ' . plugin_lang_get ( 'remark_since' ) . ' ' . $stat_time_difference .
                     ' ' . plugin_lang_get ( 'remark_day' );
                  ?>
                  <br/>
               </a>
               <?php
            }
            else
            {
               $stat_issue_id_db_result = MantisEnum::getAssocArrayIndexedByValues ( lang_get ( 'status_enum_string' ) );
               echo $stat_issue_id_db_result [ $stat_status_id ] .
                  ' ' . plugin_lang_get ( 'remark_since' ) . ' ' . $stat_time_difference .
                  ' ' . plugin_lang_get ( 'remark_day' ) . '<br/>';
            }
         }
      }
   }

   if ( $unreachable_issue )
   {
      $unreachable_issue_status = plugin_config_get ( 'URIThreshold' );
      echo plugin_lang_get ( 'remark_noProject' );
      ?> [
      <a href="search.php?project_id=<?php echo $assigned_project_id .
         prepare_filter_string ( count ( $unreachable_issue_status ), $unreachable_issue_status ); ?>
                  &amp;handler_id=<?php echo get_link_user_id ( $user_id ); ?>
                  &amp;sticky_issues=on
                  &amp;target_version=<?php echo $target_version; ?>
                  &amp;sortby=last_updated
                  &amp;dir=DESC
                  &amp;hide_status_id=-2
                  &amp;match_type=0">
         <?php echo plugin_lang_get ( 'remark_showURIssues' ); ?>
      </a>
      ]<br/>
      <?php
   }
   if ( $user_id > 0 )
   {
      if ( !user_exists ( $user_id ) || !user_is_enabled ( $user_id ) )
      {
         echo plugin_lang_get ( 'remark_IAUser' ) . '<br/>';
      }
   }
   if ( $no_issue )
   {
      echo plugin_lang_get ( 'remark_ZIssues' ) . '<br/>';
   }
   if ( $no_user )
   {
      echo plugin_lang_get ( 'remark_noUser' ) . '<br/>';
   }
   echo '</td>';
}

/**
 * Print the option panel where the user manage user->project-assignments and the overall amount of issues
 * for each status under the user table
 *
 * @param $stat_issue_count
 * @param $print
 */
function print_option_panel ( $stat_issue_count, $print )
{
   $access_level = user_get_access_level ( auth_get_current_user_id (), helper_get_current_project () );
   $colspan = 9;
   if ( plugin_config_get ( 'ShowAvatar' ) )
   {
      $colspan = 10;
   }
   $footer_colspan = $colspan - 1;
   ?>
   <tr>
      <td colspan="<?php echo $footer_colspan; ?>">
         <?php
         if ( !$print )
         {
            if ( access_has_global_level ( $access_level ) )
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