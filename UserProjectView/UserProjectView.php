<?php

class UserProjectViewPlugin extends MantisPlugin
{
   function register()
   {
      $this->name        = 'UserProjectView';
      $this->description = 'Shows detailed information about each user and his assigned issues';
      $this->page        = 'config_page';

      $this->version     = '1.2.4';
      $this->requires    = array
      (
         'MantisCore' => '1.2.0, <= 1.3.99'
      );

      $this->author      = 'Stefan Schwarz';
      $this->contact     = '';
      $this->url         = '';
   }
   
   function hooks()
   {
      $hooks = array
      (
         'EVENT_LAYOUT_PAGE_FOOTER' => 'footer',
         'EVENT_MENU_MAIN'          => 'menu'
      );
      return $hooks;
   }
   
   function init()
   {
      $t_core_path = config_get_global( 'plugin_path' )
                   . plugin_get_current()
                   . DIRECTORY_SEPARATOR
                   . 'core'
                   . DIRECTORY_SEPARATOR;
      require_once( $t_core_path . 'constant_api.php' );
   }

   function config()
   {
      return array
   	(
         'ShowMenu' => ON,
         'ShowInFooter' => ON,
         'ShowAvatar' => ON,

         // IAU -> inactive user
         'IAUHighlighting' => OFF,
         'IAUHBGColor' => '#663300',

         // URIU -> unreachable issue user (issue isnt reachable by user)
         'URIUHighlighting' => OFF,
         'URIUHBGColor' => '#663300',

         // NUI -> no user issue (issues without user)
         'NUIHighlighting' => OFF,
         'NUIHBGColor' => '#663300',

         // ZIU -> zero issue user | ZI -> zero issue
         'ShowZIU' => OFF,
         'ZIHighlighting' => OFF,
         'ZIHBGColor' => '#663300',

         // C -> column
         'CAmount' => 1,

         // C -> Column | IAM -> issue amount | IAG -> issue age | TAMH -> threshold amount highlighting
         'CStatSelect1' => 50,
         'IAMThreshold1' => 5,
         'IAGThreshold1' => 30,

         'TAMHBGColor' => '#663300',

         // URI -> unreachable issue
         'URIThreshold' => 50,

         'UserProjectAccessLevel' => ADMINISTRATOR
   	);


   }
   
   function getUserHasLevel()
   {
   	$projectId = helper_get_current_project();
   	$userId = auth_get_current_user_id();
   	
   	return user_get_access_level( $userId, $projectId ) >= plugin_config_get( 'UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT );
   }
   
   function footer()
   {
   	if ( plugin_config_get( 'ShowInFooter' ) && $this->getUserHasLevel() )
   	{
   		return '<address>' . $this->name . ' ' . $this->version . ' Copyright &copy; 2015 by ' . $this->author . '</address>';
   	}
   	return null;
   }
   
   function menu()
   {
      if ( plugin_config_get( 'ShowMenu' ) && $this->getUserHasLevel() )
      {
      	return '<a href="' . plugin_page( 'UserProject' ) . '&sortVal=userName&sort=ASC">' . plugin_lang_get( 'menu_title' ) . '</a>';
      }
      return null;
   }
}