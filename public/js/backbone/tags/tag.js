window.Tag = Backbone.Model.extend({

        defaults: function() {
          return {
            name:  "",
            topic_num: 0
          };
        },

        // Toggle the `done` state of this todo item.
        loadTopics: function() {
        	_loading = true;
        	
        	page=0;
        	
        	_selectKey = this.get("name");
            
        	jQuery(".topics .loadingtopic").show();
        	jQuery.get("tag/ajaxtopics?key="+this.get("name")+"&totalCount="+this.get("topic_num")+"&page="+page,function(data){
        		jQuery(".topics .loadingtopic").hide();
        		jQuery(".loadingtopic").before(data);
        		_loading = false;
        	});
        }

      });
      
      
      window.TagList = Backbone.Collection.extend({

        // Reference to this collection's model.
        model: Tag,


        done: function() {
        }

      });
      
      window.tags = new TagList;
      
      
      window.TagView = Backbone.View.extend({

        //... is a list tag.    	
        tagName:  "li",
        
        //display: "none",

        // Cache the template function for a single item.
        template: _.template(jQuery('#tag_template').html()),

        // The DOM events specific to an item.
        events: {
           "click"    : "loadTopics",
        },

        // The TodoView listens for changes to its model, re-rendering.
        initialize: function() {
           this.model.bind("change:topicLoaded", this.afterTopicLoaded,this);
        },

        // Re-render the contents of the todo item.
        render: function() {
            jQuery(this.el).html(this.template(this.model.toJSON()));
            return this;
        },
        

        afterPostLoaded: function(){
        	 alert("topics loaded");
//            var posts = this.model.posts;
//            var view = new PostsView({model: {models:posts.models}});
//            $(".posts",this.$el).html(view.render().el);
        },
        
        loadTopics: function(){
        	this.model.loadTopics();
        }

      });
