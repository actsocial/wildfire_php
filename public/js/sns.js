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

function action(name, writer_host, id){
	jQuery("#editSnsModal").modal('hide');
	jQuery("#authModal").modal({keyboard: false});
	jQuery("#authModal").modal('show');
	
	requestToken(name, writer_host, id);
}


function requestToken(name, writer_host, id){
	var domain = writer_host;
	var code = parseInt(Date.now().toString() + id); 
	var base_uri = "http://" + writer_host + "/sender/dispatcher?"
	window.open(base_uri + "source="+name+"&domain="+domain+"&code="+code+"&user_id="+id+"&from=community", '_blank');
	
	checkAndSave(code);
}

function checkAndSave(code){
	jQuery.ajax({
		type: "GET",
		url: "sns/ajaxcheckandsave",
		contentType: "application/json",
	  //dataType: "json",
	  //accept: "application/json",
	  data: {"code": code},
	  success: function(data){
	  	if(data['status'] === 1) {
	  		//jQuery("#authModal .waiting").text("授权成功，窗口即将关闭");
	  		//setTimeout("jQuery('#authModal').modal('hide');", 10000);
	  		location.reload();	
	  	} else {
	  		setTimeout(checkAndSave(code), 5000);
	  	}
		}
 	});							
}

jQuery("#close").bind('click', function(){
	location.reload();
});
