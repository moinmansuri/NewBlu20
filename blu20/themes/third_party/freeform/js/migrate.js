$(function(){

	//--------------------------------------------  
	//	migrate live build
	//--------------------------------------------

	var $container	= $('#live_block');
	
	var $ajax_url	= $container.find('input[name="ajax_url"]').val();
				
	$container.find('.block_state').show();

	$container.find('.block_success').hide();
	
	$container.find('.block_in_progress').show();

	$container.find('.block_progressbar').show();

	$container.find('.block_progressbar').progressbar({value: 0});

	migrate_live_block( $container, $ajax_url );

	//--------------------------------------------  
	//	parse errors
	//--------------------------------------------
	
	function parse_errors(errors) {
		var string	= '';
		for (var i in errors) {
			if ( errors.hasOwnProperty(i)) {
				string += ($.isArray(errors[i])) ? errors[i].join(',') :
 errors[i];
			}
		}
		return string;
	}

	//--------------------------------------------  
	//	migrate
	//--------------------------------------------

	function migrate_live_block( $that, url ) {
	
		$.ajax({
			url : url + '&ajax=yes',
			data : null,
			success : function( data, status ){

				if( data.error ){
					// add our marker to say we're done
					// ..			
					$that.find('.block_in_progress').hide();
					
					$that.find('.block_progressbar').fadeOut(function(){

						$(this).progressbar({value: 0});

						$that.find('.block_progress_count_current').html('0');

						$that.find('.block_error').show();
						
						$that.find('#block_error_message').html( parse_errors( data.errors ) );
					});
					
					return false;
				}
				
				if( data.done ){
					// add our marker to say we're done
					// ..			
					$that.find('.block_in_progress').hide();
					
					$that.find('.block_progressbar').fadeOut(function(){

						$(this).progressbar({value: 0});

						$that.find('.block_progress_count_current').html('0');

						$that.find('.block_success').show();
					});
				}
				else {
					var new_url = url.replace(/batch=\d+/, 'batch='+data.batch);
					// mark up some progress indicator
					//alert(' new url : \n' + new_url );
					var obj = { current : data.batch, total : $that.find('.block_progress_count_total').html() };

					if( obj.current > obj.total ) obj.current = obj.total;

					$that.find('.block_progressbar').progressbar({value: obj.current/obj.total * 100 });

					$that.find('.block_progress_count_current').html(obj.current);

					migrate_live_block( $that, new_url );
				}
			},
			dataType : 'json'
		});

	}
});
