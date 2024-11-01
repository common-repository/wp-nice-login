$=jQuery;
$(document).ready(function(){

	function nl_post(ajaxurl,data,cb,ecb)
	{
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,    
			timeout: 20000,
			error: ecb,
			success: cb
			}
			);
	}

	function pos(e)
	{
		e.css('left',(($(window).width()-e.outerWidth())/2)+'px')

		if(e.outerHeight()>$(window).height())
		{
			e.css('top','20px')
			e.css('position','absolute')
		}
		else
		{
			e.css('position','fixed')
			e.css('top',(($(window).height()-e.outerHeight())/2)+'px')
		}

	}

	function showBox(e,c)
	{
		if(c==undefined)
			e.parent().css('left',(e.index()*e.outerWidth()*-1)+'px')
		e.parent().parent().css('height',e.outerHeight()+'px')
		pos($('#webocado_nl_forms'))
		e.parent().parent().data('active',e)
	}

	$(window).resize(function(){
		
		if($('#webocado_nl_forms').data('active')!=undefined)
		showBox($('#webocado_nl_forms').data('active'))
	})

	$('#webocado_nl_overlay,#webocado_nl_close').click(function(){
		$('#webocado_nl_overlay,#webocado_nl_forms').hide()
	})

	$('#webocado_nl_overlay,#webocado_nl_inv_close').click(function(){
		$('#webocado_nl_overlay,#webocado_nl_invalid_activation').hide()
	})

	$('#webocado_nl_or_register_btn').click(function(){
		showBox($("#webocado_nl_register_form"))
	})

	$('#webocado_nl_or_login_btn,#webocado_nl_or_loginr_btn').click(function(){
		showBox($("#webocado_nl_login_form"))
	})
	

	$('#webocado_nl_or_forgot_btn').click(function(){
		showBox($("#webocado_nl_forgot_form"))
	})

	window.showNiceLogin = function(redirect)
	{
		if(redirect==undefined)
			redirect = window.location.href 
		$('.webocado_nl_redirect').val(redirect)
		$('#webocado_nl_forms,#webocado_nl_overlay').show()
		showBox($("#webocado_nl_login_form"))
	}

	if($("#webocado_nl_invalid_activation").length>0)
	{
		$('#webocado_nl_invalid_activation,#webocado_nl_overlay').show()
		showBox($("#webocado_nl_invalid_activation .webocado_nl_cont"))
		pos($("#webocado_nl_invalid_activation"))
	}

	$('body').on('click','.webocado_nl_resend_activation_link',function(){

		$(".webocado_nl_resend_activation_link_response").html("")
		if(!$("#webocado_nl_invalid_activation").hasClass('webocado_nl_loading'))
			$("#webocado_nl_invalid_activation").addClass('webocado_nl_loading')

		var userid = $(this).attr('data-userid');
		if(!$(this).hasClass('webocado_nl_loading'))
			$(this).addClass('webocado_nl_loading')
		
		var data = {
			'action': 'webocado_nl_resend',
			'userid': userid
		};

		nl_post(ajaxurl, data, function(r) {

			$('#webocado_nl_invalid_activation').removeClass('webocado_nl_loading')
			var res = $.parseJSON(r);
			$('.webocado_nl_resend_activation_link_response').html(res['sent'])
			showBox($("#webocado_nl_invalid_activation"),true)

		}, function(jqXHR, textStatus, errorThrown) {

			$('#webocado_nl_invalid_activation').removeClass('webocado_nl_loading')
			$('.webocado_nl_resend_activation_link_response').html("Please try again.")
			showBox($("#webocado_nl_invalid_activation"),true)

		});

	})

	$('#webocado_nl_register_btn').click(function(){

		$("#webocado_nl_register_response").html("")
		if(!$("#webocado_nl_register_form").hasClass('webocado_nl_loading'))
			$("#webocado_nl_register_form").addClass('webocado_nl_loading')
		nl_post(ajaxurl, $('#webocado_nl_register_form').serialize(), function(r) {
			var res = $.parseJSON(r);
			$("#webocado_nl_register_form").removeClass('webocado_nl_loading')
			if(res.error!=false)
			{
				$.each(res.errors,function(i,v){
					$.each(v,function(i,v){
						$("#webocado_nl_register_response").append("<li>"+v[0]+"</li>")
					})
				})
				
			}
			else
			{
				$("#webocado_nl_register_form").html(res.data)
			}
			showBox($("#webocado_nl_register_form"),true)

		}, function(jqXHR, textStatus, errorThrown) {

			$("#webocado_nl_register_form").removeClass('webocado_nl_loading')
			$("#webocado_nl_register_response").html("Please try again.")
			showBox($("#webocado_nl_register_form"),true)

		});
	})

	$('#webocado_nl_login_btn').click(function(){

		$("#webocado_nl_login_response").html("")
		if(!$("#webocado_nl_login_form").hasClass('webocado_nl_loading'))
			$("#webocado_nl_login_form").addClass('webocado_nl_loading')
		nl_post(ajaxurl, $('#webocado_nl_login_form').serialize(), function(r) {
			var res = $.parseJSON(r);
			$("#webocado_nl_login_form").removeClass('webocado_nl_loading')
			if(res.error!=false)
			{
				$.each(res.errors,function(i,v){
					$.each(v,function(i,v){
						$("#webocado_nl_login_response").append("<li>"+v[0]+"</li>")
					})
				})
				
			}
			else
			{
				$("#webocado_nl_login_form").html(res.data)
				location.reload();
			}
			showBox($("#webocado_nl_login_form"),true)

		}, function(jqXHR, textStatus, errorThrown) {

			$("#webocado_nl_login_form").removeClass('webocado_nl_loading')
			$("#webocado_nl_login_response").html("Please try again.")
			showBox($("#webocado_nl_login_form"),true)

		});
	})

		$('#webocado_nl_forgot_btn').click(function(){

		$("#webocado_nl_forgot_response").html("")
		if(!$("#webocado_nl_forgot_form").hasClass('webocado_nl_loading'))
			$("#webocado_nl_forgot_form").addClass('webocado_nl_loading')
		nl_post(ajaxurl, $('#webocado_nl_forgot_form').serialize(), function(r) {
			var res = $.parseJSON(r);
			$("#webocado_nl_forgot_form").removeClass('webocado_nl_loading')
			if(res.error!=false)
			{
				$.each(res.errors,function(i,v){
					$.each(v,function(i,v){
						$("#webocado_nl_forgot_response").append("<li>"+v[0]+"</li>")
					})
				})
			}
			else
			{
				$("#webocado_nl_forgot_response").append(res['data'])
			}
			showBox($("#webocado_nl_forgot_form"),true)

		}, function(jqXHR, textStatus, errorThrown) {

			$("#webocado_nl_forgot_form").removeClass('webocado_nl_loading')
			$("#webocado_nl_forgot_response").html("Please try again.")
			showBox($("#webocado_nl_forgot_form"),true)

		});
	})




})
