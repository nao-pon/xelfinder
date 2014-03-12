<?php
function xelfinderAdminDropboxGetAuthorizeUrl(&$tokens, $oauth, $mydirname) {
	try {
		$tokens = $oauth->getRequestToken();
		$url = rawurlencode(XOOPS_MODULE_URL.'/'.$mydirname.'/admin/index.php?page=dropbox');
		return $oauth->getAuthorizeUrl($url);
	} catch (Exception $e) {
		return false;
	}
}

// Add PEAR Dirctory into include path
$incPath = get_include_path();
$addPath = XOOPS_TRUST_PATH . '/PEAR';
if (strpos($incPath, $addPath) === FALSE) {
	set_include_path( $incPath . PATH_SEPARATOR . $addPath );
}

$config = $xoopsModuleConfig;
$state_key = $mydirname.'admin_dropbox_state';

if (isset($_GET['remake'])) {
	unset($_SESSION[$state_key]);
}

$state = 0;
$oauth = null;

if (!empty($config['dropbox_token']) && !empty($config['dropbox_seckey'])) {

	if (isset($_SESSION[$state_key])) {
		$state = (int)$_SESSION[$state_key];
	}

	@include_once 'Dropbox/autoload.php';
	
	if (class_exists('OAuth')) {
		try {
			$oauth = new Dropbox_OAuth_PHP($config['dropbox_token'], $config['dropbox_seckey']);
		} catch (Exception $e) {
			$state = 0;
		}
	} else {
		if (! class_exists('HTTP_OAuth_Consumer')) {
			@include 'HTTP/OAuth/Consumer.php';
		}
		if (class_exists('HTTP_OAuth_Consumer')) {
			try {
				$oauth = new Dropbox_OAuth_PEAR($config['dropbox_token'], $config['dropbox_seckey']);
			} catch (Exception $e) {
				$state = 0;
			}
		}
	}
	
	if (!empty($config['dropbox_acc_token']) && !empty($config['dropbox_acc_seckey'])) {
		$_SESSION[$state_key.'_tokens'] = array(
			'token'        => $config['dropbox_acc_token'],
			'token_secret' => $config['dropbox_acc_seckey']
		);
		$state = 3;
	}
	
	if ($state > 2 && isset($_SESSION[$state_key.'_tokens'])) {
		// acc token check
		try {
			$oauth->setToken($_SESSION[$state_key.'_tokens']);
			$dropbox = new Dropbox_API($oauth);
			$dropbox->getAccountInfo();
		} catch (Dropbox_Exception $e) {
			unset($_SESSION[$state_key], $_SESSION[$state_key.'_tokens']);
			$state = 0;
		}
	}
}

if ($state) {
	$dropbox = new Dropbox_API($oauth);
	switch($state) {
		case 1:
			$tokens = array();
			if (! $url = xelfinderAdminDropboxGetAuthorizeUrl($tokens, $oauth, $mydirname)) {
				$state = 0;
			}
			break;
		case 2:
			try {
				$oauth->setToken($_SESSION[$state_key.'_tokens']);
				$tokens = $oauth->getAccessToken();
			} catch (Exception $e) {
				$state = 1;
				$tokens = array();
				if (! $url = xelfinderAdminDropboxGetAuthorizeUrl($tokens, $oauth, $mydirname)) {
					$state = 0;
				}
			}
		default:
	}
}

if (!$state) {
	if ($config['dropbox_token'] && $config['dropbox_seckey']) {
		$state = 1;
		$tokens = array();
		if (! $url = xelfinderAdminDropboxGetAuthorizeUrl($tokens, $oauth, $mydirname)) {
			$state = 0;
		}
	} else {
		$state = 0;
	}
}

xoops_cp_header();
include dirname(__FILE__).'/mymenu.php' ;

echo '<h3>'.xelfinderAdminLang('DROPBOX_GET_TOKEN').'</h3>' ;

switch($state) {
	case 1 :
		echo '<h4>'.xelfinderAdminLang('DROPBOX_STEP2').'</h4>';
		echo '<p>'.xelfinderAdminLang('DROPBOX_GOTO_CONFIRM').'</p>';
		echo "<p><a href='$url'>".xelfinderAdminLang('DROPBOX_CONFIRM_LINK')."</a></p>";
		$_SESSION[$state_key] = 2;
		$_SESSION[$state_key.'_tokens'] = $tokens;
		break;

	case 2 :
		echo '<h4>'.xelfinderAdminLang('DROPBOX_STEP3').'</h4>';
		$_SESSION[$state_key] = 3;
		$_SESSION[$state_key.'_tokens'] = $tokens;

	case 3 :
		$token_cap = xelfinderAdminLang('DROPBOX_ACC_TOKEN');
		$token_secret_cap = xelfinderAdminLang('DROPBOX_ACC_SECKEY');
		echo '<p>'.xelfinderAdminLang('DROPBOX_SET_PREF').'</p>';
		$token = $_SESSION[$state_key.'_tokens']['token'];
		$token_secret = $_SESSION[$state_key.'_tokens']['token_secret'];
		echo "
<table class=\"outer\">
	<tr>
		<td class=\"head\">{$token_cap}</td>
		<td class=\"even\">{$token}</td>
	</tr>
	<tr>
		<td class=\"head\">{$token_secret_cap}</td>
		<td class=\"even\">{$token_secret}</td>
	</tr>
</table>";
		break;

	case 0:
	default:
		unset($_SESSION[$state_key]);
		$cap = xelfinderAdminLang('DROPBOX_STEP1');
		$desc = sprintf(xelfinderAdminLang('DROPBOX_GOTO_APP'), xelfinderAdminLang('DROPBOX_TOKEN'), xelfinderAdminLang('DROPBOX_SECKEY'));
		echo "
<h4>$cap</h4>
<p>$desc</p>
<p><a href=\"https://www.dropbox.com/developers/apps\" target=\"_blank\">App Console - Dropbox</a></p>";
		break;
}

xoops_cp_footer();