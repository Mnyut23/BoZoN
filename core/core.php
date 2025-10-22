<?php 
	/**
	* BoZoN core part:
	* Sets Constants, language, directories, htaccess and bozon's behaviour (files to download and files to echo)
	* @author: Bronco (bronco@warriordudimanche.net)
	**/
	
	# INIT SESSIONS VARS AND ENVIRONMENT
	define('VERSION','2.4 (build 18)');
	
	start_session();
	$message='';

	#################################################
	# Prepare or load config   
	#################################################	
	include('config.php');
	if (!$root){$root=getRacine();}	
	define('ROOT',$root);
	if (empty($_SESSION['private_folder'])){$_SESSION['private_folder']=$default_private;}
	if (empty($_SESSION['config_file'])){	$_SESSION['config_file']=$default_config_file;}
	if (!is_dir($_SESSION['private_folder'])){mkdir($_SESSION['private_folder'],0744);}
	if (!is_file($_SESSION['config_file'])){$_SESSION['config']=extract_config_vars();save_config($_SESSION['config']);}
	elseif (empty($_SESSION['config'])){	$_SESSION['config']=load_config();}

	#################################################
	# secure get data / post data / $_SESSION data / $_FILES
	#################################################
	if (!empty($_GET)){$_GET=array_map('deep_strip_tags',$_GET);}
	if (!empty($_POST)){$_POST=array_map('deep_strip_tags',$_POST);}
	if (!empty($_SESSION)){$_SESSION=array_map('deep_strip_tags',$_SESSION);}
	if (!empty($_FILES)){$_FILES=array_map('deep_strip_tags',$_FILES);}
	#################################################

	# locale
	if (!conf('language')){conf('language',$default_language);}
	if (is_file('locale/'.conf('language').'.php')){include('locale/'.conf('language').'.php');}else{$lang=array();}
	# Aspect config
	if (!conf('aspect')){conf('aspect',$default_aspect);}
	if (!conf('theme')){conf('theme',$default_theme);}
	if (!conf('mode')){conf('mode',$default_mode);}

	# SESSION VARS
	# System vars
	if (empty($_SESSION['api_rss_key'])&&!empty($_SESSION['login'])){$_SESSION['api_rss_key']=hash_user($_SESSION['login']);}
	if (!conf('stats_max_entries')){conf('stats_max_entries',$default_limit_stat_file_entries);}
	if (!conf('stats_max_lines')){conf('stats_max_lines',$default_max_lines_per_page_on_stats_page);}
	if (empty($_SESSION['private_folder'])){$_SESSION['private_folder']=$default_private;}
	if (empty($_SESSION['zip'])){$_SESSION['zip']=class_exists('ZipArchive');}
	if (empty($_SESSION['curl'])){$_SESSION['curl']=function_exists('curl_init');}
	if (empty($_SESSION['GD'])){$_SESSION['GD']=function_exists('imagecreatetruecolor');}
	if (empty($_SESSION['home'])){$_SESSION['home'] =getRacine();}	
	if (empty($_SESSION['clean_temp_folder_time'])){$_SESSION['clean_temp_folder_time'] =$clean_temp_folder_time;}	
	if (empty($_SESSION['temp_folder'])){$_SESSION['temp_folder'] = $default_temp_folder;}	
	if (empty($_SESSION['root'])){$_SESSION['root']=ROOT;}
	if (empty($_SESSION['id_file'])){$_SESSION['id_file']=$default_id_file;}
	if (empty($_SESSION['folder_share_file'])){$_SESSION['folder_share_file']=$default_folder_share_file;}
	if (empty($_SESSION['stats_file'])){$_SESSION['stats_file']=$default_stat_file;}
	if (!conf('max_files_per_page')){conf('max_files_per_page',$default_max_files_per_page);}
	if (!isset($_SESSION['current_path'])){$_SESSION['current_path']="";}
	if (!isset($_SESSION['users_rights_file'])){$_SESSION['users_rights_file']=$default_users_rights_file;}
	if (empty($_SESSION['upload_root_path'])){$_SESSION['upload_root_path']=addslash_if_needed($default_path);}
	if (empty($_SESSION['upload_user_path'])&&!empty($_SESSION['login'])){$_SESSION['upload_user_path']=$_SESSION['login'].'/';}
	if (empty($_SESSION['profiles_rights_file'])){$_SESSION['profiles_rights_file']=$default_profiles_rights_file;}
	
	# Check system folders
	if (!is_dir('thumbs/')){mkdir('thumbs/');}
	if (!is_dir($_SESSION['temp_folder'])){ mkdir($_SESSION['temp_folder'],0744, true); }
	if (!is_dir($_SESSION['upload_root_path'])){ mkdir($_SESSION['upload_root_path'],0744, true); }
	if (!empty($_SESSION['upload_user_path'])&&!is_dir($_SESSION['upload_root_path'].$_SESSION['upload_user_path'])){ mkdir($_SESSION['upload_root_path'].$_SESSION['upload_user_path'],0744, true); }
	if (!empty($_SESSION['upload_user_path'])&&!is_dir('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'])){ mkdir('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'],0744, true); }
	if (!is_dir('thumbs/'.$_SESSION['upload_root_path'])){mkdir('thumbs/'.$_SESSION['upload_root_path']);}
	if (!is_dir($_SESSION['private_folder'].'trees')){mkdir($_SESSION['private_folder'].'trees',0744);}
	if (!isset($_SESSION['profile_folder_max_size'])&&isset($_SESSION['status'])&&$_SESSION['status']!='superadmin'){
		if (isset($_SESSION['login'])&&isset($_SESSION['users_rights'][$_SESSION['login']])){
			$_SESSION['profile_folder_max_size']=$_SESSION['users_rights'][$_SESSION['login']];
		}elseif (isset($_SESSION['login'])){
			complete_users_rights();
			$_SESSION['profile_folder_max_size']=$default_profile_folder_max_size;
		}
	}else{$_SESSION['profile_folder_max_size']=$default_profile_folder_max_size;}
	if (!isset($_SESSION['temp_cleaned'])||time()>$_SESSION['temp_cleaned']+$_SESSION['clean_temp_folder_time']){
		# clean temp once in a session
		$files=_glob($_SESSION['temp_folder'],'');
		foreach($files as $file){
			if (time()>filectime($file)+$_SESSION['clean_temp_folder_time']){unlink($file);}
		}
		$_SESSION['temp_cleaned']=time();
	}

	# Check R/W rights for system folders
	if (!is_writable($_SESSION['private_folder'])){echo '<p class="error">'.e('Private folder is not writable',false).'</p>';}
	if (!is_readable($_SESSION['private_folder'])){echo '<p class="error">'.e('Private folder is not readable',false).'</p>';}
	if (!is_writable($_SESSION['temp_folder'])){echo '<p class="error">'.e('Temp folder is not writable',false).'</p>';}
	if (!is_readable($_SESSION['temp_folder'])){echo '<p class="error">'.e('Temp folder is not readable',false).'</p>';}
	if (!is_readable($_SESSION['private_folder'].'trees')){$message.='<div class="error">'.e('Problem accessing tree folder: not readable',false).'</label>';}
	if (!is_writable($_SESSION['private_folder'].'trees')){$message.='<div class="error">'.e('Problem accessing tree/folder file: not writable',false).'</label>';}
	if (!empty($_SESSION['upload_user_path'])&&!is_readable($_SESSION['upload_root_path'].$_SESSION['upload_user_path'].$_SESSION['current_path'])){$message.='<div class="error">'.e('Problem accessing ',false).$_SESSION['current_path'].e(': folder not readable',false).'</label>';}
	if (!empty($_SESSION['upload_user_path'])&&!is_writable($_SESSION['upload_root_path'].$_SESSION['upload_user_path'].$_SESSION['current_path'])){$message.='<div class="error">'.e('Problem accessing ',false).$_SESSION['current_path'].e(': folder not writable',false).'</label>';}

	# Check necessary libs
	if(!$disable_non_installed_libs_warning){
		if (!$_SESSION['zip']){$message.='<div class="error">ZipArchive '.e('is not installed on this server',false).' <a   href="http://php.net/manual/'.conf('language').'/zip.setup.php">'.e('More info',false).'</a></div>';}
		if (!$_SESSION['GD']){$message.='<div class="error">GD '.e('is not installed on this server',false).' <a   href="http://php.net/manual/'.conf('language').'/image.installation.php">'.e('More info',false).'</a></div>';}
		if (!$_SESSION['curl']){$message.='<div class="error">Curl '.e('is not installed on this server',false).' <a   href="http://php.net/manual/'.conf('language').'/curl.setup.php">'.e('More info',false).'</a></div>';}
		}
	# Check files
	if (!is_file('thumbs/'.$_SESSION['upload_root_path'].'.htaccess')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].'.htaccess', 'deny from all');}
	if (!is_file('thumbs/'.$_SESSION['upload_root_path'].'index.html')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].'index.html',' ');}
	if (!is_file($_SESSION['temp_folder'].'.htaccess')){
		file_put_contents($_SESSION['temp_folder'].'.htaccess', '
Order allow,deny
Deny from all
<FilesMatch \.zip>
    Allow from all
</FilesMatch>
		');
	}
	if (!is_file($_SESSION['upload_root_path'].'index.html')){ file_put_contents($_SESSION['upload_root_path'].'index.html',' '); }
	if (!is_file('thumbs/.htaccess')){file_put_contents('thumbs/.htaccess', 'deny from all');}
	if (!is_file('thumbs/index.html')){file_put_contents('thumbs/index.html',' ');}
	if (!empty($_SESSION['upload_user_path'])&&!is_file('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'.htaccess')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'.htaccess', 'deny from all');}
	if (!empty($_SESSION['upload_user_path'])&&!is_file('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'index.html')){file_put_contents('thumbs/'.$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].'index.html',' ');}

	if (!is_file($_SESSION['private_folder'].'.htaccess')){file_put_contents($_SESSION['private_folder'].'.htaccess', 'deny from all');}
	if (!is_file($_SESSION['private_folder'].'index.html')){file_put_contents($_SESSION['private_folder'].'index.html',' ');}
	
	if (!is_file($_SESSION['folder_share_file'])){save_folder_share(array());}
	if (!is_file($_SESSION['private_folder'].'salt.php')){ file_put_contents($_SESSION['private_folder'].'salt.php','<?php define("BOZON_SALT",'.var_export(generate_bozon_salt(),true).'); ?>'); }
	else{include($_SESSION['private_folder'].'salt.php');}
	if (!is_file($_SESSION['id_file'])){$ids=array();store($ids);}
	if (!is_file($_SESSION['stats_file'])){save($_SESSION['stats_file'], array());}
	if (!is_file($_SESSION['upload_root_path'].'.htaccess')){file_put_contents($_SESSION['upload_root_path'].'.htaccess', 'deny from all');}
	if (!is_file($_SESSION['users_rights_file'])){save_users_rights(array());$_SESSION['users_rights']=array();}
	else{$_SESSION['users_rights']=load_users_rights();}
	if (!is_file($_SESSION['profiles_rights_file'])){save($_SESSION['profiles_rights_file'],array());	}
	if (is_file($_SESSION['id_file'])&&!is_readable($_SESSION['id_file'])){$message.='<div class="error">'.e('Problem accessing ID file: not readable',false).'</label>';}
	if (is_file($_SESSION['id_file'])&&!is_writable($_SESSION['id_file'])){$message.='<div class="error">'.e('Problem accessing ID file: not writable',false).'</label>';}
	if (is_file($_SESSION['stats_file'])&&!is_readable($_SESSION['stats_file'])){$message.='<div class="error">'.e('Problem accessing stats file: not readable',false).'</label>';}
	if (is_file($_SESSION['stats_file'])&&!is_writable($_SESSION['stats_file'])){$message.='<div class="error">'.e('Problem accessing stats file: not writable',false).'</label>';}

	# Libs configuration
	# Files to echo in browser (secured) 
	$behaviour['FILES_TO_ECHO']=explode(',',conf('files_to_echo'));
	# Files to send to browser directly 
	$behaviour['FILES_TO_RETURN']=explode(',',conf('files_to_return'));
 	$auto_dropzone['destination_filepath']=addslash_if_needed($_SESSION['current_path']);
	$auto_thumb['default_width']='64';
	$auto_thumb['default_height']='64';
	$auto_thumb['dont_try_to_resize_thumbs_files']=true;

	# CONSTANTS & GLOBALS
	define('THEME_PATH','templates/'.conf('theme').'/');
	$ACTIONS=array('users page','add user','delete user','change user status','change folder size','change status rights','change passes','markdown editor','regen ID base','acces logfile','config page','upload','delete files','create folder','rename files','move files','import');
	$RIGHTS=load($_SESSION['profiles_rights_file']);
		
	if (empty($RIGHTS)){ #default profiles if not configured
		$PROFILES=array('admin','user','guest');
		$RIGHTS['admin']=array('add user'=>1,'delete user'=>1,'change folder size'=>1,'markdown editor'=>1,'regen ID base'=>1,'access logfile'=>1,'upload'=>1,'delete files'=>1,'create folder'=>1,'rename files'=>1,'move files'=>1,'import'=>1);
		$RIGHTS['user']=array('markdown editor'=>1,'upload'=>1,'delete files'=>1,'create folder'=>1,'rename files'=>1,'move files'=>1,'import'=>1);
		$RIGHTS['guest']=array('upload'=>1,'create folder'=>1,'rename files'=>1,'move files'=>1);
		save($_SESSION['profiles_rights_file'],$RIGHTS);
	}else{$PROFILES=array_filter(array_keys($RIGHTS));}

	include('core/templates.php');


	#################################################
	# Load IDS  
	#################################################	
	if (is_user_connected()&&$check_ID_base_on_page_load){
		$ids=updateIDs();
	}else{$ids=unstore();}
	#################################################
	#################################################
	# Load current path tree   
	#################################################	
	if (is_user_connected()){
		$tree=tree($_SESSION['upload_root_path'].$_SESSION['upload_user_path'].$_SESSION['current_path'],false,false,$allow_folder_size_stat);
	}
	#################################################


	#################################################
	# Functions 
	#################################################
	# Data save/load & files
	#################################################
        function load($file){
                if (!is_file($file)){
                        return array();
                }

                $raw=file_get_contents($file);
                if ($raw===false){
                        return array();
                }

                if (strncmp($raw,'<?php /* ',9)!==0 || substr($raw,-6)!==' */ ?>'){
                        return array();
                }

                $payload=substr($raw,9,-6);
                if ($payload===false || $payload===''){
                        return array();
                }

                $decoded=base64_decode($payload,true);
                if ($decoded===false || $decoded===''){
                        return array();
                }

                if (strlen($decoded)>10485760){
                        return array();
                }

                $inflated=@gzinflate($decoded);
                if ($inflated===false || $inflated===''){
                        return array();
                }

                try{
                        $data=@unserialize($inflated,array('allowed_classes'=>false));
                }catch(\Throwable $exception){
                        return array();
                }

                return (is_array($data)?$data:array());
        }
        function save($file,$data){
                if (!is_array($data)){
                        return false;
                }

                $serialized=serialize($data);
                $compressed=gzdeflate($serialized);
                if ($compressed===false){
                        return false;
                }

                $encoded=base64_encode($compressed);
                if ($encoded===false){
                        return false;
                }

                return file_put_contents($file,'<?php /* '.$encoded.' */ ?>',LOCK_EX);
        }
	function store($ids=null){if (!$ids){return false;}natcasesort($ids);return save($_SESSION['id_file'],$ids);}
	function unstore(){return array_filter(load($_SESSION['id_file']));}
        function share_storage_defaults(){
                return array('shares'=>array());
        }

        function save_folder_share($array=null){
                if (!is_array($array)){
                        $array=share_storage_defaults();
                }

                if (empty($array['shares'])||!is_array($array['shares'])){
                        $array['shares']=array();
                }

                $result=save($_SESSION['folder_share_file'],$array);
                load_folder_share(true);

                return $result;
        }

        function load_folder_share($force_refresh=false){
                static $cache=null;

                if ($force_refresh){
                        $cache=null;
                }

                if ($cache!==null){
                        return $cache;
                }

                $raw=load($_SESSION['folder_share_file']);
                if (!is_array($raw)){
                        $raw=share_storage_defaults();
                }

                $dirty=false;
                $normalized=share_storage_normalize($raw,$dirty);

                if ($dirty){
                        save($_SESSION['folder_share_file'],$normalized);
                        $cache=$normalized;
                        return $cache;
                }

                $cache=$normalized;
                return $cache;
        }

        function share_storage_normalize($raw,&$dirty){
                $dirty=false;

                if (!isset($raw['shares'])||!is_array($raw['shares'])){
                        $converted=array();
                        foreach ($raw as $recipient=>$items){
                                if (!is_array($items)){
                                        continue;
                                }

                                foreach ($items as $id=>$entry){
                                        if (!is_string($id)||$id===''){
                                                $dirty=true;
                                                continue;
                                        }

                                        if (!isset($converted[$id])){
                                                $converted[$id]=array(
                                                        'owner'=>(isset($entry['owner'])&&is_string($entry['owner']))?$entry['owner']:'',
                                                        'path'=>(isset($entry['path'])&&is_string($entry['path']))?$entry['path']:(isset($entry['folder'])&&is_string($entry['folder'])?$entry['folder']:''),
                                                        'token'=>null,
                                                        'created_at'=>time(),
                                                        'expires_at'=>null,
                                                        'recipients'=>array(),
                                                        'active'=>true,
                                                );
                                        }

                                        if (!empty($entry['from'])&&is_string($entry['from'])&&$converted[$id]['owner']===''){
                                                $converted[$id]['owner']=$entry['from'];
                                        }

                                        if (!empty($recipient)&&is_string($recipient)){
                                                $converted[$id]['recipients'][]=$recipient;
                                        }
                                }
                        }

                        $raw=array('shares'=>$converted);
                        $dirty=true;
                }

                $normalized=array('shares'=>array());
                foreach ($raw['shares'] as $id=>$entry){
                        if (!is_string($id)||$id===''){
                                $dirty=true;
                                continue;
                        }

                        $normalized_entry=share_normalize_entry($id,$entry,$dirty);
                        if ($normalized_entry){
                                $normalized['shares'][$id]=$normalized_entry;
                        }else{
                                $dirty=true;
                        }
                }

                return $normalized;
        }

        function share_normalize_entry($id,$entry,&$dirty){
                if (!is_array($entry)){
                        $dirty=true;
                        return null;
                }

                $path=id2file($id);
                if (!$path||(!is_dir($path)&&!is_file($path))){
                        if (!empty($entry['path'])&&is_string($entry['path'])&&(is_dir($entry['path'])||is_file($entry['path']))){
                                $path=$entry['path'];
                        }else{
                                return null;
                        }
                }

                $owner=return_owner($id);
                if (!$owner){
                        if (!empty($entry['owner'])&&is_string($entry['owner'])){
                                $owner=$entry['owner'];
                        }elseif (!empty($entry['from'])&&is_string($entry['from'])){
                                $owner=$entry['from'];
                        }else{
                                return null;
                        }
                }

                $active=true;
                if (isset($entry['active'])){
                        $active=(bool)$entry['active'];
                }else{
                        $dirty=true;
                }

                $token=null;
                if (!empty($entry['token'])&&is_string($entry['token'])){
                        $token=$entry['token'];
                }
                if ($active){
                        if (!$token){
                                $token=share_new_token();
                                $dirty=true;
                        }
                }else{
                        if ($token!==null){
                                $token=null;
                                $dirty=true;
                        }
                }

                $created=null;
                if (isset($entry['created_at'])&&is_numeric($entry['created_at'])){
                        $created=(int)$entry['created_at'];
                }
                if ($created===null||$created<=0){
                        $created=time();
                        $dirty=true;
                }

                $expires=null;
                if (array_key_exists('expires_at',$entry)){
                        if ($entry['expires_at']===null||$entry['expires_at']===''){
                                $expires=null;
                        }elseif (is_numeric($entry['expires_at'])){
                                $expires=(int)$entry['expires_at'];
                        }else{
                                $dirty=true;
                        }
                }else{
                        $dirty=true;
                }

                $recipients=array();
                if (!empty($entry['recipients'])&&is_array($entry['recipients'])){
                        foreach ($entry['recipients'] as $recipient){
                                if (is_string($recipient)){
                                        $recipient=trim($recipient);
                                        if ($recipient!==''){
                                                $recipients[$recipient]=true;
                                        }
                                }
                        }
                }

                $normalized=array(
                        'owner'=>(string)$owner,
                        'path'=>$path,
                        'token'=>$token,
                        'created_at'=>$created,
                        'expires_at'=>$expires,
                        'recipients'=>array_keys($recipients),
                        'active'=>$active,
                );

                if ($expires!==null&&$expires<=0){
                        $normalized['expires_at']=null;
                        $dirty=true;
                }

                return $normalized;
        }

        function share_is_expired($entry){
                if (empty($entry)||!is_array($entry)){
                        return true;
                }

                if (!empty($entry['expires_at'])&&is_int($entry['expires_at'])&&$entry['expires_at']>0){
                        return $entry['expires_at']<time();
                }

                return false;
        }

        function share_is_active($entry){
                if (empty($entry)||!is_array($entry)){
                        return false;
                }

                if (share_is_expired($entry)){
                        return false;
                }

                if (empty($entry['active'])){
                        return false;
                }

                return !empty($entry['token'])&&is_string($entry['token']);
        }

        function share_new_token(): string{
                try{
                        return bin2hex(random_bytes(32));
                }catch(\Throwable $exception){
                        if (function_exists('openssl_random_pseudo_bytes')){
                                $bytes=openssl_random_pseudo_bytes(32);
                                if ($bytes!==false){
                                        return bin2hex($bytes);
                                }
                        }

                        return bin2hex(pack('d',microtime(true)).pack('V',mt_rand()));
                }
        }

        function share_ensure_entry($id,$owner=null,$path=null){
                if (!is_string($id)||$id===''){
                        return null;
                }

                $storage=load_folder_share();
                if (!empty($storage['shares'][$id])){
                        $entry=$storage['shares'][$id];
                        if (empty($entry['path'])||!is_string($entry['path'])){
                                $entry['path']=id2file($id);
                        }
                        if ($owner&&empty($entry['owner'])){
                                $entry['owner']=$owner;
                        }
                        if ($entry!=$storage['shares'][$id]){
                                $dirty_flag=false;
                                $normalized=share_normalize_entry($id,$entry,$dirty_flag);
                                if ($normalized){
                                        $storage['shares'][$id]=$normalized;
                                        save_folder_share($storage);
                                        return $storage['shares'][$id];
                                }

                                unset($storage['shares'][$id]);
                                save_folder_share($storage);
                                return null;
                        }

                        return $storage['shares'][$id];
                }

                if (!$path){
                        $path=id2file($id);
                }

                if (!$path||(!is_dir($path)&&!is_file($path))){
                        return null;
                }

                if (!$owner){
                        $owner=return_owner($id);
                }

                if (!$owner){
                        return null;
                }

                $storage['shares'][$id]=array(
                        'owner'=>$owner,
                        'path'=>$path,
                        'token'=>share_new_token(),
                        'created_at'=>time(),
                        'expires_at'=>null,
                        'recipients'=>array(),
                        'active'=>true,
                );

                save_folder_share($storage);

                return $storage['shares'][$id];
        }

        function share_public_path($id,$entry=null){
                if (!is_string($id)||$id===''){
                        return null;
                }

                if ($entry===null){
                        $storage=load_folder_share();
                        $entry=$storage['shares'][$id]??null;
                }

                if (empty($entry)||!is_array($entry)){
                        return null;
                }

                if (empty($entry['token'])||!is_string($entry['token'])){
                        return null;
                }

                return 'index.php?share='.$id.'&t='.$entry['token'];
        }

        function share_export_payload($share_id,$token,$ids){
                if (!is_string($share_id)||$share_id===''){
                        return array('status'=>404,'data'=>array());
                }

                if (!is_string($token)||$token===''){
                        return array('status'=>404,'data'=>array());
                }

                if (!is_array($ids)||!isset($ids[$share_id])||empty($ids[$share_id])){
                        return array('status'=>404,'data'=>array());
                }

                $share_path=$ids[$share_id];
                $storage=load_folder_share();
                if (empty($storage['shares'][$share_id])){
                        $owner=return_owner($share_id);
                        $ensured=share_ensure_entry($share_id,$owner,$share_path);
                        if (!$ensured){
                                return array('status'=>404,'data'=>array());
                        }

                        $storage=load_folder_share();
                }

                $entry=$storage['shares'][$share_id]??null;
                if (!$entry||!share_is_active($entry)){
                        return array('status'=>404,'data'=>array());
                }

                if (!hash_equals($entry['token'],$token)){
                        return array('status'=>404,'data'=>array());
                }

                if (!is_dir($share_path)&&!is_file($share_path)){
                        return array('status'=>410,'data'=>array());
                }

                $tree=array();
                if (is_dir($share_path)){
                        $content=folder_content($share_path);
                        if (is_array($content)){
                                foreach ($content as $id=>$path){
                                        $child=share_ensure_entry($id,return_owner($id),$path);
                                        if (!$child||!share_is_active($child)){
                                                continue;
                                        }

                                        $tree[$id]=array(
                                                'path'=>$path,
                                                'url'=>ROOT.'index.php?share='.$id.'&t='.$child['token'],
                                                'type'=>is_dir($path)?'folder':'file'
                                        );
                                }
                        }
                }else{
                        $tree[$share_id]=array(
                                'path'=>$share_path,
                                'url'=>ROOT.'index.php?share='.$share_id.'&t='.$entry['token'],
                                'type'=>is_dir($share_path)?'folder':'file'
                        );
                }

                store_access_stat($share_path,$share_id);

                return array('status'=>200,'data'=>$tree);
        }

        function share_revoke(string $id): bool{
                $storage=load_folder_share();
                if (empty($storage['shares'][$id])){
                        return false;
                }

                $entry=$storage['shares'][$id];
                $entry['active']=false;
                $entry['token']=null;
                $storage['shares'][$id]=$entry;
                save_folder_share($storage);

                return true;
        }

        function share_regenerate(string $id): ?array{
                $storage=load_folder_share();
                if (empty($storage['shares'][$id])){
                        $existing=share_ensure_entry($id);
                        if (empty($existing)){
                                return null;
                        }
                        $storage=load_folder_share();
                }

                $entry=$storage['shares'][$id];
                $path=$entry['path']??null;
                if (!is_string($path)||$path===''){
                        $path=id2file($id);
                }
                if (!$path||(!is_dir($path)&&!is_file($path))){
                        share_revoke($id);
                        return null;
                }

                $owner=null;
                if (!empty($entry['owner'])&&is_string($entry['owner'])){
                        $owner=$entry['owner'];
                }
                if (!$owner){
                        $owner=return_owner($id);
                }
                if (!$owner){
                        share_revoke($id);
                        return null;
                }

                if (!share_revoke($id)){
                        return null;
                }

                removeID($id);
                $new_id=addID($path);
                if (!is_string($new_id)||$new_id===''){
                        return null;
                }

                $storage=load_folder_share();
                $revoked=$storage['shares'][$id]??null;
                if (!is_array($revoked)){
                        return null;
                }

                $revoked['token']=share_new_token();
                $revoked['active']=true;
                $revoked['created_at']=time();
                $revoked['owner']=$owner;
                $revoked['path']=$path;

                $storage['shares'][$new_id]=$revoked;
                save_folder_share($storage);

                $fresh=load_folder_share();
                if (empty($fresh['shares'][$new_id])){
                        return null;
                }

                $normalized=$fresh['shares'][$new_id];
                $normalized['id']=$new_id;

                return $normalized;
        }

	function save_users_rights($array=null){return save($_SESSION['users_rights_file'],$array);}
	function load_users_rights(){return load($_SESSION['users_rights_file']);}
	function save_config($array=null){if (!$array){$array=$_SESSION['config'];}return save($_SESSION['config_file'],$array);}
	function load_config(){return load($_SESSION['config_file']);}
	# Delete a file or a folder and apply changes in ids file
	function delete_file_or_folder($id=null,$ids=null,$tree=array()){
		global $ids,$tree;
		if (empty($ids)){$ids=unstore();}
		if (empty($id)){return false;}
		if (empty($tree)){tree(null,$_SESSION['login'],false,false,$tree);}
		$f=id2file($id);
		if(is_file($f)){
			# delete file & thumb
			unlink($f);
			$thumbfilename=get_thumbs_name($f);
			if (is_file($thumbfilename)){unlink($thumbfilename);}
			unset($ids[$id]);
			store($ids);
			return remove_branch($f,$id);
		}else if (is_dir($f)){
			$fthumbs=explode('/',$f); unset($fthumbs[0]);$fthumbs=implode('/',$fthumbs);
			# delete dir
			rrmdir($f);
			rrmdir('thumbs/'.$fthumbs);
			# remove all vanished sub files & folders from id file
			foreach($ids as $id=>$path){
				if (strpos($path, $f.'/')!==false){unset($ids[$id]);}
			}
			store($ids);
			return remove_branch($f,$id);
		}
		return $tree;
	}

	# New folder in the current path
	function new_folder($folder=null){
		if (!$folder){return false;}
		$thumbs_path='thumbs/'.$_SESSION['upload_user_path'].addslash_if_needed($_SESSION['current_path']);
		$path=$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].addslash_if_needed($_SESSION['current_path']);
		$complete_path=$path.$folder;
		$complete_thumbs=$thumbs_path.$folder;
		if (is_dir($complete_path)&&is_dir($complete_thumbs)){
			# Folder already exists, rename
			$folder=rename_item($folder,$path);
			$complete_path=$path.$folder;
			$complete_thumbs=$thumbs_path.$folder;
		}else if(!is_dir($complete_path)&&is_dir($complete_thumbs)){
			rrmdir($complete_thumbs);
		}
		mkdir($complete_path, 0744, true);
		mkdir($complete_thumbs, 0744, true);
		return addID($complete_path);		
	}

	# checks if dir is empty
	function is_empty_dir($src){
		# form https://openclassrooms.com/forum/sujet/savoir-si-un-dossier-est-vide-39930
		if (!is_dir($src)){return 'no such dir';}
		$h = opendir($src); 
		while (($o = readdir($h)) !== FALSE){ 
			if (($o != '.') and ($o != '..')){$c++;} 
		} 
		closedir($h); 
		if($c==0){return true;}else {return false; }
	} 

	# store all client access to a file
        function encode_json($value){
                $flags=JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE;
                if (defined('JSON_INVALID_UTF8_SUBSTITUTE')){
                        $flags|=JSON_INVALID_UTF8_SUBSTITUTE;
                }
                if (defined('JSON_THROW_ON_ERROR')){
                        $flags|=JSON_THROW_ON_ERROR;
                }

                try{
                        $json=json_encode($value,$flags);
                }catch(\Throwable $exception){
                        return '[]';
                }

                if ($json===false){
                        return '[]';
                }

                return $json;
        }
        function send_json($payload=array(),$status=200){
                if (!is_int($status) || $status<100 || $status>599){
                        $status=200;
                }

                if (!headers_sent()){
                        http_response_code($status);
                        header('Content-Type: application/json; charset=utf-8');
                }

                exit(encode_json($payload));
        }
        function store_access_stat($file=null,$id=null){
                if (!is_string($file)||$file===''){return false;}
                if (!is_string($id)||$id===''){return false;}

                $host='&#8709;';
                if (isset($_SERVER['REMOTE_HOST'])&&$_SERVER['REMOTE_HOST']!==''){
                        $host=substr(strip_tags((string)$_SERVER['REMOTE_HOST']),0,255);
                }

                $ref='&#8709;';
                if (isset($_SERVER['HTTP_REFERER'])&&$_SERVER['HTTP_REFERER']!==''){
                        $ref=substr(strip_tags((string)$_SERVER['HTTP_REFERER']),0,2048);
                }

                if (isset($_GET['rss'])){$access='RSS';}
                elseif (isset($_GET['json'])){$access='Json';}
                elseif (isset($_GET['export'])){$access='Export';}
                else{$access='Website';}

                $ip='0.0.0.0';
                if (isset($_SERVER['REMOTE_ADDR'])){
                        $candidate=(string)$_SERVER['REMOTE_ADDR'];
                        $zone=strpos($candidate,'%');
                        if ($zone!==false){$candidate=substr($candidate,0,$zone);}
                        if (filter_var($candidate,FILTER_VALIDATE_IP)!==false){
                                $ip=$candidate;
                        }
                }

                $data=array(
                        'ip'=>$ip,
                        'host'=>$host,
                        'referrer'=>$ref,
                        'date'=>date('D d M, H:i:s'),
                        'file'=>$file,
                        'id'=>$id,
                        'access'=>$access
                );

                $stats=load($_SESSION['stats_file']);
                if (!is_array($stats)){$stats=array();}

                $stats[]=$data;
                $limit=(int)conf('stats_max_entries');
                if ($limit>0 && count($stats)>$limit){
                        $stats=array_slice($stats,-$limit);
                }

                save($_SESSION['stats_file'], $stats);
        }
	function addslash_if_needed($chaine){
		if (substr($chaine,strlen($chaine)-1,1)!='/'&&!empty($chaine)){return $chaine.'/';}else{return $chaine;}
	}
	function rename_item($file=null,$folder=''){
		if (!$file){return false;}
		if (strpos($file, '/')!==false){$file=_basename($file);}
		$folder=addslash_if_needed($folder);
		$destination=$folder.$file;
		$nb=1;
		$extension=pathinfo($file,PATHINFO_EXTENSION);
		$file2=$file;
		while (is_file($destination) || is_dir($destination)){
			$nb++;
			$add='('.$nb.')';
			if (is_file($destination)) {$file2=str_replace('.'.$extension,$add.'.'.$extension,$file);}
			else{$file2=$file.$add;}
			$destination=$folder.$file2;
		}
		return $file2;
	}
	function no_special_char($string){return preg_replace('/[\"*\/\:<>\?|]/','',$string);}
	function file_curl_contents($url,$pretend=true){
		# distant version of file_get_contents
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: UTF-8'));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		if (!ini_get("safe_mode") && !ini_get('open_basedir') ) {curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);}
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		if ($pretend){curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:40.0) Gecko/20100101 Firefox/40.0');}    
		curl_setopt($ch, CURLOPT_REFERER, 'http://noreferer.com');// notez le referer "custom"
		$data = curl_exec($ch);
		$response_headers = curl_getinfo($ch);
		curl_close($ch);
		return $data;
	}
	function getUrl() {
     $url = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] : 'https://'.$_SERVER["SERVER_NAME"];
     $url .= $_SERVER["SCRIPT_NAME"];
     return $url;
    }
	function rrmdir($dir) { 
		# delete a folder and its content
	   if (is_dir($dir)) { 
	     $objects = scandir($dir); 
	     foreach ($objects as $object) { 
	       if ($object != "." && $object != "..") { 
	         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
	       } 
	     } 
	     reset($objects); 
	     rmdir($dir); 
	   } 
	}
	function _glob($path,$pattern='') {
		# glob function fallback by Cyril MAGUIRE (thx bro' ;-)
		if($path=='/'){
			$path='';
		}
	    $liste =  array();
	    $pattern=str_replace('*','',$pattern);
	    if ($handle = opendir($path)) {
	        while (false !== ($file = readdir($handle))) {
	        	if(stripos($file, $pattern)!==false || $pattern=='' && $file!='.' && $file!='..' && $file!='.htaccess') {
	                $liste[] = $path.$file;
	            }
	        }
	        closedir($handle);
	    }
		natcasesort($liste);
	    return $liste;
	}
	function _basename($file){$array=explode('/',$file);if (is_array($array)){return end($array);}else{return $file;}} 
	function only_type($tree=null, $ext=null){
		if (empty($tree)||empty($ext)){return false;}
		if (is_string($tree)){
			$extension=pathinfo($tree,PATHINFO_EXTENSION);
			if (stripos($ext, $extension)===false||empty($extension)){return false;}
			return true;
		}elseif (is_array($tree)){
			$tree=unset_first_element($tree);				  
			if (empty($tree)){return false;}	
		    foreach($tree as $file){
		    	if (is_file($file)||is_dir($file)){
		    		if (strtolower(_basename($file))!='readme.md'){
						$extension=pathinfo($file,PATHINFO_EXTENSION);
						if (stripos($ext, $extension)===false||empty($extension)){return false;}
					}
				}
			}
			return true;
		}
	}
	function unset_first_element($array=null){
		if (!$array){return false;}
		$first=array_keys($array);$first=$first[0];
		unset($array[$first]);
		return $array;		
	}
        function unzip($file, $destination){
            if (!class_exists('ZipArchive')){return false;}
            if (empty($file) || !is_file($file) || !is_readable($file)){return false;}
            if (empty($destination)){return false;}
            if (!is_dir($destination) && !mkdir($destination, 0744, true)){return false;}

            $root = isset($_SESSION['upload_root_path']) ? realpath($_SESSION['upload_root_path']) : false;
            if ($root === false){return false;}
            $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

            $targetRoot = realpath($destination);
            if ($targetRoot === false){return false;}
            $targetPrefix = rtrim($targetRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if (strpos($targetPrefix, $rootPrefix) !== 0){return false;}

            $zip = new ZipArchive();
            if ($zip->open($file) !== true){return false;}

            $createdFiles = array();
            $createdDirs = array();
            $abort = function() use ($zip, &$createdFiles, &$createdDirs){
                $zip->close();
                foreach ($createdFiles as $path){
                    if (is_file($path)){@unlink($path);}
                }
                if (!empty($createdDirs)){
                    usort($createdDirs, function($a, $b){
                        return strlen($b) <=> strlen($a);
                    });
                }
                foreach ($createdDirs as $path){
                    if (is_dir($path)){@rmdir($path);}
                }
                return false;
            };
            $ensureDir = function($path) use (&$createdDirs){
                if (is_dir($path)){return true;}
                $build = array();
                $current = $path;
                while (!is_dir($current)){
                    $build[] = $current;
                    $parent = dirname($current);
                    if ($parent === $current){break;}
                    $current = $parent;
                }
                for ($i = count($build) - 1; $i >= 0; $i--){
                    if (!mkdir($build[$i], 0744)){return false;}
                    $createdDirs[] = $build[$i];
                }
                return is_dir($path);
            };

            $maxBytes = conf('max_length');
            $maxBytes = is_numeric($maxBytes) ? (int)$maxBytes * 1024 * 1024 : 0;
            $totalBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++){
                $stat = $zip->statIndex($index);
                if (!is_array($stat) || !isset($stat['name'])){return $abort();}

                $rawName = $stat['name'];
                $name = str_replace(chr(92), '/', $rawName);
                if ($name === '' || strpos($name, "\0") !== false){return $abort();}
                if ($name[0] === '/' || strpos($name, '../') !== false){return $abort();}
                if (preg_match('#^[A-Za-z]:#', $name) === 1){return $abort();}

                $totalBytes += isset($stat['size']) ? (int)$stat['size'] : 0;
                if ($maxBytes > 0 && $totalBytes > $maxBytes){return $abort();}

                $segments = array_filter(explode('/', $name), 'strlen');
                $target = $targetRoot;
                foreach ($segments as $segment){
                    if ($segment === '.' || $segment === '..'){return $abort();}
                    $target .= DIRECTORY_SEPARATOR.$segment;
                }

                $targetParent = dirname($target);
                if (!$ensureDir($targetParent)){return $abort();}

                $realParent = realpath($targetParent);
                if ($realParent === false){return $abort();}
                $parentPrefix = rtrim($realParent, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                if (strpos($parentPrefix, $rootPrefix) !== 0){return $abort();}

                $mode = isset($stat['external_attributes']) ? (($stat['external_attributes'] >> 16) & 0xF000) : 0;
                $isDirectory = ($mode === 0x4000) || substr($name, -1) === '/';
                $isSymlink = ($mode === 0xA000);
                if ($isSymlink){return $abort();}

                if ($isDirectory){
                    if (!$ensureDir($target)){return $abort();}

                    $realDir = realpath($target);
                    if ($realDir === false){return $abort();}
                    $dirPrefix = rtrim($realDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                    if (strpos($dirPrefix, $rootPrefix) !== 0){return $abort();}
                    continue;
                }

                $stream = $zip->getStream($rawName);
                if (!$stream){return $abort();}

                $handle = fopen($target, 'wb');
                if (!$handle){
                    fclose($stream);
                    return $abort();
                }

                $writeOk = true;
                while (!feof($stream)){
                    $buffer = fread($stream, 8192);
                    if ($buffer === false){
                        $writeOk = false;
                        break;
                    }
                    if ($buffer === ''){continue;}
                    if (fwrite($handle, $buffer) === false){
                        $writeOk = false;
                        break;
                    }
                }

                fclose($handle);
                fclose($stream);

                if (!$writeOk){
                    if (is_file($target)){@unlink($target);}
                    return $abort();
                }

                $realFile = realpath($target);
                if ($realFile === false){
                    if (is_file($target)){@unlink($target);}
                    return $abort();
                }
                $filePrefix = rtrim($realFile, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                if (strpos($filePrefix, $rootPrefix) !== 0){
                    @unlink($realFile);
                    return $abort();
                }

                $createdFiles[] = $realFile;
            }

            $zip->close();
            return true;
        }

        function zip($source, $destination)
        {
                if (!extension_loaded('zip') || !class_exists('ZipArchive')){return false;}
                if (empty($source) || empty($destination)){return false;}
                if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0744, true)){return false;}

                $zip = new ZipArchive();
                if (is_file($destination) && !unlink($destination)){return false;}
                if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true){return false;}

                $sources = is_array($source) ? $source : array($source);
                $root = realpath($_SESSION['upload_root_path']);
                if ($root === false){ $zip->close(); return false; }
                $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

                foreach($sources as $item){
                        if (!is_string($item)){ $zip->close(); @unlink($destination); return false; }
                        $real = realpath($item);
                        if ($real === false){ $zip->close(); @unlink($destination); return false; }
                        $realPrefix = rtrim($real, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                        if (strpos($realPrefix, $rootPrefix) !== 0){ $zip->close(); @unlink($destination); return false; }
                        if (is_dir($real) === true){
                                if (!is_readable($real)){ $zip->close(); @unlink($destination); return false; }
                                $base = basename($real);
                                if (!$zip->addEmptyDir($base)){ $zip->close(); @unlink($destination); return false; }

                                $iterator = new RecursiveIteratorIterator(
                                        new RecursiveDirectoryIterator($real, FilesystemIterator::SKIP_DOTS),
                                        RecursiveIteratorIterator::SELF_FIRST
                                );

                                foreach ($iterator as $file){
                                        $pathName = $file->getPathname();
                                        $relative = substr($pathName, strlen($real) + 1);
                                        $zipPath = $relative === false || $relative === '' ? $base : $base.'/'.str_replace(chr(92), '/', $relative);

                                        if ($file->isLink()){ continue; }

                                        if ($file->isDir()){
                                                if (!$zip->addEmptyDir($zipPath)){ $zip->close(); @unlink($destination); return false; }
                                                continue;
                                        }

                                        if (!$file->isFile() || !is_readable($pathName)){ $zip->close(); @unlink($destination); return false; }
                                        if (!$zip->addFile($pathName, $zipPath)){ $zip->close(); @unlink($destination); return false; }
                                }
                        }
                        else if (is_file($real) === true){
                                if (!is_readable($real)){ $zip->close(); @unlink($destination); return false; }
                                if (!$zip->addFile($real, basename($real))){ $zip->close(); @unlink($destination); return false; }
                        }
                        else{
                                $zip->close(); @unlink($destination); return false;
                        }

                }

                if ($zip->numFiles === 0){ $zip->close(); @unlink($destination); return false; }

                $result = $zip->close();
                if (!$result){ @unlink($destination); }
                return $result;

        }
	
	function check_path($path){
		return (strpos($path, '//')===false && strpos($path, '..')===false && ( empty($path[0]) || (!empty($path[0]) && $path[0]!='/') || (!empty($path[0]) && $path[0]!='.') ) );
	}

	function get_thumbs_name($file){
		global $auto_thumb;
		if($file[0]=='/'){
			$file=substr($file,1);
		}
		return 'thumbs/'.preg_replace('#\.(jpe?g|png|gif)#i','_THUMB_'.$auto_thumb['default_width'].'x'.$auto_thumb['default_height'].'.$1',$file);
	}

	function get_thumbs_name_gallery($file){
		if($file[0]=='/'){
			$file=substr($file,1);
		}
		return 'thumbs/'.preg_replace('#\.(jpe?g|png|gif)#i','_THUMBGALLERY_'.$_SESSION['config']['gallery_thumbs_width'].'x'.$_SESSION['config']['gallery_thumbs_width'].'.$1',$file);
	}

	function recursive_glob($dir='.',$files=true){		
         # scann a folder and subfolders and return the tree
        if (!isset($dossiers[0]) || $dossiers[0]!=$dir){$dossiers[0]=$dir;}
        if (!is_dir($dir)&&$files){ return array($dir); }
        elseif (!is_dir($dir)&&!$files){return array();}
        $list=_glob(addslash_if_needed($dir));         
        foreach ($list as $dossier) {
            $dossiers=array_merge($dossiers,recursive_glob($dossier,$files));
        }
        return $dossiers;
    }

	# from Cyril MAGUIRE / Jerrywham
	function protocolIsHTTPS() {
	    return (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] == 'on') ? true : false;
	}
	function getRacine($truncate=false) {

	    $protocol = protocolIsHTTPS() ? 'https://' : "http://";
	    $servername = $_SERVER['HTTP_HOST'];
	    $serverport = (preg_match('/:[0-9]+/', $servername) OR $_SERVER['SERVER_PORT'])=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
	    $dirname = preg_replace('/\/(core|plugins)\/(.*)/', '', dirname($_SERVER['SCRIPT_NAME']));
	    $racine = rtrim($protocol.$servername.$serverport.$dirname, '/').'/';
	    $racine = str_replace(array('webroot/','install/'), '', $racine);
	    if(!checkSite($racine, false))
	        die('Error: wrong or invalid url');
	    if ($truncate){ 
	        $root = substr($racine,strpos($racine, '://')+3,strpos($racine,basename($racine))+4);
	        $racine = substr($root,strpos($root,'/'));
	    }
	    return $racine;
	}
	function checkSite(&$site, $reset=true) {
	    $site = preg_replace('#([\'"].*)#', '', $site);
	    # Méthode Jeffrey Friedl - http://mathiasbynens.be/demo/url-regex
	    # modifiée par Amaury Graillat pour prendre en compte la valeur localhost dans l'url
	    if(preg_match('@\b((ftp|https?)://([-\w]+(\.\w[-\w]*)+|localhost)|(?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)+(?: com\b|edu\b|biz\b|gov\b|in(?:t|fo)\b|mil\b|net\b|org\b|[a-z][a-z]\b))(\:\d+)?(/[^.!,?;"\'<>()\[\]{}\s\x7F-\xFF]*(?:[.!,?]+[^.!,?;"\'<>()\[\]{}\s\x7F-\xFF]+)*)?@iS', $site))
	            return true;
	    else {
	        if($reset) $site='';
	        return false;
	    }
	}
    ############################################
	# IDS functions 
	############################################
	# Delete the id if it's a burn one
	function burned($id){
		if ($id[0]=='*'&&!isset($_GET['thumbs'])){
			if (!is_user_connected() || !is_owner($id)){removeID($id);}
		}
	}
	# Create an ID for a file/folder
	function addID($path,$ids=null){# $path must be complete upload path
		global $ids;
		if (!$ids){$ids=unstore();}
		if ($id=array_search($path, $ids)){return $id;}
		$item_id=uniqid(true);
		$ids[$item_id]=$path;
		store($ids);
		return $item_id;
	}

	# remove an id from id file
	function removeID($id){
		$ids=unstore();
		if (!empty($ids[$id])){unset ($ids[$id]);}
		store($ids);
	}

	# remove unused ids, add new ids for current user
	function updateIDs($ids=null,$folder_id=null){
		if (!$ids){$ids=unstore();}
		$ids=array_map(function($id){return str_replace('//','/',$id);},$ids);
		$sdi=array_flip($ids);$saveid=$savetree=false;
		$ids=array_flip($sdi); # here, all the redundant ids have gone ^^
		# scann all uploads folder (can be long but it's important)
		# or only the requested folder
		if (!empty($folder_id)){
			$tree=recursive_glob(id2file($folder_id),true);
		}else{
			$tree=recursive_glob($_SESSION['upload_root_path'],true);
		}

		unset($tree[0]);
		# add missing ids
		foreach($tree as $index=>$file){
			if (!isset($sdi[$file])){
				$saveid=true;
				$id=uniqid(true);
				$ids[$id]=$file;
			}else{unset($sdi[$file]);}
		}
		if (empty($folder_id)){
			# remove ids with no file (not required for single folder update)
			if (!empty($sdi)){
				$saveid=true;
				foreach ($sdi as $file=>$id){
					if (!is_dir($file)&&!is_file($file)){
						unset($ids[$id]);
						if ($remove=array_search($file, $tree)){
							unset($tree[$remove]);
						}
					}
				}
			}
		}
		if ($saveid){
			store($ids);
			if (!empty($_SESSION['login'])){
				regen_tree($_SESSION['login'],$ids);
			}else{
				include($_SESSION['private_folder'].'/auto_restrict_users.php');
				foreach($auto_restrict["users"] as $user=>$data){
					regen_tree($user,$ids);
				}
			}
		}
		return $ids;
	}

	function is_in($ext,$type){
		global $behaviour;
		if (!empty($behaviour[$type])){return array_search($ext,$behaviour[$type]);}else{return false;}
	}
	function id2file($id){
		global $ids;
		if (isset($ids[$id])){
			return $ids[$id];
		}else{
			return false;
		}
	}
	function file2id($file){
		global $ids;
		$sdi=array_flip($ids);
		if (isset($sdi[$file])){return $sdi[$file];}else{return false;}
	}
	function only_folders($array=null){
		if (empty($array)){return false;}
		$tree=array();
		foreach($array as $key=>$value){
			if (is_dir($value)){$tree[]=$value;}
		
		}
		return $tree;
	}
	function add_branch($path=null,$id=null,$user=null,$tree=array()){
		global $ids;
		if (empty($path)){return false;}
		if (empty($id)&&empty($ids)){return false;}
		if (empty($id)){$id=file2id($path);}
		if (empty($user)&&!empty($_SESSION['login'])){$user=$_SESSION['login'];}
		if (empty($tree)){$tree=loadtree($user);}
		$tree[$id]=$path;
		natcasesort($tree);
		savetree($user,$tree);
		return $tree;
	}
	function remove_branch($path=null,$id=null,$user=null,$tree=array()){
		global $ids;
		if (empty($path)){return false;}
		if (empty($id)&&empty($ids)){return false;}
		if (empty($id)){$id=file2id($path);}
		if (empty($user)&&!empty($_SESSION['login'])){$user=$_SESSION['login'];}
		if (empty($tree)){$tree=loadtree($user);}
		if (isset($tree[$id])||$id=array_search($path, $tree)){
			unset($tree[$id]);
		}
		return $tree;
	}
	function rename_branch($new=null,$old=null,$id=null,$user=null,$tree=array()){
		global $ids;
		if (empty($new)||empty($old)||$new==$old){return false;}
		if (empty($id)&&empty($ids)){return false;}
		if (empty($id)){$id=file2id($old);}
		if (empty($user)&&!empty($_SESSION['login'])){$user=$_SESSION['login'];}
		if (empty($tree)){$tree=loadtree($user);}
		if (isset($tree[$id])||$id=array_search($old, $tree)){
			$tree[$id]=$new;
		}
		return $tree;
	}
	function savetree($user=null,$tree=array()){
		if (empty($tree)){return false;}
		if (empty($user)&&!empty($_SESSION['login'])){$user=$_SESSION['login'];}
		save($_SESSION['private_folder'].'trees/'.$user.'.php',$tree);
	}
	function loadtree($user=null){
		if (empty($user)&&!empty($_SESSION['login'])){$user=$_SESSION['login'];}
		if (empty($_SESSION['private_folder'])){return false;}
		$path=$_SESSION['private_folder'].'trees/'.$user.'.php';
		if (is_file($path)){return load($path);}
		return false;
	}

	# Creates the user's folder tree (for ids file) to reduce all actions to this folder and not to all upload dir
	# if path, only regen a subfolder, return it
	function regen_tree($user='',$ids=null,$path=null){
		global $ids;
		if (!$ids){$ids=unstore();}
		if (empty($user)){$user=$_SESSION['login'];}
		if (empty($path)){$path=$_SESSION['upload_root_path'].$user.'/';
			/*if (!empty($user)){$path=$_SESSION['upload_root_path'].$user.'/';}
			elseif (!empty($_SESSION['upload_user_path'])){
				$path=$_SESSION['upload_root_path'].$_SESSION['upload_user_path'];
				$user=$_SESSION['login'];
			}else{$path=$_SESSION['upload_root_path'];}*/
		}
		
		$tree=array();
		foreach($ids as $id=>$value){
			if (strpos($value,$path)!==false){$tree[$id]=$value;}
		}
		return $tree;
	}

	# regen recursive folder tree if needed
	function user_folder_tree($user=null){
		if (!$user&&!empty($_SESSION['login'])){$user=$_SESSION['login'];}
		if (isset($_SESSION['regenfolder'])||!is_file($_SESSION['private_folder'].$user.'.php')){
			# regen folder tree
			$tree=tree($_SESSION['upload_root_path'].$user,$user,true,true);
			savetree($user,$tree);
			unset($_SESSION['regenfolder']);
			return $tree;
		}else{
			return loadtree($user);
		}
	}

	# build folder content from the user's tree file to avoid excessive _glob access 
	function tree($folder=null,$user=false,$folders_only=false,$recursive=false,$tree=null,$filter=null){
		global $current_tree,$ids;
		if (!empty($current_tree)&&!$folders_only&&!$recursive){return $current_tree;}
		$dir=array();
		if (!$user){$user=$_SESSION['login'];}
		if (!$tree){$tree=regen_tree($user);}
		if (!empty($current_tree)&&$folders_only){$tree=only_folders($current_tree);}
		elseif($folders_only){$tree=only_folders($tree);}

		if (empty($folder)){$folder=$_SESSION['upload_root_path'].$_SESSION['upload_user_path'].$_SESSION['current_path'];}
		if (!empty($tree)){
			foreach ($tree as $id=>$path){
				if (isset($filter)){
					$p=addslash_if_needed($path);
					$f=addslash_if_needed($folder);
					if (strpos($p, $f)!==false&&stripos(_basename($path),$filter)!==false){
						$dir[$id]=$path;
					}
				}else{
					if ($recursive){
						$p=addslash_if_needed($path);
						$f=addslash_if_needed($folder);
						$match=(strpos($p, $f)!==false);				
					}else{
						$match=(addslash_if_needed(dirname($path))===addslash_if_needed($folder));
					} 
					if ($match===true){
						$dir[$id]=$path;
					}
				}	
			}
		}else{$dir=array();}
		return $dir;
	}
	function deep_strip_tags($var){if (is_string($var)){return strip_tags($var);}if (is_array($var)){return array_map('deep_strip_tags',$var);}return $var; }
	function visualizeIcon($extension){
		global $behaviour;
		$array=array_merge(array_flip($behaviour['FILES_TO_RETURN']),array_flip($behaviour['FILES_TO_ECHO']));
		return isset($array[$extension]);
	}
	function generate_bozon_salt($length=512){
		$salt='';
		for($i=1;$i<=$length;$i++){
			$salt.=chr(mt_rand(35,126));
		}
		return $salt;
	}
	function blur_password($pw){
		if (!empty($pw)){return hash('sha512', BOZON_SALT.$pw);}
		return false;
	}
	# to solve some problems on mime detection, fallback
	if (function_exists('mime_content_type')){
		function _mime_content_type($filename) {return mime_content_type($filename);}
	}elseif (function_exists('finfo_file')){
		function _mime_content_type($filename) {return finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $filename );}
	}else{
		function _mime_content_type($filename){
			#inspired by http://stackoverflow.com/questions/8225644/php-mime-type-checking-alternative-way-of-doing-it
		    $mime_types = array(
		        'txt' => 'text/plain',
		        'md' => 'text/plain',
 		        'nfo' => 'text/plain',
		        'htm' => 'text/html',
		        'html' => 'text/html',
		        'php' => 'text/html',
		        'css' => 'text/css',
		        'js' => 'application/javascript',
		        'json' => 'application/json',
		        'xml' => 'application/xml',
		        'swf' => 'application/x-shockwave-flash',
		        'flv' => 'video/x-flv',

		        // images
		        'png' => 'image/png',
		        'jpe' => 'image/jpeg',
		        'jpeg' => 'image/jpeg',
		        'jpg' => 'image/jpeg',
		        'gif' => 'image/gif',
		        'bmp' => 'image/bmp',
		        'ico' => 'image/vnd.microsoft.icon',
		        'tiff' => 'image/tiff',
		        'tif' => 'image/tiff',
		        'svg' => 'image/svg+xml',
		        'svgz' => 'image/svg+xml',

		        // archives
		        'zip' => 'application/zip',
		        'rar' => 'application/x-rar-compressed',
		        'exe' => 'application/x-msdownload',
		        'msi' => 'application/x-msdownload',
		        'cab' => 'application/vnd.ms-cab-compressed',

		        // audio/video
		        'mp3' => 'audio/mpeg',
		        'qt' => 'video/quicktime',
		        'mov' => 'video/quicktime',
		        'm3u' => 'audio/x-mpegurl',

		        // adobe
		        'pdf' => 'application/pdf',
		        'psd' => 'image/vnd.adobe.photoshop',
		        'ai' => 'application/postscript',
		        'eps' => 'application/postscript',
		        'ps' => 'application/postscript',

		        // ms office
		        'doc' => 'application/msword',
		        'rtf' => 'application/rtf',
		        'xls' => 'application/vnd.ms-excel',
		        'ppt' => 'application/vnd.ms-powerpoint',
		        'docx' => 'application/msword',
		        'xlsx' => 'application/vnd.ms-excel',
		        'pptx' => 'application/vnd.ms-powerpoint',


		        // open office
		        'odt' => 'application/vnd.oasis.opendocument.text',
		        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		    );

		    $ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
			if (array_key_exists($ext, $mime_types)) {return $mime_types[$ext];} 
			else {return 'application/octet-stream';}
		}
	}

	# counts how much levels to go back (to close <ul folder_content> correctly)
	function count_back($previous,$current){
		$max=max(count($previous),count($current));
		for($c=0; $c<$max;$c++){
			if (!isset($previous[$c]) || !isset($current[$c]) || $previous[$c]!=$current[$c]){break;}
		}
		$c++;
		return $max-$c;
	}
	function draw_tree($tree=null){
		if (!$tree){return false;}
		$first=array_keys($tree);
		$second=$first[1];
		$first=$first[0];
		$image_only=only_type($tree,'.jpg .jpeg .gif .png');
		$sound_only=only_type($tree,'.mp3 .ogg .wav');
		$readme=array_search(dirname($tree[$second]).'/readme.md', array_map('strtolower',$tree));
		if ($readme&&!empty($tree[$readme])&&is_file($tree[$readme])){$readme=file_get_contents($tree[$readme]);}
		if (!$image_only&&!$sound_only){
			# file list tree		
			echo '<section class="tree">';
			$tree=array_map(function($i){return $i.'/';}, $tree);
			natcasesort($tree);
			echo '<h1>'._basename(rtrim($tree[$first], '/\\')).'</h1>';
			unset($tree[$first]);
			if (empty($tree)){return false;}
			
			$previous_branch=explode('/',$tree[$second]);
			$previous_level=$current_level=count($previous_branch);

			foreach ($tree as $id=>$branch){
				if (!is_dir($branch)){$branch=rtrim($branch, '/\\');}
				$current_branch=explode('/',$branch);
				$current_level=count($current_branch);

				if (is_dir($branch)&&$previous_level==$current_level){echo '</ul>';}				
				if ($current_level<$previous_level){
					$nb=count_back($previous_branch,$current_branch);
					echo str_repeat('</ul>',$nb);
				}
				if (is_dir($branch)){
					echo '<li class="folder"><span class="icon-folder-1"></span> '._basename(rtrim($branch, '/\\')).'</li>';
					echo '<ul class="folder_content">';	
				}elseif (is_file($branch)){
					$extension=strtolower(pathinfo($branch,PATHINFO_EXTENSION));					
					echo '<li><a href="index.php?f='.$id.'"><span class="icon-file '.$extension.'"><em>'.$extension.'</em></span> '._basename($branch).'</a></li>';
				}
				$previous_level=$current_level;$previous_branch=$current_branch;
			}

			echo '</ul>';
			echo '</section>';
			return;
		}elseif($image_only){
			# image gallery
			if (!function_exists('auto_thumb')){include('core/auto_thumb.php');}
			global $gallery_thumbs_width;
			$title=explode('/',$tree[$first]);$title=$title[count($title)-1];unset($tree[$first]);
			echo '<link rel="stylesheet" type="text/css" href="'.THEME_PATH.'/css/gallery.css">';			
			echo '<section><ul class="gallery"><h1>'.$title.'</h1>';
			if (!empty($readme)){echo parse($readme);}
			
			foreach($tree as $id=>$image){
				if (is_file($image)){
					$link='index.php?f='.$id;
					$file=_basename($image);
					$filesize = sizeconvert(filesize($image));
					$ext=strtolower(pathinfo($image,PATHINFO_EXTENSION));
					if ($ext!='mp4'){					
						$size = getimagesize($image);
						$size=$size[0].'x'.$size[1];
						auto_thumb($image,$width=conf('gallery_thumbs_width'),$height=conf('gallery_thumbs_width'),$add_to_thumb_filename='_THUMBGALLERY_',$crop_image=true);
						echo '<a class="image" data-type="img" data-group="gallery" href="'.$link.'" ><img class="b-lazy" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="'.$link.'&gthumbs" alt="'.$file.'"/><span class="info"><em>'.$file.'</em> '.$size.' '.$filesize.'</span></a>';

					}else{
						$size = sizeconvert(filesize($image));
						echo '<a class="image video" data-type="" data-group="gallery" href="'.$link.'" ><img class="blank b-lazy" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="  data-src="'.THEME_PATH.'img/video.png"/><span class="info"><em>'.$file.'</em>'.$size.'</span></a>';
					}
				}
			}
			echo '</ul></section>';
			echo '
			<script src="core/js/blazy.js"></script>
		    <script>
		        ;(function() {
		            // Initialize
		            var bLazy = new Blazy();
		        })();
		    </script>';
		    return;
			
		}elseif($sound_only){
			# music player
			$title=explode('/',$tree[$first]);
			$title=$title[count($title)-1];unset($tree[$first]);
			echo '<section class="music_player"><h1>'.$title.'</h1>';
			if (!empty($readme)){echo '<div class="markdown">'.parse($readme).'</div>';}			
			echo '<audio controls  src="index.php?f='.$second.'"></audio>';
			$i=1;
			if (!empty($readme)){$nb=count($tree)-1;}else{$nb=count($tree);}
			if ($nb>1){
				foreach($tree as $id=>$sound){
					if (is_file($sound)&&strtolower(basename($sound))!='readme.md'){
						$link='index.php?f='.$id;
						$file=_basename($sound);
						$ext=strtolower(pathinfo($sound,PATHINFO_EXTENSION));							
						$filename=pathinfo($sound,PATHINFO_FILENAME);
						$size = sizeconvert(filesize($sound));
						echo '<li class="sound" data-href="'.$link.'" data-index="'.$i.'"><em>'.$filename.'</em> '.$size.'</li>';
						$i++;
					}
				}	
			}
			echo '</section>';
			echo '			
			<script src="core/js/playlist.js"></script>';
			return;
		}
	}

	function template($key,$array){		
		global $templates;
		
		if (isset($templates[$key])){
			$tpl=$templates[$key];
			foreach($array as $key=>$value){
				$tpl= str_replace($key,$value,$tpl);
			}				
			return $tpl;
		}else{return false;}
	}

        function navigatorLanguage(){
                if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                        return 'fr';
                }

                $language=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
                $code=substr($language,0,2);
                if ($code===false || strlen($code)<2){
                        return 'fr';
                }

                return strtolower($code);
        }



	# locales functions 
	function e($txt,$echo=true){
		global $lang;
		if (isset($lang[$txt])){$t= $lang[$txt];}else{$t= $txt;}
		if ($echo){echo $t;}else{return $t;}
	}
	function available_languages(){
		$l=_glob('locale/','php');
		foreach($l as $key=>$lang){
			$l[$key]=str_replace('.php','',_basename($lang));
		}

		return $l;
	}
	function available_themes(){
		$l=_glob('templates/','');
		foreach($l as $key=>$lang){
			$l[$key]=_basename($lang);
		}
		return $l;
	}
	# Links functions
	# create language links
	function make_lang_link($pattern='<a #CLASS href="index.php?p=#PAGE&lang=#LANG&token=#TOKEN">#LANG</a>'){
		$langs=available_languages();
		if (!empty($_GET['p'])){$page=$_GET['p'];}else{$page='';}
		if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
		# current language in first position
		echo '<ul><li>'.str_replace(array('#CLASS','#LANG','#TOKEN','#PAGE'),array('class="active"',$_SESSION['config']['language'],$token,$page),$pattern).'<ul>';
		# other languages 
		$class='';
		foreach($langs as $lang){
			if (conf('language')==$lang){$class=' class="active'.$lang.'" ';}else{$class='class="'.$lang.'" ';}
			echo '<li>'.str_replace(array('#CLASS','#LANG','#TOKEN','#PAGE'),array($class,$lang,$token,$page),$pattern).'</li>';
		}
		echo '</ul></li></ul>';
	}

	# create the connection/admin button
	function make_connect_link($label_admin='&nbsp;',$label_logout='&nbsp;',$label_login='&nbsp;'){
		$status='';
		if (is_user_connected()){
			if (!empty($_SESSION['login'])&&$label_admin=='&nbsp;'){$label_admin= $_SESSION['login'];}
			if (!empty($_SESSION['status'])){$status= $_SESSION['status'];}
			if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
			echo '<a id="admin_button" class="btn '.$status.'" href="index.php?p=admin&token='.$token.'" title="'.e($status,false).'"><span class="icon-'.$status.'"></span> '.$label_admin.'</a>';
			echo '<a id="logout_button" class="btn" href="index.php?deconnexion" title="'.e('Logout',false).'"><span class="icon-logout" ></span></a>';

		}
		else{echo '<a id="login_button" class="btn" href="index.php?p=login" title="'.e('Connection',false).'"><span class="icon-lock" ></span> '.$label_login.'</a>';}
	}

	# create the menu link (to change view)
	function make_menu_link($pattern='<a id="#MENU" title="#TITLE" class="#CLASS" href="index.php?p=#PAGE&aspect=#MENU&token=#TOKEN"><span class="icon-#MENU" ></span></a>'){
		if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
		if (!empty($_GET['p'])){$page=$_GET['p'];}else{$page='';}
		if (conf('aspect')=='list'){
		echo str_replace(array('#MENU','#THEME','#TOKEN','#PAGE','#TITLE'),array('icon',THEME_PATH,$token,$page,e('See as icon',false)),$pattern);
	}elseif (conf('aspect')=='icon'){
		echo str_replace(array('#MENU','#THEME','#TOKEN','#PAGE','#TITLE'),array('list',THEME_PATH,$token,$page,e('See as file list',false)),$pattern);
	}
	}

	# create the mode links (to change access mode)
	function make_mode_link($pattern='<a id="mode_#MODE" class="#CLASS" title="#TITLE" href="index.php?p=admin&mode=#MODE&token=#TOKEN"><span class="icon-#MODE" ></span></a>'){
		if(function_exists('returntoken')){$token=returnToken();}else{$token='';}
		if (conf('mode')=='view'){$class='active';}else{$class='';}
		echo str_replace(array('#MODE','#TITLE','#TOKEN','#CLASS'),array('view',e('Manage files',false),$token,$class),$pattern);
		if (conf('mode')=='links'){$class='active';}else{$class='';}
		echo str_replace(array('#MODE','#TITLE','#TOKEN','#CLASS'),array('links',e('Manage links',false),$token,$class),$pattern);		
	}

	# Checks auto_restrict's session vars to know if a user is connected
	function is_user_connected(){
		if (empty($_SESSION['id_user'])||empty($_SESSION['login'])||empty($_SESSION['expire'])){
			return false;
		}
		return true;
	}

	# echo some classes depending on filemode, pages etc
	function body_classes(){
		if (isset($_GET['users_list'])){echo 'users_list ';}
		if (!empty($_GET['p'])){echo preg_replace('#[^a-zA-Z0-9_]#','',$_GET['p']).' ';}else{echo 'home ';}
		if (conf('language')){echo 'body_'.conf('language').' ';}
		if (conf('mode')){echo conf('mode').' ';}
		if (conf('aspect')&&empty($_GET['f'])){echo conf('aspect').' ';}
	}


	# Users functions
	# return the user's name hashed or the user's name corresponding to a hash 
	function hash_user($user_or_hash){
		if (!is_file('private/auto_restrict_users.php')){return false;}
		include ('private/auto_restrict_users.php');
		if (strlen($user_or_hash)>100){
			# hash > user
			foreach ($auto_restrict['users'] as $user=>$data){
				$hash=hash('sha512',$data['salt'].$user);
				if ($hash==$user_or_hash){return $user;}
			}
			
			return false;
		}else{
			# user > hash
			if (!empty($auto_restrict['users'][$user_or_hash])){
				return hash('sha512',$auto_restrict['users'][$user_or_hash]['salt'].$user_or_hash);
			}
			return false;
		}
	}

	# Check if current user is the id's owner 
	function is_owner($id=null){
		if (!$id || empty($_SESSION['login']) || !$f=id2file($id)){return false;}
		$file=explode('/',$f);$owner=$file[1];
		return $_SESSION['login']==$owner;
	}
	# Return id's owner 
	function return_owner($id=null){
		if (!$id){return false;}
		$file=explode('/',id2file($id));
		if (!empty($file[1])){$owner=$file[1];}
		else{$owner=e('Deleted',false);}
		
		return $owner;
	}

	function is_allowed($action,$profile=null){
		global $RIGHTS;
		if (!is_user_connected()){return false;}
		if (!isset($RIGHTS)){return false;}
		if (!$profile&&!empty($_SESSION['status'])){$profile=$_SESSION['status'];}elseif (!$profile){return false;}
		if ($profile=='superadmin'){return true;}
		if (isset($RIGHTS[$profile][$action])){return true;}else{return false;}
	}

	function is_superadmin(){if (!empty($_SESSION['status'])&&$_SESSION['status']=='superadmin'){return true;}return false;}
	function load_users_list(){
		global $auto_restrict;
		if (empty($auto_restrict["users"])){
			$auto_restrict_users=$_SESSION['private_folder'].'auto_restrict_users.php';
			if (!is_file($auto_restrict_users)){return false;}
			include($auto_restrict_users);
		}
		return $auto_restrict["users"];
	}

	# Complete users rights
	function complete_users_rights(){
		global $auto_restrict;
		$save=false;
		$users=$auto_restrict["users"];
		if (empty($users)){return false;}		
		if (!isset($_SESSION['users_rights'])){$_SESSION['users_rights']=load_users_rights();}
		foreach ($users as $key=>$user){ # add missing
			if (!isset($_SESSION['users_rights'][$user['login']])){
				$_SESSION['users_rights'][$user['login']]=conf('profile_folder_max_size');
				$save=true;
			}
		}
		foreach ($_SESSION['users_rights'] as $user=>$size){ # remove deleted profiles
			if (!isset($users[$user])){
				unset($_SESSION['users_rights'][$user]);
				$save=true;
			}
		}
		if ($save){save_users_rights($_SESSION['users_rights']);}
		return $_SESSION['users_rights'];
	}

	# creates a form with the users list
	function generate_users_folder_space_formlist($text='Check users to delete account and files'){
		global $auto_restrict;
		
		echo '<h1>'.$text.'</h1><form action="" method="POST" class="folder_size_users_list"><table>';
		
		foreach ($_SESSION['users_rights'] as $user=>$size){
			if ($auto_restrict['users'][$user]['status']!='superadmin'){
				$class=' class="'.$auto_restrict['users'][$user]['status'].'" title="'.e($auto_restrict['users'][$user]['status'],false).'"';
				echo '<tr>';
				echo '<td>';
				echo '<span '.$class.'>'.$user.' <em>('.folder_free($_SESSION['upload_root_path'].$user,$user).' '.e('free',false).')</em></span></td>';
				echo '<input type="hidden" name="user_name[]" value="'.$user.'"/>';
				echo '<td><input type="number" name="user_right[]" class="npt" value="'.$size.'" title="MB" min="0"/></td>';
				newToken();
				echo '</tr>';
			}
		}
		
		echo '</table><input type="submit" value="Ok" class="btn"/></form>';
	}


	# Folder functions
	# folder_size % functions
	function sizeconvert( $bytes ){
	    $label = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );
	    for( $i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++ );
	    return( round( $bytes, 2 ) . " " . $label[$i] );
	}
	function folder_size($folder,$convert=true,$user=null){
		$tree=tree($folder,$user,false,true);$size=0;
		foreach($tree as $branch){
			if (is_file(($branch))){$size+=filesize($branch);}
		}
		if ($convert){return sizeconvert($size);}	
		return $size;
	}
	function folder_content($folder){
		global $ids;
		$content=array();
		foreach($ids as $id=>$path){
			$folder=addslash_if_needed($folder);
			if (strpos(addslash_if_needed($path),$folder)!==false){
				$content[$id]=$path;
			}
		}
		return $content;
	}
	function folder_free($folder,$user='',$mode=false){
		$max=(user_folder_max_size($user)*1048576);
		$size=round($max-strval(folder_size($folder,false,$user)),2);
		if ($size<=0){return false;}
		if (!$mode){ # converted size
			return sizeconvert($size);
		}elseif ($mode=1){ # oct
			return $size;
		}else{ # %
			return round((100*$size)/$max,1);
		}
	}
	function folder_fit($file=null,$size=null,$profile=null){ 
		if (!$file&&!$size||!$profile){return false;}
		$is_admin=is_superadmin();
		if (empty($_SESSION['profile_folder_max_size'])&&!$is_admin){return false;}
		$folder=$_SESSION['upload_root_path'].$profile;
		$max=user_folder_max_size($profile)*1048576;
		if (!empty($file)){
			if (!is_file($file)){return false;}
			$size=filesize($file);
		}
		if (folder_size($folder,false)+$size>$max&&!$is_admin){return false;}
		return true;
	}

	function folder_usage_draw($profile,$mode=1){
		$folder=$_SESSION['upload_root_path'].$profile;
		if (!is_dir($folder)){return false;}
		if (empty($_SESSION['profile_folder_max_size'])){return false;}
		if (is_admin()){return false;}
		$free=folder_free($folder,$profile,1);
		$user_size=$_SESSION['profile_folder_max_size']*1048576;
		$used=round($user_size-$free,1);
		$usedpc=round($used*100/$user_size,1);
		$freepc=round($free*100/$user_size,1);
		if (empty($free)){$free=0;}
		
		if ($mode==1){echo '<div class="free_space_bar" ><span class="used" style="width:'.$usedpc.'%" title="'.$used.' M">'.$usedpc.'%</span><span class="free" style="width:'.$freepc.'%;" title="'.$free.' M">'.$freepc.'%</label>';}
		if ($mode==2){echo '<div class="free_space_icon btn" title="'.$freepc.'% '.e('free',false).' ('.$free.' MB)"><span class="free" style="height:'.(($freepc*32)/100).'px"></span><span class="used" style="height:'.(($usedpc*32)/100).'px"></span></label>';}
		if ($mode==3){echo '<div class="free_space_text">'.$freepc.'% '.e('free',false).' ('.sizeconvert($free).')</label>';}
	}

	function user_folder_max_size($user=''){
		if (empty($user)&&empty($_SESSION['profile_folder_max_size'])){return false;}
		if (empty($user)){return $_SESSION['profile_folder_max_size'];}
		if (!empty($_SESSION['users_rights'][$user])){return $_SESSION['users_rights'][$user];}
		return false;
	}
	function draw_lb_link($file,$alt=null,$text_link='&nbsp;',$group='',$type='iframe'){
		if(@is_array(getimagesize($file))){$type='img';}
		if (!empty($group)){$group='data-group="'.$group.'"';}
		echo '<a href="'.$file.'" '.$group.' onclick="lb_show(this);" data-type="'.$type.'" alt="'.$alt.'">'.$text_link.'</a>';
	}

	function start_session(){if (!session_id()){session_start();}}
	
	function extract_config_vars(){
		preg_match_all('#\$([^\';=]*?)=#', file_get_contents('./config.php'), $vars);
		$conf=array();
		foreach($vars[1] as $index=>$varname){
			if (!isset($$varname)){global $$varname;}
			$conf[str_replace('default_','',$varname)]=$$varname;
		}
		return $conf;
	}

	function conf($key=null,$value=null){
		if (!$key && $value===null && empty($_SESSION['config'])){return false;}
		if (!$key && $value===null ){return $_SESSION['config'];}
		if (!isset($_SESSION['config'][$key]) && $value===null ){return false;}
		if ($value===null){return $_SESSION['config'][$key];}
		elseif ($key && is_string($value)||is_integer($value)){
			$bool=toBool($value);
			if (is_bool($bool)){$value=$bool;}
			$_SESSION['config'][$key]=$value;
		}
		elseif (is_array($value)){
			foreach ($value as $k=>$v){
				$bool=toBool($v);
				if (is_bool($bool)){$v=$bool;}
				$_SESSION['config'][$k]=$v;
			}
		}
		return false;
	}
	function toBool($str){//http://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php
	    if($str === 'true' || $str === 'TRUE' || $str === 'True'){
	        return true;
	    }elseif($str === 'false' || $str === 'FALSE' || $str === 'False'){
	        return false;
	    }
	}
	# Generate configuration form from config vars
	function generate_config_form($vars=null){
		if (empty($vars)&&empty($_SESSION['config'])){$vars=extract_config_vars();}
		if (empty($vars)){$vars=$_SESSION['config'];}
		include ('core/config_form_help.php');
		echo '<form action="#" method="POST"><input type="hidden" name="config" value="true"/><table>';
		foreach ($vars as $varname=>$value){
			if (isset($config_form['help'][$varname])){
				echo '<tr>';
				if (is_bool($value)){
					echo '<td><label>'.e(str_replace('_',' ',$varname),false).'</label><p>'.$config_form['help'][$varname].'</p></td><td><select class="npt" name="'.$varname.'">'."\n";
						if ($value===true){$selected=' selected="true" ';}else{$selected='';}
						echo '<option value="true" '.$selected.'>'.e('Yes',false).'</option>'."\n";
						if ($value===false){$selected=' selected="true" ';}else{$selected='';}
						echo '<option value="false" '.$selected.'>'.e('No',false).'</option>'."\n";
					echo '</select></td>'."\n";
				}elseif(!empty($config_form['options'][$varname])&&is_array($config_form['options'][$varname])){
					echo '<td><label>'.e(str_replace('_',' ',$varname),false).'</label><p>'.$config_form['help'][$varname].'</p></td><td><select class="npt" name="'.$varname.'">'."\n";
					foreach ($config_form['options'][$varname] as $option){
						if ($option==$value){$selected=' selected="true" ';}else{$selected='';}
						echo '<option value="'.$option.'" '.$selected.'>'.e($option,false).'</option>'."\n";
					}
					echo '</select></td>'."\n";
				}else{
					echo '<td><label>'.e(str_replace('_',' ',$varname),false).'</label><p>'.$config_form['help'][$varname].'</p></td><td><input class="npt" name="'.$varname.'" value="'.$value.'"/></td>';
				}
				echo '</tr>';
			}
			//echo "'$varname' => e('text',false),\n";
		}
		newToken();
		echo '<tr><td></td><td><input type="submit" class="btn" value="Ok"/><td><tr></table></form>';
	}



	#################################################
	# Debug functions 
	#################################################
	function aff($var,$stop=true){
		$dat=debug_backtrace();$origin='<table>';
		echo '<div style="background-color:rgba(0,0,0,0.8);color:red;padding:5px">Arret ligne <em><strong style="color:white;font-weight:bold">'.$dat[0]['line'].'</strong></em> dans le fichier <em style="color:white;font-weight:bold">'.$dat[0]['file'].'</em></label>';
		echo '<pre style="background-color:rgba(0,0,0,0.8);color:#fff;padding:10px"><code>';var_dump($var);echo '</code></pre>';
		foreach (array_reverse($dat) as $data){
			$dir=dirname($data['file']).'/';
			$fil=_basename($data['file']);
			$origin.='<tr><td style="width:50%"><em style="color:#888">'.$dir.'</em></td><td style="max-width:10%"><em style="color:white;font-weight:bold">'.$fil.'</em></td><td style="max-width:10%"><em style="color:yellow;font-weight:bold">'.$data['function'].'()</em></td><td style="max-width:10%"> <em style="color:lightblue;font-weight:bold">'.$data['line'].'</em> </td></tr>';
		}
		$origin.='</table>';
		echo '<div style="background-color:rgba(0,0,0,0.8);color:#aaa;padding:10px">'.$origin.'</label>';
		if ($stop){exit();}
	}
	function li($string){echo '<li>'.$string.'</li>';}
	function debug_log($str){file_put_contents('debug.html', $str,FILE_APPEND);}
	function chrono($name='chrono',$delete=false){
		if ($delete){@unlink('debug.html');}
		global $debug_mode;
		if (!$debug_mode){return false;}
		if (!isset($_SESSION[$name])){$_SESSION[$name]=microtime(true);}
		else{
			$count=round(microtime(true)-$_SESSION[$name],2);
			if ($count<1){$color='lightgreen';$color2='black';}
			if ($count>1&&$count<=2){$color='yellow';$color2='maroon';}
			if ($count>2&&$count<=5){$color='orange';$color2='maroon';}
			if ($count>5){$color='red';$color2='pink';}
			$debug= '<li> '.date('h:i:s').' '.$name.':<em style="padding:5px;font-size:1.3em; color:'.$color2.';background-color:'.$color.'">'.$count.'sec</em> <ul style="margin-left:30px;">'; unset($_SESSION[$name]);
			$dat=debug_backtrace();
			foreach (array_reverse($dat) as $data){
				$dir=dirname($data['file']).'/';
				$fil=_basename($data['file']);
				$debug.= '<li><em style="color:black;font-weight:bold">'.$fil.'</em> <em style="color:red;font-weight:bold">'.$data['function'].'()</em> l.<em style="color:blue;font-weight:bold">'.$data['line'].'</em> </li>';
			}
			$debug.='</ul></li>';
			debug_log($debug);
			unset($_SESSION[$name]);
		}

	}
?>
