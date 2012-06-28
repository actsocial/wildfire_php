(function($){
window.Topic = Backbone.Model
		.extend({

			defaults : function() {
				return {
					title : "",
					date : null,
					site : "",
					lang : "zh-CN",
					body : "",
					img : null,
					nation : "",
					id : "",
					posts : null,
					postLoaded : false,
					postShowed : false,
					read : false
				};
			},

			// Toggle the `done` state of this todo item.
			loadposts : function() {
				if (this.posts != undefined) {
					return;
				} else {
					this.posts = new PostList;
				}

				var data = {
						topic : this.id
				};
				var posts = this.posts;
				var topic = this;
				$.ajax({
							url : 'tag/ajaxgetposts',
							dataType : 'json',
							data : data,
							success : function(data) {
								//TODO: translate
								var i = 0;
								data.forEach(function(row) {
											var d = row.value.date;
											var post = new Post(
													{
														body : row.value.body,
														id : encodeURIComponent(row.id),
														topicId : encodeURIComponent(row.key[0]),
														date : row.value.date,
														index : i,
														author : row.value.author,
														username : row.value.userName,
														profile_img_path : row.value.profile_img_path,
														display : row.value.display
													});
											i++;
											posts.add(post);
										});
								topic.set("postLoaded", true);
								topic.set("read", true);
							}
						});
			}

		});

window.TopicList = Backbone.Collection.extend({

	// Reference to this collection's model.
	model : Topic,

	done : function() {
	}

});

window.topics = new TopicList;

window.ContainerView = Backbone.View.extend({
	tagName : "div",
	
	className: "span11 topics isotope",

	// Cache the template function for a single item.
	template : _.template($('#container_template').html()),

	// The DOM events specific to an item.
	events : {
//		"click .title" : "toggle",
//		"click .add-url" : "addSourceUrl",
//		"click .weibo-reply-img" : "saveReply"
	},

	// The TodoView listens for changes to its model, re-rendering.
	initialize : function() {
//		this.model.bind("change:postLoaded", this.afterPostLoaded, this);
		//this.model.bind('change', this.render, this);
		//this.model.bind('destroy', this.remove, this);
	},

	// Re-render the contents of the todo item.
	render : function() {
		$(this.el).html(this.template());
		return this;
	},
	
	layout : function(){
		$(this.el).isotope({
			  itemSelector : '.topic',
			  filter: '*'
			});
	},
	
	relayout : function(){
		$(this.el).isotope("reLayout");
	}
});

window.TopicView = Backbone.View.extend({

	//... is a list tag.
	//tagName : "li",

	display : "none",

	// Cache the template function for a single item.
	template : _.template($('#topic_template').html()),

	// The DOM events specific to an item.
	events : {
		"click .title" : "toggle",
		"click .add-url" : "addSourceUrl",
		"click .weibo-reply-img" : "saveReply",
		"click .reply_window" : "open_reply_window"
	},

	// The TodoView listens for changes to its model, re-rendering.
	initialize : function() {
		this.model.bind("change:postLoaded", this.afterPostLoaded, this);
		this.model.bind("change:read", this.markAsRead, this);
		//this.model.bind('change', this.render, this);
		//this.model.bind('destroy', this.remove, this);
	},

	// Re-render the contents of the todo item.
	render : function() {
		$(this.el).html(this.template(this.model.toJSON()));
		return this;
	},

	toggle : function() {
		//this.model.displayPost = "loading";
		if(!this.model.get("postShowed")){
			this.model.set("postShowed",true);
			if (!this.model.get("postLoaded")) {
				$(".posts", this.$el).html("Loading...");
				this.model.loadposts();
			}
			$(".topicPRR", this.$el).show();
//			$('.topic',this.$el).removeClass("span4");
//			$('.topic',this.$el).addClass("span61");

			this.resizeTopicDiv(100);
		} else {
			this.model.set("postShowed",false);
			$(".topicPRR", this.$el).hide();
			this.resizeTopicDiv(100);
		}
		App.container.relayout();

	},

	afterPostLoaded : function() {
		var posts = this.model.posts;
		var view = new PostsView({
			model : {
				models : posts.models
			}
		});
		$(".posts", this.$el).html(view.render().el);
		App.container.relayout();

	},

	markAsRead : function(){
		if(this.model.get("read"))
			jQuery(this.el).find(".topic").addClass("read");
	},
	
	resizeTopicDiv : function(adjust) {
		var ll = 250;
		if (adjust) {
			ll += adjust;
		}
		$('.topics>div').each(function(i, e) {
			ll += $(e).height() + 3;
			if ($.browser.webkit) {
				ll += 4;
			}
		});
		$('.topics').css("height", ll);
	},
	
	addSourceUrl : function(e){
		jQuery(el).find(".reply-content").val(jQuery(el).find(".reply-content").val()+'\r\n http://'+this.model.id);
	},
	
	saveReply: function(e){
		jQuery.post('tag/ajaxsaveweiboreply',{topicId:this.model.id,content:jQuery(el).find(".reply-content").val()},function(data){
			if(data=='ok'){
				alert("回复成功");
			}else{
				alert("系统忙,请稍后再试");
			}
		});
	},
	
	open_reply_window: function(e){
		$(".modal").modal("show");
		$("#reply").text("懂了，去回复");
		$("#reply").attr("data", this.model.id);
		$("#reply").bind('click', function(){
			var uri;
			if(Sns.isSns(this.model.id)){
				var sns = new Sns(this.model.id);
				uri = sns.getSinglePage();
			}else{
				uri = "http://"+this.model.id;
			}
			window.setTimeout(
				function() {
					window.open('http://'+uri, '_blank');
					$("#reply").text("已完成回复");
				}, 
				4000
			);
			$('#reply').unbind('click');
		});
>>>>>>> 1b29fec345a070e67d7ab229a17499b165b5ce06
	},

});

})(jQuery);