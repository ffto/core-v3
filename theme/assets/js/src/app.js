Class(function App (){
	this.$mixins = 'Events,Element,Style';

	// contants --------------------------------------------------------------------------------------------------------
	var CLASSES = {		
	};

	// init ------------------------------------------------------------------------------------------------------------
	this.init = function (){
		
	};

	this._render = function (args, callback){
		if (utils.is.fn(args)){
			callback = args;
			args     = {};
		}

		args        = args || {};
		args.now 	= 'now' in args ? args.now : true;
		args.resize = 'resize' in args ? args.resize : true;
		args.load   = 'load' in args ? args.load : true;
		args.scroll = 'scroll' in args ? args.scroll : false;
		callback 	= callback.bind(this);

		args.now && callback();
		args.load && this.on(Browser, 'load', callback);
		args.resize && this.on(Browser, 'resize', callback);
		args.scroll && this.on(Browser, 'scroll', callback);
		// this.callbacks.push(callback);
	};

	// private ---------------------------------------------------------------------------------------------------------

	// events ----------------------------------------------------------------------------------------------------------

	// methods ---------------------------------------------------------------------------------------------------------
});