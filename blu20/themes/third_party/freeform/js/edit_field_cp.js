/**
 * Solspace - Freeform
 *
 * @package		Solspace:Freeform
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2008-2011, Solspace, Inc.
 * @link		http://solspace.com/docs/addon/c/Freeform/
 * @version		4.0.8.b1
 * @filesource	./system/expressionengine/third_party/freeform/
 */

/**
 * Freeform - Composer CP JS
 *
 * @package		Solspace:Freeform
 * @author		Solspace DevTeam
 * @filesource	./themes/third_party/freeform/js/edit_field_cp.js
 */

;(function(global, $){
	//es5 strict mode
	"use strict";

	//JSHint globals
	/*global _:true, Freeform:true, Security:true, jQuery:true, EE:true */

	var Freeform = global.Freeform = global.Freeform || {};

	Freeform.initFieldEvents = function ()
	{
		var $context			= $('#field_edit_form');
		var $fieldTypeDescs		= $('.field_type_desc',		$context);
		var $fieldDesc			= $('#field_description',	$context);
		var $fieldLabel			= $('#field_label',			$context);
		var $autoGenerateName	= $('#auto_generate_name',	$context);
		var $fieldName			= $('#field_name',			$context);
		var $wordCount			= $('#desc_word_count',		$context);
		var $optionsInsert		= $('#options_insert',		$context);
		var $fieldSettings		= $('#field_settings',		$context);
		var $fieldSettingsInner = $('#field_settings_inner',$context);
		var $formList			= $('#form_list',			$context);
		var $chosenFormList		= $('#chosen_form_list',	$context);
		var $formIdsRow			= $('#form_ids_row',		$context);
		var $formIds			= $('#form_ids',			$context);

		//auto generate name if checkbox checked
		Freeform.autoGenerateShortname($fieldLabel, $fieldName, $autoGenerateName);

		$('#field_type', $context).change(function(){
			var type		= $(this).val();
			var	$settings	= $('.field_settings[data-field="' + type + '"]');

			$fieldTypeDescs.addClass('hidden');
			$('#' + type + '_desc').removeClass('hidden');

			//if there are settings in the holder div, insert and show
			//if we don't do add and remove, duplicate third party
			// field names could get screwy in the post data
			if ($settings.length)
			{
				//set html, but also first td field to match parent width
				$fieldSettingsInner.html(Freeform.atob($.trim($settings.html()))).
					find('td:first').css('width', '30%').end().
					//jQuery uses 0 based indexing,
					//so starting with 0 instead of 1,
					//these need to be swapped.
					find('tr:even').addClass('odd').end().
					find('tr:odd').addClass('even').end();
				$fieldSettings.show();

				setTimeout(function(){
					Freeform.setUpChosenSelect($fieldSettingsInner);
				}, 0);
			}
			else
			{
				$fieldSettingsInner.html('');
				$fieldSettings.hide();
			}
		}).change(); //run once for the settings inner html just in case we are returning

		//word count
		$fieldDesc.keyup(function(){
			$wordCount.html($fieldDesc.val().length);
		}).keyup();

		// -------------------------------------
		//	resets the field IDs
		// -------------------------------------

		var setFormIds = function()
		{
			var formIds = [];

			$('.field_tag', $chosenFormList).each(function(){
				formIds.push($(this).attr('data-form-id'));
			});

			$formIds.val(formIds.join('|'));
		};

		// -------------------------------------
		//	field ids
		// -------------------------------------

		//move to the new table and sort elements on it
		//no need to sort the table its removed from as
		//it just pops this item out from its place
		$formList.delegate('.field_tag', 'click', function(){
			$chosenFormList.
				append($(this).remove()).children().
				sortElements(function(a, b){
					return $(a).text().toLowerCase() > $(b).text().toLowerCase() ? 1 : -1;
				});
			setFormIds();
		});

		//same crap, different tag
		$chosenFormList.delegate('.field_tag', 'click', function(){
			$formList.
				append($(this).remove()).children().
				sortElements(function(a, b){
					return $(a).text().toLowerCase() > $(b).text().toLowerCase() ? 1 : -1;
				});
			setFormIds();
		});

		// -------------------------------------
		//	setup fields on load to be in
		//	the correct field.
		// -------------------------------------

		var currentForms = ($.trim($formIds.val()) !== '') ? $formIds.val().split('|') : [];

		if (currentForms.length > 0)
		{
			$.each(currentForms, function(i, item){
				$('.field_tag[data-form-id="' + item + '"]', $formList).click();
			});
		}
	};
	//END Freeform.initFieldEvents


	//if this is the standard field page, laod the events
	$(function(){
		if ($('#field_edit_form').length > 0)
		{
			Freeform.initFieldEvents();
		}
	});

}(window, jQuery));