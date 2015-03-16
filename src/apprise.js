function apprise(string, args, callback)
	{
	var default_args =
		{
		'confirm'		:	false, 		// Ok and Cancel buttons
		'verify'		:	false,		// Yes and No buttons
		'input'			:	false, 		// Text input (can be true or string for default text)
		'textOk'		:	'Ok',		// Ok button default text
		'textCancel'	:	'Cancel',	// Cancel button default text
		'textYes'		:	'Yes',		// Yes button default text
		'textNo'		:	'No',		// No button default text
		'justText'		: 	false		//whether it is just showing text or not
		}
	
	if(args) {
		for(var index in default_args) {
			if(typeof args[index] == "undefined") args[index] = default_args[index];
		}
	}
	var aHeight = $(document).height();
	var aWidth = $(document).width();
	$('body').append('<div class="appriseOverlay" id="aOverlay"></div>');
	$('.appriseOverlay').css('height', aHeight).css('width', aWidth).fadeIn(100);
	$('body').append('<div class="appriseOuter"></div>');
	$('.appriseOuter').append('<div class="appriseInner"></div>');
	$('.appriseInner').append(string);
	if(args.justText) {
		$('.appriseInner').addClass('text');
	}
    $('.appriseOuter').css("left", ( $(window).width() - $('.appriseOuter').width() ) / 2+$(window).scrollLeft() + "px").css('top', '100px').fadeIn(200);
    if(args)
    	{
    	if(args['input'])
    		{
    		if(typeof(args['input'])=='string')
    			{
    			$('.appriseInner').append('<div class="aInput"><input type="text" class="aTextbox" t="aTextbox" value="'+args['input']+'" /></div>');
    			}
    		else
    			{
				$('.appriseInner').append('<div class="aInput"><input type="text" class="aTextbox" t="aTextbox" /></div>');
				}
			$('.aTextbox').focus();
    		}
    	}
    
    $('.appriseInner').after('<div class="aButtons"></div>');
    if(args)
    	{
		if(args['confirm'] || args['input'])
			{ 
			$('.aButtons').append('<div><button value="ok">'+args['textOk']+'</button></div>');
			$('.aButtons').append('<div><button value="cancel">'+args['textCancel']+'</button></div>'); 
			}
		else if(args['verify'])
			{
			$('.aButtons').append('<div><button value="ok">'+args['textYes']+'</button></div>');
			$('.aButtons').append('<div><button value="cancel">'+args['textNo']+'</button></div>');
			}
		else
			{ $('.aButtons').append('<div><button value="ok">'+args['textOk']+'</button></div>'); }
		}
    else
    	{ $('.aButtons').append('<div><button value="ok">Ok</button></div>'); }
	
	/*$(document).keydown(function(e) {
		if($('.appriseOverlay').is(':visible')) {
			if(e.keyCode == 13) {
				$('.aButtons button[value="ok"]').click();
			}
			if(e.keyCode == 27) {
				$('.aButtons button[value="cancel"]').click();
			}
		}
	});*/
	var aText = $('.aTextbox').val();
	if(!aText) {
		aText = false;
	}
	$('.aTextbox').keyup(function() {
		aText = $(this).val();
	});
    $('.aButtons button').click(function() {
		if(callback) {
			var wButton = $(this).attr("value");
			if(wButton=='ok') { 
				if(args) {
					if(args['input']) {
						callback(aText);
					}else{
						callback(true);
					}
				}else{
					callback(true);
				}
			}else if(wButton=='cancel') {
				callback(false);
			}
		}
    	$('.appriseOverlay').fadeOut(100,function() {
			$('.appriseOverlay').remove();
		});
		$('.appriseOuter').fadeOut(200,function() {
			$('.appriseOuter').remove();
		});
	});
}
function report_content(id) {
	apprise('<div align="center"><strong>Are you sure you want to report this content?</strong><br />This will be temporarily removed from the website until moderators have inspected it.</div>', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/report.php",
				data: {id: id},
				dataType: 'json'
			}).done(function(data) {
				if(data.success!==undefined && data.success) {
					$('#'+id).remove();
				}
			});
		}
	});
}
function remove_post(id) {
	apprise('<div align="center"><strong>Are you sure you want to remove this post?</strong><br />This cannot be undone.</div>', {'verify':true}, function(r) {
		if(r) {
			$.ajax({
				type: "POST",
				url: "/ajax/delete_post.php",
				data: {id: id},
				dataType: 'json'
			}).done(function(data) {
				if(data.success!==undefined && data.success) {
					$('#'+id).remove();
				}
			});
		}
	});
}