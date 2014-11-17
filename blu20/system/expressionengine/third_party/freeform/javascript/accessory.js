jQuery(function($){
	"use strict";

	//replaces the loading gif and info link with html
	$('a.freeform_acc:first').one('click', function(){
		var $input	= $('#freeform_acc').
						find('input[name="freeform_acc_ajax_link"]:first');

		var link	= $input.val();

		$.get(link, function(data){
			$input.parent().replaceWith(data);

			var $context = $('#freeform_acc_table');

			$context.delegate('.ff_acc_field_label', 'click', function(e){
				e.preventDefault();

				var $that	= $(this);
				var $ul		= $that.parent().find('ul:first');

				if ($ul.is(":visible"))
				{
					$that.find('.hide').hide();
					$that.find('.show').show();
				}
				else
				{
					$that.find('.hide').show();
					$that.find('.show').hide();
				}

				$ul.toggle('fast');

				return false;
			});
		});
	});
});