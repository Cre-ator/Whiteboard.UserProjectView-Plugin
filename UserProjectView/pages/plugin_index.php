<?php
include USERPROJECTVIEW_CORE_URI . 'PluginManager.php';

// PluginManager object
$pluginManager = new PluginManager();

html_page_top1( plugin_lang_get( 'title' ) );

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

if ( $pluginManager->getUserHasLevel() )
{
	$pluginManager->printPluginMenu();
}

html_page_bottom();