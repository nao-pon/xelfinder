# X-elFinder (えっくす・えるふぁいんだー)

JavaScript + PHP で動作する Webベースファイルマネージャーの [elFinder 2.0](http://elfinder.org/) を XOOPS 用にモジュール化したもの。

XOOPS にインストールすることで、イメージマネージャーと換装して利用することができます。

ただし、XOOPS Cube Legacy 以外は、XOOPS_ROOT_PATH/imagemanager.php を若干編集する必要があります。

開発は github 上で行われています。

* [nao-pon/xelfinder - GitHub](https://github.com/nao-pon/xelfinder)

ダウンロードは上記のページの「[ZIP](https://github.com/nao-pon/xelfinder/zipball/master)」から行えます。

X-elFinder に関する話題、質問、要望 はフォーラムーへ。

* [X-elFinder - フォーラム - XOOPS マニア](http://xoops.hypweb.net/modules/forum/index.php?forum_id=25)

## 動作環境

* XOOPS 系プラットフォーム
 * 動作確認済みプラットフォーム
  * XOOPS Cube Legacy 2.2.0, 2.2.1
  * XOOPS 2.1.16-JP
  * XOOPS 2.5.5
* PHP 5.2 以上

## インストール時の注意事項

次のディレクトリに書き込み(ファイル作成)権限 (777 とか 707 など) が必要です。

* html/modules/xelfinder/cache
* html/modules/xelfinder/cache/tmb
* xoops_trust_path/uploads/xelfinder

画像参照に PathInfo を使用していますが、サーバー環境によっては PathInfo が使えず正常に表示されない場合があります。

その場合には、管理画面の一般設定で「ファイル参照URLの PathInfo を無効にする」を「はい」にしてください。

### ポップアップを IFRAME に変更したい場合

elFinder のポップアップには XOOPS の xoops.js に含まれている openWithSelfMain() を使用しますが、
openWithSelfMain() では、別ウィンドウが開きます。これを IFRAME を使ったポップアップに変更したい場合は、
テーマの theme.html にて `<{$xoops_js}>` を読み込んだ後で、openWithSelfMain_iframe.js を読みこませることで
それが可能になります。

HypConf(HypCommon の設定) モジュールの「その他の設定」-「&lt;head&gt;内の最後に挿入するタグ」に

    <script type="text/javascript" src="<{$xoops_url}>/modules/xelfinder/include/js/openWithSelfMain_iframe.js"></script>

を追加するか次のように theme.html を編集してください。

例 (theme.html):

    <script type="text/javascript">
    <!--
    <{$xoops_js}>
    //-->
    </script>
    <script type="text/javascript" src="<{$xoops_url}>/modules/xelfinder/include/js/openWithSelfMain_iframe.js"></script>

### 依存ライブラリについて

BBcode での参照時など用に任意の縮小サイズの画像を表示できますが、その機能を有効にするために HypCommonFunc が必須になっています。

* [HypCommonFunc について](http://xoops.hypweb.net/modules/xpwiki/156.html)

## X-elFinder 固有の主な機能

elFinder の機能に加えて次のような機能を持っています。

* ブラウザウィンドウ間でのドラッグ＆ドロップによるファイルアップロード(Firefox, Chrome, Safari)
* Pixlr.com を利用した画像編集
* Dropbox.com 上のデータストレージの直接操作
* グループ毎に無効コマンドを指定可能（指定機能の制限）
* プラグイン形式によるボリューム(ドライブのようなもの)の追加
    * ボリューム毎に有効にするグループIDを指定可能
    * xelfinder_db プラグインによるきめ細やかな対応
        * ユーザー別フォルダー
        * グループ別フォルダー
        * ゲスト用フォルダー
        * フォルダー・ファイルの権限(パーミッション)設定(オーナー・グループ・ゲストに対してそれぞれ、読み込み・書き込み・ロック解除・非表示 を設定可能)
        * フォルダー単位に新規アイテムのパーミッションを設定可能
    * xelfinder プラグインでの、サーバー内の任意のディレクトリを指定してそのディレクトリ内の画像ファイルの操作
    * XOOPS の d3diary, GNAVI, MailBBS, MyAlbum モジュールのプラグインを同梱
        * それぞれのモジュールに保存されている画像を利用可能＿

## XOOPS Cube Legacy 以外の imagemanager.php

XOOPS_ROOT_PATH/imagemanager.php で mainfile.php を読み込んでいる行の直後に

    include 'modules/xelfinder/manager.php';

を挿入すればOKです。

## アンインストール時の注意事項

アンインストールをすると、アップロードされたファイルの実体は残りますが、フォルダ・パーミッション・オーナーなどすべての情報が失われます。

それらの情報を保存したい場合は、データベースのバックアップを保存しておいてください。

X-elFinder のテーブル名は "[XOOPS DBプレフィクス]_[X-elFinderモジュールディレクトリ名]_" から始まるものとなります。

なお、アンインストールしてファイル実体も削除したい場合は、"XOOPS_TRUST_PATH/uploads/xelfinder" ディレクトリにある

* ファイル実体: "[XOOPS_URLのドメイン部以降]_[X-elFinderモジュールディレクトリ名]_[ファイルID(数値)]"
* 縮小画像: "[XOOPS_URLのドメイン部以降]_[X-elFinderモジュールディレクトリ名]_[ファイルID(数値)]_[縮小率(数値)].tmb"

が対象です。
