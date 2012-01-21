<?php
////////////////////////////
// for test
// ['volume_setting'] = 'mydirname:plugin:path:title:options, ...';

$config['volume_setting'] = 'myalbum:myalbum:uploads/photos:イメージマネージャー, mailbbs:mailbbs:modules/mailbbs/imgs:写メールBBS, xelfinder:xelfinder:uploads/elfinder:共有ホルダ';


// xelfinder 共有ホルダのアクセス権限

$attributes = array(
//	array(
//		'pattern' => '~\.php$~i',
//		'read' => false,
//		'write' => false,
//		'hidden' => false,
//		'locked' => true
//	),
	array(
		'pattern' => '#/\.#',
		'read' => false,
		'write' => false,
		'hidden' => true,
		'locked' => false
	),
);

$attributes_guest = array(
	array(
		'pattern' => '#/locked.*#',
		'read' => true,
		'write' => true,
		'hidden' => false,
		'locked' => true
	),
	array(
		'pattern' => '#/_.*#',
		'read' => true,
		'write' => false,
		'hidden' => false,
		'locked' => true
	),
);

if (! $isAdmin) {
	$attributes = array_merge($attributes, $attributes_guest);
}

// $extras[key:mydirename] = (extra options array);
$extras = array(
	'xelfinder' => array(
		'attributes' => $attributes
	)
);

// end for test
////////////////////////////


