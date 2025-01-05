<?php 
function ffto_the_skip_to_content ($args=null, $echo=true){
    $args = _args($args, [
        'target' => '#main',
        'attrs'  => null,
    ], 'target');

    $html = __html('a', $args['attrs'], [
        'class' => 'item-skip-to-content',
        'href'  => $args['target'],
        'html'  => __t('Skip to content')
    ]);

	return _echo($html);
}
