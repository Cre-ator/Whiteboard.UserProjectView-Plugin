<?php
require_once USERPROJECTVIEW_CORE_URI . 'constantapi.php';

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
elseif ( check_user_id_is_valid ( $user_id ) && $project_id != null )
{
   removeProjectUser ( $user_id, $project_id );
}

$redirect_url = 'plugin.php?page=UserProjectView/UserProject&sortVal=userName&sort=ASC';

html_page_top ( null, $redirect_url );
?>
   <div align="center">
      <?php echo plugin_lang_get ( 'remove_confirm' ); ?>
   </div>
<?php

/**
 * Remove a user from a bunch of projects
 *
 * @param $record_set
 */
function removeProjectUserSet ( $record_set )
{
   $record = array ();
   $record_count = count ( $record_set );

   for ( $record_index = 0; $record_index < $record_count; $record_index++ )
   {
      $record[ $record_index ] = explode ( '_', $record_set[ $record_index ] );
   }

   for ( $record_index = 0; $record_index < $record_count; $record_index++ )
   {
      project_remove_user ( $record[ $record_index ][ 1 ], $record[ $record_index ][ 0 ] );
   }
}

/**
 * Remove a user from on project
 *
 * @param $user_id
 * @param $project_id
 */
function removeProjectUser ( $user_id, $project_id )
{
   $user_count = count ( $user_id );
   $project_count = count ( $project_id );

   if ( $user_count == $project_count && user_exists ( $user_id ) )
   {
      for ( $user_index = 0; $user_index < $user_count; $user_index++ )
      {
         project_remove_user ( $project_id[ $user_index ], $user_id[ $user_index ] );
      }
   }
   else
   {
      echo plugin_lang_get ( 'remove_failure' );
   }
}