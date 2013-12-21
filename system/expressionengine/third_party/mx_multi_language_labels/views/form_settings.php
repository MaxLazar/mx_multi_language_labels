<?php if($message) : ?>
<div class="mor alert success">
<p><?php print($message); ?></p>
</div>
<?php endif; ?>

<?php if($settings_form) : ?>
<?= form_open(
'C=addons_extensions&M=extension_settings&file=&file=mx_multi_language_labels',
'',
array("file" => "mx_multi_language_labels")
)
?>

<table class="mainTable padTable" id="event_table" border="0" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<td class="default" colspan="3">
<div class="box" style="border-width: 0pt 0pt 1px; margin: 0pt; padding: 10px 5px;"><p><?= lang('extension_settings_info')?></p></div>
</td>
</tr>
</tbody> <?php endif; ?>
<tbody>

		
		<?php
				$out  ="";
				
				foreach ($language_packs as $language)
				{
					$i	=	1;
					$out .= '<tr class="header"><th>'. $language.'</th><th>
					<span style="float:right;color:#fff"> <input type="checkbox" name="'.$input_prefix.'['.strtolower($language).']" value="d"   '.((isset($settings[strtolower($language)])) ? (($settings[strtolower($language)] == 'd') ? " checked=checked'" : "" ) : "").'"/> Default Labels</span>
					</th></tr>';

	
					foreach ($field_packs->result() as $field)
					{
						$out .= '<tr class="'.(($i&1) ? "odd" : "even").'">
						<td><strong>'.$field->field_label.'</strong> {'.$field->field_name.'}</td>
						<td><input dir="ltr" style="width: 100%;" name="'.$input_prefix.'['.strtolower($language).'_'.$field->field_id.']" id="" value="'.((isset($settings[strtolower($language).'_'.$field->field_id])) ? $settings[strtolower($language).'_'.$field->field_id] : '' ).'" size="20" maxlength="120" class="input" type="text"></td>
						</tr>';
						$i++;
					}	

				}		
				
			echo $out;

		?>	
		
</tbody>		

</table>
<p><input name="edit_field_group_name" value="<?= lang('save_extension_settings'); ?>" class="submit" type="submit"></p>


<?= form_close(); ?>



