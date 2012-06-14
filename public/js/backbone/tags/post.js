    window.Post = Backbone.Model.extend({
        defaults: function() {
          return {
            title:  "",
            date: null,
            site: "",
            body: "",
            id:"",
            author: "",
            username: "",
            translatedBody: "",
            display:""
          };
        }
    });

    window.PostList = Backbone.Collection.extend({
        // Reference to this collection's model.
        model: Post,
    });
    
    
    window.PostsView = Backbone.View.extend({
        tagName:  "div",

        template: _.template(jQuery('#posts_template').html()),

        events: {
        	"click .showHiddenPost" : "showPosts"
        },

        initialize: function() {

        },

        // Re-render the contents of the todo item.
        render: function() {
        	jQuery(this.el).html(this.template(this.model));
          return this;
        },
    	showPosts : function(e){
    		jQuery(e.currentTarget).parent().find("div.post").show().end().find("div.posthidden").hide();
    	}
    });
