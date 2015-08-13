<?php
form_security_validate('plugin_UserProjectView_config_update');

access_ensure_global_level(config_get('UserProjectAccessLevel'));
auth_reauthenticate();

$ShowInFooter = gpc_get_int('ShowInFooter', ON);

if(plugin_config_get('ShowInFooter') != $ShowInFooter)
{
   plugin_config_set('ShowInFooter', $ShowInFooter);
}

$UserProjectAccessLevel = gpc_get_int('UserProjectAccessLevel');

if(plugin_config_get('UserProjectAccessLevel') != $UserProjectAccessLevel)
{
   plugin_config_set('UserProjectAccessLevel', $UserProjectAccessLevel);
}

form_security_purge('plugin_UserProjectView_config_update');

print_successful_redirect(plugin_page('config_page', true));