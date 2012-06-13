window.Tag = Backbone.Model
		.extend({

			defaults : function() {
				return {
					name : "",
					topic_num : 0,
					page : null,
					topicloading : false,
					selected:false,
					topics : []
				};
			},

			// Toggle the `done` state of this todo item.
			loadTopics : function() {
				if (this.get("topicloading") == false) {
					this.set({"topicloading":true});
					var self = this;
					jQuery.ajax({
						type : "GET",
						url : "tag/ajaxtopics",
						data : key = "key=" + this.get("name")
								+ "&totalCount="
								+ this.get("topic_num") + "&page="
								+ this.get("page"),
						dataType : 'json',
						success : function(data) {
							var topicsArray = data['topics'];
							_.each(topicsArray,function(t){
								var topic = new Topic({
					                id:t['id'],
					                title : t['value']['title'],
									date : t['value']['date'],
									lang : "zh-CN",
									body : t['value']['body'],
									comment_count : t['value']['comments'],
									view_count : t['value']['views']
					              });
					        	var view = new TopicView({model: topic});
					        	jQuery(".topics .loadingtopic").before(view.render().el);
							});
							self.set({"topicloading":false});
						}
					});
				}
			}

		});

window.TagList = Backbone.Collection.extend({

	// Reference to this collection's model.
	model : Tag,

	done : function() {
	}

});

window.tags = new TagList;

window.TagView = Backbone.View.extend({

	//... is a list tag.    	
	tagName : "li",

	//display: "none",

	// Cache the template function for a single item.
	template : _.template(jQuery('#tag_template').html()),

	// The DOM events specific to an item.
	events : {
		"click a" : "selectTag"
	},

	// The TodoView listens for changes to its model, re-rendering.
	initialize : function() {
		this.model.bind('change:page',this.loadPage,this);
		this.model.bind('change:topicloading',this.loadingStatus,this);
	},

	// Re-render the contents of the todo item.
	render : function() {
		jQuery(this.el).html(this.template(this.model.toJSON()));
		return this;
	},

	selectTag : function(e) {
		jQuery(".topics").html(jQuery(".loadingtopic"));
		jQuery(window).unbind('scroll');
		this.model.set({'name':jQuery(e.currentTarget).text(),'page':0,'selected':true,'topic_num':jQuery(e.currentTarget).attr('rel')});
		jQuery(window).bind('scroll', _.bind(this.turnPage, this));
	},
	
	turnPage : function(e){
		if(this.model.get('name')!=undefined){
			var top = document.documentElement.scrollTop + document.body.scrollTop;
			if (jQuery(".topics").height() - top < jQuery(window).height()) {
				if(!this.model.get('topicloading')){
					this.model.set({'page':this.model.get('page')+1});
				}
			}
		}
		
	},
	loadPage : function(e){
		this.model.loadTopics();
	},
	loadingStatus : function(e){
		if(this.model.get("topicloading")){
			jQuery(".loadingtopic").show();
		}else{
			jQuery(".loadingtopic").hide();
		}
	}

});
