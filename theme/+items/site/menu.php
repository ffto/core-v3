<?php 
$toggle = isset($toggle) ? $toggle : false;

_meta([
    'tag'   => 'nav',
    'alias' => 'site-menu',
    'is'    => "container/overlay",
    'hidden'=> true,
    'data'  => [
        'alias'           => '&',
        'toggle-selector' => $toggle
    ]
]);

// {{ WP MENU }}
// wp_nav_menu(array(
//     'items_wrap'     => '<ul id="%1$s" class="%2$s" unstyled>%3$s</ul>',
//     'container'      => false,
//     'menu_class'     => 'menu',
//     'theme_location' => 'main',
//     'fallback_cb'    => false,
// ));

// {{ SITE MENU }}
// h9_the_menu([
    
// ]);


// h9_the_languages([
//     'list'      => 'ul[unstyled]',
//     'container' => false,
//     'current'   => false,
//     'full'      => false,
// ]);