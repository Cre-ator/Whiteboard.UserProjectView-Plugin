<?php
header('Content-type: text/css');
 
$backgroundcolor = config_get( 'backgroundcolor' );
$textcolor = config_get( 'textcolor' );
?>

a:link
{
   color: black;
   background: transparent;
   text-decoration: none;
}

a:visited
{
   color: black;
   background: transparent;
   text-decoration: none;
}

a:hover
{
   color: black;
   background: transparent;
   text-decoration: underline;
}

a:active
{
   color: black;
   background: transparent;
   text-decoration: none;
}

td.attention
{
   background-color: <?=$backgroundcolor?>;
   text-color: <?=$textcolor?>;
}