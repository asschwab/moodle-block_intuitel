M.local_intuitel={
		Y:null,
		params:null,
		transaction : [],
		init : function(Y,params)
		{
			this.Y = Y;
			this.params = params;
			    // Y is the YUI instance.
			    ///Get a reference to the DIV that we are using
				//to report results.
			var d = Y.one('#INTUITEL_render_area'),
				    gStr = '',
				    tStr = '';	
	        /* transaction event object */
	        var tH = {
	            write: function(s) {
                    	tStr += s;
	                   },

	            success: function(id, o, args) {
	            			this.write(o.response);
	                     },
	            failure: function(id, o, args) {
	                       this.write(id + ": Transaction Event Failure.  The status text is: " + o.statusText + ".");
	                     },
	            end: function(id, args) {
	                     flush(gStr + tStr);
	            }
	        };
	        /* end transaction event object */

	        /* Output the results to the DIV container */
	        function flush(s)
	        {
	         d.set("innerHTML", s);
	         Y.one('#INTUITEL_loading_icon').hide();
			 M.local_intuitel.tagModules(Y);
			 d.show();
	        }
	        // add environmental params to query string
	        if (typeof params['ignoreLO']=="undefined")
	        	params['ignoreLO']='';
	        
	        params['pixel_density']='&pixel_density='+window.devicePixelRatio*96;
	        params['query_string'];
	        /* configuration object for transactions */
	        var cfg = {
	            on: {
	            //    start: tH.start,
	            //    complete: tH.complete,
	                success: tH.success,
	                failure: tH.failure,
	                end: tH.end
	            },
	            context: tH,
	            headers: { 'X-Transaction': 'GET INTUITEL STATUS'},
	            data: params['query_string']+params['ignoreLO']+params['pixel_density'],
	            arguments: {
	                       }
	        };
	        /* end configuration object */
			var icon = Y.one('#INTUITEL_loading_icon');
                        if (icon)
                            icon.show();

	        Y.io(params['intuitel_proxy'], cfg);
	        
	        /* Geolocation */
	        if (params['geolocate']=='yes')
	    	{
	    	Y.Geo.getCurrentPosition(function(response)
	    			{
	    			if (response.success)
	    				{
	    				console.log('You are located at: '+response.coords.latitude+','+response.coords.longitude+' to change this permission go to (FireFox) Page Info->Permissions');
	    				var cfgGeo={
	    						headers: { 'X-Transaction': 'GEO LOCATION'},
	    			            data: '_intuitel_intent=GEOLOCATION&lat='+response.coords.latitude+'&lon='+response.coords.longitude,
	    				};
	    				Y.io(params['intuitel_proxy'], cfgGeo);
	    				}
	    			});
	    	}
	 
			//////////////////////////////////
			// End of Init
			//////////////////////////////////
		},
		submitTUG:function(Y,mId)
		{
			this.Y=Y;
			Y.mId=mId;
			// Create a YUI instance using the io-form sub-module.
			Y.use("io-form","transition","panel","node","console", function()
			{
			
			mId= Y.mId;
		    // Create a configuration object for the file upload transaction.
		    // The form configuration should include two defined properties:
		    // id: This can be the ID or an object reference to the HTML form.
		    // useDisabled: Set this property to "true" to include disabled
		    //              HTML form fields, as part of the data.  By
		    //              default, disabled fields are excluded from the
		    //              serialization.
		    // The HTML form data are sent as a UTF-8 encoded key-value string.
			
		    var cfg = {
		        method: 'GET',
		        form: {
		            id: 'INTUITEL_TUG_FORM_'+mId,
		            useDisabled: true
		        }
		    };
			
			
		    // Define a function to handle the response data.
		    function success(id, o, args) 
		    {
		      var id = id; // Transaction ID.
		      var data = o.responseText; // Response data.
			
		      var div=Y.one('#INTUITEL_TUG_'+args['mId']);
		      if (div!=null)
		    	  {
		    	  div.args=args;
			    	div.transition({
					    duration: 1, // seconds
					    easing: 'ease-out',
					    height: 0,
				  		opacity: 0
						}, 
							function()//transition call-back
							{
							this.set("innerHTML", "");
			  // JPC: Implementation of dialog chaining of TUG-LearnerUpdate requests
							M.local_intuitel.params['ignoreLO']='&ignoreLo=true';
							if (this.args.tugCancel!='true')
								M.local_intuitel.init(M.local_intuitel.Y,M.local_intuitel.params);
							}
					);
		    	  }
		    };
			function failure(id, o, args) {
		      var id = id; // Transaction ID.
		      var data = o.responseText; // Response data.
		      Y.log("Error submitting TUG data:"+data,"error","Intuitel User Interface");
			Y.one("#INTUITEL_TUG_MSG_"+args['mId']).setHTML("Sorry an error has ocurred:"+o.status+" "+o.statusText+" info was not sent.");
				
		    };
			var form = Y.one('#INTUITEL_TUG_FORM_'+mId);
			var uri= form.get('action');
			var tug_cancel = form.get('_intuitel_TUG_cancel').get('value');
		    // Subscribe to event "io:complete", and pass an array
		    // as an argument to the event handler "complete".
		    Y.on('io:success', success, Y, { 'mId':mId ,'tugCancel':tug_cancel});
		    Y.on('io:failure', failure, Y, { 'mId':mId });
			
		  
		    // Start the transaction.
		    var request = Y.io(uri, cfg);
			});
			

			return false;
		},
		
		showTUGNotification: function (Y)
		{
		this.Y=Y;
		var content = Y.one("#INTUITEL_TUG").getHTML();
		M.core_message.init_notification(Y,"Notice from INTUITEL",content,null);
		},
	/**
	 * Annotate each module in the front page with a Stars scale.
	 * @param Y
	 * @param modules
	 */	
		tagModules: function(Y,modules)
		{
//			 YUI({
//				    //Last Gallery Build of this module
//				    gallery: 'gallery-2013.02.27-21-03'
//				}).use('gallery-ratings', function(Y) {
//					Y.Ratings.prototype.onRatingClick=function (e){
//						e.preventDefault();
//					}; // patch to avoid clicking
			YUI(
//				{
//			    modules: {
//			        'gallery-ratings': {
//			            fullpath: 'http://localhost/moodle2/blocks/intuitel/script/gallery-ratings/gallery-ratings.js',
//			           
//			        }
//			    }
//			}
			).use('gallery-ratings', function (Y){
				Y.Ratings.prototype.onRatingClick=function (e){
				e.preventDefault();
			}; // patch to avoid clicking
			
			
			var Lore=Y.one('#INTUITEL_LORE');
			var recom= Lore.all('li');	
			// clean up the interface
			var previous_widgets=Y.all('.yui3-gallery-ratings');
			previous_widgets.each(function (previous_widget)
					{
					previous_widget.remove(true);
					});
			recom.each(function(reco)
				{
				var id=reco.get('id');
				var cmid=id.substr(14);
				var span_val= reco.one('#intuitel_lore_score_navigation');
				var val= span_val.get('innerHTML');
				var  module_div=Y.one('#module-'+cmid);
				if (module_div!=null)
					{ // add marker for rating stars
					var widget = module_div.one('div').one('a').one('#intuitel_lore_score');
					if (widget==null)
						widget = module_div.one('div').one('a').append('<span id="intuitel_lore_score"/>').one('#intuitel_lore_score');
					widget.setHTML(val);
					}
			});
			
			var scores = Y.all('#intuitel_lore_score');
			scores.each(function (score)
					{
				 	var ratings = new Y.Ratings({ srcNode: score, inline: false, skin: "" });
					});
			var scores_nav= Y.all('#intuitel_lore_score_navigation');
			scores_nav.each(function (score)
					{
				 	var ratings = new Y.Ratings({ srcNode: score, inline: true, skin: "small" });
					});
				});
		},
};
