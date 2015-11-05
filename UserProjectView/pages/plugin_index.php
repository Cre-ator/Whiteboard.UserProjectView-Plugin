<?php

require_once( USERPROJECTVIEW_CORE_URI . 'constant_api.php' );
include USERPROJECTVIEW_CORE_URI . 'UPSystem_api.php';
include USERPROJECTVIEW_CORE_URI . 'UPPrint_api.php';

// UserProjectView_api object
$upp_api = new UPPrint_api();
$upv_api = new UPSystem_api();

html_page_top1( plugin_lang_get( 'menu_userprojecttitle' ) );

echo '<link rel="stylesheet" href="' . USERPROJECTVIEW_PLUGIN_URL . 'files/UserProjectView.css">';

html_page_top2();

if ( $upv_api->userHasLevel() )
{
   $upp_api->printWBMMenu();
}

html_page_bottom();