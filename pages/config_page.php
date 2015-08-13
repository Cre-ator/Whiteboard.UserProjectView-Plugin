<?php
	access_ensure_global_level(plugin_config_get('UserProjectAccessLevel'));
	auth_reauthenticate();
	
   $mantis_version = substr(MANTIS_VERSION, 0, 4);
	
	html_page_top1(plugin_lang_get('config_title'));
	html_page_top2();
	
	print_manage_menu();
?>

<br/>
<form action="<?php echo plugin_page('config_update') ?>" method="post">
<?php
   echo form_security_field('plugin_UserProjectView_config_update');
   
   if($mantis_version == '1.2.')
   {
   	echo '<table align="center" class="width75" cellspacing="1">';
   }
   else
   {
   	echo '<div class="form-container">';
      echo '<table>';
   }
?>
		<tr>
		   <td class="form-title" colspan="2">
		      <?php echo plugin_lang_get('config_caption'); ?>
		   </td>
		</tr>
<?php 
	if($mantis_version == '1.2.')
	{
	   echo '<tr ' . helper_alternate_class() . '>';
	}
	else
	{
		echo '<tr>';
	}
?>
		   <td class="category">
		      <?php echo plugin_lang_get('footer'); ?>
		   </td>
		   <td width="200px">
            <label><input type="radio" name="ShowInFooter" value="1" <?php echo (ON == plugin_config_get('ShowInFooter')) ? 'checked="checked" ' : '' ?>/>Yes</label>
		      <label><input type="radio" name="ShowInFooter" value="0" <?php echo (OFF == plugin_config_get('ShowInFooter')) ? 'checked="checked" ' : '' ?>/>No</label>
		   </td>
		</tr>
		<!-- Upload access level -->
<?php 
   if($mantis_version == '1.2.')
   {
      echo '<tr ' . helper_alternate_class() . '>';
   }
   else
   {
      echo '<tr>';
   }
?>
      <td class="category" width="30%">
         <span class="required">*</span><?php echo plugin_lang_get('accesslevel'); ?>
      </td>
         <td width="200px">
            <select name="UserProjectAccessLevel">
               <?php print_enum_string_option_list('access_levels', plugin_config_get('UserProjectAccessLevel', PLUGINS_USERPROJECTVIEW_THRESHOLD_LEVEL_DEFAULT)); ?>
            </select>
         </td>
      </tr>
		<tr>
         <td class="center" colspan="2">
            <input type="submit" class="button" value="<?php echo lang_get('change_configuration') ?>" />
         </td>
      </tr>
   </table>
<?php 
   if($mantis_version == '1.2.')
   {
      
   }
   else
   {
   	echo '</div>';
   }
?>
</form>
<?php
html_page_bottom1();