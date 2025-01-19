Component(function Camera (){
	this.$$extend = '';

	this.$$       = {};
	this.$$style  = {};
	this.$$states = {};

	this.$el   = Query();
	this.$win  = Query();
	this.$body = Query();
	this.$head = Query();

	// init --------------------------------------------------------------------
	// these should also be available as get/set right away, compared to keys in "$" instead, $ would be for cached values instead
	this.data = {
	};
	
	this.init = function (){
		// this._super();	
	};

	// private -----------------------------------------------------------------
	this._private = function (){

	};

	// events ------------------------------------------------------------------
	this.onPageResize = function (e){

	};

	// method ------------------------------------------------------------------
	this.open = function (){

	};

	this.close = function (){
		
	};	
});