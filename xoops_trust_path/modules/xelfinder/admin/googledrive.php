<?php

$php54up = false;

if ($php54up = version_compare(PHP_VERSION, '5.4.0', '>=')) {

	require $mytrustdirpath . '/plugins/vendor/autoload.php';

	$selfURL = XOOPS_MODULE_URL . '/' . $mydirname . '/admin/index.php?page=googledrive';
	$sessTokenKey = $mydirname . 'AdminGoogledriveToken';
	$sessCliantKey = $mydirname . 'AdminGoogledriveToken';
	$client = null;
	if ($_SESSION [$sessClientKey]) {
		$clientId = $_SESSION [$sessClientKey] ['ClientId'];
		$clientSecret = $_SESSION [$sessClientKey] ['ClientSecret'];
	}

	if (! empty ( $_POST ['ClientId'] ) && ! empty ( $_POST ['ClientSecret'] )) {
		$clientId = trim ( $_POST ['ClientId'] );
		$clientSecret = trim ( $_POST ['ClientSecret'] );
		$_SESSION [$sessClientKey] = array (
				'ClientId' => $clientId,
				'ClientSecret' => $clientSecret 
		);
	}

	if (! empty ( $_SESSION [$sessClientKey] ) && ! isset ( $_GET ['start'] )) {
		
		$client = new \Google_Client ();
		// クライアントID
		// $client->setClientId('439932857153-o4vqqv0c9b6cqnbamec2a0h80o9oqkbd.apps.googleusercontent.com');
		$client->setClientId ( $_SESSION [$sessClientKey] ['ClientId'] );
		// クライアントSecret ID
		// $client->setClientSecret('35TbJlEANKrKI7dMVwh7t5xP');
		$client->setClientSecret ( $_SESSION [$sessClientKey] ['ClientSecret'] );
		// リダイレクトURL
		$client->setRedirectUri ( $selfURL );
		
		$service = new \Google_Service_Drive ( $client );
		// 許可されてリダイレクトされると URL に code が付加されている
		// code があったら受け取って、認証する
		if (isset ( $_GET ['code'] )) {
			// 認証
			$client->authenticate ( $_GET ['code'] );
			$_SESSION [$sessTokenKey] = $client->getAccessToken ();
			// header('Location: ' . $selfURL);
			// exit;
		}
		
		// セッションからアクセストークンを取得
		if (isset ( $_SESSION [$sessTokenKey] )) {
			// トークンセット
			$client->setAccessToken ( $_SESSION [$sessTokenKey] );
		}
	}
}

xoops_cp_header ();
include dirname ( __FILE__ ) . '/mymenu.php';

echo '<h3>' . xelfinderAdminLang ( 'GOOGLEDRIVE_GET_TOKEN' ) . '</h3>';

if ($php54up) {
	$form = true;
	// トークンがセットされていたら
	if ($client) {
		if (empty ( $_POST ) && $client->getAccessToken ()) {
			try {
				$aToken = $client->getAccessToken ();
				$token = array (
						'client_id' => $client->getClientId (),
						'client_secret' => $client->getClientSecret (),
						'access_token' => $aToken ['access_token'] 
				);
				if (isset ( $aToken ['refresh_token'] )) {
					unset ( $token ['access_token'] );
					$token ['refresh_token'] = $aToken ['refresh_token'];
				}
				$ext_token = json_encode ( $token );
				echo '<h3>Google Drive API Token</h3>';
				echo '<div><textarea style="width:70%;height:5em;">' . $ext_token . '</textarea></div>';
				echo '<h3>Example to Volume Driver Setting</h3>';
				echo '<div><textarea style="width:70%;height:7em;">xelfinder:flyGoogleDrive:/[TargetPath]:GoogleDrive:gid=1|id=gd|ext_token=' . $ext_token . '</textarea></div>';
				echo '<h4><a href="' . $selfURL . '&start">Reauthorization</a></h4>';
				$form = false;
			} catch ( Google_Exception $e ) {
				echo $e->getMessage ();
			}
		} else if (! empty ( $_POST ['scopes'] )) {
			if (! empty ( $_POST ['revoke'] )) {
				$client->revokeToken ();
			}
			// 認証用URL取得
			$scopes = array ();
			foreach ( $_POST ['scopes'] as $scope ) {
				switch ($scope) {
					case 'DRIVE' :
					case 'DRIVE_READONLY' :
					case 'DRIVE_FILE' :
					case 'DRIVE_PHOTOS_READONLY' :
					case 'DRIVE_APPS_READONLY' :
						$scopes [] = constant ( 'Google_Service_Drive::' . $scope );
				}
			}
			$client->setScopes ( $scopes );
			if (! empty ( $_POST ['offline'] )) {
				$client->setApprovalPrompt ( 'force' );
				$client->setAccessType ( 'offline' );
			}
			$authUrl = $client->createAuthUrl ();
			echo '<a href="' . $authUrl . '">Please allow the application access.</a>';
			$form = false;
		}
	}
	if ($form) {
?>
<h3>Authentication options</h3>
<hr>
<p>Redirect URI: <?php echo $selfURL; ?></p>
<form action="index.php?page=googledrive" method="post">
	<h4>Web client ID and Secret key</h4>
	<p>
		ClientId: <input type="text" name="ClientId" style="width: 50em"
			value="<?php echo htmlspecialchars($clientId); ?>"><br />
	</p>
	<p>
		ClientSecret: <input type="text" name="ClientSecret"
			style="width: 20em"
			value="<?php echo htmlspecialchars($clientSecret); ?>">
	</p>
	<h4>Required scopes</h4>
	<p>
		<input type="checkbox" name="scopes[]" value="DRIVE" checked="checked">:
		Google Drive ( Read & Write )<br> <input type="checkbox"
			name="scopes[]" value="DRIVE_READONLY">: Google Drive ( Read only )<br>
		<input type="checkbox" name="scopes[]" value="DRIVE_FILE">: Google
		Drive ( Read & Write, Only opened or created with this app)<br> <input
			type="checkbox" name="scopes[]" value="DRIVE_PHOTOS_READONLY">:
		Google Photes ( Read only )<br> <input type="checkbox" name="scopes[]"
			value="DRIVE_APPS_READONLY">: Google Appss ( Read only )
	</p>
	<h4>Continuous connectivity</h4>
	<div>Are you need refresh token?</div>
	<input type="radio" name="offline" value="1" checked="checked"> Yes
	&nbsp;|&nbsp; <input type="radio" name="offline" value="0"> No
	(Expiration time: 1 hour)
	<h4>Revoke previous authentication</h4>
	<input type="checkbox" name="revoke" value="1">: Revoke
	<p>
		<input type="submit" value="Get authentication link">
	</p>
	<input type="hidden" name="auth" value="1">
</form>
<?php
	}
} else {
?>
<p>GoogleDrive Driver Require PHP >= 5.4 . Your PHP version is <?php echo PHP_VERSION; ?> .</p>
<?php
}
xoops_cp_footer ();
