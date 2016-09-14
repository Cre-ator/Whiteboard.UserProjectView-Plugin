<?php

class UserProjectViewPlugin extends MantisPlugin
{
   private $shortName = null;

   function register ()
   {
      $this->shortName = 'UserProjectView';
      $this->name = 'Whiteboard.' . $this->shortName;
      $this->description = 'Shows detailed information about each user and his assigned issues';
      $this->page = 'config_page';

      $this->version = '1.4.4';
      $this->requires = array
      (
         'MantisCore' => '1.2.0, <= 1.3.99'
      );

      $this->author = 'cbb software GmbH (Rainer Dierck, Stefan Schwarz)';
      $this->contact = '';
      $this->url = 'https://github.com/Cre-ator';
   }

   function hooks ()
   {
      $hooks = array
      (
         'EVENT_LAYOUT_PAGE_FOOTER' => 'footer',
         'EVENT_MENU_MAIN' => 'menu'
      );
      return $hooks;
   }

   function init ()
   {
      require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'uvConst.php' );
      require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectapi.php' );
   }

   function config ()
   {
      return array
      (
         'ShowMenu' => ON,
         'ShowInFooter' => ON,
         'ShowAvatar' => ON,

         // IAU -> inactive user
         'IAUHighlighting' => ON,
         'IAUHBGColor' => '#E67C7C',

         // URIU -> unreachable issue user (issue isnt reachable by user)
         'URIUHighlighting' => ON,
         'URIUHBGColor' => '#E67C7C',

         // NUI -> no user issue (issues without user)
         'NUIHighlighting' => ON,
         'NUIHBGColor' => '#FCBDBD',

         // ZIU -> zero issue user | ZI -> zero issue
         'ShowZIU' => ON,
         'ZIHighlighting' => ON,
         'ZIHBGColor' => '#F8FFCC',

         // C -> column | TAMH -> threshold amount highlighting
         'CAmount' => 3,
         'TAMHBGColor' => '#FAD785',

         // C -> Column | IAM -> issue amount | IAG -> issue age
         'CStatSelect1' => 10,
         'IAMThreshold1' => 0,
         'IAGThreshold1' => 60,
         'CStatSelect2' => 50,
         'IAMThreshold2' => 5,
         'IAGThreshold2' => 30,
         'CStatSelect3' => 20,
         'IAMThreshold3' => 5,
         'IAGThreshold3' => 30,
         'CStatIgn1' => OFF, 'CStatIgn2' => OFF, 'CStatIgn3' => OFF,
         'CStatIgn4' => OFF, 'CStatIgn5' => OFF, 'CStatIgn6' => OFF,
         'CStatIgn7' => OFF, 'CStatIgn8' => OFF, 'CStatIgn9' => OFF,
         'CStatIgn10' => OFF, 'CStatIgn11' => OFF, 'CStatIgn12' => OFF,
         'CStatIgn13' => OFF, 'CStatIgn14' => OFF, 'CStatIgn15' => OFF,
         'CStatIgn16' => OFF, 'CStatIgn17' => OFF, 'CStatIgn18' => OFF,
         'CStatIgn19' => OFF, 'CStatIgn20' => OFF,
         'layer_one_name' => 0,

         // URI -> unreachable issue
         'URIThreshold' => array (),

         'UserProjectAccessLevel' => ADMINISTRATOR
      );
   }

   function schema ()
   {
      require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectapi.php' );
      $tableArray = array ();

      $whiteboardMenuTable = array
      (
         'CreateTableSQL', array ( plugin_table ( 'menu', 'whiteboard' ), "
            id                   I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
            plugin_name          C(250)  DEFAULT '',
            plugin_access_level  I       UNSIGNED,
            plugin_show_menu     I       UNSIGNED,
            plugin_menu_path     C(250)  DEFAULT ''
            " )
      );

      $boolArray = userprojectapi::checkWhiteboardTablesExist ();
      # add whiteboardmenu table if it does not exist
      if ( !$boolArray[ 0 ] )
      {
         array_push ( $tableArray, $whiteboardMenuTable );
      }

      return $tableArray;
   }

   function getUserHasLevel ()
   {
      $project_id = helper_get_current_project ();
      $user_id = auth_get_current_user_id ();

      return user_get_access_level ( $user_id, $project_id ) >= plugin_config_get ( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
   }

   function footer ()
   {
      if ( plugin_config_get ( 'ShowInFooter' ) && $this->getUserHasLevel () )
      {
         return '<address>' . $this->shortName . ' ' . $this->version . ' Copyright &copy; 2015 by ' . $this->author . '</address>';
      }
      return null;
   }

   function menu ()
   {
      require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectapi.php' );
      if ( !userprojectapi::checkPluginIsRegisteredInWhiteboardMenu () )
      {
         userprojectapi::addPluginToWhiteboardMenu ();
      }

      if ( ( !plugin_is_installed ( 'WhiteboardMenu' ) || !file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' ) )
         && plugin_config_get ( 'ShowMenu' ) && $this->getUserHasLevel ()
      )
      {
         return '<a href="' . plugin_page ( 'UserProject' ) . '&sortVal=userName&sort=ASC">' . plugin_lang_get ( 'menu_userprojecttitle' ) . '</a>';
      }
      return null;
   }

   function uninstall ()
   {
      require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'userprojectapi.php' );
      userprojectapi::removePluginFromWhiteboardMenu ();
   }
}
