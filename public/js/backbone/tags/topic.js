$(function(){
window.Topic = Backbone.Model.extend({

        defaults: function() {
          return {
            title:  "",
            date: null,
            site: "",
            lang: "zh-CN",
            body: "",
            img: null,
            nation: "",
            id:"",
            posts:null,
            postLoaded:false
          };
        },

        // Toggle the `done` state of this todo item.
        loadposts: function() {
           if (this.posts!=undefined) {
             return;
           }else{
             this.posts = new PostList;
           }
           var uri = decodeURIComponent(this.id);
           var start = '["'+uri+'",0,0]';
           var end = '["'+uri+'",{},{}]';

           var data = { 
                  start_key: start,
                  end_key:   end,
                  stale:     'update_after',
                  format: 'html'
                  };
           var posts = this.posts;
           var topic = this;
           $.ajax({
                  url: '../../../posts_lb/_design/socialmediathread/_view/posts-by-topic',
                  //dataType:  'html',
                  accepts: 'application/json',
                  data: data,
                  success:   function(data){
                      //TODO: translate
                      var i=0;
                      JSON.parse(data).rows.forEach( function(row){
                          var d = row.value.date;
                          var date = new Date(d[0], d[1], d[2], d[3], d[4], d[5]);
                          var post = new Post({
                            body :row.value.body,
                            id :encodeURIComponent(row.id),
                            topicId: encodeURIComponent(row.key[0]),
                            date : date.toLocaleString(),
                            date_since  : timeSince(date),
                            index :i,
                            author : row.value.author,
                            username : row.value.userName
                          });   
                          i++;         
                          posts.add(post);
                      });
                      topic.set("postLoaded",true);
                    }
                  });                      
        }

      });
      
      
      window.TopicList = Backbone.Collection.extend({

        // Reference to this collection's model.
        model: Topic,


        done: function() {
        }

      });
      
      window.topics = new TopicList;
      
      
      window.TopicView = Backbone.View.extend({

        //... is a list tag.
        tagName:  "li",
        
        display: "none",

        // Cache the template function for a single item.
        template: _.template($('#topic_template').html()),

        // The DOM events specific to an item.
        events: {
           "click .title"    : "toggle",
        },

        // The TodoView listens for changes to its model, re-rendering.
        initialize: function() {
           this.model.bind("change:postLoaded", this.afterPostLoaded,this);
          //this.model.bind('change', this.render, this);
          //this.model.bind('destroy', this.remove, this);
        },

        // Re-render the contents of the todo item.
        render: function() {
          $(this.el).html(this.template(this.model.toJSON()));
          return this;
        },
        
        toggle: function(){
            //this.model.displayPost = "loading";
            if ($(".topicPRR",this.$el).css("display")=="none"){
                if (!this.model.get("postLoaded")) {
                    $(".posts",this.$el).html("Loading...");
                    this.model.loadposts();
                }
                $(".topicPRR",this.$el).show();
                this.resizeTopicDiv(100);
            }else{
                $(".topicPRR",this.$el).hide();
                this.resizeTopicDiv(100);                
            }

        },

        afterPostLoaded: function(){
            var posts = this.model.posts;
            var view = new PostsView({model: {models:posts.models}});
            $(".posts",this.$el).html(view.render().el);
        },

        resizeTopicDiv: function(adjust){
           		var ll = 250;
        		if(adjust){
        			ll += adjust;
        		}
        		$('.topics>div').each(function(i,e){
        		    ll+=$(e).height()+3;
        			if($.browser.webkit){
        				ll+=4;
        			}
        		});
        		$('.topics').css("height", ll);
           }        

      });
});

function timeSince(date) {

    var seconds = Math.floor((new Date() - date) / 1000);

    var interval = Math.floor(seconds / 31536000);

    if (interval > 1) {
        return interval + " years ago";
    }
    interval = Math.floor(seconds / 2592000);
    if (interval > 1) {
        return interval + " months ago";
    }
    interval = Math.floor(seconds / 86400);
    if (interval > 1) {
        return interval + " days ago";
    }
    interval = Math.floor(seconds / 3600);
    if (interval > 1) {
        return interval + " hours ago";
    }
    interval = Math.floor(seconds / 60);
    if (interval > 1) {
        return interval + " minutes ago";
    }
    return Math.floor(seconds) + " seconds ago";
}