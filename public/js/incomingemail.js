function showContent(id){
	jQuery("#c"+id).toggleClass("hidden");
	if(jQuery('#unseen_'+id).attr('value')==1){
		jQuery.ajax({
			   type: "POST",
			   url: jQuery('#ajaxurl').attr('value'),
			   data: "action=seen&id="+id,
			   success: function(msg){
			        jQuery('#h'+id).css("font-weight","normal");
			        jQuery('#unseen_'+id).attr('value',0);
			        //alert(msg);
			   }
			 });
	}
}
function deleteEmail(id){
	jQuery.ajax({
		   type: "POST",
		   url: jQuery('#ajaxurl').attr('value'),
		   data: "action=delete&id="+id,
		   success: function(msg){
		        jQuery('#h'+id).addClass("hidden");
		        jQuery('#c'+id).addClass("hidden");
		        //alert(msg);
		   }
		 });
}

function markEmails(action){
	var checkedObj = jQuery('.eitem:checked');
	var strId = "";
	var num        = checkedObj.size();	
	if(num>0){
		switch(action){
		   case "seen"   : 
							jQuery('.eitem:checked').each(
									function(){
										strId += (strId=="")?jQuery(this).attr('value'):','+jQuery(this).attr('value');	
										jQuery('#h'+jQuery(this).attr('value')).css("font-weight","normal");
								        jQuery('#unseen_'+jQuery(this).attr('value')).attr('value',0);
										
									}							
							);
							jQuery.ajax({
								   type: "POST",
								   url: jQuery('#ajaxurl').attr('value'),
								   data: "action=seen&id="+strId
								 });
			   			   break;
		   case "unseen" :jQuery('.eitem:checked').each(
								function(){
									strId += (strId=="")?jQuery(this).attr('value'):','+jQuery(this).attr('value');	
									jQuery('#h'+jQuery(this).attr('value')).css("font-weight","bold");
							        jQuery('#unseen_'+jQuery(this).attr('value')).attr('value',1);
									
								}					
							);
							jQuery.ajax({
								   type: "POST",
								   url: jQuery('#ajaxurl').attr('value'),
								   data: "action=unseen&id="+strId
							}); 			   
			   			   break;
		   case "delete" :jQuery('.eitem:checked').each(
								function(){
									strId += (strId=="")?jQuery(this).attr('value'):','+jQuery(this).attr('value');	
									jQuery('#h'+jQuery(this).attr('value')).addClass("hidden");
							        jQuery('#c'+jQuery(this).attr('value')).addClass("hidden");
									
								}		
							);
							jQuery.ajax({
								   type: "POST",
								   url: jQuery('#ajaxurl').attr('value'),
								   data: "action=delete&id="+strId
							}); 
			   			   break;
		   default       : 
			   			   break;		
		}		
	}

}
jQuery(document).ready(function(){   
	jQuery('#selectall').click(
			function(){
				if(jQuery('#selectall').is(':checked')){
					jQuery('.eitem').attr('checked', true);
				}else{
					jQuery('.eitem').attr('checked', false);
				}					
			});
});
