<?php
	/**
	* BoZoN share page:
	* handles a user share request
	* @author: Bronco (bronco@warriordudimanche.net)
	**/
                require_once('core/markdown.php');
                $share_id='';
                if (!empty($_GET['share'])){
                        $share_id=strip_tags($_GET['share']);
                }elseif (!empty($_GET['f'])){
                        $share_id=strip_tags($_GET['f']);
                }

                $provided_token='';
                if (isset($_GET['t'])&&is_string($_GET['t'])){
                        $provided_token=trim($_GET['t']);
                }

                $f=false;
                if ($share_id!==''){
                        $f=id2file($share_id); # complete filepath including profile folder
                }

                $storage=load_folder_share();
                $entry=($share_id!==''?$storage['shares'][$share_id]??null:null);
                if (!$entry&&$share_id!==''&&$f){
                        $entry=share_ensure_entry($share_id,return_owner($share_id),$f);
                        $storage=load_folder_share();
                        $entry=$storage['shares'][$share_id]??$entry;
                }

                if ($share_id===''||!$f||empty($entry)||!share_is_active($entry)||!is_string($provided_token)||$provided_token===''||!hash_equals($entry['token'],$provided_token)){
                        http_response_code(404);
                        require(THEME_PATH.'/header.php');
                        echo '<div class="link_error">\n                                <br/>\n                                '.e('This link is no longer available, sorry.',false).'\n                                <br/>\n                        </div>';
                        require(THEME_PATH.'/footer.php');
                        exit;
                }

                $id=$share_id;
                $share_token=$entry['token'];
                $share_url='index.php?share='.$id.'&t='.$share_token;
                $absolute_share_url=$_SESSION['home'].'?share='.$id.'&t='.$share_token;
                header('Cache-Control: no-store, private');
                header('Pragma: no-cache');
                $f=id2file($id);

		$qrcode='
		<script src="core/js/qr.js"></script>
		<script>
                    function qrcode() {
                        qr=document.getElementById("qrcode");
                        id=qr.getAttribute("data-src");
                        token=qr.getAttribute("data-token");
                        var data = "'.$_SESSION["home"].'?share="+id+"&t="+token;
		    	var options = {ecclevel:"M"};
		    	var url = QRCode.generatePNG(data, options);
		    	qr.src = url;
		    	return false;
		    }
    	</script>
		';
		$m3u='


		';
		if(!empty($f)){
                        if (!is_user_connected()){$_SESSION['config']=load_config();}
                        set_time_limit (0);
                        store_access_stat($f,$id);
                        $call_qrcode='<img id="qrcode" data-src="'.$id.'" data-token="'.$share_token.'" src=""/><script>qrcode();</script>';
		
			# password mode
			if (isset($_POST['password'])){
				# the file id is a md5 password.original id
				$blured=blur_password($_POST['password']);
				$sub_id=str_replace($blured,'',$id); # here we try to recover the original id to compare 
			}
			if (strlen($id)>23 && empty($_POST['password'])){
				require(THEME_PATH.'/header.php');
				echo '
				<div id="lock">					
					<p id="message"><img src="'.THEME_PATH.'/img/home/locked.png"/>'.e('This share is protected, please type the correct password:',false).'</p>
                                <form action="'.$share_url.'" method="post">
						<input type="password" name="password" class="npt"/>
						<input type="submit" value="Ok" class="btn"/>
					</form>
				</div>
				';
				require(THEME_PATH.'/footer.php');
			}else if(empty($_POST['password'])||!empty($_POST['password']) && $blured.$sub_id==$id){	
				# normal mode or access granted
				if ($f && is_file($f)){
	
					# file request => return file according to $behaviour var (see core.php)
					$type=_mime_content_type($f);
					$ext=strtolower(pathinfo($f,PATHINFO_EXTENSION));
					if ($ext=='md'&&!isset($_GET['view'])){
						//include('core/markdown.php');
						require(THEME_PATH.'/header_markdown.php');	
						echo $qrcode;
						echo  parse(url2link(file_get_contents($f)));
						echo $call_qrcode;
						require(THEME_PATH.'/footer_markdown.php');
						
					}else if ($ext=='m3u'){
						require(THEME_PATH.'/header.php');	
						echo $qrcode;
                                                echo str_replace($share_url,'#m3u_link',$templates['dialog_share']);
						echo $call_qrcode;
						require(THEME_PATH.'/footer.php');
						
					}else if (is_in($ext,'FILES_TO_ECHO')!==false&&!isset($_GET['view'])){		
						require(THEME_PATH.'/header.php');
						echo $qrcode;		
						echo '<pre>'.htmlspecialchars(file_get_contents($f)).'</pre>';
						echo $call_qrcode;
						require(THEME_PATH.'/footer.php');						
					}else if (is_in($ext,'FILES_TO_RETURN')!==false||$type=='text/plain'&&empty($ext)){
						header('Content-type: '.$type.'; charset=utf-8');
						header('Content-Disposition: attachment; filename="'._basename($f).'"');
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: '.filesize($f));
						readfile($f);
					}else{
						header('Content-type: '.$type);
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: '.filesize($f));
						# lance le téléchargement des fichiers non affichables
						header('Content-Disposition: attachment; filename="'._basename($f).'"');
						readfile($f);
					}	
					# burn access ?
					burned($id);	
					exit();	
				
				}else if ($f && is_dir($f)){
					# folder request: return the folder & subfolders tree 					
					$tree=tree($f,return_owner($id),false,true);
					if (!isset($_GET['rss'])&&!isset($_GET['json'])){ # no html, header etc for rss feed & json data
						require(THEME_PATH.'/header.php');
						echo $qrcode;
						echo '<div id="share">';
						draw_tree($tree);
						echo '</div>';
						echo '
						<div class="feeds">'.$call_qrcode;
						if (conf('allow_shared_folder_RSS_feed')||conf('allow_shared_folder_JSON_feed')){
							echo '<br/>'.e('This page in',false);
						}
                                                if (conf('allow_shared_folder_RSS_feed')){
                                                        echo ' <a href="'.$absolute_share_url.'&rss" class="rss btn">RSS</a>';
                                                }
                                                if (conf('allow_shared_folder_JSON_feed')){
                                                        echo '<a href="'.$absolute_share_url.'&json" class="json btn blue">Json</a>';
                                                }
						if (conf('allow_shared_folder_download')){
							echo '<br/>
							<a class="zipfolder" href="index.php?zipfolder='.$id.'" title ="zip"><span class="icon-download-cloud"></span> '.e('Download a zip from this folder',false).'</a>';
						}
						echo '</div>';
						require(THEME_PATH.'/footer.php');
					}else{
						# json format of a shared folder (but not for a locked one)
                                                if (isset($_GET['json']) && !empty($tree)  && strlen($id)<=23){
                                                        $upload_path_size=strlen($_SESSION['upload_root_path']);
                                                        foreach ($tree as $branch){
                                                                $id_tree[file2id($branch)]=$branch;
                                                        }
                                                        # burn access ?
                                                        burned($id);
                                                        send_json($id_tree);
                                                }

						# RSS format of a shared folder (but not for a locked one)
						if (isset($_GET['rss']) && !empty($tree)  && strlen($id)<=23){
							$rss=array('infos'=>'','items'=>'');
                                                        $rss['infos']=array(
                                                                'title'=>_basename($f),
                                                                'description'=>e('Rss feed of ',false)._basename($f),
                                                                //'guid'=>$_SESSION['home'].'?f='.$id,
                                                                'link'=>htmlentities($absolute_share_url.'&rss'),
                                                        );

							include('core/Array2feed.php');
							$upload_path_size=strlen($_SESSION['upload_root_path']);
							foreach ($tree as $branch){
								$id_branch=file2id($branch);
                                                                $branch_entry=share_ensure_entry($id_branch,return_owner($id_branch),$branch);
                                                                $branch_storage=load_folder_share();
                                                                $branch_entry=$branch_storage['shares'][$id_branch]??$branch_entry;
                                                                if (empty($branch_entry)||empty($branch_entry['token'])){
                                                                        continue;
                                                                }
                                                                $branch_url=$_SESSION['home'].'?share='.$id_branch.'&t='.$branch_entry['token'];

                                                                $rss['items'][]=array(
                                                                        'title'=>_basename($branch),
                                                                        'description'=>'',
                                                                        'pubDate'=>makeRSSdate(date("d-m-Y H:i:s.",filemtime($branch))),
                                                                        'link'=>$branch_url,
                                                                        'guid'=>$branch_url,
                                                                );
							}
							array2feed($rss);
							# burn access ?
							burned($id);
							exit();
						}
					}
					# burn access ?
					burned($id);
					exit();	
				}else{ 
					require(THEME_PATH.'/header.php');
					echo '<div class="error">
						<br/>
						'.e('This link is no longer available, sorry.',false).'
						<br/>
					</div>';
					require(THEME_PATH.'/footer.php');
				}

				
			}

		}else{ 
			require(THEME_PATH.'/header.php');
			echo '<div class="link_error">
				<br/>
				'.e('This link is no longer available, sorry.',false).'
				<br/>
			</div>';
			require(THEME_PATH.'/footer.php');
		}	
	


?>
