<?php

class userprojectview_print_api
{
   public function printUPMenu()
   {
      echo '<table align="center">';
      echo '<tr>';
      echo '<td>';
      echo '[ <a href="' . plugin_page( 'UserProject_Print' ) . '&sortVal=userName&sort=ASC">';
      echo plugin_lang_get( 'menu_printbutton' );
      echo '</a> ]';
      echo '</td>';
      echo '</tr>';
      echo '</table>';
   }

   public function printTH( $lang_string, $sort_val, $colspan )
   {
      if ( $colspan != null )
      {
         echo '<th />';
         echo '<th colspan="' . $colspan . '">';
      }
      else
      {
         echo '<th>';
      }

      echo plugin_lang_get( $lang_string ) . ' ';
      echo '<a href="' . plugin_page( 'UserProject' ) . '&sortVal=' . $sort_val . '&sort=ASC">';
      echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/up.gif"' . ' ';
      echo '</a>';
      echo '<a href="' . plugin_page( 'UserProject' ) . '&sortVal=' . $sort_val . '&sort=DESC">';
      echo '<img src="' . USERPROJECTVIEW_PLUGIN_URL . 'files/down.gif"' . ' ';
      echo '</a>';
      echo '</th>';
   }

   public function printTHRow( $colspan, $print_flag )
   {
      echo '<tr>';
      echo '<td class="form-title" colspan="' . ( $colspan - 1 ) . '">' .
         plugin_lang_get( 'menu_userprojecttitle' ) . ' - ' .
         plugin_lang_get( 'thead_projects_title' ) .
         project_get_name( helper_get_current_project() );
      echo '</td>';
      if ( !$print_flag )
      {
         echo '<td><form action="' . plugin_page( 'UserProject' ) . '&sortVal=userName&sort=ASC' . '" method="post">';
         echo '&nbsp<input type="submit" name="print_flag" class="button" value="' . lang_get( 'print' ) . '"/>';
         echo '</form></td>';
      }
      echo '</tr>';
   }

   public function printTDRow( $user_id, $row_index, $no_user_flag, $zero_issues_flag, $unreachable_issue_flag )
   {
      $iA_background_color = plugin_config_get( 'IAUHBGColor' );
      $uR_background_color = plugin_config_get( 'URIUHBGColor' );
      $nU_background_color = plugin_config_get( 'NUIHBGColor' );
      $zI_background_color = plugin_config_get( 'ZIHBGColor' );

      if ( user_exists( $user_id ) && $user_id != '0' && user_get_field( $user_id, 'enabled' ) == '0' && plugin_config_get( 'IAUHighlighting' ) )
      {
         echo '<tr style="background-color:' . $iA_background_color . '">';
      }
      elseif ( $zero_issues_flag && plugin_config_get( 'ZIHighlighting' ) )
      {
         echo '<tr style="background-color:' . $zI_background_color . '">';
      }
      elseif ( $no_user_flag && plugin_config_get( 'NUIHighlighting' ) )
      {
         echo '<tr style="background-color:' . $nU_background_color . '">';
      }
      elseif ( $unreachable_issue_flag && plugin_config_get( 'URIUHighlighting' ) )
      {
         echo '<tr style="background-color:' . $uR_background_color . '">';
      }
      else
      {
         if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
         {
            echo '<tr ' . helper_alternate_class( $row_index ) . '">';
         }
         else
         {
            echo '<tr class="row-' . $row_index . '">';
         }
      }
   }

   public function printConfigRow()
   {
      if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
      {
         echo '<tr ' . helper_alternate_class() . '>';
      }
      else
      {
         echo '<tr>';
      }
   }

   public function printSpacer( $colspan )
   {
      echo '<tr>';
      echo '<td class="spacer" colspan="' . $colspan . '">&nbsp;</td>';
      echo '</tr>';
   }

   public function printConfigCategory( $colspan, $rowspan, $lang_string )
   {
      echo '<td class="category" colspan="' . $colspan . '" rowspan="' . $rowspan . '">';
      echo plugin_lang_get( $lang_string );
      echo '</td>';
   }

   public function printConfigTitle( $colspan, $lang_string )
   {
      echo '<tr>';
      echo '<td class="form-title" colspan="' . $colspan . '">';
      echo plugin_lang_get( $lang_string );
      echo '</td>';
      echo '</tr>';
   }

   public function printRadioButton( $colspan, $name )
   {
      echo '<td width="100px" colspan="' . $colspan . '">';
      echo '<label>';
      echo '<input type="radio" name="' . $name . '" value="1"';
      echo ( ON == plugin_config_get( $name ) ) ? 'checked="checked"' : '';
      echo '/>' . lang_get( 'yes' );
      echo '</label>';
      echo '<label>';
      echo '<input type="radio" name="' . $name . '" value="0"';
      echo ( OFF == plugin_config_get( $name ) ) ? 'checked="checked"' : '';
      echo '/>' . lang_get( 'no' );
      echo '</label>';
      echo '</td>';
   }

   public function printColorPicker( $colspan, $name, $default )
   {
      echo '<td width="100px" colspan="' . $colspan . '">';
      echo '<label>';
      echo '<input class="color {pickerFace:4,pickerClosable:true}" type="text" name="' . $name . '" value="' . plugin_config_get( $name, $default ) . '" />';
      echo '</label>';
      echo '</td>';
   }

   public function print_table_head()
   {
      if ( substr( MANTIS_VERSION, 0, 4 ) == '1.2.' )
      {
         echo '<table class="width100" cellspacing="1">';
      }
      else
      {
         echo '<table>';
      }
   }
}