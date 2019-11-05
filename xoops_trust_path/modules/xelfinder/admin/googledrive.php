<?php

$php54up = false;
$vendor =false;

if ($php54up = version_compare(PHP_VERSION, '5.4.0', '>=')) {
	if (include_once $mytrustdirpath . '/plugins/vendor/autoload.php') {
		$vendor = true;
		$selfURL = XOOPS_MODULE_URL . '/' . $mydirname . '/admin/index.php?page=googledrive';
		$sessTokenKey = $mydirname . 'AdminGoogledriveToken';
		$sessClientKey = $mydirname . 'AdminGoogledriveClientKey';
		$client = null;
		$clientId = $clientSecret = '';
		$config = $xoopsModuleConfig;
		
		if (! empty($_POST['json'])) {
			$json = @json_decode($_POST['json'], true);
			if ($json && isset($json['web'])) {
				$clientId = @$json['web']['client_id'];
				$clientSecret = @$json['web']['client_secret'];
			}
		}
		if (! empty($_POST['ClientId']) && ! empty($_POST['ClientSecret'])) {
			$clientId = trim($_POST['ClientId']);
			$clientSecret = trim($_POST['ClientSecret']);
		} else {
			if (isset($config['googleapi_id'])) {
				$clientId = $config['googleapi_id'];
			}
			if (isset($config['googleapi_secret'])) {
				$clientSecret = $config['googleapi_secret'];
			}
		}

		if ($clientId && $clientSecret) {
			$_SESSION[$sessClientKey] = array (
				'ClientId' => $clientId,
				'ClientSecret' => $clientSecret
			);
		} elseif (isset($_SESSION[$sessClientKey])) {
			$clientId = $_SESSION[$sessClientKey]['ClientId'];
			$clientSecret = $_SESSION[$sessClientKey]['ClientSecret'];
		}

		if (! empty($_SESSION[$sessClientKey]) && !isset($_GET ['start'])) {
			
			$client = new \Google_Client();
			$client->setClientId($_SESSION[$sessClientKey]['ClientId']);
			$client->setClientSecret($_SESSION[$sessClientKey]['ClientSecret']);
			$client->setRedirectUri($selfURL);
			
			$service = new \Google_Service_Drive($client);
			if (isset($_GET['code'])) {
				$client->authenticate($_GET['code']);
				$_SESSION[$sessTokenKey] = $client->getAccessToken();
			}
			
			if (isset($_SESSION[$sessTokenKey]) && isset($_SESSION[$sessTokenKey]['access_token'])) {
				$client->setAccessToken($_SESSION[$sessTokenKey]);
			}
		}
	}
}

xoops_cp_header();
include dirname(__FILE__) . '/mymenu.php';

echo '<h3>' . xelfinderAdminLang('GOOGLEDRIVE_GET_TOKEN') . '</h3>';

if ($php54up && $vendor) {
	$form = true;
	if ($client) {
		if (empty($_POST) && $client->getAccessToken()) {
			try {
				$aToken = $client->getAccessToken();
				$token = array (
					'client_id' => $client->getClientId(),
					'client_secret' => $client->getClientSecret(),
					'access_token' => $aToken['access_token']
				);
				if (isset($aToken['refresh_token'])) {
					unset($token['access_token']);
					$token['refresh_token'] = $aToken['refresh_token'];
				}
				$ext_token = json_encode($token);
				echo '<h3>Google Drive API Token</h3>';
				echo '<div><textarea class="allselect" style="width:70%;height:5em;" spellcheck="false">' . $ext_token . '</textarea></div>';
				echo '<h3>Example to Volume Driver Setting</h3>';
				echo '<div><p>Folder ID as root: <input type=text id="xelfinder_googledrive_folder" value="root"></input> "root" is <a href="https://drive.google.com/drive/my-drive" target="_blank">"My Drive" of your Google Drive</a>.</p>';
				echo '<p>You can find the folder ID to the URL(folders/[Folder ID]) of the site of <a href="https://drive.google.com/drive/">GoogleDrive</a>.</p></div>';
				echo '<div><textarea class="allselect" style="width:70%;height:7em;" id="xelfinder_googledrive_volconf" spellcheck="false">xelfinder:GoogleDrive:root:GoogleDrive:gid=1|id=gd|ext_token=' . $ext_token . '</textarea></div>';
				echo "<script>(function($){
					$('#xelfinder_googledrive_folder').on('change keyup mouseup paste', function(e) {
						var self = $(this);
						setTimeout(function(){
							var conf = $('#xelfinder_googledrive_volconf');
								data = conf.val();
							conf.val(data.replace(/GoogleDrive:[^:]*:/, 'GoogleDrive:' + self.val() + ':'));
						}, e.type === 'paste'? 100 : 0);
					});
					$('textarea.allselect').on('focus', function() { $(this).select(); });
				})(jQuery);</script>";
				echo '<h4><a href="' . $selfURL . '&start">Reauthorization</a></h4>';
				$form = false;
			} catch(Google_Exception $e) {
				echo $e->getMessage();
			}
		} elseif (! empty($_POST['scopes'])) {
			if (! empty($_POST['revoke'])) {
				$client->revokeToken();
			}
			$scopes = array();
			foreach($_POST['scopes'] as $scope) {
				switch ($scope) {
					case 'DRIVE' :
					case 'DRIVE_READONLY' :
					case 'DRIVE_FILE' :
					case 'DRIVE_PHOTOS_READONLY' :
					case 'DRIVE_APPS_READONLY' :
						$scopes[] = constant('Google_Service_Drive::' . $scope);
				}
			}
			$client->setScopes($scopes);
			if (! empty($_POST['offline'])) {
				$client->setApprovalPrompt('force');
				$client->setAccessType('offline');
			}
			$authUrl = $client->createAuthUrl();
			echo '<a href="' . $authUrl . '">Please allow the application access.</a>';
			$form = false;
		}
	}
	if ($form) {
?>
<h3>Authentication options</h3>
<hr>
<h4>Step by step</h4>
<ol>
	<li>Make Project in Google Developers Console (<a href="https://console.developers.google.com/apis/dashboard" target="_brank">console.developers.google.com/apis/dashboard</a>)</li>
	<li>Enable Drive API</li>
	<li>Make Authentication  infomation (Type of Web Server & User data)</li>
	<li>Make OAuth 2.0 Client<br />(Redirect URI: <?php echo $selfURL; ?> )</li>
	<li>Get JSON and Paste it next TextArea</li>
	<li>And Click "Get authentication link"</li>
	<li>Then Approve this app in your account</li>
</ol>
<form action="index.php?page=googledrive" method="post">
	<h4>Web client ID and Secret key</h4>
	<p>
		JSON: <textarea name="json"></textarea>
	</p>
	<p><strong>OR</strong></p>
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
		Google Drive(Read & Write)<br> <input type="checkbox"
			name="scopes[]" value="DRIVE_READONLY">: Google Drive(Read only)<br>
		<input type="checkbox" name="scopes[]" value="DRIVE_FILE">: Google
		Drive(Read & Write, Only opened or created with this app)<br> <input
			type="checkbox" name="scopes[]" value="DRIVE_PHOTOS_READONLY">:
		Google Photes(Read only)<br> <input type="checkbox" name="scopes[]"
			value="DRIVE_APPS_READONLY">: Google Appss(Read only)
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
} else if ($php54up) {
?>
<p>GoogleDrive Driver needs to perform "<a href="./index.php?page=vendorup"><?php echo xelfinderAdminLang('ADMENU_VENDORUPDATE') ?></a>". Run it to see the contents of this menu.</p>
<?php
} else {
?>
<p>GoogleDrive Driver Require PHP >= 5.4 . Your PHP version is <?php echo PHP_VERSION; ?> .</p>
<?php
}
xoops_cp_footer();
