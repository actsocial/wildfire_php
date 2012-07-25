jQuery(function(){
	jQuery("#oauthWindow").attr("src","");
	jQuery(".sns-box.new").bind("click",addAccount);
});
function addAccount(){
	jQuery("#oauthWindow").attr("src",'').hide();
	jQuery(".thumbnails").show();
	jQuery("#editSnsModal .modal-body").css("height", '')
	jQuery("#editSnsModal").removeClass("Weibo_iframe").modal('show');
}
function requestToken(name, writer_host, id){
	jQuery("#editSnsModal").modal('hide');
	jQuery("#authModal").modal({keyboard: false});
	jQuery("#authModal").modal('show');
	var domain = writer_host;
	var code = parseInt(Date.now().toString() + id); 
	var base_uri = "http://" + writer_host + "/sender/dispatcher?"
	window.open(base_uri + "source="+name+"&domain="+domain+"&code="+code+"&from=community", '_blank');
											  
	jQuery.ajax({
		type: "GET",
		url: "sns/ajaxsave",
		contentType: "application/json",
	  dataType: "json",
	  accept: "application/json",
	  data: {"code": code},
	  success: function(data){
	  	jQuery("#authModal .waiting").text("授权成功，窗口即将关闭");
	  	setTimeout("jQuery('#authModal').modal('hide');", 10000);
	  	location.reload();
		}
 	});							
}

jQuery("#close").bind('click', function(){
	location.reload();
});
