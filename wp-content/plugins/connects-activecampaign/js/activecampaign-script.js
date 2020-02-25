// JavaScript Document
jQuery(document).on("change keyup paste keydown","#activecampaign_api_key", function(e) {
	var val = jQuery(this).val();
	if( val !== "" )
		jQuery("#auth-activecampaign").removeAttr('disabled');
	else
		jQuery("#auth-activecampaign").attr('disabled','true');
});

jQuery(document).on( "click", "#auth-activecampaign", function(e){
	e.preventDefault();
	jQuery(".smile-absolute-loader").css('visibility','visible');
	var auth_token = jQuery("#activecampaign_api_key").val();
	var campaingURL = jQuery('#activecampaign_url').val();
	var action = 'update_activecampaign_authentication';
	var data = {action:action,campaingURL:campaingURL,authentication_token:auth_token};
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		dataType: 'JSON',
		success: function(result){
			if(result.status == "success" ){
				jQuery(".bsf-cnlist-mailer-help").hide();
				jQuery("#save-btn").removeAttr('disabled');
				jQuery("#activecampaign_url").closest('.bsf-cnlist-form-row').hide();
				jQuery("#activecampaign_api_key").closest('.bsf-cnlist-form-row').hide();
				jQuery("#auth-activecampaign").closest('.bsf-cnlist-form-row').hide();
				jQuery(".activecampaign-list").html(result.message);
			} else {
				jQuery(".activecampaign-list").html('<span class="bsf-mailer-error">'+result.message+'</span>');
			}
			jQuery(".smile-absolute-loader").css('visibility','hidden');
		}
	});
	e.preventDefault();
});

jQuery(document).on( "click", "#disconnect-activecampaign", function(){
															
	if(confirm("Are you sure? If you disconnect, your previous campaigns syncing with ActiveCampaign will be disconnected as well.")) {
		var action = 'disconnect_activecampaign';
		var data = {action:action};
		jQuery(".smile-absolute-loader").css('visibility','visible');
		jQuery.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			dataType: 'JSON',
			success: function(result){

				jQuery("#save-btn").attr('disabled','true');
				if(result.message == "disconnected" ){

					jQuery("#activecampaign_api_key").val('');
					jQuery('#activecampaign_url').val('');
					jQuery('.activecampaign-list').html('');
					jQuery("#disconnect-activecampaign").replaceWith('<button id="auth-activecampaign" class="button button-secondary auth-button" disabled="true">Authenticate Active Campaign</button><span class="spinner" style="float: none;"></span>');
					jQuery("#auth-activecampaign").attr('disabled','true');
				}

				jQuery('.bsf-cnlist-form-row').fadeIn('300');
				jQuery(".bsf-cnlist-mailer-help").show();
				jQuery(".smile-absolute-loader").css('visibility','hidden');
			}
		});
	}
	else {
		return false;
	}
});