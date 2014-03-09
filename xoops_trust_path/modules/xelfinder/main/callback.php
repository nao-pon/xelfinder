<?php
$checkReg = '/[^a-zA-Z0-9;._-]/';
$node = (isset($_GET['node']) && !preg_match($checkReg, $_GET['node']))? $_GET['node'] : '';
$bind = (isset($_GET['bind']) && !preg_match($checkReg, $_GET['bind']))? $_GET['bind'] : '';
$json = (isset($_GET['json']) && @json_decode($_GET['json']))? $_GET['json'] : '{}';

if ($node &&  $json) {
	$script = '
		var elf=window.opener.document.getElementById(\''.$node.'\').elfinder;
		var data = '.$json.';
		data.warning && elf.error(data.warning);
		data.removed && data.removed.length && elf.remove(data);
		data.added   && data.added.length   && elf.add(data);
		data.changed && data.changed.length && elf.change(data);';
	if ($bind) {
		$script .= '
		elf.trigger(\''.$bind.'\', data);';
	}
	$script .= '
		data.sync && elf.sync();
		window.close();';
} else {
	$script = 'window.close();';
}
$out = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><script>'.$script.'</script></head><body><a href="#" onlick="window.close();return false;">Close this window</a></body></html>';
 
while( ob_get_level() ) {
	if (! ob_end_clean()) {
		break;
	}
}
 
header('Content-Type: text/html; charset=utf-8');
header('Content-Length: '.strlen($out));
header('Cache-Control: private');
header('Pragma: no-cache');
echo $out;
 
exit();
