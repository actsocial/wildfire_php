window.Tag = Backbone.Model
		.extend({

			defaults : function() {
				return {
					name : "",
					topic_num : 0,
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
					//var typeArray = ["success","warning","error"];
					var topics = self.get("topics").slice(0);
					var start_key;
					var startkey_docid;
					if(topics.length>0){
						var lastTopic = topics[topics.length-1]
						start_key = lastTopic.get('key');
						startkey_docid = lastTopic.get('id');
					}
					jQuery.ajax({
						type : "GET",
						url : "tag/ajaxtopics",
						data : {
								key:this.get("name"),
								totalCount:this.get("topic_num"),
								start_key:start_key,
								startkey_docid:startkey_docid
						},
						dataType : 'json',
						success : function(data) {
							var topicsArray = data['topics'];
							
							_.each(topicsArray,function(t){
								var topic = new Topic({
					                id:t['id'],
					                read:t['value']['read'],
					                title : t['value']['title'],
									date : t['value']['date'],
									lang : "zh-CN",
									key : t['key'],
									body : t['value']['body'],
									comment_count : t['value']['comments'],
									view_count : t['value']['views'],
									//type: typeArray[~~(Math.random()*10/3)],
									author: t['value']['author'],
									site: t['value']['site']
					              });
								topics.push(topic);
//					        	var view = new TopicView({model: topic});
//					        	jQuery(".topics .loadingtopic").before(view.render().el);
					        	
//					        	var newItems = $(view.render().el);
//					        	$('.topics').append(newItems).isotope('addItems',newItems);
					        	//alert($(view.render().el).html());
//					        	$('.topics').isotope( 'insert', newItems );
							});
							self.set("topics",topics);
							self.set({"topicloading":false});
						}
					});
				}
			},
			
//			loadNewTopic : function(){
//				var self = this;
//				//var typeArray = ["success","warning","error"];
//				jQuery.ajax({
//					type : "GET",
//					url : "tag/ajaxtopics",
//					data : key = "key=" + this.get("name")
//							+ "&totalCount="
//							+ this.get("topic_num") + "&page="
//							+ this.get("page"),
//					dataType : 'json',
//					success : function(data) {
//						var topicsArray = data['topics'];
//						_.each(topicsArray,function(t){
//							var topic = new Topic({
//				                id:t['id'],
//				                read:t['value']['read'],
//				                title : t['value']['title'],
//								date : t['value']['date'],
//								lang : "zh-CN",
//								body : t['value']['body'],
//								comment_count : t['value']['comments'],
//								view_count : t['value']['views'],
//								//type: typeArray[~~(Math.random()*10/3)],
//								author: t['value']['author'],
//								site: t['value']['site']
//				              });
//				        	var view = new TopicView({model: topic});
//				        	var newItems = $(view.render().el);
//				        	$('.topics').prepend( $newItems).isotope( 'reloadItems').isotope({sortBy: 'original-order'});
//						});
//					}
//				});
//			}

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
		this.model.bind('change:topics',this.renderTopics,this);
		this.model.bind('change:topicloading',this.loadingStatus,this);
	},

	// Re-render the contents of the todo item.
	render : function() {
		jQuery(this.el).html(this.template(this.model.toJSON()));
		return this;
	},

	renderTopics : function() {
		var oldTopics = this.model.previous("topics");
		var topics = this.model.get("topics");
		var newTopics = _.without(topics, oldTopics);
		_.each(newTopics,function(topic){
			var view = new TopicView({model: topic});
	    	var newItems = jQuery(view.render().el);
        	$('.topics').append(newItems).isotope('addItems',newItems);
        	$('.topics').isotope( 'insert', newItems );
		});
		
	},
	
	turnPage : function(e){
		if(this.model.get('name')!=undefined){
			var top = document.documentElement.scrollTop + document.body.scrollTop;
			if (jQuery(".topics").height() - top < jQuery(window).height()) {
				if(!this.model.get('topicloading')){
					this.model.loadTopics();
				}
			}
		}
	},
	
	selectTag : function(e) {
		jQuery(".topics").isotope( 'remove', $(".topic"));
		jQuery(".topics").html(jQuery(".loadingtopic"));
		jQuery(".topics").isotope( 'reloadItems' );
		this.model.set({'name':jQuery(e.currentTarget).text(),'selected':true,'topic_num':jQuery(e.currentTarget).attr('rel')});
		if(this.model.get("topics").length > 0){
			
		}else{
			this.model.loadTopics();
		}
		jQuery(window).unbind('scroll');
		jQuery(window).bind('scroll', _.bind(this.turnPage, this));	
		
	},
	
	loadingStatus : function(e){
		if(this.model.get("topicloading")){
			jQuery(".loadingtopic").show();
		}else{
			jQuery(".loadingtopic").hide();
	    	//$('.topics').isotope('reLayout');
		}
	}

});
