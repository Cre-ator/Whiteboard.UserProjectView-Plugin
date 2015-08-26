<?php
// URL to UserProjectView plugin
define ('USERPROJECTVIEW_PLUGIN_URL', config_get_global ('path') . 'plugins/' . plugin_get_current () . '/');

// Path to UserProjectView plugin folder
define ('USERPROJECTVIEW_PLUGIN_URI', config_get_global ('plugin_path') . plugin_get_current () . DIRECTORY_SEPARATOR);

// Path to UserProjectView core folder
define ('USERPROJECTVIEW_CORE_URI', USERPROJECTVIEW_PLUGIN_URI . 'core' . DIRECTORY_SEPARATOR);

define ('PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT', ADMINISTRATOR);
?>