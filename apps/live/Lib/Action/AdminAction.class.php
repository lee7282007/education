<?php
/**
 * 后台直播管理
 * @author wangjun@chuyouyun.com
 * @version chuyouyun2.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAction extends AdministratorAction
{
	/**
	 * 初始化，
	 */
	public function _initialize() {
		$this->pageTitle['index']  = '直播间列表';
		$this->pageTitle['create'] = '创建直播间';
		$this->pageTitle['update'] = '修改直播间';
		
		$this->pageTab[] = array('title'=>'直播间列表','tabHash'=>'index','url'=>U('live/Admin/index'));
		$this->pageTab[] = array('title'=>'创建直播间','tabHash'=>'create','url'=>U('live/Admin/create'));
		parent::_initialize();
	}
	
	//直播间列表（带分页）
	public function index(){
		$_REQUEST['tabHash'] = 'index';
		$this->pageKeyList = array('id','roomname','studiourl','password','DOACTION');
		$list = M ('studioroom') -> field('id,roomid,roomname,password') -> findPage(20);
		foreach($list['data'] as &$val){
			//直播间地址
			$val['studiourl']  =  U ('home/Live/index',array('roomid'=>$val['roomid']));
			$val['password']   =  $val['password'];
			$val['roomname']   =  $val['roomname'];
			$val['DOACTION']   = '<a href="'.U('live/Admin/update',array('roomid'=>$val['id'])).'">编辑</a> | ';
			$val['DOACTION']   .= '<a href="'.U('live/Admin/deteleRoom',array('id'=>$val['id'])).'">删除</a>';
		}
		// echo '<pre>';
		// print_r($list);exit;
		$this->displayList($list);
	}
	
	//创建直播间
	public function create(){
		if( isset($_POST) ) {
			$roomid = t($_REQUEST['roomid']);
			if($data = M ('studioroom') -> create()) {
				$pattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
			 	for($i = 0; $i < 10; $i ++) {
			        $returnStr .= $pattern {mt_rand ( 0, 36 )}; //随机生成房间id
				} //生成php随机数
				$data['roomid'] = $returnStr;
				$id = M ('studioroom') -> add($data);
			}else {
				$this->error(M ('studioroom') -> getError());
			}
			if($id) {
				$this->assign( 'jumpUrl', U('live/Admin/index') );
				$this->success('创建成功');
			} else {
				$this->error('创建失败');
			}
		} else {
			$_REQUEST['tabHash'] = 'create';
			$this->pageKeyList = array('roomid','roomname','password','room_ak','yy_number','vid','speak_interval','onoff','viewtime','viewstatus','is_guest','sensitive_words','is_say','studio_type');
			$this->opt['onoff']          = array('1'=>'开启','0'=>'不开启'); //房间权限
			$this->opt['viewstatus']     = array('1'=>'不限制','0'=>'限制'); //观看权限
			$this->opt['is_guest']       = array('1'=>'不可以','0'=>'可以'); //游客权限
			$this->opt['is_say']         = array('1'=>'不可以','0'=>'可以'); //能否发言
			$this->opt['studio_type']    = array('1'=>'录像回放','0'=>'YY直播'); //直播方式
			$this->savePostUrl = U('live/Admin/create');
			$this->displayConfig();
		}
		
	}
	
	//编辑直播间
	public function update(){
		if( isset($_POST) ) {
			$roomid = t($_REQUEST['roomid']);
			if($data = M ('studioroom') -> create($_POST, 2)) {
				$id = M ('studioroom') -> where("roomid = '{$roomid}'") -> save($data);
			}else {
				$this->error(M ('studioroom') -> getError());
			}

			if($id) {
				$this->assign( 'jumpUrl', U('live/Admin/index') );
				$this->success('修改成功');
			} else {
				$this->error('修改失败');
			}
		} else {
			$_REQUEST['tabHash'] = 'create';
			//查找所有视频信息
			$videoinfo = M ('videolist') -> select();
			foreach($videoinfo as $key => $val) {
				$this->opt['vid'][$val['id']] = $val['videoname'];
			}

			$this->pageKeyList = array('roomid','roomname','password','room_ak','yy_number','vid','speak_interval','onoff','viewtime','viewstatus','is_guest','sensitive_words','is_say','studio_type');
			$this->opt['onoff']          = array('1'=>'开启','0'=>'不开启'); //房间权限
			$this->opt['viewstatus']     = array('1'=>'不限制','0'=>'限制'); //观看权限
			$this->opt['is_guest']       = array('1'=>'不可以','0'=>'可以'); //游客权限
			$this->opt['is_say']         = array('1'=>'不可以','0'=>'可以'); //能否发言
			$this->opt['studio_type']    = array('1'=>'录像回放','0'=>'YY直播'); //直播方式
			
			$roomid = t($_REQUEST['roomid']);
			$list   = $this->roomInfo($roomid);
			$this->savePostUrl = U('live/Admin/update');
			//print_r($list);exit;
			$this->displayConfig($list['room']);
		}
	}

	//删除直播间信息
	public function deteleRoom()
	{
		$roomid = t($_REQUEST['id']);
		if(M ('studioroom') -> delete($roomid) ){
			$this->assign( 'jumpUrl', U('live/Admin/index') );
			$this->success('删除成功');
		} else {
			$this->error('删除失败');
		}
	}
	
	//关闭直播间
	public function close(){
		$roomid = t($_REQUEST['roomid']);
		$url    = C('API_URL').'room/close?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$res = $this->getDataByUrl($url);
		if($res['result'] == 'OK') {
			$this->success('关闭成功');
		} else {
			$this->error('关闭失败');
		}
	}
	
	//直播间信息
	private function roomInfo($roomid){
		$roominfo = array();
		$roominfo['room'] = M ('studioroom') -> where("id = '{$roomid}'") ->find();

		return $roominfo;
	}
	
	//直播列表信息（带分页）
	public function info(){
		$roomid = t($_REQUEST['roomid']);
		$url    = C('API_URL').'live/info?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$list   = $this->getDataByUrl($url);
		$this->assign('list',$list);
		$this->assign('type','info');
		$this->display('list');
	}
	
	//直播间连接数统计
	public function connections(){
	
	}
	
	//获取直播间代码
	public function getCode(){
		$roomid = t($_REQUEST['roomid']);
		$url    = C('API_URL').'room/code?';
		$param  = 'roomid='.$roomid.'&userid='.C('USER_ID');
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$list   = $this->getDataByUrl($url);
		$this->assign('list',$list);
		$this->assign('type','code');
		$this->display('list');
	}
	
	//获取直播间内用户登录、退出行为统计
	public function useraction(){
	
	}
	
	//直播间模板信息
	public function templateInfo(){
		$url    = C('API_URL').'viewtemplate/info?';
		$param  = 'userid='.C('USER_ID');;
		$hash   = md5( $param.'&time='.time().'&salt='.C('API_KEY') );
		$url    = $url.$param.'&time='.time().'&hash='.$hash;
		$list = $this->getDataByUrl($url);
		dump($list);exit;
	}
	
	//根据url读取文本
	private function getDataByUrl($url , $type = true){
		return json_decode(file_get_contents($url) , $type);
	}

}