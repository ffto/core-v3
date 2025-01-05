<?php 
define('SCRIPT_HELPERS', 'script_helpers');

function add_script_helper ($callback, $args=''){
    $scripts = get_global(SCRIPT_HELPERS, array());
    if (!isset($scripts[$callback])){
        $scripts[$callback] = array(
            'html' => $callback,
            'args' => $args,
        );
    }
    set_global(SCRIPT_HELPERS, $scripts);
}

function the_script_helpers (){
    $scripts = get_global(SCRIPT_HELPERS, array());
    if (empty($scripts)) return;

    // empty the list, since we'll output them already
    set_global(SCRIPT_HELPERS, []);

    $html = [];
    foreach ($scripts as $script){
        $replace = get_value($script, 'args.replace', array());
        $h       = to_content($script['html'], 'callable=1');
        $h       = strtr($h, $replace);
        // $h       = to_minify($h, 'js');
        $html[]  = $h;
    }

    $html = implode(NL, $html);
    echo $html;
}

function the_script_helpers_fallback (){
    ?>
    <script id="website-fallback">
        var Web = Web || {};
        Web.Element = Web.Element || function (){ Web.Elements=Web.Elements||[]; Web.Elements.push(arguments); };
        Web.Style   = Web.Style || function (){ Web.Styles=Web.Styles||[]; Web.Styles.push(arguments); };
        Web.refresh = Web.refresh || function (){};
    </script>
    <?php 
}
function the_script_helpers_refresh (){
    ?>
    <script id="website-refresh">Web.refresh();</script>
    <?php 
}

add_action('the_head-start', function (){
    the_script_helpers_fallback();
});

add_action('the_foot-end', function (){
    the_script_helpers();
    the_script_helpers_refresh();
});

add_action('wp_head', function (){
    the_script_helpers_fallback();
}, 0);

add_action('wp_footer', function (){
    the_script_helpers_refresh();
    the_script_helpers();
}, 0);