<?php
	/**
	* BoZoN commands GET vars part:
	* Here we handle the GET data for commands WITHOUT <header> <Body> <footer>
	* like thumbnails request, users list, login/logout request, public share file/folder request...
	* @author: Bronco (bronco@warriordudimanche.net)
	**/


	# thumbnail request
	if(isset($_GET['thumbs'])&&!empty($_GET['f'])&&$_SESSION['GD']){
		$f=get_thumbs_name(id2file($_GET['f']));
		$type=_mime_content_type($f);
		header('Content-type: '.$type.'; charset=utf-8');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($f));
		readfile($f);
		exit;
	}
	if(isset($_GET['gthumbs'])&&!empty($_GET['f'])&&$_SESSION['GD']){
		$f=get_thumbs_name_gallery(id2file($_GET['f']));
		$type=_mime_content_type($f);
		header('Content-type: '.$type.'; charset=utf-8');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($f));
		readfile($f);
		exit;
	}

	# Cron update request
	if (!empty($_GET['cron_update'])&&$_GET['cron_update']==$cron_security_string){
		$ids=updateIDs($ids);
		exit('Ok');
	}
	
	# export shared file(s) data in json format
        if (isset($_GET['export'])&& !empty($_GET['f'])){
                $share_id=$_GET['f'];
                $token=isset($_GET['t'])?$_GET['t']:'';
                if (!isset($ids[$share_id]) || empty($ids[$share_id])){
                        send_json(array(),404);
                }

                $share_path=$ids[$share_id];
                $storage=load_folder_share();
                $entry=$storage['shares'][$share_id]??share_ensure_entry($share_id,return_owner($share_id),$share_path);
                $storage=load_folder_share();
                $entry=$storage['shares'][$share_id]??null;

                if (!$entry || !share_is_active($entry) || !is_string($token) || $token==='' || !hash_equals($entry['token'],$token)){
                        send_json(array(),404);
                }

                if (!is_file($share_path) && !is_dir($share_path)){
                        send_json(array(),410);
                }

                $tree=array();
                if (is_dir($share_path)){
                        $content=folder_content($share_path);
                        if (is_array($content)){
                                foreach($content as $id=>$path){
                                        $child_entry=share_ensure_entry($id,return_owner($id),$path);
                                        $storage=load_folder_share();
                                        $child=$storage['shares'][$id]??$child_entry;
                                        if (!$child || empty($child['token'])){
                                                continue;
                                        }

                                        $tree[$id]=array(
                                                'path'=>$path,
                                                'url'=>ROOT.'index.php?share='.$id.'&t='.$child['token'],
                                                'type'=>(is_dir($path)?'folder':'file')
                                        );
                                }
                        }
                }else{
                        $tree=array(
                                $share_id=>array(
                                        'path'=>$share_path,
                                        'url'=>ROOT.'index.php?share='.$share_id.'&t='.$entry['token'],
                                        'type'=>(is_dir($share_path)?'folder':'file')
                                )
                        );
                }

                store_access_stat($share_path,$share_id);
                send_json($tree);
        }

        # public share request
        if (!empty($_GET['share'])||!empty($_GET['f'])){
                require('core/share.php');
                exit;
        }

	# Try to login or logout ? => auto_restrict
	if (!empty($_POST['pass'])&&!empty($_POST['login'])||isset($_GET['logout'])||isset($_GET['deconnexion'])){
		require_once('core/auto_restrict.php');
		exit;
	}

	# ask for rss stats 
	if (isset($_GET['statrss'])&&!empty($_GET['key'])&&hash_user($_GET['key'])){
		$rss=array('infos'=>'','items'=>'');
		$rss['infos']=array(
			'title'=>'BoZoN - stats',
			'description'=>e('Rss feed of stats',false),
			'link'=>htmlentities($_SESSION['home']),
		);

		include('core/Array2feed.php');
		$stats=load($_SESSION['stats_file']);
		for ($index=0;$index<conf('stats_max_lines');$index++){
			if (!empty($stats[$index])){
                                $stat_id=$stats[$index]['id'];
                                $share_link=$_SESSION['home'].'?share='.$stat_id;
                                if (!empty($ids[$stat_id])){
                                        $entry=share_ensure_entry($stat_id,return_owner($stat_id),$ids[$stat_id]);
                                        $storage=load_folder_share();
                                        $entry=$storage['shares'][$stat_id]??$entry;
                                        if ($entry && !empty($entry['token'])){
                                                $share_link=$_SESSION['home'].'?share='.$stat_id.'&t='.$entry['token'];
                                        }
                                }

                                $rss['items'][]=
                                array(
                                        'title'=>$stats[$index]['file'],
                                        'description'=>'[ip:'.$stats[$index]['ip'].'] '.'[referrer:'.$stats[$index]['referrer'].'] '.'[host:'.$stats[$index]['host'].'] ',
                                        'pubDate'=>makeRSSdate($stats[$index]['date']),
                                        'link'=>$share_link,
                                        'guid'=>$share_link,
                                );
                        }
		}
		array2feed($rss);
		exit;
	}


	# ask for json format stats 
        if (isset($_GET['statjson'])&&!empty($_GET['key'])&&hash_user($_GET['key'])){
                $stats=load($_SESSION['stats_file']);
                send_json($stats);
        }

	# zip and download a folder from visitor's share page
	if (!empty($_GET['zipfolder'])&&$_SESSION['zip']){
		$folder=id2file($_GET['zipfolder']);
		if (!$folder || !is_dir($folder)){http_response_code(404);exit;}
		if (!is_dir($_SESSION['temp_folder']) && !mkdir($_SESSION['temp_folder'],0744,true)){http_response_code(500);exit;}
		$zipfile=$_SESSION['temp_folder'].return_owner($_GET['zipfolder']).'-'._basename($folder).'.zip';
		if (!zip($folder,$zipfile) || !is_file($zipfile)){http_response_code(500);exit;}
		header('Content-type: application/zip');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($zipfile));
		# lance le téléchargement des fichiers non affichables
		header('Content-Disposition: attachment; filename="'._basename($zipfile).'"');
		readfile($zipfile);
		@unlink($zipfile);
		exit;
		}

	if (is_user_connected()){
		# users list request
		if (isset($_GET['users_list'])&&is_allowed('user page')){
			$_GET['p']='users';unset($_GET['users_list']); # To avoid useless changes in auto_restrict
		}
		# if user is connected, use auto_restrict
		require_once('core/auto_restrict.php');
		$token=returnToken();
		
		# complete list files ajax request button «load more»
		if(isset($_GET['async'])){
			include('core/listfiles.php');
			exit;
		}
		if (empty($_GET['p'])&&!empty($_GET)||count($_GET)>2||!empty($_POST)){include('core/GET_POST_admin_data.php');}
		if (!empty($_FILES)){
			include('core/auto_dropzone.php');
			exit();
		}
		
		# users share list request
                if (isset($_GET['users_share_list'])){
                        $shared_id=$_GET['users_share_list'];
                        require_once('core/auto_restrict.php');
                        $storage=load_folder_share();
                        $entry=$storage['shares'][$shared_id]??null;
                        $selected=array();
                        if (!empty($entry['owner'])&&$entry['owner']===$_SESSION['login']&&!empty($entry['recipients'])&&is_array($entry['recipients'])){
                                foreach ($entry['recipients'] as $recipient){
                                        $selected[$recipient]=true;
                                }
                        }
                        $users=$auto_restrict['users'];
                        unset($users[$_SESSION['login']]);
                        foreach($users as $login=>$data){
                                # creates a checkbox list of users (if the folder is already shared by logged user, checked)
                                if (isset($selected[$login])){
                                        $check=' checked ';$class=' class="shared" ';
                                }else{$check='';$class='';}
                                echo '<li><input type="checkbox" '.$class.' id="check_'.$login.'" value="'.$login.'" name="users[]"'.$check.'><label for="check_'.$login.'">'.$login.'</label></li>';
			}		
			exit;
		}



	}else{$token='';}
	if (!empty($_GET['p'])){$page=$_GET['p'];}else{$page='';}
	if (!empty($_GET['msg'])){$message=$_GET['msg'];}
	if (!empty($_GET['lang'])){conf('language',$_GET['lang']);header('location:index.php?p='.$page.'&token='.$token);}
	if (!empty($_GET['aspect'])){conf('aspect',$_GET['aspect']);header('location:index.php?p='.$page.'&token='.$token);}
	
?>
