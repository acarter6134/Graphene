Cobler.types.uApp = function(container){
	function get() {
		item.widgetType = 'uApp';
		return item;
	}
	var item = {
		guid: generateUUID()}
	var fields = {
		Title: {},
		'App ID': {type: 'select', options: '/api/groups/'+group_id+'/appinstances',format:{label:"{{name}}",value:function(i){return i.id;}}},
    'User Options':{name:'user_edit',type:'checkbox'},
		// 'Template': {}
	}
	return {
    container:container,
		fields: fields,
		render: function() {
      var temp = get();
      temp.uapp_admin = group_admin;
			return templates['widgets_microapp'].render(temp, templates);
		},
		edit: defaultCobEditor.call(this, container),
		toJSON: get,
		get: get,
		set: function (newItem) {
			$.extend(item, newItem);
		},
		initialize: function(el){

    if(typeof this.get().app_id == 'undefined'){return false;};
      this.fields['App ID'].enabled = false;
      if(this.container.owner.options.disabled && this.get().enable_min){
          var collapsed = (Lockr.get(this.get().guid) || {collapsed:this.get().collapsed}).collapsed;
          this.set({collapsed:collapsed});
          $(el).find('.widget').toggleClass('cob-collapsed',collapsed)
      }
      $.ajax({
          url: '/api/fetch/'+this.get().app_id,
          dataType : 'json',
					type: 'POST',
          data: (Lockr.get('/api/apps/instances/'+this.get().app_id+'/user_options')|| {options:{}}),
					success  : function(data){
            
            if(typeof data.user.id == 'undefined') {
              var url = '/api/apps/instances/'+this.get().app_id+'/user_options';
              data.user.options = (Lockr.get(url)|| {options:{}}).options;
            }


            var opts = {
              template: this.get().template || 'dashboard',
              $el: $(el).find('.collapsible'),
              crud: function(name, data, callback, verb){
                var send_data = {request: data};
                if(typeof this.data.user.id == 'undefined') {
                  send_data.options = this.data.user.options;
                }
                $.ajax({
                  url: '/api/fetch/'+ this.config.app_instance_id + '/' +name+ '?verb='+verb,
                  // dataType : 'json',
                  contentType: 'application/json',
                  data: JSON.stringify(send_data),
                  type: 'POST',
                  error: function (data) {
                    // if(typeof data.responseJSON !== 'undefined' && typeof data.responseJSON.error !== 'undefined' && data.responseJSON.error) {
                    //   toastr.error(data.responseJSON.error.message || data.responseJSON.error,'ERROR')
                    // }else{
                      toastr.error(data.statusText, 'ERROR')
                    // }
                  }.bind(this),
                  success  : callback.bind(this)
                });
              }
            }
            opts.data = data;
            opts.config = (_.find(apps, {id: parseInt(this.get().app_id,10)}) || _.find(gform.collections.get('/api/groups/'+group_id+'/appinstances'), {id: parseInt(this.get().app_id,10)})).app.code || {};
            // opts.config = _.find(Berry.collection.get('/api/appinstances'), {id: parseInt(this.get().app_id,10)}).app.code;
            opts.config.app_instance_id = this.get().app_id;
            opts.config.title = this.get().title;

            // $('style[name="'+opts.config.app_instance_id+'"]').remove();
            // if(opts.config.css.length){
            //   $('body').append('<style name="'+opts.config.app_instance_id+'">'+opts.config.css+'</style>');
            // }
            opts.onLoad = function(){
              this.appEngine.app.on('refetch', function(data){
                var options;
                if(typeof this.appEngine.data.user.id == 'undefined') {
                  options =  (Lockr.get('/api/apps/instances/'+this.get().app_id+'/user_options')|| {options:{}});
                }
                $.ajax({
                  type: 'POST',
                  url:'/api/fetch/'+this.get().app_id,
                  data:options,
                  success:function(data){

                      if(typeof data.user.id == 'undefined') {
                        var url = '/api/apps/instances/'+this.get().app_id+'/user_options';
                        data.user.options = (Lockr.get(url)|| {options:{}}).options;
                      }
                    this.appEngine.app.update(data);
                    // toastr.success('', 'Data refetched Successfully');
                  }.bind(this),
                  error:function(data){
                      toastr.error(data.statusText, 'An error occured updating App')
                  }
                })
              }.bind(this));
              $g.emit('loaded',this)

            }.bind(this)

            switch(opts.config.engine){
              case 'graphene':
                if('graphene' in $g.engines){
                  this.appEngine = $g.engines[opts.config.version||'v1']['v1'](opts)
                }else{
                  this.appEngine = gAE_v0001(opts);
                }
                break;
              case 'vue':
                this.appEngine = vueAppEngine(opts);
                break;
              default:
                // this.appEngine = vue_v0001(opts);
                // this.appEngine = grapheneAppEngine(opts);
                this.appEngine = $g.engines['graphene']['v1'](opts)

            }
            
            $g.apps[opts.config.app_instance_id] = this;

          }.bind(this)
      })
		}
	}
}