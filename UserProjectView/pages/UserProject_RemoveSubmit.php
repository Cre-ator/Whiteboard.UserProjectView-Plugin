<?php
require_once USERPROJECTVIEW_CORE_URI . 'constant_api.php';
require_once USERPROJECTVIEW_CORE_URI . 'UPSystem_api.php';

$upv_api = new UPSystem_api();
$record_set = null;
$user_id = null;
$project_id = null;

if ( !empty( $_POST['recordSet'] ) )
{
   $record_set = $_POST['recordSet'];
}
if ( !empty( $_POST['user'] ) )
{
   $user_id = $_POST['user'];
}
if ( !empty( $_POST['project'] ) )
{
   $project_id = $_POST['project'];
}

if ( $record_set != null )
{
   $upv_api->removeProjectUserSet( $record_set );
}
elseif ( $user_id != null && $project_id != null )
{
   $upv_api->removeProjectUser( $user_id, $project_id );
}

$redirect_url = 'plugin.php?page=UserProjectView/UserProject&sortVal=userName&sort=ASC';

html_page_top( null, $redirect_url );

echo '<div align="center">';
echo plugin_lang_get( 'remove_confirm' );
echo '</div>';