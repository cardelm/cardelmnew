<?php

$indata = addslashes($_GET['indata']);
$sign = addslashes($_GET['sign']);
$apiaction = addslashes($_GET['apiaction']);


$outdata = array();

if($sign != md5(md5($indata))) {
	$outdata[] = lang('plugin/cardelmserver','api_sign_error');
}
if(!$apiaction) {
	$outdata[] = lang('plugin/cardelmserver','api_apiaction_error');
}


$indata = base64_decode($indata);
$indata = dunserialize($indata);




if (count($outdata)==0){
	if ($apiaction == 'install'){//系统安装，分几种情况1、初次安装；2、白名单；3、黑名单；4、非法安装
		$site_info = DB::fetch_first("SELECT * FROM ".DB::table('cardelm_site')." WHERE siteurl='".$indata['siteurl']."'");
		if($site_info['status']==0) {
			$outdata['error'] = $site_info['sitekey'];
		}else{
			$insertdata['salt'] = random(6);
			$insertdata['sitekey'] = md5(md5($indata['siteurl']).$insertdata['salt']);
			$insertdata['charset'] = $indata['charset'];
			$insertdata['clientip'] = $indata['clientip'];
			$insertdata['version'] = $indata['version'];
			$insertdata['siteurl'] = $indata['siteurl'];
			$insertdata['installtime'] = time();
			$outdata = $insertdata['sitekey'];
			DB::insert('cardelm_site',$insertdata);
		}

	}elseif($apiaction == 'getmokuailist'){
		//$site_info = DB::fetch_first("SELECT * FROM ".DB::table('cardelmserver_site')." WHERE siteurl='".$indata['siteurl']."'");
		//$mokuais = dunserialize($site_info['mokuais']);
		$query = DB::query("SELECT * FROM ".DB::table('cardelmserver_mokuai')." order by mokuaiid asc");
		while($row = DB::fetch($query)) {
			$outdata[$row['mokuaiid']] = $row;
		}

	}elseif($apiaction == 'getserverdist'){
		$prov = intval($indata['prov']);
		$city = intval($indata['city']);
		if($city){
			$outdata = '<select name="dist"><option value="0">县区</option>';
			$query = DB::query("SELECT * FROM ".DB::table('common_district')." WHERE upid = ".$city." order by displayorder asc");
			while($row = DB::fetch($query)) {
				$outdata .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			$outdata .= '</select>';
		}
		if($prov){
			$outdata = '<select name="city" onchange="ajaxget(\'plugin.php?id=yiqixueba&submod=ajax&ajaxtype=getserverdist&city=\'+ this.value, \'dist\');"><option value="0">城市</option>';
			$query = DB::query("SELECT * FROM ".DB::table('common_district')." WHERE upid = ".$prov." order by displayorder asc");
			while($row = DB::fetch($query)) {
				$outdata .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			$outdata .= '</select><br /><span id="dist" class="xi1"></span>';
		}
		if(!$prov && !$city){
			$outdata = '<select name="prov" onchange="ajaxget(\'plugin.php?id=yiqixueba&submod=ajax&ajaxtype=getserverdist&prov=\'+ this.value, \'city\');"><option value="0">省份</option>';
			$query = DB::query("SELECT * FROM ".DB::table('common_district')." WHERE upid = 0 order by displayorder asc");
			while($row = DB::fetch($query)) {
				$outdata .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			$outdata .= '</select><br /><span id="city" class="xi1"></span>';
		}
	}elseif($apiaction == 'getweixinsetting'){
		$site_info = DB::fetch_first("SELECT * FROM ".DB::table('cardelmserver_site')." WHERE siteurl ='".$indata['siteurl']."' AND sitekey = '".$indata['sitekey']."'");
		if($site_info){
			if(DB::result_first("SELECT count(*) FROM ".DB::table('cardelmserver_wxq123_member')." WHERE membertype='site' AND typeid = ".$site_info['siteid'])==0){
				$insertdata['shibiema'] = random(4,1);
				$insertdata['token'] = random(6);
				$insertdata['membertype'] = 'site';
				$insertdata['typeid'] = $site_info['siteid'];
				DB::insert('cardelmserver_wxq123_member', $insertdata);
			}
			$weixin_info = DB::fetch_first("SELECT * FROM ".DB::table('cardelmserver_wxq123_member')." WHERE membertype='site' AND typeid = ".$site_info['siteid']);
			$outdata['shibiema'] = $weixin_info['shibiema'];
			$outdata['token'] = $weixin_info['token'];
		}
		//$outdata = $indata;

	//}elseif($apiaction == 'install'){
	//}elseif($apiaction == 'install'){
	//}elseif($apiaction == 'install'){
	//}elseif($apiaction == 'install'){
	//}elseif($apiaction == 'install'){
	//}elseif($apiaction == 'install'){
	//}elseif($apiaction == 'install'){
	}







//	if($indata['sitekey']) {
//	}else{
//		if($indata['siteurl']){
//			if (DB::result_first("SELECT count(*) FROM ".DB::table('cardelmserver_site')." WHERE siteurl='".$indata['siteurl']."'")==0){
//				//DB::insert('cardelmserver_site',$data);
//			}
//		}else{
//		}
//	}
}

if(is_array($outdata)){
	require_once libfile('class/xml');
	$filename = $apiaction.'.xml';
	$plugin_export = array2xml($outdata, 1);
	ob_end_clean();
	dheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	dheader('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	dheader('Cache-Control: no-cache, must-revalidate');
	dheader('Pragma: no-cache');
	dheader('Content-Encoding: none');
	dheader('Content-Length: '.strlen($plugin_export));
	dheader('Content-Disposition: attachment; filename='.$filename);
	dheader('Content-Type: text/xml');
	echo $plugin_export;
	define('FOOTERDISABLED' , 1);
	exit();
}else{
	echo $outdata;
}

?>