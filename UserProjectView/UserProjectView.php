<?php

class UserProjectViewPlugin extends MantisPlugin
{
   function register()
   {
      $this->name        = 'UserProjectView';
      $this->description = 'A view that shows all projects of a specific user.';
      $this->page        = 'config_page';

      $this->version     = '1.0.9';
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
   			
   		'IAUserHighlighting' => OFF,
   		'IABGColor' => "#8b0000",
   		'IATColor' => "#000000",
   			
   		'URUserHighlighting' => OFF,
   		'URBGColor' => "#8b0000",
   		'URTColor' => "#000000",
   			
			'NUIssueHighlighting' => OFF,
			'NUBGColor' => "#8b0000",
			'NUTColor' => "#000000",
   			
			'ZIssueHighlighting' => OFF,
			'ZIBGColor' => "#8b0000",
			'ZITColor' => "#000000",
   			
   		'colAmount' => 1,
   		'CTFHighlighting' => OFF,
   		'OIHighlighting' => OFF,
   			
   		'issueThreshold1' => 5,
   		'ITBGColor1' => "#8b0000",
   		'ITTColor1' => "#000000",
			'issueThreshold2' => 5,
   		'ITBGColor2' => "#8b0000",
   		'ITTColor2' => "#000000",
			'issueThreshold3' => 5,
   		'ITBGColor3' => "#8b0000",
   		'ITTColor3' => "#000000",
   			
   		'oldIssueThreshold1' => 30,
			'oldIssueThreshold2' => 30,
			'oldIssueThreshold3' => 30,
   			
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
      	return '<a href="' . plugin_page( 'plugin_index' ) . '">' . plugin_lang_get( 'title' ) . '</a>';
      }
      return null;
   }
}