function loadNotification(container){
	var defaultContent;
	jQuery.ajax({
		  url: '/notification/ajaxpop',
		  success: function(data) {
		  	if (data=="False"){
				//close(container);
			} else {
				shift(container,data);
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
	container = jQuery("#notification-box")
	//ajax put , change the status of notification
	jQuery.ajax({
	  url: 'notification/ajaxchange/nid/'+id,
	  success: function(msg) {
	  	if (msg=="Success"){
			close(container);
		}
	  }
	});
	//load next notification
	loadNotification(container);
}

function close(container){
	container.hide();
}