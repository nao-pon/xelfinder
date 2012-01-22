<?php
////////////////////////////
// for test
// ['volume_setting'] = 'mydirname:plugin:path:title:options, ...';

$config['volume_setting'] =
'myalbum:myalbum:uploads/photos:イメージマネージャー
mailbbs:mailbbs:modules/mailbbs/imgs:写メールBBS
xelfinder:xelfinder:uploads/elfinder:共有ホルダ';


// xelfinder 共有ホルダのアクセス権限

// 基本設定
$attributes = array(
//	array(
//		'pattern' => '~\.php$~i',
//		'read' => false,
//		'write' => false,
//		'hidden' => false,
//		'locked' => true
//	),
	// .(dot) start
	array(
		'pattern' => '#/\.#',
		'read' => false,
		'write' => false,
		'hidden' => true,
		'locked' => true
	),
);

// メンバー用
$attributes_member = array(
	array(
		'pattern' => '#^/forGuest\b#',
		'read' => true,
		'write' => true,
		'hidden' => false,
		'locked' => true
	),
	array(
		'pattern' => '#^/forMember\b#',
		'read' => true,
		'write' => true,
		'hidden' => false,
		'locked' => true
	),
	array(
		'pattern' => '#.+#',
		'read' => true,
		'write' => false,
		'hidden' => false,
		'locked' => true
	),
);

// ゲスト用
$attributes_guest = array(
	array(
		'pattern' => '#^/forGuest\b#',
		'read' => true,
		'write' => true,
		'hidden' => false,
		'locked' => true
	),
	array(
		'pattern' => '#.+#',
		'read' => true,
		'write' => false,
		'hidden' => false,
		'locked' => true
	),
);
// ユーザーランクで組み立て
if (! $memberUid) {
	$attributes = array_merge($attributes, $attributes_guest);
} else if (! $isAdmin) {
	$attributes = array_merge($attributes, $attributes_member);
}

// $extras に登録
// $extras[key:mydirename] = (extra options array);
$extras = array(
	$mydirname => array(
		'attributes' => $attributes
	)
);

// end for test
////////////////////////////