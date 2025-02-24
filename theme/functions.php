<?php
/**
 *	- using https://openexchangerates.org/account/usage for API convertion
 **/

// [ ] add an "icon" for the description expenses. Plus, when trying to change the icon, show in the dropdown, the latest 3 you normally use

// constants -------------------------------------------------------------------

// includes --------------------------------------------------------------------
include_once('assets/php/core/utils.php');
include_once('assets/php/core/site.php');

// [ ] text translation (add better context)
// [ ] with get, be able to escape "||" "|" and "." with prefix "\"

_config([
	'version'   => '1.0.0',
	'languages' => 'en, fr',
	// 'lang'      => 'fr',
	// 'date' => [
	// 	'timezone' => 'America/Toronto',
	// 	'formats'  => [

	// 	]
	// ]
]);

// /*
echo ffto_to_asset('@styles/style.css');

$i = null;
$v = null;

ffto_add_route('/test/[bob?]', function (){
	$v = _var('bob', 888);
	return $v;
});

$r = ffto_set_routes(true);
$v = ffto_get_route(null, 'content');

// Route::get('/api', function (){
// })->post('/update', function (){
// });

echo '<pre>';
echo NL . NL;
	echo 'Value is set: ' . ($v ? 'Yes' : 'no');
	echo NL . NL;
	
	echo '<br><br>---------<br><br>';
	
	echo _string($v, 'pretty=php', ','.NL);
	
	echo '<br><br>---------<br><br>' . NL;

	_p($v);
echo '</pre>';
?>
<script>
function test (){ bob(); }
function bob (){ _js(':trace', 'OK ok'); }
</script> 
<?php


ffto_startup_javascript();

// _config(array(
// 	'version'  => '1.00',
// 	'domain'   => '{{ DOMAIN }}',
// 	'sitename' => '{{ SITE_NAME }}',
// 	// 'password' => '{{ PASSWORD }}',
// 	// 'favicons' => true,
// 	'scripts'  => 'app.js,app2.js',
//     // 'langs' => [
// 	// 	'en' => 'English',
// 	// 	'fr' => 'label=French&active=0',
// 	// ],
//     'settings' => [
//         // 'google_analytics' => '',
//         // 'captcha_key'      => '',
//         // 'captcha_secret'   => '',
//     ]
// ));

// h9_add_strings([
//     'close' => __t('Close'),
// ]);

// routes ----------------------------------------------------------------------
// set_routes(true);
// h9_set_routes([
//     'base'   => 'api/',
//     'layout' => false,
// ], true);

// h9_set_routes([
//     'base'      => 'admin/',
//     'dir'       => '@routes/admin/',
//     'layout'    => '@theme/layout-admin',
//     'classname' => 'page-admin',
//     'on_match'  => function (){ _config(['save_translations' => false]); }
// ], [
//     '*' => true,
//     '/' => 'index',
// ]);

// h9_set_sitemap([
//     '/',
//     '/contact',
// ]);

// h9_set_page([
// 	'title'   => 'Page Title',
// ]);

// Page ------------------------------------------------------------------------
// the_page();



/*
$f = [
	'*'                   => 'AA M j, Y',
	':article'            => 'F j, Y',
	'same-month-start'    => 'M j',
	'same-month-end'      => 'j, Y',
	'same-year-start'     => 'M j',
	'same-year-end'       => 'M j, Y',

	'fr/*'                => ['*'=>'j M, Y'],
	'fr/:article'         => 'j F, Y',
	'fr/current-year'     => 'j M',
	'fr/same-month-start' => 'j',
	'fr/same-month-end'   => ['*'=>'j M, Y', 'current-year'=>'j M'],
	'fr/same-year-start'  => 'j M',
	'fr/same-year-end'    => ['*'=>'j M, Y'],
	'fr/diff-year-end'    => ['*'=>'j M, Y', 'current-year'=>'j M, Y'],

	'en/*'                => ['*'=>'M j, Y'],
	'en/current-year'     => 'M j',
	'en/same-month-start' => 'M j',
	'en/same-month-end'   => ['*'=>'j, Y', 'current-year'=>'j'],
	'en/same-year-start'  => 'M j',
	'en/same-year-end'    => ['*'=>'M j, Y', 'current-year'=>'M j'],
	'en/diff-year-end'    => ['*'=>'M j, Y', 'current-year'=>'M j, Y'],
];
$v = to_config_args(null, $f, [], 'fr');


$f = [
	'*'                              => 'M j, Y',
	'fr'                             => 'j M, Y',
	'fr/:article'                    => 'j F, Y',
	'fr/current-year'                => 'j M',
	'fr/same-month-start'            => 'j',
	'fr/same-month-end'              => 'j M, Y',
	'fr/same-month-end/current-year' => 'j M',
	'fr/same-month-end'              => 'j M, Y',
	'fr/same-month-end/current-year' => 'j M',
	'fr/same-year-start'             => 'j M',
	'fr/same-year-end'               => 'j M, Y',
	'fr/diff-year-end'               => 'j M, Y',
	'fr/diff-year-end/current-year'  => 'j M, Y',
];

$f = [
	'*'  => [
		'*'            => 'M j, Y',
		'current-year' => 'M j',
	],
	'fr' => [
		'*'                => 'j M, Y',
		':article'         => 'j F, Y',
		'current-year'     => 'j M',
		'same-month-start' => 'j',
		'same-month-end'   => [
			'*'            => 'j M, Y',
			'current-year' => 'j M',
		],
		'same-year-start' => 'j M',
		'same-year-end'   => 'j M, Y',
		'diff-year-end'   => [
			'*'            => 'j M, Y',
			'current-year' => 'j M, Y',
		],
	],
];

patterns:
{ctx}/{group}/{key}
{ctx}/{group}/*
{ctx}/{key}
{ctx}/*
{key}
*

fr/diff-year-end/current-year
fr/current-year 
current-year
*


fr/diff-year-end
fr/current-year
fr/
current-year
*

/*
$v = _get($f, '
	fr/same-month-end/current-year, 
	fr/same-month-end/*, 
	fr/same-month-end, 
	same-month-end/current-year, 
	same-month-end/*, 
	same-month-end, 
	fr/current-year, 
	fr/*
	current-year, 
	*
')
*/

// 'same-year-end' = [
//     'fr/same-year-end/',
//     '*',
// ]

// $v = ffto_to_config_args($f);

/*
'date_templates' => [
		'*'          => '{{ start }}–{{ end }}',
		'fr/time'    => '{{ start }} à {{ end }}',
		'admin/join' => ' - ',
	],
	'date_formats'  => [
		'*'                   => 'M j, Y',
		':article'            => 'F j, Y',
		'same-month-start'    => 'M j',
		'same-month-end'      => 'j, Y',
		'same-year-start'     => 'M j',
		'same-year-end'       => 'M j, Y',

		'fr/*'                => ['*'=>'j M, Y'],
		'fr/:article'         => 'j F, Y',
		'fr/current-year'     => 'j M',
		'fr/same-month-start' => 'j',
		'fr/same-month-end'   => ['*'=>'j M, Y', 'current-year'=>'j M'],
		'fr/same-year-start'  => 'j M',
		'fr/same-year-end'    => ['*'=>'j M, Y'],
		'fr/diff-year-end'    => ['*'=>'j M, Y', 'current-year'=>'j M, Y'],

		'en/*'                => ['*'=>'M j, Y'],
		'en/current-year'     => 'M j',
		'en/same-month-start' => 'M j',
		'en/same-month-end'   => ['*'=>'j, Y', 'current-year'=>'j'],
		'en/same-year-start'  => 'M j',
		'en/same-year-end'    => ['*'=>'M j, Y', 'current-year'=>'M j'],
		'en/diff-year-end'    => ['*'=>'M j, Y', 'current-year'=>'M j, Y'],
	],
	'time_formats' => [
		'*'        => 'g:i a',
		'short'    => 'g a',
		'fr/*'     => 'G \h i',
		'fr/short' => 'G \h',
		'en/*'     => 'g:i a',
		'en/short' => 'g a',
	],
*/