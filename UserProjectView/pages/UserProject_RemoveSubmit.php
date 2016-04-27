<?php
require_once USERPROJECTVIEW_CORE_URI . 'userprojectview_constant_api.php';

$record_set = null;
$user_id = null;
$project_id = null;

if ( !empty( $_POST[ 'recordSet' ] ) )
{
   $record_set = $_POST[ 'recordSet' ];
}
if ( !empty( $_POST[ 'user' ] ) )
{
   $user_id = $_POST[ 'user' ];
}
if ( !empty( $_POST[ 'project' ] ) )
{
   $project_id = $_POST[ 'project' ];
}

if ( $record_set != null )
{
   removeProjectUserSet ( $record_set );
}
elseif ( $user_id != null && $project_id != null )
{
   removeProjectUser ( $user_id, $project_id );
}

$redirect_url = 'plugin.php?page=UserProjectView/UserProject&sortVal=userName&sort=ASC';

html_page_top ( null, $redirect_url );

echo '<div align="center">';
echo plugin_lang_get ( 'remove_confirm' );
echo '</div>';

function removeProjectUserSet ( $record_set )
{
   $record = array ();
   $record_count = count ( $record_set );

   for ( $recordIndex = 0; $recordIndex < $record_count; $recordIndex++ )
   {
      $record[ $recordIndex ] = explode ( '__', $record_set[ $recordIndex ] );
   }

   for ( $recordIndex = 0; $recordIndex < $record_count; $recordIndex++ )
   {
      project_remove_user ( $record[ $recordIndex ][ 1 ], $record[ $recordIndex ][ 0 ] );
   }
}

function removeProjectUser ( $user_id, $project_id )
{
   $user_count = count ( $user_id );
   $project_count = count ( $project_id );

   if ( $user_count == $project_count && user_exists ( $user_id ) )
   {
      for ( $dIndex = 0; $dIndex < $user_count; $dIndex++ )
      {
         project_remove_user ( $project_id[ $dIndex ], $user_id[ $dIndex ] );
      }
   }
   else
   {
      echo plugin_lang_get ( 'remove_failure' );
   }
}