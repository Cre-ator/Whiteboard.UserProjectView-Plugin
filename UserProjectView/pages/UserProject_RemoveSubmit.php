<?php
require_once USERPROJECTVIEW_CORE_URI . 'constantapi.php';
require_once USERPROJECTVIEW_CORE_URI . 'userprojectapi.php';

$record_set = null;
if ( isset( $_POST[ 'recordset' ] ) )
{
   $record_set = $_POST[ 'recordset' ];
}

if ( $record_set != null )
{
   removeProjectUserSet ( $record_set );
}

$redirect_url = 'plugin.php?page=UserProjectView/UserProject&sortVal=userName&sort=ASC';
html_page_top ( null, $redirect_url );
echo '<div align="center">' . plugin_lang_get ( 'remove_confirm' ) . '</div>';

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
      $record[ $record_index ] = explode ( ',', $record_set[ $record_index ] );
   }

   for ( $record_index = 0; $record_index < $record_count; $record_index++ )
   {
      project_remove_user ( $record[ $record_index ][ 1 ], $record[ $record_index ][ 0 ] );
   }
}