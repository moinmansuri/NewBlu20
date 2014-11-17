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
 * @filesource	./themes/third_party/freeform/js/composer_cp.js
 */

;(function(global, $){
	//es5 strict mode
	"use strict";

	//JSHint globals
	/*global _:true, Freeform:true, Security:true, jQuery:true, EE:true */

	// -------------------------------------
	//	Private Variables
	// -------------------------------------
	//	defining early for use in functions
	// -------------------------------------

	var $captchaButton;
	var $composer;
	var $composerRows;
	var $context;
	var $doubleRow;
	var $dynamicRecipientsButton;
	var $titleButton;
	var $pageBreakButton;
	var $paragraphButton;
	var $quadrupleRow;
	var $searchClear;
	var $singleRow;
	var $submitButtonButton;
	var $submitPreviousButtonButton;
	var $tripleRow;
	var $userRecipientsButton;
	var $composerSideBar;
	var $stickyControls;

	var	columnTemplate;
	var captchaTemplate;
	var column;
	var composerRow;
	var composerRowTemplate;
	var dynrec;
	var dynrecTemplate;
	var editDynrec;
	var editDynrecTemplate;
	var editParagraph;
	var editParagraphTemplate;
	var editSubmit;
	var editSubmitTemplate;
	var editSubmitPrevious;
	var editSubmitPreviousTemplate;
	var editUserrec;
	var editUserrecTemplate;
	var fieldDrag;
	var fieldDragTemplate;
	var fieldTag;
	var fieldTagTemplate;
	var fieldWrapper;
	var fieldWrapperTemplate;
	var pageBreakTemplate;
	var paragraph;
	var paragraphTemplate;
	var submitButton;
	var submitButtonTemplate;
	var submitPreviousButton;
	var submitPreviousButtonTemplate;
	var textareaTemplate;
	var userrec;
	var userrecTemplate;

	var sortOut				= false;
	var missingSubmits		= false;
	var insertOutputs		= {};
	var rowTemplates		= {};
	var hiddenFields		= [];

	// -------------------------------------
	//	default fancybox settings for field pop
	// -------------------------------------

	var fieldFancyboxDefaults = {
		'width'				: '90%',
		//'height'			: '90%',
		//'autoDimensions'	: false,
		'overlayOpacity'	: '0.75',
		'onStart'			: function()
		{
			$('#fancybox-content').addClass('solspace_ui_wrapper');
		}
	};

	var nonfieldReauireable = ['dynamic_recipients', 'user_recipients', 'captcha'];

	// -------------------------------------
	//	Private Functions
	// -------------------------------------

	// --------------------------------------------------------------------

	/**
	 * set row button events and dragging
	 *
	 * @access	private
	 * @param	{Object}	$buttonObj	jQuery object of button
	 * @param	{String}	name		beginning of id name for button
	 * @param	{Int}		count		column count
	 */

	function setRowButtonEvents ($buttonObj, name, count)
	{
		var colspan			= 12/count;
		var blanks			= [];
		var i				= count;

		//fill blanks
		while (i--){ blanks.push(''); }

		var template		= composerRow({
			"data"			: blanks,
			'colspan'		: colspan.toString()
		});

		rowTemplates[name]	= template;

		$buttonObj.click(function(){
			var $element = $(template).appendTo($composerRows);
			updateRowEvents($element, 'row');
		});

		$buttonObj.draggable({
			'connectToSortable' : '#composer_rows',
			'forceHelperSize'	: true,
			'zIndex'			: 1000,
			'helper'			: function()
			{
				sortOut = false;
				var $clone = $(this).clone().addClass('insert_element_width').
								height($(this).outerHeight()).
								remove().
								appendTo($composer).
								css('zIndex', 3000);
				return $clone;
			}
		});
	}
	//END setRowButtonEvents


	// --------------------------------------------------------------------

	/**
	 * Set fields to draggable. If no buttonObj set, run all .field_tag
	 *
	 * @access	private
	 * @param	{Object} $buttonObj	optional object to set the event for
	 *								if empty, loops over $('.field_tag')
	 * @return	{Null}
	 */

	function setFieldDragging ($buttonObj)
	{
		$buttonObj = $buttonObj || false;

		// -------------------------------------
		//	overloaded if blank to do field tags
		// -------------------------------------

		if ($buttonObj === false)
		{
			$('.field_tag').each(function(){
				setFieldDragging($(this));
			});

			return;
		}

		//cannot be cached because we want to hit new fields
		$buttonObj.draggable({
			"connectToSortable"	: '#composer .connectedSortable',
			"revert"			: true ,
			//this cuts that crappy animation
			'revertDuration'	: 0,
			'zIndex'			: 1000,
			//'helper'			: 'clone',
			'helper'			: function() {
				return $(fieldDrag({'fieldLabel': $buttonObj.text()})).
						remove().
						appendTo($composer).
						css('zIndex', 3000).
						get(0); //gets the dom element object
			},
			'start'				: function(e, ui)
			{
				//this thing has to have a static width for whatever reason
				ui.helper.width(ui.helper.outerWidth());
			}
		});
	}
	//END setFieldDragging


	// --------------------------------------------------------------------

	/**
	 * sets dragability for the special insert buttons
	 *
	 * @access	private
	 * @param	{Object}	$buttonObj	the object to add dragging to
	 * @param	{Boolean}	disable		disable after drop?
	 * @return	{Null}
	 */

	function setInsertButtonEvent ($buttonObj, element, disableOnInsert)
	{
		disableOnInsert	= disableOnInsert	|| false;

		var elementId	= $buttonObj.attr('data-element-id');

		insertOutputs[elementId] = fieldWrapper({
			'elementId' : 'nonfield_' + elementId,
			'element'	: element,
			'requireable': ($.inArray(elementId, nonfieldReauireable) > -1) ? 'yes' : 'no'
		});

		$buttonObj.click(function(){
			var template = composerRow({
				"data"			: [insertOutputs[elementId]],
				'colspan'		: '12'
			});

			var $element = $(template).appendTo($composerRows);
			updateRowEvents($element, 'row');
		});

		setFieldDragging($buttonObj);
	}
	//END setInsertButtonEvent


	// --------------------------------------------------------------------

	/**
	 * Update row events
	 * Use after adding anything to the table
	 *
	 * @param	{Object} $element	element just inserted
	 * @param	{String} type		the type of event that happened, 'row' inserted?
	 * @return	{Null}
	 */

	function updateRowEvents ($element, type)
	{
		// -------------------------------------
		//	makes the rows in tbody sort
		// -------------------------------------

		$composerRows.sortable('refresh');

		// -------------------------------------
		//	the holder in each row cell
		// -------------------------------------

		$composerRows.find('tr:not(.no_sort) td:not(.row_control_holder)').sortable({
			'connectWith'			: '#composer .connectedSortable',
			'items'					: '.element_wrapper',
			'handle'				: '.element_cover',
			'forcePlaceholderSize'	: false,
			//it sucks to run this on stop because it affects
			//every element that comes in to every sortable
			//but since we are detecting class that should never
			//be there, this is safe for now
			//be careful where you add these classes
			'stop'					: function (event, ui)
			{
				var $that	= $(this),
					$item	= ui.item;

				//field tags from field dragging
				if (ui.item.is('.field_tag'))
				{
					var fieldName = ui.item.attr('data-field-name');

					$item.replaceWith(fieldOutput(fieldName));

					setTimeout(function(){
						updateRowEvents($that);
						checkRemovedFields();
					}, 0);
				}
				//insert element dragging
				else if (ui.item.is('.insert_element'))
				{
					var elementId = ui.item.attr('data-element-id');

					$item.replaceWith(insertOutputs[elementId]);

					setTimeout(function(){
						updateRowEvents($that);
						checkRemovedFields();
					}, 0);
				}

				return ui;
			},
			'start'				: function(e, ui)
			{
				if (ui.helper.width() > 400)
				{
					ui.helper.width(400);
				}
				//jqui uses height instead of outerheight so we need to reset
				ui.helper.height(ui.helper.css('height', '').outerHeight());
				return ui;
			}
		});

		// -------------------------------------
		//	draggable fields
		// -------------------------------------

		//adjust if needed row controlls
		checkRowControls();
	}
	//END updateRowEvents


	// --------------------------------------------------------------------

	/**
	 * Check fields that have been removed and enable or disable items
	 *
	 * @access	private
	 * @return	{Null}
	 */

	function checkRemovedFields ()
	{
		//check all fields visible and turn off grey used
		var $wrappers	= $composer.find(
				'.element_wrapper[data-element-id]:' +
					'not([data-element-id^="nonfield"])'
			);
		var $fields		= $('#field_list .field_tag');
		var fieldDict	= {};

		$fields.removeClass('disabled');

		//go over available fields and grey out and disable
		//the ones that are already out composer
		if ($wrappers.length > 0)
		{
			$fields.each(function(){
				var $that = $(this);

				fieldDict[$that.attr('data-field-name')] = $that;
			});

			$wrappers.each(function(){
				if (typeof fieldDict[$(this).attr('data-element-id')] === 'undefined')
				{
					return;
				}

				fieldDict[$(this).attr('data-element-id')].addClass('disabled');
			});
		}
	}
	//END checkRemovedFields


	// --------------------------------------------------------------------

	/**
	 * Checks to see if row controls should be deactivated
	 *
	 * @access	private
	 * @return	{Null}
	 */

	function checkRowControls ()
	{
		//see if they need to be greyed out
		$('.row_control').each(function(){
			var $that	= $(this),
				$tr		= $that.parent().parent(),
				total	= $tr.find('td').length;

			if (total == 5)
			{
				$that.find('.row_add_column').addClass('disabled');
			}
			else
			{
				$that.find('.row_add_column').removeClass('disabled');
			}

			if (total == 2)
			{
				$that.find('.row_remove_column').addClass('disabled');
			}
			else
			{
				$that.find('.row_remove_column').removeClass('disabled');
			}
		});
	}
	//END checkRowControls


	// --------------------------------------------------------------------

	/**
	 * Events listeners for the field edit/new modal window
	 *
	 * @access	private
	 * @param	{Object}	fancyboxObject	field edit form object from inside of fancybox
	 * @param	{Mixed}		update			is this an update, if so whats its name?
	 * @return	{Null}
	 */

	function fieldModalEvents (fancyboxObject, isUpdate)
	{
		var $fieldEditForm	= $('#field_edit_form');
		var formUrl			= $fieldEditForm.attr('action');
		var update			= isUpdate || false;

		//does standard field events from edit_field_cp.js
		Freeform.initFieldEvents();

		//custom select dropdowns
		Freeform.setUpChosenSelect($fieldEditForm);

		//prevent on submit and do via ajax
		$fieldEditForm.submit(function(e){
			e.preventDefault();

			var postData	= $fieldEditForm.serializeArray();

			postData.push({
				name	: 'include_field_data',
				//intentionally a string and not a bool
				value	: 'true'
			});

			postData = $.param(postData);

			//whirly window
			$.fancybox.showActivity();

			$.post(formUrl, postData, function(data) {

				$('.validation_error_holder').hide();

				//errors
				if ( ! data.success)
				{
					$.fancybox.hideActivity();

					Freeform.showValidationErrors(
						data.errors,
						$fieldEditForm
					);
				}
				//if this is an update we don't want to add
				else if (update !== false)
				{
					updateField(update, data, function(){
						$.fancybox.hideActivity();
						$.fancybox.close();
					});
				}
				else
				{
					addNewField(data, function(){
						$.fancybox.hideActivity();
						$.fancybox.close();
					});
				}
			}, 'json');

			return false;
		});
		//END $fieldEditForm.submit

		//don't want this silly thing going to the middle of the page.
		setTimeout(function(){
			$("#fancybox-wrap").stop().css({
				'top': '50px'
			});
		}, 0);
	}
	//end fieldModalEvents


	// --------------------------------------------------------------------

	/**
	 * Updates existing available field and output currently in form
	 *
	 * @access	private
	 * @param	{String}	oldName		old field_name of item updated
	 * @param	{Object}	data		data from response ajax
	 * @param	{Function}	callback	callback if any
	 * @return	{Null}
	 */

	function updateField (oldName, data, callback)
	{
		if (data.success)
		{
			var fieldName	= data.composerFieldData.fieldName;
			var fieldId		= data.composerFieldData.fieldId;

			//update composer data
			Freeform.composerFieldData[fieldName] = data.composerFieldData;

			if (oldName != fieldName)
			{
				$('.field_tag[data-field-name="' + oldName + '"]').
					attr('data-field-name', fieldName);

				$('.element_wrapper[data-element-id="' + oldName + '"]').
					attr('data-element-id', fieldName);
			}

			var $fieldTag	= $('.field_tag[data-field-name="' + fieldName + '"]');
			var isDisabled	= $fieldTag.hasClass('disabled');

			$fieldTag.replaceWith(fieldTag({
				'fieldName'		: fieldName,
				'fieldLabel'	: data.composerFieldData.fieldLabel
			}));

			if (isDisabled)
			{
				//this has to be retrieved again since it changed.
				$('.field_tag[data-field-name="' + fieldName + '"]').addClass('disabled');
			}

			$('.element_wrapper[data-element-id="' + fieldName + '"]').replaceWith(
				fieldOutput(fieldName)
			);

			updateRowEvents();

			//make the fields draggable
			setFieldDragging();
		}

		if (typeof callback == 'function')
		{
			callback();
		}
	}
	//END updateField


	// --------------------------------------------------------------------

	/**
	 * Add new Field to Composer after save
	 *
	 * @access	private
	 * @param	{Object}	data		data from response ajax
	 * @param	{Function}	callback	callback if any
	 */

	function addNewField (data, callback)
	{
		if (data.success)
		{
			var fieldName	= data.composerFieldData.fieldName;
			var fieldId		= data.composerFieldData.fieldId;

			Freeform.composerFieldData[fieldName]	= data.composerFieldData;
			Freeform.composerFieldIdList[fieldName]	= fieldId;

			//get new field data and add.
			$('#field_list').append(fieldTag({
				'fieldName'		: fieldName,
				'fieldLabel'	: data.composerFieldData.fieldLabel
			}));

			//make the new field draggable
			setFieldDragging();
		}
		/*else
		{
			//console.log(data);
		}*/

		if (typeof callback === 'function')
		{
			callback();
		}
	}
	//END addNewField


	// --------------------------------------------------------------------

	/**
	 * Field Output for Composer
	 *
	 * @access
	 * @param  {String} fieldName
	 * @return {Object}
	 */

	function fieldOutput (fieldName, required)
	{
		return fieldWrapper({
			'elementId'		: fieldName,
			'elementLabel'	: Freeform.composerFieldData[fieldName].fieldLabel,
			'element'		: Freeform.atob(
				Freeform.composerFieldData[fieldName].fieldOutput
			),
			'required'		: required || 'no'
		});
	}
	//END fieldOutput


	// --------------------------------------------------------------------

	/**
	 * Convert composer rows to a JSON output
	 *
	 * @access	private
	 * @return	{String}	JSON output of shown rows
	 */

	function getComposerRows (validate)
	{
		validate = validate || false;

		var $trs				= $('#composer_rows tr');
		var rows				= [];
		var fields				= [];
		var multipage			= false;
		var hasSubmitThisRow	= false;
			//defined earlier
			missingSubmits		= false;

		//each row
		$trs.each(function(){

			var row		= [];
			var $row	= $(this);
			var $tds	= $row.find('td:not(.row_control_holder)');

			//page breaks are nothing special
			if ($row.hasClass('page_break_holder'))
			{
				multipage = true;
				//we need to make sure there is at least
				//one submit before each page break.
				//we will use the bools to alert the user
				if ( ! hasSubmitThisRow)
				{
					missingSubmits = true;
				}

				rows.push('page_break');
				hasSubmitThisRow = false;
				return;
			}

			//each column
			$tds.each(function(){

				var $td			= $(this);
				var $elements	= $td.find('.element_wrapper');
				var items		= [];

				//each field
				$elements.each(function(){

					var $element	= $(this);
					var eid			= $element.attr('data-element-id');
					var required	= $element.attr('data-required');
					var item		= {};
					var text		= '';
					var html		= '';

					item.required	= required;

					//if this isn't a normal fields we need some special
					//provisions to make them store properly
					if (eid.match(/^nonfield_/))
					{
						var nonFieldType = eid.replace('nonfield_', '');

						item.type = eid;

						if (nonFieldType == 'paragraph')
						{
							html = $.trim(
								$element.find('.editable_paragraph .paragraph_content').html()
							);

							//if it's not just the placeholder text, then store
							if (html !== Freeform.composerLang.dblClickEdit)
							{
								item.html = html;
							}
							else
							{
								item.html = '';
							}
						}
						else if (nonFieldType == 'user_recipients')
						{
							//get label with required * removed if present
							text = $.trim(
								$element.
									find('.editable_userrec .element_label:first').
									text().replace(/\*$/igm, '')
							);

							//if it's not just the placeholder text, then store
							if (text !== Freeform.composerLang.notfyFriends)
							{
								item.html = Freeform.formPrep(text);
							}
							else
							{
								item.html = Freeform.composerLang.notfyFriends;
							}
						}
						else if (nonFieldType == 'dynamic_recipients')
						{
							var $pItem			= $element.find('.editable_dynrec');
							var $dataHolder		= $pItem.find('[data-recipients]');
							var $labelHolder	= $pItem.find('.element_label');
							var data			= '';
							var label			= '';
							var type			= 'select';
							var notificationId	= 0;

							if ($dataHolder.length > 0)
							{
								var rData = $dataHolder.attr('data-recipients');

								if (typeof rData === 'string')
								{
									var dataTest;

									try {
										dataTest = JSON.parse($.trim(rData));
									}
									catch (e)
									{
										dataTest = false;
									}

									if (dataTest)
									{
										data = dataTest;
									}
								}

								var rType = $dataHolder.attr('data-type');

								if (rType === 'select' || rType === 'checks')
								{
									type = rType;
								}

								var nId = parseInt($dataHolder.attr('data-notification-id'), 10);

								if ( ! isNaN(nId))
								{
									notificationId = nId;
								}
							}

							if ($labelHolder.length > 0 && $labelHolder.text())
							{
								//get label with required * removed if present
								label = $labelHolder.text().replace(/\*$/igm, '');
							}

							item.data			= data;
							item.label			= label;
							item.outputType		= type;
							item.notificationId = notificationId;
						}
						else if (nonFieldType == 'submit')
						{
							hasSubmitThisRow = true;

							text = $.trim(
								$element.find('.editable_submit button:first').text()
							);

							//if it's not just the placeholder text, then store
							if (text !== Freeform.composerLang.submit)
							{
								item.html = Freeform.formPrep(text);
							}
							else
							{
								item.html = Freeform.composerLang.submit;
							}
						}
						else if (nonFieldType == 'submit_previous')
						{
							//this doesn't count as a submit so we don't
							//add the boolean here
							//
							text = $.trim(
								$element.find('.editable_submit_previous button:first').text()
							);

							//if it's not just the placeholder text, then store
							if (text !== Freeform.composerLang.submit_previous)
							{
								item.html = Freeform.formPrep(text);
							}
							else
							{
								item.html = Freeform.composerLang.submit_previous;
							}
						}
					}
					//freeform custom fields
					else
					{
						item.type		= 'field';
						item.fieldId	= Freeform.composerFieldData[eid].fieldId;

						//fields is a field id holder
						fields.push(item.fieldId);
					}

					items.push(item);
				});

				row.push(items);
			});

			rows.push(row);
		});

		if ( ! multipage && ! hasSubmitThisRow)
		{
			missingSubmits = true;
		}

		var output = {
			'rows'		: rows,
			'fields'	: fields
		};

		return JSON.stringify(output);
	}
	//END getComposerRows


	// --------------------------------------------------------------------

	/**
	 * Removed disallowed tags from html
	 *
	 * @access	private
	 * @param	{String}	html	html to remove tags from
	 * @return	{String}			cleaned html
	 */

	function cleanParagraphTags (html)
	{
		//we have to have the wrapper here so we can look at every
		//element as a child element.
		//when we return html() it wont return the outerwrapper <div>
		return (
			$('<div>' + html + '</div>').
				find(":not(" + Freeform.allowedHtmlTags.join(', ') + ")").
					removeTags().
				end().
				html()
		);
	}
	//END cleanParagraphTags


	// --------------------------------------------------------------------

	/**
	 * Set Dynamic Recipient Events
	 *
	 * @access	private
	 * @param	{Object}	$elementWrapper		jquery object of parent
	 */

	function setDynrecEditEvents ($elementWrapper)
	{
		var $dynrec			= $('#dynrec_editor');
		var $valueHolder	= $('.type_value_label_holder:first', $dynrec);

		$dynrec.delegate('.freeform_delete_button', 'click', function(){
			$(this).parent().remove();
		});

		Freeform.autoDupeLastInput($valueHolder, 'value_label_holder_input');
		Freeform.setUpChosenSelect($dynrec);

		// -------------------------------------
		//	save data
		// -------------------------------------

		$dynrec.find('.dynrec_edit.finished').click(function(e){
			var dynrecType = (
				($('[name="dynrec_type"]:checked').val() == 'checks') ?
					'checks' :
					'select'
			);

			var dynrecData = {};

			$dynrec.find('.value_label_holder_input').each(function(){
				var $that = $(this);

				var value = $.trim($that.find('[name^="list_value_holder_input"]:first').val());
				var label = $.trim($that.find('[name^="list_label_holder_input"]:first').val());

				if (value !== '' && label !== '')
				{
					dynrecData[value] = label;
				}
			});

			var dynrecLabel = $.trim($dynrec.find('[name="dynrec_output_label"]').val());

			var notificationId = $dynrec.find('#dynrec_notification_id').val();

			$elementWrapper.replaceWith(dynrec({
				"label"				: Freeform.formPrep(dynrecLabel),
				"data"				: dynrecData,
				"jsonData"			: Freeform.formPrep(JSON.stringify(dynrecData)),
				"type"				: dynrecType,
				"notificationId"	: notificationId
			}));

			$.fancybox.close();

			e.preventDefault();
			return false;
		});
	}
	//END setDynrecEditEvents


	// --------------------------------------------------------------------

	// -------------------------------------
	//	On ready
	// -------------------------------------

	$(function(){
		// -------------------------------------
		//	we need underscore to run templates
		//	<{= thing }> style because of stupid
		//	asp tags in PHP
		// -------------------------------------

		_.templateSettings = {
			evaluate	: /<\{([\s\S]+?)\}>/g,
			interpolate	: /<\{=([\s\S]+?)\}>/g,
			escape		: /<\{-([\s\S]+?)\}>/g
		};

		// -------------------------------------
		//	these are all defined earlier
		//	so var keywords are left off on purpose
		// -------------------------------------

		//doing this all with context avoids name
		//collision even if double IDs are present
		$context					= $('#composer_wrapper');

		//purposefully not set with var as its defined outside of this
		$composer					= $('#composer',					$context);

		//local vars
		$singleRow					= $('#single_row_button',			$context);
		$doubleRow					= $('#double_row_button',			$context);
		$tripleRow					= $('#triple_row_button',			$context);
		$quadrupleRow				= $('#quadruple_row_button',		$context);
		$titleButton				= $('#title_button',				$context);
		$paragraphButton			= $('#paragraph_button',			$context);
		$pageBreakButton			= $('#page_break_button',			$context);
		$captchaButton				= $('#captcha_button',				$context);
		$dynamicRecipientsButton	= $('#dynamic_recipients_button',	$context);
		$userRecipientsButton		= $('#user_recipients_button',		$context);
		$submitButtonButton			= $('#submit_button_button',		$context);
		$submitPreviousButtonButton	= $('#submit_previous_button_button', $context);
		$composerRows				= $('#composer_rows',				$context);
		$searchClear				= $('#search_clear_button',			$context);
		$composerSideBar			= $('#composer_side_bar',			$context);
		$stickyControls				= $('#sticky_controls',				$context);

		// -------------------------------------
		//	template html
		// -------------------------------------

		captchaTemplate				= $('#captcha_template').html();
		columnTemplate				= $('#column_template').html();
		composerRowTemplate			= $('#composer_row_template').html().
										replace('<{= columnTemplate }>', columnTemplate);
		dynrecTemplate				= $('#dynrec_template').html();
		editDynrecTemplate			= $('#edit_dynrec_template').html();
		editParagraphTemplate		= $('#edit_paragraph_template').html();
		editSubmitTemplate			= $('#edit_submit_button_template').html();
		editSubmitPreviousTemplate	= $('#edit_submit_previous_button_template').html();
		editUserrecTemplate			= $('#edit_userrec_template').html();
		fieldDragTemplate			= $('#field_drag_template').html();
		fieldDragTemplate			= $('#field_drag_template').html();
		fieldTagTemplate			= $('#field_tag_template').html();
		fieldWrapperTemplate		= $('#field_wrapper_template').html();
		pageBreakTemplate			= $('#page_break_template').html();
		paragraphTemplate			= $('#paragraph_template').html();
		submitButtonTemplate		= $('#submit_button_template').html();
		submitPreviousButtonTemplate= $('#submit_previous_button_template').html();
		textareaTemplate			= $('#textarea_template').html();
		userrecTemplate				= $('#userrec_template').html();

		// -------------------------------------
		//	templates
		// -------------------------------------
		//	underscore JS required
		// -------------------------------------

		column						= _.template(columnTemplate);
		composerRow					= _.template(composerRowTemplate);
		dynrec						= _.template(dynrecTemplate);
		editDynrec					= _.template(editDynrecTemplate);
		editParagraph				= _.template(editParagraphTemplate);
		editSubmit					= _.template(editSubmitTemplate);
		editSubmitPrevious			= _.template(editSubmitPreviousTemplate);
		editUserrec					= _.template(editUserrecTemplate);
		fieldDrag					= _.template(fieldDragTemplate);
		fieldTag					= _.template(fieldTagTemplate);
		fieldWrapper				= _.template(fieldWrapperTemplate);
		paragraph					= _.template(paragraphTemplate);
		submitButton				= _.template(submitButtonTemplate);
		submitPreviousButton		= _.template(submitPreviousButtonTemplate);
		userrec						= _.template(userrecTemplate);

		var pageBreakOutputTemplate	= composerRow({
			"data"			: [pageBreakTemplate],
			'colspan'		: '12',
			'notSortable'	: false
		});

		setRowButtonEvents($singleRow, 'single', 1);
		setRowButtonEvents($doubleRow, 'double', 2);
		setRowButtonEvents($tripleRow, 'triple', 3);
		setRowButtonEvents($quadrupleRow, 'quadruple', 4);


		$pageBreakButton.click(function(){
			var $element = $(pageBreakOutputTemplate);

			$element.addClass('page_break_holder no_sort').
					appendTo($composerRows);
			updateRowEvents($element, 'row');
		});

		$pageBreakButton.draggable({
			'connectToSortable' : '#composer_rows',
			'forceHelperSize'	: true,
			'zIndex'			: 1000,
			'helper'			: function()
			{
				sortOut = false;
				return $(this).clone().
							addClass('insert_element_width taller').
							css('text-align', 'center').
							height($(this).outerHeight()).
							remove().
							appendTo($composer).
							css({'zIndex': 3000, paddingRight:0});
			}
		});

		// -------------------------------------
		//	special buttons
		// -------------------------------------

		setInsertButtonEvent($titleButton, '<h2>' + Freeform.composerLang.formLabel + '<\/h2>');
		setInsertButtonEvent($captchaButton, captchaTemplate);
		setInsertButtonEvent($submitButtonButton, submitButton());
		setInsertButtonEvent($submitPreviousButtonButton, submitPreviousButton());
		setInsertButtonEvent($paragraphButton, paragraph());
		setInsertButtonEvent($dynamicRecipientsButton, dynrec());
		setInsertButtonEvent($userRecipientsButton, userrec());

		// -------------------------------------
		//	paragraph editing
		// -------------------------------------

		$composer.delegate(
			'.element_wrapper:has(.editable_paragraph)',
			'dblclick',
			function()
			{
				var $that		= $(this);
				var $pItem		= $that.find('.editable_paragraph');
				var data		= $pItem.find('.paragraph_content').html();
				//if input data is the same as the instructions placeholder, don't show
				var inputData	= (
					Freeform.composerLang.dblClickEdit == $.trim(data)
				) ? '' : $.trim(data);

				//replace paragraph with editor
				$pItem.replaceWith(
					editParagraph({
						"data": Freeform.formPrep(inputData)
					})
				);
				//hide element cover used for moving element
				$that.find('.element_cover').hide();
				//focus in text area
				$that.find('.editor textarea').get(0).focus();
			}
		);

		// -------------------------------------
		//	paragraph edit save
		// -------------------------------------

		$composer.delegate('.element_wrapper .paragraph_edit.finished', 'click', function(){
			var $that		= $(this);
			var $wrapper	= $that.closest('.element_wrapper');
			var $editor		= $wrapper.find('.editor');
			var $pItem		= $editor.find('textarea:first');
			var data		= $.trim($pItem.val());

			//if no input data was recorded, put the instuctions back
			var inputData	= Freeform.composerLang.dblClickEdit;

			if (data !== '' && data !== inputData)
			{
				$.fancybox.showActivity();
				inputData	= cleanParagraphTags(Security.xssClean(data));
				$.fancybox.hideActivity();
			}

			//replace with normal paragraph placeholder
			$editor.replaceWith(paragraph({"data":inputData}));
			//put cover back up for moving
			$wrapper.find('.element_cover').show();
		});

		// -------------------------------------
		//	submit editing
		// -------------------------------------

		$composer.delegate(
			'.element_wrapper:has(.editable_submit)',
			'dblclick',
			function()
			{
				var $that		= $(this);
				var $pItem		= $that.find('.editable_submit');
				var data		= $pItem.find(' button:first').text();
				//if input data is the same as the instructions placeholder, don't show
				var inputData	= (
					Freeform.composerLang.submit == $.trim(data)
				) ? '' : $.trim(data);

				//replace paragraph with editor
				$pItem.replaceWith(
					editSubmit({
						"data": Freeform.formPrep(inputData)
					})
				);
				//hide element cover used for moving element
				$that.find('.element_cover').hide();
				//focus in text area
				$that.find('.editor input').get(0).focus();
			}
		);

		// -------------------------------------
		//	submit previous editing
		// -------------------------------------

		$composer.delegate(
			'.element_wrapper:has(.editable_submit_previous)',
			'dblclick',
			function()
			{
				var $that		= $(this);
				var $pItem		= $that.find('.editable_submit_previous');
				var data		= $pItem.find(' button:first').text();
				//if input data is the same as the instructions placeholder, don't show
				var inputData	= (
					Freeform.composerLang.submit_previous == $.trim(data)
				) ? '' : $.trim(data);

				//replace paragraph with editor
				$pItem.replaceWith(
					editSubmitPrevious({
						"data": Freeform.formPrep(inputData)
					})
				);
				//hide element cover used for moving element
				$that.find('.element_cover').hide();
				//focus in text area
				$that.find('.editor input').get(0).focus();
			}
		);

		// -------------------------------------
		//	submit edit save
		// -------------------------------------

		$composer.delegate('.element_wrapper .submit_edit.finished', 'click', function(){
			var $that		= $(this);
			var $wrapper	= $that.closest('.element_wrapper');
			var $editor		= $wrapper.find('.editor');
			var $pItem		= $editor.find('input[name="submit_edit"]:first');
			var data		= $.trim($pItem.val());

			//if no input data was recorded, put the instuctions back
			var inputData	= Freeform.composerLang.submit;

			if (data !== '' && data !== inputData)
			{
				inputData	= Freeform.formPrep(data);
			}

			//replace with normal submit placeholder
			$editor.replaceWith(submitButton({"data":inputData}));
			//put cover back up for moving
			$wrapper.find('.element_cover').show();
		});

		// -------------------------------------
		//	submit edit save
		// -------------------------------------

		$composer.delegate('.element_wrapper .submit_previous_edit.finished', 'click', function(){
			var $that		= $(this);
			var $wrapper	= $that.closest('.element_wrapper');
			var $editor		= $wrapper.find('.editor');
			var $pItem		= $editor.find('input[name="submit_previous_edit"]:first');
			var data		= $.trim($pItem.val());

			//if no input data was recorded, put the instuctions back
			var inputData	= Freeform.composerLang.submit_previous;

			if (data !== '' && data !== inputData)
			{
				inputData	= Freeform.formPrep(data);
			}

			//replace with normal submit placeholder
			$editor.replaceWith(submitPreviousButton({"data":inputData}));
			//put cover back up for moving
			$wrapper.find('.element_cover').show();
		});

		// -------------------------------------
		//	User Recipients editing
		// -------------------------------------

		$composer.delegate(
			'.element_wrapper:has(.editable_userrec)',
			'dblclick',
			function()
			{
				var $that		= $(this);
				var $pItem		= $that.find('.editable_userrec');
				//get label with required * removed if present
				var data		= $pItem.find('.element_label').text().replace(/\*$/igm, '');
				//if input data is the same as the instructions placeholder, don't show
				var inputData	= (
					Freeform.composerLang.notfyFriends == $.trim(data)
				) ? '' : $.trim(data);

				//replace paragraph with editor
				$pItem.replaceWith(
					editUserrec({
						"data": Freeform.formPrep(inputData)
					})
				);
				//hide element cover used for moving element
				$that.find('.element_cover').hide();
				//focus in text area
				$that.find('.editor input').get(0).focus();
			}
		);

		// -------------------------------------
		//	User Recipients edit save
		// -------------------------------------

		$composer.delegate('.element_wrapper .userrec_edit.finished', 'click', function(){
			var $that		= $(this);
			var $wrapper	= $that.closest('.element_wrapper');
			var $editor		= $wrapper.find('.editor');
			var $pItem		= $editor.find('input[name="userrec_edit"]:first');
			var data		= $.trim($pItem.val());

			//if no input data was recorded, put the instuctions back
			var inputData	= Freeform.composerLang.notfyFriends;

			if (data !== '' && data !== inputData)
			{
				inputData	= Freeform.formPrep(data);
			}

			//replace with normal placeholder
			$editor.replaceWith(userrec({"data":inputData}));
			//put cover back up for moving
			$wrapper.find('.element_cover').show();
		});

		// -------------------------------------
		//	dynamic recipients editing
		// -------------------------------------

		$composer.delegate(
			'.element_wrapper:has(.editable_dynrec)',
			'dblclick',
			function()
			{
				var $that			= $(this);
				var $pItem			= $that.find('.editable_dynrec');
				var $dataHolder		= $pItem.find('[data-recipients]');
				var $labelHolder	= $pItem.find('.element_label');
				var data			= '';
				var label			= '';
				var type			= 'select';
				var notificationId	= 0;

				if ($dataHolder.length > 0)
				{
					var rData = $dataHolder.attr('data-recipients');

					if (typeof rData === 'string')
					{
						//if its busted somehow, sadly move on :/
						try
						{
							data = JSON.parse($.trim(rData));
						}
						catch (e)
						{
							data = '';
						}
					}

					var rType = $dataHolder.attr('data-type');

					if (rType === 'select' || rType === 'checks')
					{
						type = rType;
					}

					var nId = parseInt($dataHolder.attr('data-notification-id'), 10);

					if ( ! isNaN(nId))
					{
						notificationId = nId;
					}
				}

				if ($labelHolder.length > 0 && $labelHolder.text())
				{
					//remove the requiered labels * output
					label = $labelHolder.text().replace(/\*$/igm, '');
				}

				$.fancybox(_.extend(fieldFancyboxDefaults, {
					'content'				: editDynrec({
						"data"				: data,
						"label"				: Freeform.formPrep(label),
						"type"				: type,
						"notificationId"	: notificationId
					}),
					'onComplete' : function ()
					{
						$(this).stop();
						$.fancybox.center();

						setDynrecEditEvents($pItem);
					}
				}));
			}
		);

		// -------------------------------------
		//	add field tag by clicking
		// -------------------------------------

		$('#field_list').delegate('.field_tag', 'click', function(e){
			var $that		= $(this),
				fieldName	= $that.attr('data-field-name');

			//disabled? NO CLICK FOR YOU!
			if ($that.hasClass('disabled'))
			{
				return false;
			}

			var $element = $(composerRow({
				"data"			: [fieldOutput(fieldName)],
				'colspan'		: '12'
			}));

			$element.appendTo($composerRows);

			$that.addClass('disabled');

			updateRowEvents($element, 'row');

			e.preventDefault();
			return false;
		});

		// -------------------------------------
		//	new field button
		// -------------------------------------

		//this is ajax so that if there are changes
		//to the edit field setup, we don't have to
		//keep updating the initialized vars in PHP

		$.get(Freeform.url.newField, function(data){
			Freeform.newFieldHtml = data;

			$('#new_field_button').fancybox(_.extend(fieldFancyboxDefaults, {
				'content'			: Freeform.newFieldHtml,
				'onComplete'		: function()
				{
					fieldModalEvents(this);
				}
			}));
		});

		// -------------------------------------
		//	add field tag by clicking
		// -------------------------------------

		$('#field_list').delegate('.field_tag .freeform_edit_button', 'click', function(e){
			var $that		= $(this),
				fieldName	= $that.parent().attr('data-field-name'),
				fieldData	= Freeform.composerFieldData[fieldName];

			$.fancybox.showActivity();

			$.post(fieldData.fieldEditUrl, function(data){
				$.fancybox(_.extend(fieldFancyboxDefaults, {
					'content'			: data,
					'onComplete'		: function()
					{
						fieldModalEvents(this, fieldName);
					}
				}));
			});

			e.preventDefault();
			return false;
		});

		// -------------------------------------
		//	row sorting
		// -------------------------------------

		$composerRows.sortable({
			"handle"			: '.row_control .freeform_drag_button',
			"containment"		: "#composer > table tbody",
			"axis"				: 'y',
			"items"				: 'tr',
			"forceHelperSize"	: true,
			'helper'			: function(e, ui)
			{
				//need this for its liftoff, but
				//once it hits back down the parent widths
				//do their work again
				ui.children().each(function() {
					var $that = $(this);
					$that.width($that.outerWidth());
				});

				return ui;
			},
			'receive'				: function (event, ui) {
				var $that	= $(this);
				var $element;
					//this sort of breaks in situtions with conncetedsortable
					//but not here since there is only one group of rows
				var $item	= $that.data().sortable.currentItem;

				//incoming item
				sortOut = true;

				//field tags from field dragging
				if (ui.item.is('[id$="_row_button"]'))
				{
					var name = ui.item.attr('id').replace("_row_button", '');

					$element = $(rowTemplates[name]);

					$item.replaceWith($element);

					setTimeout(function(){
						updateRowEvents($element, 'row');
					}, 0);
				}
				//how i hate special exceptions
				else if (ui.item.attr('data-element-id') == 'page_break')
				{
					$element = $(pageBreakOutputTemplate);
					$element.addClass('page_break_holder no_sort');

					$item.replaceWith($element);

					setTimeout(function(){
						updateRowEvents($element, 'row');
					}, 0);
				}

				return ui;
			},
			'over'					: function(e, ui)
			{
				sortOut = true;
			}
		});
		//END	$composerRows.sortable

		// -------------------------------------
		//	allow rows to drop over the empty composer
		//	so we can have drop below work for rows
		//	we cannot make the composer table 100% height
		//	because the rows need their min height and
		//	would instead expand to the height of the table
		// -------------------------------------

		$composer.droppable({
			'accept'		: '.row_button',
			'drop'			: function(event, ui)
			{
				if (ui.draggable.attr('id'))
				{
					$('#' + ui.draggable.attr('id')).click();
				}
				//there is a false drop that fires right after
				//you sort in and sort out with a connected sortable
				//so this defeats it
				else if (sortOut)
				{
					sortOut = false;
				}
				//since sort out was false, and we don't have an ID
				//(as soon as a draggable connected to a sortable gets
				//rolled over said sortable, its ID gets stripped)
				//we regex to find its class that mimics its ID.

				else
				{
					//we need an extra check here because legit sortable
					//connections fire AFTER this does, so we need to
					//give them a chance to run and set sortOut before
					//this fires
					setTimeout(function(){
						if (sortOut)
						{
							return;
						}

						var name = /[a-z]+\_row\_button/g.exec(
							ui.draggable.attr('class')
						);

						if (typeof name[0] !== 'undefined')
						{
							$('#' + name[0]).click();
						}
					},0);
				}
			}
		});

		// -------------------------------------
		//	element require
		// -------------------------------------

		$composer.delegate(
			'.element_control .element_require',
			'click',
			function(e)
			{
				var $that = $(this);

				$that.closest('.element_wrapper').attr('data-required', 'yes');

				e.preventDefault();
				return false;
			}
		);

		// -------------------------------------
		//	element require
		// -------------------------------------

		$composer.delegate(
			'.element_control .element_unrequire',
			'click',
			function(e)
			{
				var $that = $(this);

				$that.closest('.element_wrapper').attr('data-required', 'no');

				e.preventDefault();
				return false;
			}
		);

		// -------------------------------------
		//	element control delete
		// -------------------------------------

		$composer.delegate(
			'.element_control .element_delete',
			'click',
			function(e)
			{
				var $that = $(this);

				$that.closest('.element_wrapper').remove();
				checkRemovedFields();

				e.preventDefault();
				return false;
			}
		);

		// -------------------------------------
		//	row control delete
		// -------------------------------------

		$composer.delegate(
			'.row_control .row_delete, ' +
				'.page_break_holder .freeform_delete_button',
			'click',
			function(e)
			{
				var $that = $(this);

				$that.closest('tr').remove();
				checkRemovedFields();

				e.preventDefault();
				return false;
			}
		);

		// -------------------------------------
		//	add row column
		// -------------------------------------

		$composer.delegate('.row_control .row_add_column', 'click', function(e){
			var $that	= $(this),
				$tr		= $that.closest('tr'),
				$tds	= $tr.find('td:not(.row_control_holder)');

			//if the length is already 5
			//dont add any more!
			if ($tds.length < 4)
			{
				var colspan = 12/($tds.length + 1);

				$tds.last().after(column({
					"colspan" : colspan
				}));

				//reset
				$tds	= $tr.find('td:not(.row_control_holder)');

				$tds.
					attr('colspan', colspan).
					css('width', Freeform.fractionToFloat(colspan, 12, 96) + '%');
			}

			checkRowControls();
			updateRowEvents();

			e.preventDefault();
			return false;
		});

		// -------------------------------------
		//	remove row column
		// -------------------------------------

		$composer.delegate('.row_control .row_remove_column', 'click', function(e){
			var $that	= $(this),
				$tr		= $that.closest('tr'),
				$tds	= $tr.find('td:not(.row_control_holder)');

			//if the length is already 1
			//dont remove any more!
			if ($tds.length > 1)
			{
				var colspan = 12/($tds.length - 1);

				$tds.last().remove();

				//reset
				$tds	= $tr.find('td:not(.row_control_holder)');

				$tds.
					attr('colspan', colspan).
					css('width', Freeform.fractionToFloat(colspan, 12, 96) + '%');
			}

			checkRowControls();
			checkRemovedFields();

			e.preventDefault();
			return false;
		});

		// -------------------------------------
		//	make fields draggable
		// -------------------------------------

		setFieldDragging();

		// -------------------------------------
		//	preview callBack
		// -------------------------------------

		var previewId				= 0;
		var previousComposerJSON	= '';

		var showPreview = function()
		{
			var composerTemplate = $('#composer_template_select').val();

			$.fancybox(_.extend(_.clone(fieldFancyboxDefaults), {
				'width' : '95%',
				'height' : '95%',
				'type'	: 'iframe',
				'href'	: Freeform.url.composerPreview +
							'&preview_id=' + previewId +
							'&template_id=' + composerTemplate +
							'&cache_bust=' + (new Date()).getTime(),
				'title'	: Freeform.composerLang.previewTitle + ': ' +
							Freeform.composerLang.formLabel,
				'onComplete' : function ()
				{
					$(this).stop();
					$.fancybox.center();
				}
			}));
		};

		var checkPreview = function(composerJSON)
		{
			$.fancybox.showActivity();

			if (composerJSON !== previousComposerJSON)
			{
				$.post(
					Freeform.url.composerSave,
					{
						'preview'		: 'y',
						'composer_data'	: composerJSON,
						'XID'			: EE.XID || Freeform.XID
					},
					function(data)
					{
						previousComposerJSON = composerJSON;
						previewId = data.composerId;
						showPreview();
					},
					'json'
				);
			}
			else
			{
				showPreview();
			}
		};

		// -------------------------------------
		//	ignore submit errors?
		// -------------------------------------

		var ignoreSubmitError = (
			typeof Freeform.disableMissingSubmit !== 'undefined' &&
			Freeform.disableMissingSubmit === true
		);

		// -------------------------------------
		//	preview button
		// -------------------------------------

		$('#preview').click(function(e){
			var composerJSON = getComposerRows();

			//if there isn't a submit button for every page
			if ( ! ignoreSubmitError && missingSubmits)
			{
				Freeform.jQUIDialog({
					'immediate'		: true,
					'cancel'		: Freeform.composerLang.cancel,
					'cancelClick'	: function()
					{

					},
					'ok'			: Freeform.composerLang.continueAnyway,
					'okClick'		: function()
					{
						checkPreview(composerJSON);
					},
					'message'		: Freeform.composerLang.missingSubmits
				});
			}
			else
			{
				checkPreview(composerJSON);
			}

			e.preventDefault();
			return false;
		}).css('cursor', 'pointer');

		// -------------------------------------
		//	save composer
		// -------------------------------------

		var $saveForm = $('#save_composer');
		var submitRedirect = true;

		function saveComposer(e, redirect)
		{
			redirect = (submitRedirect === false) ? false : true;

			$.fancybox.showActivity();

			var composerJSON = getComposerRows();

			//if there isn't a submit button for every page
			if ( ! ignoreSubmitError && missingSubmits)
			{
				$.fancybox.hideActivity();

				Freeform.jQUIDialog({
					'immediate'		: true,
					'cancel'		: Freeform.composerLang.cancel,
					'cancelClick'	: function()
					{

					},
					'ok'			: Freeform.composerLang.continueAnyway,
					'okClick'		: function()
					{
						ignoreSubmitError = true;
						$('#save_composer').submit();
					},
					'message'		: Freeform.composerLang.missingSubmits
				});

				e.preventDefault();
				return false;
			}

			$('#composer_save_data').val(composerJSON);
			$('#composer_template_id').val($('#composer_template_select').val());

			if ( ! redirect)
			{
				$.post($saveForm.attr('action'), $saveForm.serialize(), function(e){
					$.fancybox.hideActivity();
				});

				e.preventDefault();
				return false;
			}
		}
		//END saveComposer

		$('#save_composer').submit(saveComposer);

		$('#save_and_finish').click(function(e){
			submitRedirect = true;
		}).css('cursor', 'pointer');

		//quicksave shouldn't post
		$('#quicksave').click(function(e){
			submitRedirect = false;
		}).css('cursor', 'pointer');

		// -------------------------------------
		//	clear all?
		// -------------------------------------

		$('#clear_all').click(function(){
			Freeform.jQUIDialog({
				'immediate'		: true,
				'cancel'		: Freeform.composerLang.cancel,
				'cancelClick'	: function()
				{

				},
				'ok'			: Freeform.composerLang.ok,
				'okClick'		: function()
				{
					$composer.
						find(
							'.row_control .row_delete, ' +
							'.page_break_holder .freeform_delete_button'
						).
						click();
				},
				'message'		: Freeform.composerLang.clearAllWarn
			});
		});

		// -------------------------------------
		//	previous composer layout?
		// -------------------------------------

		if (typeof Freeform.composerLayoutData.rows !== 'undefined')
		{
			//force a wait
			setTimeout(function(){
				var rk, rl, ck, cl, fk, fl;
				var columns, fields, row, columnData;
				var rows		= Freeform.composerLayoutData.rows;
				var pageCount	= 1;

				for (rk = 0, rl = rows.length; rk < rl; rk++)
				{
					if (rows[rk] == 'page_break')
					{
						$pageBreakButton.click();
						pageCount++;
						continue;
					}

					columns		= rows[rk];
					row			= [];

					for (ck = 0, cl = columns.length; ck < cl; ck++)
					{
						fields		= columns[ck];
						columnData	= '';

						for (fk = 0, fl = fields.length; fk < fl; fk++)
						{
							//title
							if (fields[fk].type == 'nonfield_title')
							{
								columnData += insertOutputs['title'];
							}
							//paragraph
							else if (fields[fk].type == 'nonfield_paragraph')
							{
								if (fields[fk].html === '')
								{
									columnData += insertOutputs['paragraph'];
								}
								else
								{
									columnData += fieldWrapper({
										'elementId' : 'nonfield_paragraph',
										'element'	: paragraph({data:fields[fk].html})
									});
								}
							}
							//user recipients
							else if (fields[fk].type == 'nonfield_user_recipients')
							{
								if (fields[fk].html === '')
								{
									columnData += insertOutputs['paragraph'];
								}
								else
								{
									columnData += fieldWrapper({
										'elementId' : 'nonfield_user_recipients',
										'element'	: userrec({data:fields[fk].html}),
										'requireable': 'yes',
										'reqiured' : (
											typeof fields[fk].required !== 'undefined' &&
											fields[fk].required == 'yes'
										) ? 'yes' : 'no'
									});
								}
							}
							//dynamic recipients
							else if (fields[fk].type == 'nonfield_dynamic_recipients')
							{
								if (fields[fk].html === '')
								{
									columnData += insertOutputs['dynrec'];
								}
								else
								{
									columnData += fieldWrapper({
										'elementId' : 'nonfield_dynamic_recipients',
										'element'		: dynrec({
											"jsonData"			: Freeform.formPrep(JSON.stringify(fields[fk].data)),
											"data"				: fields[fk].data,
											"label"				: Freeform.formPrep(fields[fk].label),
											"type"				: fields[fk].outputType,
											"notificationId"	: (
												(typeof fields[fk].notificationId !== "undefined") ?
													fields[fk].notificationId :
													0
												)
										}),
										'requireable': 'yes',
										'reqiured' : (
											typeof fields[fk].required !== 'undefined' &&
											fields[fk].required == 'yes'
										) ? 'yes' : 'no'
									});
								}
							}
							//captcha
							else if (fields[fk].type == 'nonfield_captcha')
							{
								columnData += insertOutputs['captcha'];
							}
							//submit
							else if (fields[fk].type == 'nonfield_submit')
							{
								if (typeof fields[fk].html === 'undefined' ||
									fields[fk].html === '')
								{
									columnData += insertOutputs['submit'];
								}
								else
								{
									columnData += fieldWrapper({
										'elementId' : 'nonfield_submit',
										'element'	: submitButton({data:Freeform.formPrep(fields[fk].html)})
									});
								}
							}
							//submit previous
							else if (fields[fk].type == 'nonfield_submit_previous')
							{
								if (typeof fields[fk].html === 'undefined' ||
									fields[fk].html === '')
								{
									columnData += insertOutputs['submit_previous'];
								}
								else
								{
									columnData += fieldWrapper({
										'elementId' : 'nonfield_submit_previous',
										'element'	: submitPreviousButton({data:Freeform.formPrep(fields[fk].html)})
									});
								}
							}
							//field
							else if (
								fields[fk].type == 'field' &&
								typeof fields[fk].fieldId !== 'undefined' &&
								typeof Freeform.composerFieldIdList[fields[fk].fieldId] !== 'undefined'
							)
							{
								columnData += fieldOutput(
									Freeform.composerFieldIdList[fields[fk].fieldId],
									(typeof fields[fk].required !== 'undefined' &&
									fields[fk].required == 'yes') ? 'yes' : 'no'
								);
							}
						}
						//END for (fk = 0, fl = fields.length; fk < fl; fk++)

						row.push(columnData);
					}
					//END for (ck = 0, cl = columns.length; ck < cl; ck++)

					var $element = $(composerRow({
						"data"			: row,
						'colspan'		: (12/row.length)
					})).appendTo($composerRows);

					updateRowEvents($element, 'row');
					checkRemovedFields();
				}
				//END for (rk = 0, rl = rows.length; rk < rl; rk++)
			}, 0);
			//END setttimeout
		}
		else
		{
			$titleButton.click();
		}
		//END if (typeof Freeform.composerLayoutData.rows !== 'undefined')

		// -------------------------------------
		//	stick conrolls
		// -------------------------------------

		var sideBartop = (
			$composerSideBar.offset().top - parseFloat(
				$composerSideBar.css('margin-top').replace(/auto/, 0)
			)
		);

		var sideBarHeight = $composerSideBar.outerHeight();

		var $uiWrapper = $('div.pageContents .solspace_ui_wrapper:first');

		var uiWrapperTop = (
			$uiWrapper.offset().top - parseFloat(
				$uiWrapper.css('margin-top').replace(/auto/, 0)
			)
		);

		var uiWrapperHeight;

		$(window).scroll(function(event)
		{
			uiWrapperHeight		= $uiWrapper.outerHeight();
			var wrapperBottom	= uiWrapperTop + uiWrapperHeight;
			var scrollTop		= $(this).scrollTop();

			//scrolling down?
			if ( $stickyControls.is(':checked') &&
				scrollTop >= sideBartop)
			{
				//going past the bottom? stick ti bottom
				if (scrollTop + sideBarHeight > wrapperBottom)
				{
					$composerSideBar.removeClass('sticky');
					$composerSideBar.addClass('sticky-bottom');
				}
				else
				{
					$composerSideBar.removeClass('sticky-bottom');
					$composerSideBar.addClass('sticky');
				}
			}
			else
			{
				$composerSideBar.removeClass('sticky-bottom');
				$composerSideBar.removeClass('sticky');
			}
		});

		// -------------------------------------
		//	search box
		// -------------------------------------

		//search filder
		Freeform.elementSearch(
			'#search_fields',
			'#field_list .field_tag',
			'.field_label',
			'html'
		);

		//clear search x
		$searchClear.click(function(){
			//have to fire keyup here to reset
			$('#search_fields').val('').keyup();
			$searchClear.hide();
		}).hide();

		//show hide clear search
		var searchClearInt = 0;

		$('body').delegate('#search_fields', 'keyup', function(e){
			var $that = $(this);

			clearTimeout(searchClearInt);

			searchClearInt = setTimeout(function(){
				//hide all unless its empty or a placeholder
				//replacement helper
				if ($.trim($that.val()) === '' ||
					($that.attr('placeholder') &&
					$that.val() == $that.attr('placeholder'))
				)
				{
					$searchClear.hide();
				}
				else
				{
					$searchClear.show();
				}
			}, 100);
		});
	});
	//END $(function
}(window, jQuery));