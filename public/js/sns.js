jQuery(function(){
	jQuery("#oauthWindow").attr("src","");
	jQuery(".sns-box.new").bind("click",addAccount);
	jQuery(".sns-box").not(".new").bind("click",loadSns);
});
function addAccount(){
	jQuery("#oauthWindow").attr("src",'').hide();
	jQuery(".thumbnails").show();
	jQuery("#editSnsModal .modal-body").css("height", '')
	jQuery("#editSnsModal").removeClass("Weibo_iframe").modal('show');
	console.log("hello");
}
function requestToken(name, writer_host){
//	if(param['oauth_version']=='1'){
		jQuery(".thumbnails").hide();
		jQuery("#editSnsModal").addClass(name+"_iframe");
		var domain = writer_host;
		console.log(domain);
		var base_uri = "http://" + writer_host + "/sender/dispatcher?"
		console.log(base_uri);
		jQuery("#oauthWindow").attr("src", base_uri + "source="+name+"&domain="+domain).show().ready(function(){
			var height = jQuery(this).height();
			if(height>300){
				height = 300;
			}
			jQuery("#editSnsModal .modal-body").css("height", height);
		});
//	}
}
function closeModal(){
	jQuery("#editSnsModal").modal('hide');
	location.reload();
}
function loadSns(){
	location.href = "/sns/detail?sns_id="+jQuery(this).attr("sns-id");
}

