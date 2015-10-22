<?php
include USERPROJECTVIEW_CORE_URI . 'UserProjectView_api.php';

// UserProjectView_api object
$upv_api = new UserProjectView_api();

html_page_top1( plugin_lang_get( 'title' ) );

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

if ( $upv_api->getUserHasLevel() )
{
	$upv_api->printPluginMenu();
}

html_page_bottom();