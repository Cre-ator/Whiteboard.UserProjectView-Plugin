<?php

class userprojectview_config_api
{
   public function includeLeadingColorIdentifier( $color )
   {
      if ( "#" == $color[0] )
      {
         return $color;
      }
      else
      {
         return "#" . $color;
      }
   }

   public function updateColor( $field_name, $default_color )
   {
      $default_color = $this->includeLeadingColorIdentifier( $default_color );
      $iA_background_color = $this->includeLeadingColorIdentifier( gpc_get_string( $field_name, $default_color ) );

      if ( plugin_config_get( $field_name ) != $iA_background_color
         && plugin_config_get( $field_name ) != ''
      )
      {
         plugin_config_set( $field_name, $iA_background_color );
      }
      elseif ( plugin_config_get( $field_name ) == '' )
      {
         plugin_config_set( $field_name, $default_color );
      }
   }

   public function updateButton( $config )
   {
      $button = gpc_get_int( $config );

      if ( plugin_config_get( $config ) != $button )
      {
         plugin_config_set( $config, $button );
      }
   }

   public function updateValue( $value, $constant )
   {
      $act_value = null;

      if ( is_int( $value ) )
      {
         $act_value = gpc_get_int( $value, $constant );
      }

      if ( is_string( $value ) )
      {
         $act_value = gpc_get_string( $value, $constant );
      }

      if ( plugin_config_get( $value ) != $act_value )
      {
         plugin_config_set( $value, $act_value );
      }
   }

   public function updateDynamicValues( $value, $constant )
   {
      $column_amount = plugin_config_get( 'CAmount' );

      for ( $columnIndex = 1; $columnIndex <= $column_amount; $columnIndex++ )
      {
         $act_value = $value . $columnIndex;

         $this->updateValue( $act_value, $constant );
      }
   }
}