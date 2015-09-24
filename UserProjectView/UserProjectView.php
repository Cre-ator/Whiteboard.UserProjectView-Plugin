<?php

class UserProjectViewPlugin extends MantisPlugin
{
   function register()
   {
      $this->name        = 'UserProjectView';
      $this->description = 'A view that shows all projects of a specific user.';
      $this->page        = 'config_page';

      $this->version     = '1.0.6';
      $this->requires    = array
      (
         'MantisCore' => '1.2.0, <= 1.3.1'
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
		   'ShowInFooter' => ON,
		   'ShowMenu' => ON,
   		'IAUserHighlighting' => ON,
   		'IABGColor' => "#8b0000",
   		'IATColor' => "#000000",
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
   	if ( plugin_config_get( 'ShowInFooter' )
         && $this->getUserHasLevel()
         )
   	{
   		return '<address>' . $this->name . ' ' . $this->version . ' Copyright &copy; 2015 by ' . $this->author . '</address>';
   	}
   	return null;
   }
   
   function menu()
   {
      if ( plugin_config_get( 'ShowMenu' )
         && $this->getUserHasLevel()
         )
      {
      	return '<a href="' . plugin_page( 'UserProject?page_number=1' ) . '">' . plugin_lang_get( 'menu_title' ) . '</a>';
      }
      return null;
   }
}