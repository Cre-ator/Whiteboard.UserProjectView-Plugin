<?php

class UserProjectViewPlugin extends MantisPlugin
{
   function register()
   {
      $this->name        = 'UserProjectView';
      $this->description = 'A view that shows all projects of a specific user.';
      $this->page        = 'config_page';

      $this->version     = '1.0.1';
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
      $t_core_path = config_get_global('plugin_path')
                   . plugin_get_current()
                   . DIRECTORY_SEPARATOR
                   . 'core'
                   . DIRECTORY_SEPARATOR;
      require_once($t_core_path . 'constant_api.php');
   }

   function config()
   {
   	return array
   	(
		   'ShowInFooter'           => ON,
		   'ShowMenu'           => ON,
		   'UserProjectAccessLevel' => ADMINISTRATOR
   	);
   }
   
   function footer()
   {
   	$t_project_id = helper_get_current_project();
   	$t_user_id = auth_get_current_user_id();
   	$t_user_has_level = user_get_access_level($t_user_id, $t_project_id) >= plugin_config_get('UserProjectAccessLevel', PLUGINS_USERPROJECTVIEWVIEW_THRESHOLD_LEVEL_DEFAULT);
   	
   	if (plugin_config_get('ShowInFooter') == 1 && $t_user_has_level)
   	{
   		return '<address>' . $this->name . ' ' . $this->version . ' Copyright &copy; 2015 by ' . $this->author . '</address>';
   	}
   	return '';
   }
   
   function menu()
   {
      $t_project_id = helper_get_current_project();
      $t_user_id = auth_get_current_user_id();
      $t_user_has_level = user_get_access_level($t_user_id, $t_project_id) >= plugin_config_get('UserProjectAccessLevel', PLUGINS_USERPROJECTVIEWVIEW_THRESHOLD_LEVEL_DEFAULT);
      
      if (plugin_config_get('ShowMenu') == 1 && $t_user_has_level)
      {
      	return '<a href="' . plugin_page('UserProject') . '">' . plugin_lang_get('menu_title') . '</a>';
      }
      return '';
   }
}