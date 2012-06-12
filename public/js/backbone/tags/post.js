$(function(){
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
            date_since: null
          };
        }
    });

    window.PostList = Backbone.Collection.extend({
        // Reference to this collection's model.
        model: Post,
    });
    
    
    window.PostsView = Backbone.View.extend({
        tagName:  "div",

        template: _.template($('#posts_template').html()),

        events: {
        },

        initialize: function() {

        },

        // Re-render the contents of the todo item.
        render: function() {
          $(this.el).html(this.template(this.model));
          return this;
        },
    });

});
