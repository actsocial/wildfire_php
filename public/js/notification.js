function loadNotification(container){
	var defaultContent;
	jQuery.ajax({
		  url: 'notification/ajaxpop',
		  success: function(data) {
		    //alert(data);
			shift(container,data);
			if (data==undefined){
				close(container,defaultContent);
			}
		  }
		});
}

function shift(container,data){
	container.html(data);
	//container.show();
	container.effect("pulsate",{"mode":"show","times":3,"duration":500},1000);
}

function react(id){
	alert(id);
	//ajax put , change the status of notification
	//load next notification
}

function close(){
	container.hide();
}