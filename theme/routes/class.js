// Simple class (get, set, static)
Class(function (){
	this.$$       = {};
	this.$$extend = '';
	this.$$static = {};
	
	this.$$get = {};
	this.$$set = {};

	this.init = function (args){

	};

	this.$$super();
});

// API with extra default methods (events, states, styles)
Api(function (){
	this.$$style  = {};
	this.$$states = {};
	this.$$events = {};

	this.$();
	this.$win  = Query();
	this.$body = Query();
	this.$head = Query();

	// DataType 
	this.args = {

	};

	this.init = function (args){

	};

	this.destroy = function (){

	};
});

// // Simple Singleton (eg.: )
// Singleton(function Browser (){

// });

// // Should this be used?
// Factory(function (){

// });

// uses [is], [el], [name], [...]
Component({
	'singleton': true,
	'path'     : 'path/to/save/camera',   // to Save in a certain path
	'global'   : true,                    // [true || false] make it globally accessible
	'extends'  : '',
}, function Camera (){
	// Special property have $$ before
	this.$$singleton = true;
	this.$$extends   = true;
	this.$$path      = true;

	// Node tag to use
	this.$$element  = 'div';                           // default tag, left to null for nothing
	this.$$elements = 'elements, node, node2, node3';
	this.$$custom   = 'bob-burger';                    // Create a Class for creating <bob-burger> custom element HTMLElement

	// Query element has a $before
	this.$el = Query();

	// init --------------------------------------------------------------------
	// these should also be available as get/set right away, compared to keys in "$" instead, $ would be for cached values instead
	this.args = {

	};
	
	this.init = function (args){
		// this.$$super();	
	};

	// private -----------------------------------------------------------------
	// Private are prefixed by _
	this._private = function (){

	};

	// events ------------------------------------------------------------------
	this.onPageResize = function (e){

	};

	this.node_onScroll = {
		'debounce' : 300,
		'callback' : function (e){
			// something
		},
	};

	// get/set -----------------------------------------------------------------
	this.$$get.menu = function (){

	};

	this.$$set.menu = function (){

	};

	// method ------------------------------------------------------------------
	this.open = function (){

	};

	this.close = function (){
		
	};	
});

// new Drawing();

// For drawing
Drawing.Shape(function (){

});

// For Special view stuff (similar to Vuejs, Svelte, ...)
View('<node-tag>', function NodeTag (){

});