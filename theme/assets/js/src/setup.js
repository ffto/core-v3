var STATIC     = {};
var CLASSES    = {};
var EVENTS     = {};
var PROPS      = {};
var COMPONENTS = {};
var SETTINGS   = window.theme_settings || {};
var VERSION    = window.version = SETTINGS.version;

STATIC['*'] = {
    'logs' : 'error,warn,log,info',
    'logs' : 'error,warn,log'
};

CLASSES['*'] = {
    //'debug' : true,
};

EVENTS['*:render'] = function (){
    // window.ImageFocus && window.ImageFocus.cache();
};

// Components ------------------------------------------------------------------
CLASSES['Component'] = {
    'filters' : {
        'http-url' : function (url){
            if (!url) return null;
            return !~url.indexOf('/') ? '/wp-admin/admin-ajax.php?action=' + url : url;
        }
    },
};

