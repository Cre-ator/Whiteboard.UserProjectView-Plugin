<?php

class userprojectapi
{
   /**
    * get database connection infos and connect to the database
    *
    * @return mysqli
    */
   public static function initializeDbConnection ()
   {
      $dbPath = config_get ( 'hostname' );
      $dbUser = config_get ( 'db_username' );
      $dbPass = config_get ( 'db_password' );
      $dbName = config_get ( 'database_name' );

      $mysqli = new mysqli( $dbPath, $dbUser, $dbPass, $dbName );
      $mysqli->connect ( $dbPath, $dbUser, $dbPass, $dbName );

      return $mysqli;
   }

   /**
    * returns array with 1/0 values when plugin comprehensive table is installed
    *
    * @return array
    */
   public static function checkWhiteboardTablesExist ()
   {
      $boolArray = array ();

      $boolArray[ 0 ] = self::checkTable ( 'menu' );

      return $boolArray;
   }

   /**
    * checks if given table exists
    *
    * @param $tableName
    * @return bool
    */
   private static function checkTable ( $tableName )
   {
      $mysqli = self::initializeDbConnection ();

      $query = /** @lang sql */
         'SELECT COUNT(id) FROM mantis_plugin_whiteboard_' . $tableName . '_table';
      $result = $mysqli->query ( $query );
      $mysqli->close ();
      if ( $result->num_rows != 0 )
      {
         return true;
      }
      else
      {
         return false;
      }
   }

   public static function checkPluginIsRegisteredInWhiteboardMenu ()
   {
      $pluginName = plugin_lang_get ( 'menu_userprojecttitle', 'UserProjectView' );

      $mysqli = self::initializeDbConnection ();

      $query = /** @lang sql */
         'SELECT COUNT(id) FROM mantis_plugin_whiteboard_menu_table
         WHERE plugin_name=\'' . $pluginName . '\'';

      $result = $mysqli->query ( $query );
      $mysqli->close ();
      if ( $result->num_rows != 0 )
      {
         $resultCount = mysqli_fetch_row ( $result )[ 0 ];
         if ( $resultCount > 0 )
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
    * register plugin in whiteboard menu
    */
   public static function addPluginToWhiteboardMenu ()
   {
      $pluginName = plugin_lang_get ( 'menu_userprojecttitle', 'UserProjectView' );
      $pluginAccessLevel = ADMINISTRATOR;
      $pluginShowMenu = ON;
      $pluginMenuPath = '<a href="' . plugin_page ( 'UserProject' ) . '&sortVal=userName&sort=ASC">' . plugin_lang_get ( 'menu_userprojecttitle' ) . '</a>';

      $mysqli = self::initializeDbConnection ();

      $query = /** @lang sql */
         'INSERT INTO mantis_plugin_whiteboard_menu_table (id, plugin_name, plugin_access_level, plugin_show_menu, plugin_menu_path)
         SELECT null,\'' . $pluginName . '\',' . $pluginAccessLevel . ',' . $pluginShowMenu . ',\'' . $pluginMenuPath . '\'
         FROM DUAL WHERE NOT EXISTS (
         SELECT 1 FROM mantis_plugin_whiteboard_menu_table
         WHERE plugin_name=\'' . $pluginName . '\')';

      $mysqli->query ( $query );
      $mysqli->close ();
   }

   /**
    * edit plugin data in whiteboard menu
    *
    * @param $field
    * @param $value
    */
   public static function editPluginInWhiteboardMenu ( $field, $value )
   {
      $pluginName = plugin_lang_get ( 'menu_userprojecttitle', 'UserProjectView' );

      $mysqli = self::initializeDbConnection ();

      $query = /** @lang sql */
         'UPDATE mantis_plugin_whiteboard_menu_table
         SET ' . $field . '=\'' . $value . '\'
         WHERE plugin_name =\'' . $pluginName . '\'';

      $mysqli->query ( $query );
      $mysqli->close ();
   }

   /**
    * remove plugin from whiteboard menu
    */
   public static function removePluginFromWhiteboardMenu ()
   {
      $pluginName = plugin_lang_get ( 'menu_userprojecttitle', 'UserProjectView' );

      $mysqli = self::initializeDbConnection ();

      $query = /** @lang sql */
         'DELETE FROM mantis_plugin_whiteboard_menu_table
         WHERE plugin_name=\'' . $pluginName . '\'';

      $mysqli->query ( $query );
      $mysqli->close ();
   }

   /**
    * Returns true, if the used mantis version is a 1.2.x release
    *
    * @return bool
    */
   public static function is_mantis_rel ()
   {
      return substr ( MANTIS_VERSION, 0, 4 ) == '1.2.';
   }

   /**
    * Returns true, if the user has access level
    *
    * @param $project_id
    * @return bool
    */
   public static function check_user_has_level ( $project_id )
   {
      $user_id = auth_get_current_user_id ();

      return user_get_access_level ( $user_id, $project_id ) >= plugin_config_get ( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
   }

   /**
    * Get the amount of status columns for the plugin
    *
    * @return int|string
    */
   public static function get_stat_count ()
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
   public static function calc_matchcodes ()
   {
      $matchcode = array ();
      $per_page = 10000;
      $page_number = gpc_get_int ( 'page_number', 1 );
      $page_count = null;
      $bug_count = null;

      $rows = filter_get_bug_rows ( $page_number, $per_page, $page_count, $bug_count, unserialize ( '' ), null, null, true );
      for ( $row_index = 0; $row_index < count ( $rows ); $row_index++ )
      {
         $bug_id = $rows[ $row_index ]->id;
         $target_version = bug_get_field ( $bug_id, 'target_version' );
         $assigned_project_id = bug_get_field ( $bug_id, 'project_id' );
         $status = bug_get_field ( $bug_id, 'status' );
         $user_id = bug_get_field ( $bug_id, 'handler_id' );

         /** filter config specific bug status */
         if ( !in_array ( false, userprojectapi::set_irrelevant ( $status ) ) )
         {
            continue;
         }

         /** target version id */
         $target_version_id = userprojectapi::get_target_version_id ( $target_version, $assigned_project_id );

         /** final user active status */
         $no_user = userprojectapi::get_user_active ( $user_id );

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
   public static function get_layer_one_column_name ()
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
   public static function get_target_version_id ( $target_version, $project_id )
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
   public static function get_user_active ( $user_id )
   {
      if ( userprojectapi::check_user_id_is_valid ( $user_id ) )
      {
         return userprojectapi::check_user_id_is_enabled ( $user_id );
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
   public static function check_user_id_is_enabled ( $user_id )
   {
      if ( !userprojectapi::check_user_id_is_valid ( $user_id ) )
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
   public static function check_user_id_is_valid ( $user_id )
   {
      return ( ( $user_id > 0 ) && ( user_exists ( $user_id ) ) );
   }

   /**
    * Extract the bundled information in the matchcode array and returns it reorganized
    *
    * @param $matchcode
    * @return array
    */
   public static function process_match_codes ( $matchcode )
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
         $target_version = userprojectapi::get_target_version ( $target_version_id );

         /** fill tablerow with data */
         $data_rows[ $matchcode_row_index ][ 'user_id' ] = $user_id;
         $data_rows[ $matchcode_row_index ][ 'assigned_project_id' ] = $assigned_project_id;
         $data_rows[ $matchcode_row_index ][ 'target_version_id' ] = $target_version_id;
         $data_rows[ $matchcode_row_index ][ 'no_user' ] = $user_active;
         $data_rows[ $matchcode_row_index ][ 'no_issue' ] = false;

         for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
    * @param $group
    * @param $data_rows
    * @return array
    */
   public static function process_no_user_matchcodes ( $group, $data_rows )
   {
      $group_three_matchcode = array ();
      foreach ( $group as $data_row_index )
      {
         $data_row = $data_rows[ $data_row_index ];
         $assigned_project_id = $data_row[ 'assigned_project_id' ];
         $target_version_id = $data_row[ 'target_version_id' ];
         $data_string = $assigned_project_id . ',' . $target_version_id;
         array_push ( $group_three_matchcode, $data_string );
      }

      $group_three_data_rows = array ();
      $matchcode_rows = array_count_values ( $group_three_matchcode );
      $matchcode_row_count = count ( $matchcode_rows );
      for ( $matchcode_row_index = 0; $matchcode_row_index < $matchcode_row_count; $matchcode_row_index++ )
      {
         /** process first entry in array */
         $matchcode_row_data = key ( $matchcode_rows );

         /** process data string */
         $matchcode_row_data_values = explode ( ',', $matchcode_row_data );

         $assigned_project_id = $matchcode_row_data_values[ 0 ];
         $target_version_id = $matchcode_row_data_values[ 1 ];
         $target_version = userprojectapi::get_target_version ( $target_version_id );

         $group_three_data_rows[ $matchcode_row_index ][ 'user_id' ] = 0;
         $group_three_data_rows[ $matchcode_row_index ][ 'assigned_project_id' ] = $assigned_project_id;
         $group_three_data_rows[ $matchcode_row_index ][ 'target_version_id' ] = $target_version_id;
         $group_three_data_rows[ $matchcode_row_index ][ 'no_issue' ] = false;

         for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
         {
            $group_three_data_rows[ $matchcode_row_index ][ 'stat_col' . $stat_index ] = '0';
            $stat_column = 'stat_col' . $stat_index;
            $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
            if ( !is_null ( $stat_status_id ) )
            {
               $databaseapi = new databaseapi();
               $group_three_data_rows[ $matchcode_row_index ][ $stat_column ] = $databaseapi->get_amount_issues_by_project_version_status ( $assigned_project_id, $target_version, $stat_status_id );
            }
         }
         array_shift ( $matchcode_rows );
      }

      return $group_three_data_rows;
   }

   /**
    * get the target version by a given target version id
    *
    * @param $target_version_id
    * @return string
    */
   public static function get_target_version ( $target_version_id )
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
   public static function process_no_issue_users ( $data_rows, $matchcode_row_index, $project_id )
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
         if ( !userprojectapi::get_user_active ( $user_id ) )
         {
            continue;
         }

         $issue_count = 0;
         if ( $project_id == 0 )
         {
            for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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

            if ( !userprojectapi::check_user_assigned_to_project_hierarchy ( $sub_project_ids, $user_id ) )
            {
               continue;
            }

            for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
            $data_rows[ $additional_row_index ][ 'no_user' ] = userprojectapi::get_user_active ( $user_id );
            $data_rows[ $additional_row_index ][ 'no_issue' ] = true;

            for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
   public static function check_user_assigned_to_project_hierarchy ( $sub_project_ids, $user_id )
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
   public static function get_link_user_id ( $user_id )
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
   public static function get_sort_col ( $get_sort_val, $data_rows )
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
         if ( userprojectapi::check_user_id_is_valid ( $user_id[ $key ] ) )
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
   public static function get_sort_order ( $get_sort_order )
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
   public static function assign_groups ( $groups, $data_rows )
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
               if ( userprojectapi::check_user_id_is_enabled ( $data_rows[ $data_row_index ][ 'user_id' ] ) )
               {
                  $valid_stat_issue_count = 0;
                  $ignored_stat_issue_count = 0;
                  for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
   public static function calculate_user_head_rows ( $data_rows, $valid_flag )
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
            if ( !user_exists ( $user_id ) || !userprojectapi::check_user_id_is_enabled ( $user_id ) )
            {
               continue;
            }
         }

         $head_row = array ();
         $stat_issue_count = array ();
         $assigned_project_id = $data_rows[ $data_row_index ][ 'assigned_project_id' ];
         if ( userprojectapi::check_user_has_level ( $assigned_project_id ) )
         {
            for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
            {
               $stat_issue_count[ $stat_index ] = $data_rows[ $data_row_index ][ 'stat_col' . $stat_index ];
            }
         }
         else
         {
            for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
                     if ( userprojectapi::check_user_has_level ( $assigned_project_id ) )
                     {
                        for ( $iCounter_index = 1; $iCounter_index <= userprojectapi::get_stat_count (); $iCounter_index++ )
                        {
                           $temp_stat_issue_count[ $iCounter_index ] += $data_rows[ $data_row_index ][ 'stat_col' . $iCounter_index ];
                        }
                     }
                     else
                     {
                        for ( $iCounter_index = 1; $iCounter_index <= userprojectapi::get_stat_count (); $iCounter_index++ )
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
    * @return mixed
    */
   public static function process_general_group ( $group, $data_rows, $stat_issue_count, $group_index, $group_name )
   {
      /** group 3 */
      if ( $group_index == 3 )
      {
         print_group_three_head_row ( $data_rows, $group_name );
         foreach ( $data_rows as $data_row )
         {
            $assigned_project_id = $data_row[ 'assigned_project_id' ];
            /** pass data row, if user has no access level */
            if ( !userprojectapi::check_user_has_level ( $assigned_project_id ) )
            {
               continue;
            }
            else
            {
               $stat_issue_count = print_user_row ( $data_row, $stat_issue_count, $group_index );
            }
         }
      }
      /** group 1 */
      else
      {
         print_group_head_row ( $group, $data_rows, $group_index, $group_name );
         foreach ( $group as $data_row_index )
         {
            $data_row = $data_rows[ $data_row_index ];
            /** assigned_project_id is always null, so check current selected project and subprojects,
             * if the user has permission to see info
             */
            $user_permission = userprojectapi::check_user_permission ();

            /** pass data row, if user has no access level */
            if ( $user_permission )
            {
               $stat_issue_count = print_user_row ( $data_row, $stat_issue_count, $group_index );
            }
            else
            {
               continue;
            }
         }
      }

      return $stat_issue_count;
   }

   /**
    * fills an array with information flags for each user row
    *
    * @param $group
    * @param $data_rows
    * @return array
    */
   public static function create_information_flag_array ( $group, $data_rows )
   {
      $information_flag_array = array ();
      for ( $group_index = 0; $group_index < count ( $group ); $group_index++ )
      {
         $data_row_index = $group[ $group_index ];
         $user_id = $data_rows[ $data_row_index ][ 'user_id' ];
         $data_row = $data_rows[ $data_row_index ];

         $information_flag_array[ $group_index ][ 'user_id' ] = $user_id;
         $information_flag_array[ $group_index ][ 'information_flag' ] = false;
         if ( userprojectapi::check_remark ( $data_row ) )
         {
            $information_flag_array[ $group_index ][ 'information_flag' ] = true;
         }
      }

      return $information_flag_array;
   }

   /**
    * returns true, if one data row by a specific user_id has information
    *
    * @param $information_flag_array
    * @param $user_id
    * @return bool
    */
   public static function check_information_flag_array ( $information_flag_array, $user_id )
   {
      foreach ( $information_flag_array as $information_hash )
      {
         $information_hash_flag = $information_hash[ 'information_flag' ];
         if ( ( $information_hash[ 'user_id' ] == $user_id )
            && ( $information_hash_flag == true )
         )
         {
            return true;
         }
      }

      return false;
   }

   /**
    * check the user row for any remarks.
    * returns true, if > 0 remarks will be displayed.
    *
    * @param $data_row
    * @return bool
    */
   public static function check_remark ( $data_row )
   {
      /** old-issue information */
      $old_issues = userprojectapi::check_remark_old_issues ( $data_row );
      /** unreachable issue information */
      $unreachable_issues = userprojectapi::check_remark_unreachable_issues ( $data_row );
      /** invalid / deleted user information */
      $inactive = userprojectapi::check_remark_inactive ( $data_row );

      return ( $old_issues || $unreachable_issues || $inactive );
   }

   /**
    * check if there are old issues
    *
    * @param $data_row
    * @return bool
    */
   public static function check_remark_old_issues ( $data_row )
   {
      $old_issues = false;
      $databaseapi = new databaseapi();
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
         if ( $assigned_project_id == null )
         {
            continue;
         }

         $stat_issue_age_threshold = plugin_config_get ( 'IAGThreshold' . $stat_index );
         $stat_ignore_status = plugin_config_get ( 'CStatIgn' . $stat_index );
         $stat_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
         $stat_issue_ids = $databaseapi->get_issues_by_user_project_version_status ( $user_id, $assigned_project_id, $target_version, $stat_status_id, $stat_ignore_status, 0 );
         if ( !empty( $stat_issue_ids ) )
         {
            $stat_time_difference = userprojectapi::calculate_time_difference ( $stat_issue_ids )[ 0 ];
            if ( $stat_time_difference > $stat_issue_age_threshold )
            {
               if ( ( $stat_ignore_status == OFF ) )
               {
                  $old_issues = true;
               }
            }
         }
      }

      return $old_issues;
   }

   /**
    * checks if there are unreachable issues
    *
    * @param $data_row
    * @return bool
    */
   public static function check_remark_unreachable_issues ( $data_row )
   {
      $unreachable_issues = false;
      $user_id = $data_row[ 'user_id' ];
      $assigned_project_id = $data_row[ 'assigned_project_id' ];
      $assigned_to_project = userprojectapi::get_assigned_to_project ( $user_id, $assigned_project_id );
      $unreachable_issue = userprojectapi::get_unreachable_issue ( $assigned_to_project );
      if ( $unreachable_issue )
      {
         $unreachable_issues = true;
      }

      return $unreachable_issues;
   }

   /**
    * check if user is inactive
    *
    * @param $data_row
    * @return bool
    */
   public static function check_remark_inactive ( $data_row )
   {
      $user_id = $data_row[ 'user_id' ];
      $inactive = false;
      if ( $user_id > 0 )
      {
         if ( !user_exists ( $user_id )
            || !userprojectapi::check_user_id_is_enabled ( $user_id )
         )
         {
            $inactive = true;
         }
      }

      return $inactive;
   }

   /**
    * check to the currently selected project, if the user has permission in current or any subproject
    *
    * @return bool
    */
   public static function check_user_permission ()
   {
      $user_permission = false;
      $current_project_id = helper_get_current_project ();
      $sub_project_ids = project_hierarchy_get_all_subprojects ( $current_project_id );
      array_push ( $sub_project_ids, $current_project_id );
      foreach ( $sub_project_ids as $project_id )
      {
         if ( userprojectapi::check_user_has_level ( $project_id ) )
         {
            $user_permission = true;
         }
      }

      return $user_permission;
   }

   /**
    * generates a string which can be used for status specified links
    *
    * @return string
    */
   public static function generate_status_link ()
   {
      $status_link = '';
      if ( userprojectapi::get_stat_count () == 1 )
      {
         $status_link .= 'status_id=' . plugin_config_get ( 'CStatSelect1' );
      }
      else
      {
         for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
   public static function get_project_hierarchy_depth ( $project_id )
   {
      $project_hierarchy_depth = 1;
      if ( $project_id == 0 )
      {
         $project_hierarchy_depth = 3;
      }
      else
      {
         $top_level_project = userprojectapi::get_main_project_id ( $project_id );
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
   public static function get_project_hierarchy_spec_colspan ( $colspan, $avatar_flag )
   {
      $project_hierarchy_depth = userprojectapi::get_project_hierarchy_depth ( helper_get_current_project () );
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

   public static function prepare_user_project_remove_group ( $selected_values )
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
    * @param $data_row
    * @param $colspan
    * @param $class
    */
   public static function get_cell_highlighting ( $data_row, $colspan, $class )
   {
      $user_id = $data_row[ 'user_id' ];
      $no_user = userprojectapi::get_no_user ( $user_id );
      $no_issue = $data_row[ 'no_issue' ];

      $assigned_project_id = $data_row[ 'assigned_project_id' ];
      $assigned_to_project = userprojectapi::get_assigned_to_project ( $user_id, $assigned_project_id );
      $unreachable_issue = userprojectapi::get_unreachable_issue ( $assigned_to_project );

      if ( ( !user_exists ( $user_id ) && !$no_user )
         || ( userprojectapi::check_user_id_is_valid ( $user_id ) && !userprojectapi::check_user_id_is_enabled ( $user_id ) && plugin_config_get ( 'IAUHighlighting' ) )
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
      else
      {
         echo '<td class="' . $class . '" colspan="' . $colspan .
            '" style="background-color: #e0e0e0">';
      }
   }

   /**
    * @param $data_row
    * @param $group_index
    * @param $stat_index
    * @return int
    */
   public static function calc_group_spec_amount ( $data_row, $group_index, $stat_index )
   {
      $user_id = $data_row[ 'user_id' ];
      $data_row_stat_spec_issue_count = $data_row[ 'stat_col' . $stat_index ];
      $stat_spec_status_ign = plugin_config_get ( 'CStatIgn' . $stat_index );

      if ( $group_index == 0 )
      {
         /** Group 0 - ignore issue count for ignored status */
         if ( ( $stat_spec_status_ign == ON )
            && ( userprojectapi::check_user_id_is_enabled ( $user_id ) )
         )
         {
            $stat_issue_count = 0;
         }
         /** Group 2 - ignore issue count for valid status */
         else
         {
            $stat_issue_count = $data_row_stat_spec_issue_count;
         }
      }
      /** Group 3 - ignore issue count for valid status */
      elseif ( $group_index == 3 )
      {
         if ( $stat_spec_status_ign == OFF )
         {
            $databaseapi = new databaseapi();
            $user_id = $data_row[ 'user_id' ];
            $assigned_project_id = $data_row[ 'assigned_project_id' ];
            $target_version_id = $data_row[ 'target_version_id' ];
            $target_version = '';
            $status = plugin_config_get ( 'CStatSelect' . $stat_index );
            if ( strlen ( $target_version_id ) > 0 )
            {
               $target_version = version_get_field ( $target_version_id, 'version' );
            }
            /** Hole die IDs der Issues, die keinem User zugewiesen sind, und nicht ignoriert werden */
            $stat_spec_issue_ids = $databaseapi->get_issues_by_user_project_version_status ( $user_id, $assigned_project_id, $target_version, $status, $stat_spec_status_ign, $group_index );
            $stat_issue_count = count ( $stat_spec_issue_ids );
         }
         else
         {
            $stat_issue_count = $data_row_stat_spec_issue_count;
         }
      }
      /** other groups - get issue count for all status */
      else
      {
         $stat_issue_count = $data_row_stat_spec_issue_count;
      }

      return $stat_issue_count;
   }

   /**
    * Prepare a filter string which depends on the mantis version
    *
    * @return string
    */
   public static function prepare_filter_string ()
   {
      $filter_string = '';
      for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
      {
         $stat_spec_issue_status_id = plugin_config_get ( 'CStatSelect' . $stat_index );
         if ( userprojectapi::is_mantis_rel () )
         {
            $filter_string .= '&amp;status_id[]=' . $stat_spec_issue_status_id;
         }
         else
         {
            $filter_string .= '&amp;status[]=' . $stat_spec_issue_status_id;
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
   public static function set_irrelevant ( $bug_status )
   {
      $irrelevant = array ();
      for ( $stat_index = 1; $stat_index <= userprojectapi::get_stat_count (); $stat_index++ )
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
   public static function get_main_project_id ( $assigned_project_id )
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
   public static function get_assigned_project_id ( $assigned_project_id, $main_project_id )
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
   public static function get_assigned_to_project ( $user_id, $assigned_project_id )
   {
      $databaseapi = new databaseapi();
      $assigned_user_id = '0';
      if ( userprojectapi::check_user_id_is_valid ( $user_id )
         && ( $assigned_project_id != '' )
      )
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
   public static function get_unreachable_issue ( $assigned_to_project )
   {
      $unreachable_issue = false;
      if ( is_null ( $assigned_to_project )
         || ( $assigned_to_project == '' )
      )
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
   public static function get_parent_project_id ( $project_id, $assigned_project_id, $main_project_id )
   {
      $parent_project_id = '';
      if ( ( $assigned_project_id == '' )
         && ( $main_project_id == '' )
      )
      {
         $parent_project_id = $project_id;
      }
      elseif ( ( $assigned_project_id == '' )
         && ( $main_project_id != '' )
      )
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
   public static function get_no_user ( $user_id )
   {
      $no_user = false;
      if ( $user_id == '0' )
      {
         $no_user = true;
      }

      return $no_user;
   }

   /**
    * Return the passed time and issue id for the OLDEST issue of a group
    *
    * @param $stat_issue_ids
    * @return array
    */
   public static function calculate_time_difference ( $stat_issue_ids )
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
   public static function validate_assigned_project_id ( $main_project_id, $assigned_project_id )
   {
      if ( ( strlen ( $assigned_project_id ) == 0 )
         && ( is_null ( $main_project_id ) == false )
      )
      {
         $assigned_project_id = $main_project_id;
      }

      return $assigned_project_id;
   }
}