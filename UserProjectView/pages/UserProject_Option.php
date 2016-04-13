<?php
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_system_api.php';

$userprojectview_system_api = new userprojectview_system_api();

auth_reauthenticate();
html_page_top1( plugin_lang_get( 'menu_userprojecttitle' ) );
html_page_top2();

if ( plugin_is_installed( 'WhiteboardMenu' ) )
{
   require_once WHITEBOARDMENU_CORE_URI . 'whiteboard_print_api.php';
   $whiteboard_print_api = new whiteboard_print_api();
   $whiteboard_print_api->printWhiteboardMenu();
}

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

$selected_values = null;

if ( !empty( $_POST['dataRow'] ) )
{
   $selected_values = $_POST['dataRow'];
}

$record_count = count( $selected_values );

$select = strtolower( $_POST['option'] );

switch ( $select )
{
   case 'removesingle':

      echo '<div align="center">';
      echo '<hr size="1" width="50%" />';
      echo plugin_lang_get( 'remove_quest' ) . '<br/><br/>';

      echo '<table class="width50" cellspacing="1">';
      echo '<tr class="row-category">';
      echo '<th>' . plugin_lang_get( 'thead_username' ) . '</th>';
      echo '<th>' . plugin_lang_get( 'thead_project' ) . '</th>';
      echo '</tr>';

      for ( $recordIndex = 0; $recordIndex < $record_count; $recordIndex++ )
      {
         $record[$recordIndex] = explode( '__', $selected_values[$recordIndex] );

         $user_id = $record[$recordIndex][0];
         $project_id = $record[$recordIndex][1];

         echo '<form action="' . plugin_page( 'UserProject_RemoveSubmit' ) . '" method="post">';
         echo '<input type="hidden" name="recordSet[]" value="' . $selected_values[$recordIndex] . '"/>';

         if ( $userprojectview_system_api->getMantisVersion() == '1.2.' )
         {
            echo '<tr ' . helper_alternate_class() . '>';
         }
         else
         {
            echo '<tr>';
         }

         echo '<td>';
         echo '<a href="manage_user_edit_page.php?user_id=' . $user_id . '">';
         if ( user_exists( $user_id ) )
         {
            echo user_get_name( $user_id );
         }
         else
         {
            echo '<s>' . user_get_name( $user_id ) . '</s>';
         }
         echo '</a>';
         echo '</td>';
         echo '<td>';
         echo '<a href="manage_proj_edit_page.php?project_id=' . $project_id . '">';
         echo project_get_name( $project_id );
         echo '</a>';
         echo '</td>';
         echo '</tr>';
      }

      echo '<tr>';
      echo '<td class="center" colspan="2">';
      ?>
      <input type="submit" name="formSubmit" class="button"
             value="<?php echo plugin_lang_get( 'remove_selectSingle' ); ?>"/>
      <?php
      echo '</td>';
      echo '</tr>';
      echo '</table>';

      echo '<hr size="1" width="50%" /></div>';
      echo '</div>';

      break;

   case 'removeall':

      echo '<div align="center">';
      echo '<hr size="1" width="50%" />';
      echo plugin_lang_get( 'remove_quest' ) . '<br/><br/>';

      echo '<table class="width50" cellspacing="1">';
      echo '<tr class="row-category">';
      echo '<th>' . plugin_lang_get( 'thead_username' ) . '</th>';
      echo '<th>' . plugin_lang_get( 'thead_project' ) . '</th>';
      echo '</tr>';

      for ( $recordIndex = 0; $recordIndex < $record_count; $recordIndex++ )
      {
         $record[$recordIndex] = explode( '__', $selected_values[$recordIndex] );

         $user_id = $record[$recordIndex][0];
         $project_id = $record[$recordIndex][1];

         $sub_projects = array();
         array_push( $sub_projects, $project_id );
         $t_sub_projects = array();
         $t_sub_projects = project_hierarchy_get_all_subprojects( $project_id );

         foreach ( $t_sub_projects as $t_sub_project )
         {
            array_push( $sub_projects, $t_sub_project );
         }

         foreach ( $sub_projects as $sub_project )
         {
            echo '<form action="' . plugin_page( 'UserProject_RemoveSubmit' ) . '" method="post">';
            echo '<input type="hidden" name="user[]" value="' . $user_id . '"/>';
            echo '<input type="hidden" name="project[]" value="' . $sub_project . '"/>';

            if ( $userprojectview_system_api->getMantisVersion() == '1.2.' )
            {
               echo '<tr ' . helper_alternate_class() . '>';
            }
            else
            {
               echo '<tr>';
            }

            echo '<td>';
            echo '<a href="manage_user_edit_page.php?user_id=' . $user_id . '">';
            if ( user_exists( $user_id ) )
            {
               echo user_get_name( $user_id );
            }
            else
            {
               echo '<s>' . user_get_name( $user_id ) . '</s>';
            }
            echo '</a>';
            echo '</td>';
            echo '<td>';
            echo '<a href="manage_proj_edit_page.php?project_id=' . $sub_project . '">';
            echo project_get_name( $sub_project );
            echo '</a>';
            echo '</td>';
            echo '</tr>';
         }
         echo '<tr>';
         echo '<td class="spacer" colspan="6">&nbsp;</td>';
         echo '</tr>';
      }

      echo '<tr>';
      echo '<td class="center" colspan="2">';
      ?>
      <input type="submit" name="formSubmit" class="button"
             value="<?php echo plugin_lang_get( 'remove_selectAll' ); ?>"/>
      <?php
      echo '</td>';
      echo '</tr>';
      echo '</table>';

      echo '<hr size="1" width="50%" /></div>';
      echo '</div>';

      break;
}

html_page_bottom();