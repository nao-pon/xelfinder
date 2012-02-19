# xelFinder (えっくすいーえる・ふぁいんだー)

JavaScript + PHP で動作する Webベースファイルマネージャーの [elFinder 2.0](http://elfinder.org/) を XOOPS 用にモジュール化したもの。

XOOPS にインストールすることで、イメージマネージャーと換装((当サイトではイメージマネージャーとして xelFinder を利用しています))して利用することができます。

ただし、XOOPS Cube Legacy 以外は、XOOPS_ROOT_PATH/imagemanager.php を若干編集する必要があります。

開発は github 上で行われています。

* [nao-pon/xelfinder - GitHub](https://github.com/nao-pon/xelfinder)

ダウンロードは上記のページの「[ZIP](https://github.com/nao-pon/xelfinder/zipball/master)」から行えます。

xelFinder に関する話題、質問、要望 はフォーラムーへ。

* [xelFinder - フォーラム - XOOPS マニア](http://xoops.hypweb.net/modules/forum/index.php?forum_id=25)

## インストール時の注意事項

次のディレクトリに書き込み(ファイル作成)権限 (777 とか 707) が必要です。

* html/modules/xelfinder/cache
* html/modules/xelfinder/cache/tmb
* xoops_trust_path/uploads/xelfinder

## xelFinder 固有の主な機能

elFinder の機能に加えて次のような機能を持っています。

* ユーザー別ホルダー
* グループ別ホルダー
* ゲスト用ホルダー
* ホルダー・ファイルの権限(パーミッション)設定(オーナー・グループ・ゲストに対してそれぞれ、読み込み・書き込み・ロック解除・非表示 を設定可能)
* ホルダー単位に新規アイテムのパーミッションを設定可能
* プラグイン形式によるボリューム(ドライブのようなもの)の追加
 * 現状は MyAlbum, MailBBS モジュールのプラグインがあり、それぞれのモジュールに保存されている画像を利用できます

## XOOPS Cube Legacy 以外の imagemanager.php

XOOPS_ROOT_PATH/imagemanager.php で mainfile.php を読み込んでいる行の直後に

 include 'modules/xelfinder/manager.php';

を挿入すればOKです。


