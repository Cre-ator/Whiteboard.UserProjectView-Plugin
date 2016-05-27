<?php

class UserProjectViewPlugin extends MantisPlugin
{
   function register ()
   {
      $this->name = 'UserProjectView';
      $this->description = 'Shows detailed information about each user and his assigned issues';
      $this->page = 'config_page';

      $this->version = '1.3.52';
      $this->requires = array
      (
         'MantisCore' => '1.2.0, <= 1.3.99'
      );

      $this->author = 'Stefan Schwarz';
      $this->contact = '';
      $this->url = '';
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
      $t_core_path = config_get_global ( 'plugin_path' )
         . plugin_get_current ()
         . DIRECTORY_SEPARATOR
         . 'core'
         . DIRECTORY_SEPARATOR;
      require_once ( $t_core_path . 'constantapi.php' );
   }

   function config ()
   {
      $t_core_path = config_get_global ( 'plugin_path' )
         . plugin_get_current ()
         . DIRECTORY_SEPARATOR
         . 'core'
         . DIRECTORY_SEPARATOR;

      require_once ( $t_core_path . 'constantapi.php' );

      return array
      (
         'ShowMenu' => ON,
         'ShowInFooter' => ON,
         'ShowAvatar' => ON,

         // IAU -> inactive user
         'IAUHighlighting' => ON,
         'IAUHBGColor' => PLUGINS_USERPROJECTVIEW_IAUHBGCOLOR,

         // URIU -> unreachable issue user (issue isnt reachable by user)
         'URIUHighlighting' => ON,
         'URIUHBGColor' => PLUGINS_USERPROJECTVIEW_URIUHBGCOLOR,

         // NUI -> no user issue (issues without user)
         'NUIHighlighting' => ON,
         'NUIHBGColor' => PLUGINS_USERPROJECTVIEW_NUIHBGCOLOR,

         // ZIU -> zero issue user | ZI -> zero issue
         'ShowZIU' => ON,
         'ZIHighlighting' => ON,
         'ZIHBGColor' => PLUGINS_USERPROJECTVIEW_ZIHBGCOLOR,

         // C -> column | TAMH -> threshold amount highlighting
         'CAmount' => PLUGINS_USERPROJECTVIEW_COLUMN_AMOUNT,
         'TAMHBGColor' => PLUGINS_USERPROJECTVIEW_TAMHBGCOLOR,
         'IgnIssBGColor' => PLUGINS_USERPROJECTVIEW_IGNISSBGColor,

         // C -> Column | IAM -> issue amount | IAG -> issue age
         'CStatSelect1' => 10,
         'IAMThreshold1' => 0,
         'IAGThreshold1' => 60,
         'CStatSelect2' => PLUGINS_USERPROJECTVIEW_COLUMN_STAT_DEFAULT,
         'IAMThreshold2' => PLUGINS_USERPROJECTVIEW_COLUMN_IAMTHRESHOLD,
         'IAGThreshold2' => PLUGINS_USERPROJECTVIEW_COLUMN_IAGTHRESHOLD,
         'CStatSelect3' => 20,
         'IAMThreshold3' => PLUGINS_USERPROJECTVIEW_COLUMN_IAMTHRESHOLD,
         'IAGThreshold3' => PLUGINS_USERPROJECTVIEW_COLUMN_IAGTHRESHOLD,
         'CStatIgn1' => OFF, 'CStatIgn2' => OFF, 'CStatIgn3' => OFF,
         'CStatIgn4' => OFF, 'CStatIgn5' => OFF, 'CStatIgn6' => OFF,
         'CStatIgn7' => OFF, 'CStatIgn8' => OFF, 'CStatIgn9' => OFF,
         'CStatIgn10' => OFF, 'CStatIgn11' => OFF, 'CStatIgn12' => OFF,
         'CStatIgn13' => OFF, 'CStatIgn14' => OFF, 'CStatIgn15' => OFF,
         'CStatIgn16' => OFF, 'CStatIgn17' => OFF, 'CStatIgn18' => OFF,
         'CStatIgn19' => OFF, 'CStatIgn20' => OFF,
         'layer_one_name' => 0,

         // URI -> unreachable issue
         'URIThreshold' => array (
            '0' => 20,
            '1' => 30,
            '2' => 40,
            '3' => 50
         ),

         'UserProjectAccessLevel' => ADMINISTRATOR
      );
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
         return '<address>' . $this->name . ' ' . $this->version . ' Copyright &copy; 2015 by ' . $this->author . '</address>';
      }
      return null;
   }

   function menu ()
   {
      if ( ( !plugin_is_installed ( 'WhiteboardMenu' ) || !file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' ) )
         && plugin_config_get ( 'ShowMenu' ) && $this->getUserHasLevel ()
      )
      {
         return '<a href="' . plugin_page ( 'UserProject' ) . '&sortVal=userName&sort=ASC">' . plugin_lang_get ( 'menu_userprojecttitle' ) . '</a>';
      }
      return null;
   }
}
