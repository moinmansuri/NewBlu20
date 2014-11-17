;(function(global, $){
	//es5 strict mode
	"use strict";

	var Freeform = global.Freeform = global.Freeform || {};

	// -------------------------------------
	//	short name
	// -------------------------------------

	Freeform.shortName = function(a)
	{
		var b = "",
			c = "_",
			f = "",
			d = {
				"223": "ss",
				"224": "a",
				"225": "a",
				"226": "a",
				"229": "a",
				"227": "ae",
				"230": "ae",
				"228": "ae",
				"231": "c",
				"232": "e",
				"233": "e",
				"234": "e",
				"235": "e",
				"236": "i",
				"237": "i",
				"238": "i",
				"239": "i",
				"241": "n",
				"242": "o",
				"243": "o",
				"244": "o",
				"245": "o",
				"246": "oe",
				"249": "u",
				"250": "u",
				"251": "u",
				"252": "ue",
				"255": "y",
				"257": "aa",
				"269": "ch",
				"275": "ee",
				"291": "gj",
				"299": "ii",
				"311": "kj",
				"316": "lj",
				"326": "nj",
				"353": "sh",
				"363": "uu",
				"382": "zh",
				"256": "aa",
				"268": "ch",
				"274": "ee",
				"290": "gj",
				"298": "ii",
				"310": "kj",
				"315": "lj",
				"325": "nj",
				"352": "sh",
				"362": "uu",
				"381": "zh"
			};

		if (b !== "")
		{
			if (a.substr(0, b.length) == b)
			{
				a = a.substr(b.length);
			}
		}

		a = a.toLowerCase();
		b = 0;

		for (var g = a.length; b < g; b++)
		{
			var e = a.charCodeAt(b);

			if (e >= 32 && e < 128)
			{
				f += a.charAt(b);
			}
			else if (d.hasOwnProperty(e))
			{
				f += d[e];
			}
		}

		d = new RegExp(c + "{2,}", "g");
		a = f;
		a = a.replace("/<(.*?)>/g", "");
		a = a.replace(/\s+/g, c);
		a = a.replace(/\//g, c);
		a = a.replace(/[^a-z0-9\-\_]/g, "");
		a = a.replace(/\+/g, c);
		a = a.replace(d, c);
		a = a.replace(/-$/g, "");
		a = a.replace(/_$/g, "");
		a = a.replace(/^_/g, "");
		a = a.replace(/^-/g, "");
		a = a.replace(/\.+$/g, "");
		return a;
	};
	//END Freeform.shortName


	//there might be a better way to do this, but this works
	Freeform.insertToTextarea = function (field, insert, ie)
	{
		ie = ie || false;

		//good browsers
		if ( ! ie &&
			typeof field.selectionEnd !== 'undefined' &&
			! isNaN(field.selectionEnd))
		{
			var selLength	= field.textLength;
			var selStart	= field.selectionStart;
			var selEnd		= field.selectionEnd;

			//if (selEnd <= 2 && typeof selLength !== 'undefined')
			//{
			//	selEnd = selLength;
			//}

			var s1			= (field.value).substring(0, selStart);
			var s2			= (field.value).substring(selStart, selEnd);
			var s3			= (field.value).substring(selEnd, selLength);
			var newStart	= selStart + insert.length;

			field.value = s1 + insert + s3;

			field.focus();
			field.selectionStart	= newStart;
			field.selectionEnd	= newStart;
		}
		//stupid IE
		else if (document.selection)
		{
			field.focus();
			document.selection.createRange().text = insert;
			field.blur();
			field.focus();
		}
		else
		{
			//all else fails
			field.value += insert;
		}
	};

	//because this should have been default years ago
	Freeform.tabTextarea = function (event, node)
	{
		var tabSpace = "    ";

		if (event.keyCode == 9)
		{
			if (typeof event.preventDefault !== 'undefined')
			{
				event.preventDefault();
			}

			var height = node.scrollTop;

			//don't allow this to move on
			event.returnValue = false;

			//good browsers
			if (node.setSelectionRange)
			{
				var d = node.selectionStart + 4;
				node.value = node.value.substring(0, node.selectionStart) +
							tabSpace +
							node.value.substring(node.selectionEnd, node.value.length);

				setTimeout(function(){
					node.focus();
					node.setSelectionRange(d, d);
				}, 0);
			}
			//crappy IE
			else
			{
				node.focus();
				document.selection.createRange().text = tabSpace;
				node.blur();
				node.focus();
			}

			node.scrollTop = height;
			node.focus();
		}
	};

	// -------------------------------------
	//	auto generate shortname form elements
	// -------------------------------------

	Freeform.autoGenerateShortname = function($label, $name, $autoGenerateCheckbox)
	{
		//check initial. If it gets clicked, set again.
		//bool is faster than attr check
		//this is intially off for edits.
		var autoGenerate = ($autoGenerateCheckbox.attr('checked') == 'checked');

		$autoGenerateCheckbox.change(function(){
			autoGenerate = ($autoGenerateCheckbox.attr('checked') == 'checked');

			//when they check, lets do the work so they don't have to type again
			if (autoGenerate)
			{
				$label.keyup();
			}
		});

		//generate on each keyup because... because.
		$label.keyup(function(){
			if (autoGenerate)
			{
				$name.val(Freeform.shortName($label.val()));
			}
		}).keyup();
	};
	//END Freeform.autoGenerateShortname

	// -------------------------------------
	//	form prep
	// -------------------------------------

	//preps items to be placed in a value element and not be
	//parsed as html
	Freeform.formPrep = function (str)
	{
		return  $.trim(
			str.replace(/"/g, '&quot;').
				replace(/'/g, '&#39;').
				replace(/</g, '&lt;').
				replace(/>/g, '&gt;')
		);
	};
	//end Freeform.formPrep


	// -------------------------------------
	//	autoDupeLastInput (private)
	// -------------------------------------

	//this checks any of the inputs on keyup, and if its the last
	//available one, it auto adds a new field below it and exposes the
	//delete button for the current one
	//this is mostly used for field_settings, but better to not load it
	//many times
	function autoDupeLastInput ($parentHolder, input_class)
	{
		var timer = 0;

		$parentHolder.find('.freeform_delete_button:last').hide();

		$parentHolder.delegate('.' + input_class + ' input', 'keyup',  function()
		{
			//this keyword not avail inside functions
			var that = this;

			clearTimeout(timer);

			timer = setTimeout(function(){
				var $that	= $(that),
					$parent	= $that.parent();

				//if the last item is not empty
				//and it is indeed the last item, lets dupe a new one
				if ($.trim($that.val()) !== '' &&
					$parent.is($('.' + input_class + ':last', $parentHolder)))
				{
					//clone BEEP BOOP BORP KILL ALL HUMANS
					var $newHolder = $parent.clone();

					//empties the inputs and
					//increments names like list_value_holder_input[10] to
					// list_value_holder_input[11]
					$newHolder.find('input').each(function(i, item){
						var $input = $(this);

						if ($input.is('[type="text"]'))
						{
							$input.val('');
						}
						else
						{
							//remove attr doesn't work for checked and selected
							//in IE
							if ($input.attr('selected'))
							{
								$input.attr('selected', false);
							}

							if ($input.attr('checked'))
							{
								$input.attr('checked', false);
							}
						}

						var match = /([a-zA-Z\_\-]+)\[([0-9]+)\]/ig.exec(
							$(this).attr('name')
						);

						if (match)
						{
							$(this).attr('name',
								match[1] + '[' +
									(parseInt(match[2], 10) + 1) +
								']'
							);
						}
					});

					//add to parent
					$parent.parent().append($newHolder);
					//show delete button for current
					$parent.find('.freeform_delete_button').show();
				}
			}, 250);
			//end setTimeout
		});
		//end delegate
	}
	//end autoDupeLastInput

	Freeform.autoDupeLastInput = autoDupeLastInput;

	// -------------------------------------
	//	carry over inputs (private)
	// -------------------------------------

	//	carries data from one type to the next
	//	on the field options for multi-line
	function carryOverInputs (oldType, newType, prefix)
	{
		//we cannot do anything with channel_field data
		if (newType == 'channel_field' ||
			oldType == 'channel_field')
		{
			return;
		}

		var data		= [];
			//these get called every hit because we need the dynamic
			//ones to recalc
		var $nld_ta		= $('textarea[name="' + prefix + 'list_nld_textarea_input"]');
		var $list		= $('input[name*="' + prefix + 'list_holder_input"]');
		var $lvList		= $('input[name*="' + prefix + 'list_value_holder_input"]');
		var $llList		= $('input[name*="' + prefix + 'list_label_holder_input"]');
		var $vlHolder	= $('#' + prefix + 'type_value_label_holder');
		var $listHolder	= $('#' + prefix + 'type_list_holder');
		var i;
		var l;


		// -------------------------------------
		//	get data
		// -------------------------------------

		if (oldType == 'nld_textarea')
		{
			//split on newline
			var temp_data = $nld_ta.val().split(/\n\r|\n|\r/ig);

			//remove blanks
			for (i = 0, l = temp_data.length; i < l; i++)
			{
				var trimmed = temp_data[i];

				if (trimmed !== '')
				{
					data.push(trimmed);
				}
			}
		}
		else if (oldType == 'value_label')
		{
			//remove blanks
			//and we are just getting the labels
			//because nothing else supports the value set

			$llList.each(function()
			{
				var trimmed = $(this).val();

				if (trimmed !== '')
				{
					data.push(trimmed);
				}

			});
		}
		else if (oldType == 'list')
		{
			//remove blanks
			$list.each(function()
			{
				var trimmed = $(this).val();

				if (trimmed !== '')
				{
					data.push(trimmed);
				}
			});
		}

		//no data? scram
		if (data.length === 0)
		{
			return;
		}

		// -------------------------------------
		//	set data
		// -------------------------------------

		var $inputs;
		var $clone;

		if (newType == 'nld_textarea')
		{
			$nld_ta.val(data.join('\n'));
		}
		else if (newType == 'value_label')
		{
			var vlHoldover = {};

			//get old labels and salvage values, or auto create
			//this way a user doesn't lose their value sets
			//if they edit/re-order in another type
			$llList.each(function(i){
				var that_label = Freeform.formPrep($llList.eq(i).val());
				var that_value = Freeform.formPrep($lvList.eq(i).val());

				if (that_label !== '' && that_value !== '' )
				{
					vlHoldover[that_label]	= that_value;
				}
			});

			//remove oldies and get a clone
			$inputs	= $('.value_label_holder_input', $vlHolder);
			$clone	= $inputs.eq(0).clone();

			//cleaaaan
			$inputs.remove();

			//this will make a blank one for us
			data.push('');

			for (i = 0, l = data.length; i < l; i++)
			{
				var shortname = Freeform.shortName(data[i]);

				if (shortname === '')
				{
					shortname = data[i];
				}

				//need to be implicit here instead of input:first/last
				//because third party devs might want to inject items
				var $item	= $clone.clone();
				var $value	= $item.find('input[name*="' + prefix + 'list_value_holder_input"]');
				var $label	= $item.find('input[name*="' + prefix + 'list_label_holder_input"]');

				$value.attr('name', prefix + 'list_value_holder_input[' + i + ']').
					val(
						(typeof vlHoldover[data[i]] !== 'undefined') ?
							vlHoldover[data[i]] :
							shortname
					);
				$label.attr('name', prefix + 'list_label_holder_input[' + i + ']').val(data[i]);

				$vlHolder.append($item);
			}

			//shows all deletes, then hids the last for the blank row
			$vlHolder.find('.freeform_delete_button').show().last().hide();
		}
		else if (newType == 'list')
		{
			//remove oldies and get a clone
			$inputs	= $('.list_holder_input', $listHolder);
			$clone	= $inputs.eq(0).clone();

			//cleaaaan
			$inputs.remove();

			//this will make a blank one for us
			data.push('');

			for (i = 0, l = data.length; i < l; i++)
			{
				$listHolder.append(
					$clone.clone().
						find('input[name*="' + prefix + 'list_holder_input"]').
							val(data[i]).
						end()
				);
			}

			//shows all deletes, then hids the last for the blank row
			$listHolder.find('.freeform_delete_button').show().last().hide();
		}
	}
	//END carryOverInputs


	// -------------------------------------
	//	set up delegation for multi row set
	// -------------------------------------

	Freeform.setupMultiRowDelegate = function (currentChoice, prefix)
	{
		currentChoice			= currentChoice || 'list';
		prefix					= prefix || '';

		$(function(){
			var $listType		= $('#' + prefix + 'list_type');
			var $listTypes		= $('#' + prefix + 'list_types button');
			var $listHolders	= $('#' + prefix + 'option_holder > div');
			var $typeListHolder	= $('#' + prefix + 'type_list_holder');
			var $typeKvlHolder	= $('#' + prefix + 'type_value_label_holder');
			var $typeNLDTholder	= $('#' + prefix + 'type_nld_textarea_holder');

			// -------------------------------------
			//	list holder auto new
			// -------------------------------------

			autoDupeLastInput($typeListHolder, 'list_holder_input');
			autoDupeLastInput($typeKvlHolder, 'value_label_holder_input');

			// -------------------------------------
			//	delete buttons
			// -------------------------------------

			$listHolders.delegate('.freeform_delete_button', 'click', function(){
				$(this).parent().remove();
			});

			// -------------------------------------
			//	type chooser
			// -------------------------------------

			$listHolders.hide();

			//shows the list holder for the chosen item
			$listTypes.each(function(){
				var $that	= $(this);
				var type	= $that.attr('data-value');

				//this will only run onload
				//and will show the correct current input
				if (type == currentChoice)
				{
					$that.addClass('active');
					$('#type_' + type + "_holder").show();
				}

				$that.click(function(e){

					//swap active
					$listTypes.removeClass('active');
					$that.addClass('active');

					//swap display
					$listHolders.hide();
					$('#type_' + type + "_holder").show();

					carryOverInputs(currentChoice, type, prefix);

					//we don't to change on channel_field
					//because it has its own list data and we are just
					//using this to help the carryOverInputs function
					if (type != 'channel_field')
					{
						currentChoice = type;
					}

					$listType.val(type);

					e.preventDefault();
					return false;
				});

			});
		});
	};
	//END Freeform.setupMultiRowDelegate


	// -------------------------------------
	//	jQuery.fn.sortElements
	// -------------------------------------

	//	https://github.com/jamespadolsey/jQuery-Plugins/tree/master/sortElements

	//added extra layer in case this is defined
	$.fn.sortElements = $.fn.sortElements || (function()
	{
		var sort = [].sort;

		return function(comparator, getSortable)
		{
			getSortable = getSortable || function(){return this;};

			var placements = this.map(function(){

				var sortElement = getSortable.call(this),
					parentNode = sortElement.parentNode,

					// Since the element itself will change position, we have
					// to have some way of storing its original position in
					// the DOM. The easiest way is to have a 'flag' node:
					nextSibling = parentNode.insertBefore(
						document.createTextNode(''),
						sortElement.nextSibling
					);

				return function()
				{
					if (parentNode === this)
					{
						/*throw new Error(
							"You can't sort elements if any one is a descendant of another."
						);*/

						return;
					}

					// Insert before flag:
					parentNode.insertBefore(this, nextSibling);
					// Remove flag:
					parentNode.removeChild(nextSibling);
				};

			});

			return sort.call(this, comparator).each(function(i){
				placements[i].call(getSortable.call(this));
			});
		};
	}());
	//end sortElements

	// -------------------------------------
	//	jQuery outerHTML
	// -------------------------------------

	$.fn.outerHTML = $.fn.outerHTML || (function(){
		return function() {
			var $that	= $(this);

			if ($that[0] && typeof $that[0].outerHTML !== 'undefined')
			{
				return $that[0].outerHTML;
			}
			else
			{
				var content = $that.wrap('<div></div>').parent().html();

				$that.unwrap();

				return content;
			}
		};
	}());
	//end $.fn.outerHTML

	// -------------------------------------
	//	jQuery remove tags
	//	jQuery("#container").find(
	//		":not(b, strong, i, em, u, br, pre, blockquote, ul, ol, li, a)"
	//	).removeTags();
	// -------------------------------------

	$.fn.removeTags = $.fn.removeTags || function()
	{
		this.each(function()
		{
			if(jQuery(this).children().length === 0)
			{
				jQuery(this).replaceWith(jQuery(this).text());
			}
			else
			{
				jQuery(this).children().unwrap();
			}
		});

		return this;
	};

	// -------------------------------------
	//	base64 replacement for IE because stupid IE
	// -------------------------------------

	// Copyright (c) 2010 Nick Galbreath
	// http://code.google.com/p/stringencoders/source/browse/#svn/trunk/javascript
	var base64 = {};
	base64.PADCHAR = '=';
	base64.ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

	base64.makeDOMException = function()
	{
		// sadly in FF,Safari,Chrome you can't make a DOMException
		var e, tmp;

		/*try
		{
			return new DOMException(DOMException.INVALID_CHARACTER_ERR);
		}
		catch (tmp)
		{*/
			// not available, just passback a duck-typed equiv
			// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Global_Objects/Error
			// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Global_Objects/Error/prototype
			var ex = new Error("DOM Exception 5");

			// ex.number and ex.description is IE-specific.
			ex.code = ex.number = 5;
			ex.name = ex.description = "INVALID_CHARACTER_ERR";

			// Safari/Chrome output format
			ex.toString = function()
			{
				return 'Error: ' + ex.name + ': ' + ex.message;
			};

			return ex;
		//}
	};
	//END base64.makeDOMException

	base64.getbyte64 = function(s,i)
	{
		//	This is oddly fast, except on Chrome/V8.
		//	Minimal or no improvement in performance by using a
		//	object with properties mapping chars to value (eg. 'A': 0)
		var idx = base64.ALPHA.indexOf(s.charAt(i));

		if (idx === -1)
		{
			throw base64.makeDOMException();
		}
		return idx;
	};
	//END base64.getbyte64


	// --------------------------------------------------------------------

	/**
	 * Base64 Get byte
	 *
	 * @access	private
	 * @param	{String}	s	string to search
	 * @param	{Int}		i	character index to get code from
	 * @return	{Int}			Character code
	 */

	base64.getbyte = function(s,i)
	{
		var x = s.charCodeAt(i);

		if (x > 255)
		{
			throw base64.makeDOMException();
		}

		return x;
	};
	//END base64.getbyte


	// --------------------------------------------------------------------

	/**
	 * Decodes a string of data which has been encoded using base-64 encoding.
	 *
	 * @access	private
	 * @param	{String}	s	base64 string
	 * @return	{String}		base64 decoded ASCII string
	 */

	base64.decode = function(s)
	{
		// convert to string
		s = '' + s;
		var getbyte64 = base64.getbyte64;
		var pads, i, b10;
		var imax = s.length;

		if (imax === 0)
		{
			return s;
		}

		if (imax % 4 !== 0)
		{
			throw base64.makeDOMException();
		}

		pads = 0;

		if (s.charAt(imax - 1) === base64.PADCHAR)
		{
			pads = 1;

			if (s.charAt(imax - 2) === base64.PADCHAR)
			{
				pads = 2;
			}

			// either way, we want to ignore this last block
			imax -= 4;
		}

		var x = [];

		for (i = 0; i < imax; i += 4)
		{
			b10 = (getbyte64(s,i) << 18) | (getbyte64(s,i+1) << 12) |
				(getbyte64(s,i+2) << 6) | getbyte64(s,i+3);
			x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 0xff, b10 & 0xff));
		}

		switch (pads)
		{
			case 1:
				b10 = (getbyte64(s,i) << 18) | (getbyte64(s,i+1) << 12) | (getbyte64(s,i+2) << 6);
				x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 0xff));
				break;
			case 2:
				b10 = (getbyte64(s,i) << 18) | (getbyte64(s,i+1) << 12);
				x.push(String.fromCharCode(b10 >> 16));
				break;
		}

		return x.join('');
	};
	//END base64.decode


	// --------------------------------------------------------------------

	/**
	 * Creates a base-64 encoded ASCII string from a string of binary data.
	 *
	 * @access	private
	 * @param	{String}	s	ASCII string to be encoded to base64
	 * @return	{String}		base64 encoded string
	 */

	base64.encode = function(s)
	{
		if (arguments.length !== 1)
		{
			throw new SyntaxError("Not enough arguments");
		}

		var padchar	= base64.PADCHAR;
		var alpha	= base64.ALPHA;
		var getbyte	= base64.getbyte;

		var i, b10;
		var x = [];

		// convert to string
		s = '' + s;

		var imax = s.length - s.length % 3;

		if (s.length === 0)
		{
			return s;
		}

		for (i = 0; i < imax; i += 3)
		{
			b10 = (getbyte(s,i) << 16) | (getbyte(s,i+1) << 8) | getbyte(s,i+2);
			x.push(alpha.charAt(b10 >> 18));
			x.push(alpha.charAt((b10 >> 12) & 0x3F));
			x.push(alpha.charAt((b10 >> 6) & 0x3f));
			x.push(alpha.charAt(b10 & 0x3f));
		}

		switch (s.length - imax)
		{
			case 1:
				b10 = getbyte(s,i) << 16;

				x.push(
					alpha.charAt(b10 >> 18) +
					alpha.charAt((b10 >> 12) & 0x3F) +
					padchar + padchar
				);

				break;
			case 2:
				b10 = (getbyte(s,i) << 16) | (getbyte(s,i+1) << 8);
				x.push(alpha.charAt(b10 >> 18) + alpha.charAt((b10 >> 12) & 0x3F) +
						alpha.charAt((b10 >> 6) & 0x3f) + padchar);
				break;
		}
		return x.join('');
	};
	//END base64.encode


	// --------------------------------------------------------------------

	/**
	 * Creates a base-64 encoded ASCII string from a string of binary data.
	 * A backup is needed for IE >:|
	 *
	 * @access	public
	 * @param	{String}	s	ASCII string to be encoded to base64
	 * @return	{String}		base64 encoded string
	 */

	Freeform.btoa = function(s)
	{
		//unescape(encodeURIComponent( is fixing unicode errors here
		//decodeURI does not work here because it doesn't do UTF-8 correctly
		//(WAT?)
		if (window.btoa)
		{
			return window.btoa(unescape(encodeURIComponent(s)));
		}
		else
		{
			return base64.encode(unescape(encodeURIComponent(s)));
		}
	};
	//END Freeform.btoa


	// --------------------------------------------------------------------

	/**
	 * Decodes a string of data which has been encoded using base-64 encoding.
	 * A backup is needed for IE >:|
	 *
	 * @access	public
	 * @param	{String}	s	base64 string
	 * @return	{String}		base64 decoded ASCII string
	 */

	Freeform.atob = function(s)
	{
		//decodeURIComponent(escape( is fixing unicode here
		//encodeURI does not work here because it doesn't do UTF-8 correctly
		//(WAT?)
		if (window.atob)
		{
			return decodeURIComponent(escape(window.atob(s)));
		}
		else
		{
			return decodeURIComponent(escape(base64.decode(s)));
		}
	};
	//END Freeform.atob


	// --------------------------------------------------------------------

	/**
	 * Fires a jQuery UI dialog immediatly or delays it for onclick
	 *
	 * @access	public
	 * @param	{Array}	options	options array
	 * @return	{Mixed}			returns a function or fires immediatly
	 */

	Freeform.jQUIDialog = function(options)
	{
		options	= $.extend({
				'message'			: 'No Message Defined',
				'ok'				: 'OK',
				'title'				: 'Alert',
				'preventDefault'	: true,
				'immediate'			: false,
				'modal'				: true
		}, options);

		var buttonsOptions	= {};
		var dialogInstalled	= (typeof $.fn.dialog !== 'undefined');
		var $dialog			= $('<div></div>').html(options.message);

		// -------------------------------------
		//	denied dialog
		// -------------------------------------

		if (dialogInstalled)
		{
			//cancel button?
			if (typeof options.cancel !== 'undefined')
			{
				buttonsOptions[options.cancel] = {
					'click'	: function()
					{
						if (typeof options.cancelClick !== 'undefined')
						{
							options.cancelClick();
						}

						$(this).dialog("close");
					},
					'class'	: 'submit',
					'text'	: options.cancel
				};
			}

			//we need at least an ok button
			buttonsOptions[options.ok] = {
				'click'	: function()
				{
					if (typeof options.close !== 'undefined')
					{
						options.close();
					}

					if (typeof options.okClick !== 'undefined')
					{
						options.okClick();
					}

					$(this).dialog("close");
				},
				'class'	: 'submit',
				'text'	: options.ok
			};

			$dialog.dialog({
				"autoOpen"		: false,
				"title"			: options.title,
				"buttons"		: buttonsOptions,
				"dialogClass"	: 'ss_jqui_dialog',
				"zIndex"		: 2000,
				"modal"			: options.modal,
				"close"			: function(){
					$('.ui-dialog.ss_jqui_dialog').remove();
				}
			});
		}

		// -------------------------------------
		//	dialog fire!
		// -------------------------------------

		var returnFunction = function (e)
		{
			if (e && options.preventDefault)
			{
				e.preventDefault();
			}

			if (dialogInstalled)
			{
				$dialog.dialog('open');
			}
			else
			{
				alert(options.message);
			}

			// prevent the default action, e.g., following a link
			if (e && options.preventDefault)
			{
				return false;
			}
		};

		// -------------------------------------
		//	fire right now?
		// -------------------------------------

		if (options.immediate)
		{
			returnFunction();
		}
		else
		{
			return returnFunction;
		}
	};
	//END Freeform.prepJQUIDialog


	// --------------------------------------------------------------------

	/**
	 * Setup process for chosen select dropdowns
	 *
	 * @access	public
	 * @param	{Object}	$context dom element for context
	 */

	Freeform.setUpChosenSelect = function($context)
	{
		$context = $context || $('body');

		if (typeof $.fn.chosen !== 'undefined')
		{
			$(".chzn_select", $context).chosen();
			$(".chzn_select_no_search", $context).each(function(){
				var $that	= $(this);

				$that.chosen();
				$('#' + $that.attr('id') + '_chzn .chzn-search').hide();
			});
		}
	};
	//END Freeform.setUpChosenSelect


	// --------------------------------------------------------------------

	/**
	 * Allows a text field to filter down elements via visibility
	 *
	 * @access	public
	 * @param	{String} searchFieldQuery	css query to find input
	 * @param	{String} resultElementQuery	css query to find filtered elements
	 * @param	{String} onQuery			inner element of elements to filter on
	 * @param	{String} onAttr				body to search. html/text/attr()
	 * @return	{Ojbect}					jquery object of search element
	 */

	Freeform.elementSearch	= function (searchFieldQuery, resultElementQuery, onQuery, onAttr)
	{
		var $searchField		= $(searchFieldQuery);
		var $resultElements		= $(resultElementQuery);

		$searchField.bind('keyup', function(e){
			var $that = $(this);

			//hide all unless its empty or a placeholder
			//replacement helper
			if ($that.val() === '' ||
				($that.attr('placeholder') &&
				$that.val() == $that.attr('placeholder'))
			)
			{
				$resultElements.show();
			}
			else
			{
				$resultElements.hide();
			}

			//build regex early
			var search = new RegExp($that.val().toLowerCase());

			//filter results to matches and show
			$resultElements.filter(function(index) {
				var $this		= $(this),
					$searchOn	= $this,
					find;

				//are we looking on a sub element?
				if (onQuery)
				{
					$searchOn = $searchOn.find(onQuery);
				}

				//what are we searching, html(), text(), attr()?
				if (onAttr != 'html' && onAttr != 'text' && $searchOn.attr(onAttr))
				{
					find = $searchOn.attr(onAttr);
				}
				else if (onAttr == 'text')
				{
					find = $searchOn.text();
				}
				else
				{
					find = $searchOn.html();
				}

				//if this matches our regex build, show
				return search.exec(find.toLowerCase());
			}).show();
		});

		//prevent enter from submitting a form
		$searchField.bind('keydown', function(e){
			if (e.keyCode == 13)
			{
				return false;
			}
		});

		return $searchField;
	};
	//END elementSearch


	// --------------------------------------------------------------------

	/**
	 * String Repeat
	 *
	 * @access	public
	 * @param	{String}	str	string to repeat
	 * @param	{Int}		num	how many times to repeat it
	 * @return	{String}		string repeated
	 */

	Freeform.strRepeat = function (str, num)
	{
		var ret = '';

		while (num--)
		{
			ret += str;
		}

		return ret;
	};
	//END strRepeat


	// --------------------------------------------------------------------

	/**
	 * Fraction To Float
	 *
	 * returns the fraction as a percent float to 2 decimal places
	 *
	 * @access	public
	 * @param	{Int}	numerator
	 * @param	{Int}	denominator
	 * @parram	{Int}	base number to divide by (100 is default)
	 * @return	{Float}
	 */

	Freeform.fractionToFloat = function (numerator, denominator, base)
	{
		base = base || 100;

		return (Math.round(100 * ((numerator/denominator) * base))/100);
	};
	//END fractionToFloat


	// --------------------------------------------------------------------

	/**
	 * Get Url Arguments
	 *
	 * @access	public
	 * @param	{String}	url	url to parse. Window.location.href default
	 * @return	{Array}			array of arguments
	 */

	Freeform.getUrlArgs = function (url)
	{
		url			= url || window.location.href;
		var urlVars	= {};
		var parts	= url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value)
		{
			urlVars[key] = value;
		});

		return urlVars;
	};
	//END getUrlArgs


	// --------------------------------------------------------------------

	var cycleSwtiches = [];

	/**
	 * Cycle over an array of items or a set of args
	 *
	 * @access	public
	 * @param	{Mixed}	items	items to cycle over
	 * @return	{Mixed}			returned cycle item
	 */

	Freeform.cycle = function(items)
	{
		if ( ! $.isArray(items))
		{
			items = Array.prototype.slice.call(arguments);
		}

		var hash = items.join('|');

		if ( typeof cycleSwtiches[hash] === 'undefined' ||
			typeof items[cycleSwtiches[hash] + 1] === 'undefined')
		{
			cycleSwtiches[hash] = 0;
		}
		else
		{
			cycleSwtiches[hash]++;
		}

		return items[cycleSwtiches[hash]];
	};


	/**
	 * clear the cycle functions cache
	 *
	 * @access	public
	 * @return	{object}		this for chaining
	 */

	Freeform.clearCycle = function()
	{
		cycleSwtiches = [];
		return this;
	};


	// --------------------------------------------------------------------

	/**
	 * Show Field Validation Errors for Field Edit/Create
	 *
	 * @access	public
	 * @param	{Object}	errors		key/value list of errors
	 * @param	{Object}	$context	instance of $('#field_edit_form') to forgo repeat jQ work
	 * @param	{Boolean}	autoScroll	autoscroll to first element. Requires jQuery.smoothScroll
	 * @return	{Null}
	 */

	Freeform.showValidationErrors = function(errors, $context, autoScroll)
	{
		autoScroll = autoScroll || false;

		var currentTopOffset	= -1;
		var $scrollToElement	= false;

		$.each(errors, function (i, item){
			var $element, $wrapper;

			$wrapper = $('[name="' + i +'"]', $context).closest('tr');

			//if its not a field, try ID
			if ($wrapper.length === 0)
			{
				$wrapper = $('#' + i, $context);
			}

			//if we still don't have it, bail
			if ($wrapper.length === 0)
			{
				return;
			}

			//get the validation wrapper
			$element = $wrapper.find('.validation_error_holder .validation_error');

			//no wrapper, bail
			if ($element.length === 0)
			{
				return;
			}

			//this has to be done before we calc offset
			$element.parent().show();

			//find highest element to scroll to
			if (autoScroll)
			{
				var offset = $element.offset();
				if (currentTopOffset == -1 || offset.top <= currentTopOffset)
				{
					currentTopOffset	= offset.top;
					$scrollToElement	= $element;
				}
			}

			//if this is an array, just join line breaks
			var html = ($.isArray(item)) ?
							item.join('<br/>') :
							item;

			$element.html(html);
		});

		if ($scrollToElement)
		{
			if (typeof $.fn.smoothScroll !== 'undefined')
			{
				$.smoothScroll({
					scrollTarget : $scrollToElement
				});
			}
		}
	};
	//END showFieldValidationErrors


	// -------------------------------------
	//	stuff to happen on document ready
	// -------------------------------------

	$(function(){

		// -------------------------------------
		//	close notices
		// -------------------------------------

		$('body').delegate('.freeform_notice .notice_close', 'click', function(e){
			e.preventDefault();
			$(this).parent().slideUp();
			return false;
		});

		// -------------------------------------
		//	support test
		// -------------------------------------

		$.support.placeholder = ('placeholder' in document.createElement('input'));

		// -------------------------------------
		//	html5 placeholder fixer
		// -------------------------------------

		if ( ! $.support.placeholder)
		{
			$('[placeholder]').focus(function() {
				var input = $(this);
				if (input.val() == input.attr('placeholder'))
				{
					input.val('');
					input.removeClass('placeholder');
				}
			}).blur(function() {
				var input = $(this);
				if (input.val() === '' ||
					input.val() == input.attr('placeholder'))
				{
					input.addClass('placeholder');
					input.val(input.attr('placeholder'));
				}
			}).blur();

			//clear on form submit
			$('[placeholder]').parents('form').bind('submit', function() {
				$(this).find('[placeholder]').each(function() {
					var input = $(this);
					if (input.val() == input.attr('placeholder'))
					{
						input.val('');
					}
				});
			});
		}

		Freeform.setUpChosenSelect();

		// --------------------------------------------------------------------

		// -------------------------------------
		//	so border-box support doens't work
		//	with min-height/width attributes on
		//	firefox <= 12 *SSSSGIIIHHH*
		//	https://bugzilla.mozilla.org/show_bug.cgi?id=308801
		//	so we need to detect with this in
		//	case they fix it or some other poop-
		//	faced browser borks this
		// -------------------------------------

		$.support.minHWBorderBox = (function(){
			var $test = $('<div id="#border-box-test">border box test</div>').
				css({
					'min-height'		: '50px',
					'padding'			: '10px',
					'-webkit-box-sizing': 'content-box',
					'-moz-box-sizing'	: 'content-box',
					'box-sizing'		: 'content-box'
				}).
				appendTo('body');

			var noBorder	= $test.outerHeight();

			$test.css({
				'-webkit-box-sizing': 'border-box',
				'-moz-box-sizing'	: 'border-box',
				'box-sizing'		: 'border-box'
			});

			var border		= $test.outerHeight();

			$test.remove();

			//the height should change if min-height is treated correctly
			return (border != noBorder);
		}());

		if ( ! $.support.minHWBorderBox )
		{
			$('body').addClass('broken_min_border_box');
		}

		var $fancyWrapper		= $('#fancybox-content');
		var $solspaceWrapper	= $('#mainContent .solspace_ui_wrapper:first');

		if ($fancyWrapper.length > 0)
		{
			if ($solspaceWrapper.hasClass('ie8'))
			{
				$fancyWrapper.addClass('ie8');
			}
			if ($solspaceWrapper.hasClass('ie9'))
			{
				$fancyWrapper.addClass('ie9');
			}
		}

		//$('.solspace_ui_wrapper .pagination a').addClass('freeform_ui_button freeform_ui_element');
		//$('.solspace_ui_wrapper .pagination strong').css('padding', '0 7px');
	});

}(window, jQuery));