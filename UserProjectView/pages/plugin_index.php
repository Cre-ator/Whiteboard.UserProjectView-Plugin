<?php
include USERPROJECTVIEW_CORE_URI . 'UserProjectView_api.php';

// UserProjectView_api object
$upv_api = new UserProjectView_api();

html_page_top1( plugin_lang_get( 'title' ) );

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

if ( $upv_api->system_getUserHasLevel() )
{
	$upv_api->menu_printPlugin();
}

html_page_bottom();