<?php
require_once USERPROJECTVIEW_CORE_URI . 'constantapi.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectapi.php';

auth_reauthenticate ();
html_page_top1 ( plugin_lang_get ( 'menu_userprojecttitle' ) );
?>
   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
   <script type="text/javascript" src="plugins/UserProjectView/javascript/table.js"></script>
   <link rel="stylesheet" href="plugins/UserProjectView/files/UserProjectView.css"/>
<?php
html_page_top2 ();
if ( plugin_is_installed ( 'WhiteboardMenu' ) && file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' ) )
{
   require_once WHITEBOARDMENU_CORE_URI . 'whiteboard_print_api.php';
   $whiteboard_print_api = new whiteboard_print_api();
   $whiteboard_print_api->printWhiteboardMenu ();
}

echo '<div align="center">';
echo '<hr size="1" width="50%"/>';
echo plugin_lang_get ( 'remove_quest' ) . '<br/><br/>';
echo '<table class="width50" cellspacing="1">';
print_thead ();
print_tbody ();
echo '</table>';
html_page_bottom ();

function print_thead ()
{
   echo '<thead>';
   echo '<tr>';
   echo '<th width="20px"></th>';
   echo '<th class="headrow" style="text-align: left" colspan="2">' . plugin_lang_get ( 'thead_username' ) . '</th>';
   echo '<th class="headrow" style="text-align: left">' . plugin_lang_get ( 'thead_realname' ) . '</th>';
   echo '</tr>';
   echo '<tr>';
   echo '<th></th>';
   echo '<th class="headrow" style="text-align: left" colspan="3">' . plugin_lang_get ( 'config_layer_one_name_two' ) . '</th>';
   echo '</tr>';
   echo '</thead>';
}

function print_tbody ()
{
   $selected_values = null;
   if ( isset( $_POST[ 'dataRow' ] ) )
   {
      $selected_values = $_POST[ 'dataRow' ];
   }
   $select = strtolower ( $_POST[ 'option' ] );

   /** prepare user groups */
   $user_group = prepare_user_project_remove_group ( $selected_values );

   echo '<tbody><form action="' . plugin_page ( 'UserProject_RemoveSubmit' ) . '" method="post">';
   foreach ( $user_group as $user )
   {
      $user_id = $user[ 0 ];
      $project_ids = explode ( ',', $user[ 1 ] );

      print_user_row ( $user_id );
      for ( $project_index = 0; $project_index < count ( $project_ids ); $project_index++ )
      {
         $project_id = $project_ids[ $project_index ];

         if ( $project_index > 0 )
         {
            $project_id_spec_sub_projects = project_hierarchy_get_all_subprojects ( $project_id );
            $old_project_id = $project_ids[ $project_index - 1 ];
            $old_project_id_spec_sub_projects = project_hierarchy_get_all_subprojects ( $old_project_id );

            if ( in_array ( $old_project_id, $project_id_spec_sub_projects ) )
            {
               /** alte löschen */
               $project_ids[ $project_index - 1 ] = null;
            }
            elseif ( in_array ( $project_id, $old_project_id_spec_sub_projects ) )
            {
               continue;
            }
         }

         switch ( $select )
         {
            case 'removesingle':

               print_project_row ( $user_id, $project_id );
               break;

            case 'removeall':

               $sub_project_ids = array ();
               array_push ( $sub_project_ids, $project_id );
               $t_sub_project_ids = project_hierarchy_get_all_subprojects ( $project_id );
               foreach ( $t_sub_project_ids as $t_sub_project_id )
               {
                  if ( !in_array ( $t_sub_project_id, $sub_project_ids, true ) )
                  {
                     array_push ( $sub_project_ids, $t_sub_project_id );
                  }
               }

               foreach ( $sub_project_ids as $sub_project_id )
               {
                  print_project_row ( $user_id, $sub_project_id );
               }
               break;
         }
      }
   }

   print_submit_button ();
   echo '</form></tbody>';
}

function print_user_row ( $user_id )
{
   echo '<tr class="clickable" data-level="0" data-status="0">';
   echo '<td class="icon" width="20px"></td>';
   print_avatar_col ( $user_id );
   print_user_name_col ( $user_id );
   print_realname_col ( $user_id );
   echo '</tr>';
}

function print_avatar_col ( $user_id )
{
   echo '<td class="group_row_bg" style="width: 25px">';
   $avatar = user_get_avatar ( $user_id );
   echo '<img class="avatar" src="' . $avatar [ 0 ] . '" />';
   echo '</td>';
}

function print_user_name_col ( $user_id )
{
   echo '<td class="group_row_bg">';
   if ( user_exists ( $user_id ) )
   {
      echo user_get_name ( $user_id );
   }
   else
   {
      echo '<s>' . user_get_name ( $user_id ) . '</s>';
   }
   echo '</td>';
}

function print_realname_col ( $user_id )
{
   echo '<td class="group_row_bg">';
   if ( user_exists ( $user_id ) )
   {
      echo user_get_realname ( $user_id );
   }
   else
   {
      echo '<s>' . user_get_realname ( $user_id ) . '</s>';
   }
   echo '</td>';
}

function print_project_row ( $user_id, $project_id )
{
   echo '<tr class="info" data-level="1" data-status="0">';
   echo '<input type="hidden" name="recordset[]" value="' . $user_id . ',' . $project_id . '"/>';
   echo '<td width="20px"></td>';
   echo '<td class="user_row_bg" style="text-align: left" colspan="3">' . project_get_name ( $project_id ) . '</td>';
   echo '</tr>';
}

function print_submit_button ()
{
   echo '<tr>';
   echo '<td class="center" colspan="4">';
   echo '<input type="submit" name="formSubmit" class="button" value="' . plugin_lang_get ( 'remove_selectSingle' ) . '"/>';
   echo '</td>';
   echo '</tr>';
}