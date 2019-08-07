<?php
namespace Weixinapp\Controller;
use Common\Controller\FrontendController;
class WxPublicController extends FrontendController{
	

	public function _initialize() {
        parent::_initialize();
       
		$secretKey = C('qscms_weixinapp_secretkey');
		//$secretKey = 'MilFun';
		$secret = I('request.secretKey',"","trim");
		if($secret != $secretKey){
			$this->ajaxReturn(2,'秘钥错误！',$list);
		}
    }

	public function reg_user() {
		if (C('qscms_mobile_captcha_open')==1 && (C('qscms_wap_captcha_config.user_login')==0 || (session('?error_login_count') && session('error_login_count')>=C('qscms_wap_captcha_config.user_login')))){
			if(true !== $reg = \Common\qscmslib\captcha::verify('mobile')) $this->ajaxReturn(0,$reg);
		}
		if($mobile = I('get.mobile','','trim')){
			if(!fieldRegex($mobile,'mobile')) $this->ajaxReturn(0,'手机号格式错误！');
			$smsVerify = I('get.md5Code','','trim');
			$verfiy_mobile=I('get.verfiy_mobile','','trim');
			$verfiy_time=I('get.verfiy_time','','trim');
			!$smsVerify && $this->ajaxReturn(0,'验证码错误！');//验证码错误！
			if($mobile != $verfiy_mobile) $this->ajaxReturn(0,'手机号不一致！');//手机号不一致
			if(time()>$verfiy_time+600) $this->ajaxReturn(0,'验证码过期！');//验证码过期
			$vcode_sms = I('get.mobile_vcode');
			$mobile_rand=substr(md5($vcode_sms), 8,16);
			if($mobile_rand!=$smsVerify) $this->ajaxReturn(0,'验证码匹配错误！');//验证码错误！

            $gender = I('get.gender');
            $nickName = I('get.nickName');
            $nickName = preg_replace_callback( '/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$nickName);
			$openid=I('get.openid','','trim');
			$user = M('Members')->where(array('mobile'=>$verfiy_mobile))->find();
			$passport = $this->_user_server();

			if($user){
					$condition['uid'] = $condition2['uid'] = $user['uid'];
					$condition['keyid'] = array('neq',$openid);
					$condition['type'] = $condition2['type'] = 'wxapp';
					$bindInfo=M('MembersBind')->where($condition)->find();
					if($bindInfo){
						M('MembersBind')->where($condition2)->delete();
					}
					
                    $user_bind_info['uid'] = $user['uid'];
                    $user_bind_info['type'] ='wxapp';
                    $user_bind_info['keyid'] =$openid;

                    $user_bind_info['info']=serialize(array('keyid'=>$openid,'keyname'=>$nickName));
                    $user_bind_info['bindingtime']=time();

                    $res=M("MembersBind")->add($user_bind_info);
                    if($res!==false){
                        $this->ajaxReturn(1,'');
                    }
                
			}elseif($passport->is_sitegroup() && false !== $sitegroup_user = $passport->uc('sitegroup')->get($verfiy_mobile, 'mobile')){


                	if($user = $this->_sitegroup_register($sitegroup_user,'mobile')){

		                    $user_bind_info['uid'] = $user['uid'];
		                    $user_bind_info['type'] ='wxapp';
		                    $user_bind_info['keyid'] =$openid;

		                    $user_bind_info['info']=serialize(array('keyid'=>$openid,'keyname'=>$nickName));
		                    $user_bind_info['bindingtime']=time();
		                    $res=M("MembersBind")->add($user_bind_info);
		            }
		            $this->ajaxReturn(1,'');

			} else {
				$this->ajaxReturn(1,'');
			}


		} else{
			$this->ajaxReturn(0,'请填手机号码！');
		}
    }
    /**
     * [_sitegroup_register 站群用户注册本地]
     */
    protected function _sitegroup_register($sitegroup_user,$reg_type){
        if(!$sitegroup_user['mobile_audit'] && $reg_type == 'mobile'){
            $sitegroup_user['mobile_audit'] = $audit = 1;
        }
        $passport = $this->_user_server();
        if($sitegroup_user['reg_type'] == 1 && !$sitegroup_user['email']) unset($sitegroup_user['email']);
        if(false === $sitegroup_user = $passport->uc('default')->register($sitegroup_user)){
            if($user = $passport->get_status()) $this->ajaxReturn(2,'检测到您的手机已绑定用户，需要解绑当前手机么');
            $this->ajaxReturn(0,$passport->get_error());
        }
        if($audit && $passport->is_sitegroup()){
            $passport->uc('sitegroup')->edit($sitegroup_user['uid'],array('mobile_audit'=>1,'_status'=>1));
        }
        D('Members')->user_register($sitegroup_user);
        $points_rule = D('Task')->get_task_cache(2,1);
        return $sitegroup_user;
    }
	public function wxreg_user() {
      	$mobile = I('get.mobile','','trim');
		if(fieldRegex($mobile,'mobile')){
			//if(!fieldRegex($mobile,'mobile')) $this->ajaxReturn(0,'手机号格式错误！');
            $nickName = I('get.nickName');
			$openid=I('get.openid','','trim');
			$user = M('Members')->where(array('mobile'=>$mobile))->find();
			if($user){

					$condition['uid'] = $user['uid'];
					//$condition['keyid'] = array('neq',$openid);
					$condition['type'] = 'wxapp';
					$bindInfo=M('MembersBind')->where($condition)->find();
					if(!$bindInfo){
						M('MembersBind')->where($condition)->delete();
					}
                    $user_bind_info['uid'] = $user['uid'];
                    $user_bind_info['type'] ='wxapp';
                    $user_bind_info['keyid'] =$openid;
                    $user_bind_info['info']=serialize(array('keyid'=>$openid,'keyname'=>$nickName));
                    $user_bind_info['bindingtime']=time();
                    $res=M("MembersBind")->add($user_bind_info);
                    if($res!==false){
                        $this->ajaxReturn(1,'',$user);
                    }
			} else {
				$this->ajaxReturn(1,'',$mobile);
			}
		} else{
			$this->ajaxReturn(0,'手机号绑定失败，前往手动注册、登陆账户！');
		}
    }
	/**
     * [register 会员注册]
     */
    public function register($utype=2){
      	//$utype = I('get.utype','','trim');
		$data['reg_type'] = 1;//注册方式(1:手机，2:邮箱，3:微信)

		$data['utype'] = $utype;
		$nickName = I('get.nickName');
        $nickName = preg_replace_callback( '/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$nickName);
		$gender = I('get.gender');
		$data['mobile'] = I('get.mobile',0,'trim');
		$openid=I('get.openid','','trim');

		$user = M('Members')->where(array('mobile'=>$data['mobile']))->find();
		if($user){
			$this->ajaxReturn(1,'欢迎回来！');
		}
		$us = (!$uc_user && C('apply.Ucenter') && C('qscms_uc_open')) ? 'ucenter' : 'default';
		$passport = $this->_user_server($us);
		$data = $passport->register($data);
		D('Members')->user_register($data);

        if(false === $data = $passport->register($data)){
			if($user = $passport->get_status()){
			}
			$this->ajaxReturn(0,$passport->get_error());
		}
        $sendSms['tpl']='set_register_resume';
        $sendSms['data']=array('username'=>$data['username'].'','password'=>$data['password']);
        $sendSms['mobile']=$data['mobile'];
        D('Sms')->sendSms('captcha',$sendSms);

		/*if($data['uid']){
			$membersinfo['uid']=$data['uid'];
			if($gender=='1'){
				$membersinfo['sex']=1;
				$membersinfo['sex_cn']='男';
			} else {
				$membersinfo['sex']=2;
				$membersinfo['sex_cn']='女';
			}
			//$membersinfo['realname']=$nickName;
			$membersinfo['phone']=$data['mobile'];
			M("MembersInfo")->add($membersinfo);
		}
		$user_bind_info['uid'] = $data['uid'];
		$user_bind_info['keyid'] =$openid;
		$user_bind_info['bind_info']['keyid']=$openid;
		$user_bind_info['bind_info']['keyname']=$nickName;
		$oauth = new \Common\qscmslib\oauth('weixin');
		$oauth->bindUser($user_bind_info);*/

		$already_bind=M("MembersBind")->where(array('keyid'=>$openid,'type'=>'wxapp'))->find();

		if(empty($already_bind) && $data['uid']){
			$user_bind_info['uid'] = $data['uid'];
			$user_bind_info['type'] ='wxapp';
			$user_bind_info['keyid'] =$openid;
			$user_bind_info['info']=serialize(array('keyid'=>$openid,'keyname'=>$nickName));
			$user_bind_info['bindingtime']=time();
			M("MembersBind")->add($user_bind_info);
		}
		$this->ajaxReturn(1,'注册成功！');
    }
	public function unbind(){
		$mobile=I('get.mobile','','trim');
		$openid=I('get.openid','','trim');

		$res = M('Members')->where(array('mobile'=>$mobile))->save(array('mobile'=>''));
		$bindInfo=M('MembersBind')->where(array('keyid'=>$openid,'type'=>'wxapp'))->find();

		if($bindInfo){
			M('MembersBind')->where(array('keyid'=>$openid,'type'=>'wxapp'))->delete();
		}
		if($res!==false){
			$passport = $this->_user_server();
			if($passport->is_sitegroup()){
				$passport->unbind_mobile($mobile);
			}
			$this->ajaxReturn(1,'解绑成功');
		} else{
			$this->ajaxReturn(2,'解绑失败');
		}
	}

	 // 注册发送短信/找回密码 短信
    public function reg_send_sms(){
        // if(C('qscms_mobile_captcha_open') && C('qscms_wap_captcha_config.varify_mobile') && true !== $reg = \Common\qscmslib\captcha::verify('mobile')) $this->ajaxReturn(0,$reg.'1');
          $vcode = I('get.vcode',0,'trim');
        $vcode_id = I('get.vcode_id',0,'trim');
        import("Library.Org.Verify.Verify",dirname(__FILE__). '/../');
        $Verify = new \Verify($config);
        if(!$Verify->check($vcode,$vcode_id)){
             $this->ajaxReturn(0,'验证码错误');
        }
		if($uid = I('get.uid',0,'intval')){
            $mobile=M('Members')->where(array('uid'=>$uid))->getfield('mobile');
            !$mobile && $this->ajaxReturn(0,'用户不存在！');
        }else{
            $mobile = I('get.mobile','','trim');
            !$mobile && $this->ajaxReturn(0,'请填手机号码！');
        }
        if(!fieldRegex($mobile,'mobile')) $this->ajaxReturn(0,'手机号错误！');

        $rand=mt_rand(1000, 9999);
        $sendSms['tpl']='set_login';
        $sendSms['data']=array('rand'=>$rand.'','sitename'=>C('qscms_site_name'));
        $smsVerify = session('reg_smsVerify');
        if($smsVerify && $smsVerify['mobile']==$mobile && time()<$smsVerify['time']+180) $this->ajaxReturn(0,'180秒内仅能获取一次短信验证码,请稍后重试');
        $sendSms['mobile']=$mobile;
        if(true === $reg = D('Sms')->sendSms('captcha',$sendSms)){
            session('reg_smsVerify',array('rand'=>substr(md5($rand), 8,16),'time'=>time(),'mobile'=>$mobile));
            $this->ajaxReturn(1,'手机验证码发送成功！',session('reg_smsVerify'));
        }else{
            $this->ajaxReturn(0,$reg);
        }
    }

	/*
	获取网站配置
	*/
	public function web_cfg(){
    	/*$list = C();
		foreach($list as $key =>$val){
			if(strpos($key, 'QSCMS_') !== false){
				$arr[strtolower($key)]=$val;
			}
		}*/
      	$arr['qscms_site_title'] = C('qscms_site_title');
		//$arr['backgroundColor'] = C('qscms_weixinapp_top_color')?C('qscms_weixinapp_top_color'):"#00b6d7";
		$arr['backgroundColor'] = "#00b6d7";
		$arr['fontColor'] = "#ffffff";
		$arr['qscms_site_footer'] = "招聘热线：0377-60585288";
		$arr['qscms_weixinapp_index_login_recommend'] = C('qscms_weixinapp_index_login_recommend');//千人千面
		$arr['recommend'] = C('qscms_weixinapp_index_login_recommend');//千人千面
		$arr['index_jobstype'] = C('qscms_weixinapp_index_jobs_type');//new,nearby,allowance

    	$this->ajaxReturn(1,'获取数据成功',$arr);
    }
    /**
     * 获取导航
     */
    public function get_index_nav(){
        $list = D('NavigationWeixinapp')->get_nav();
        foreach ($list as $key => $value) {
        	$list[$key]['nav_img'] = $value['nav_img']?attach($value['nav_img'],'images'):attach($value['alias'].'.png','resource/weixinapp_nav');
        	if($value['alias']=='allowance' && !C('apply.Allowance')){
				unset($nav[$key]);
			}
        }
        $this->ajaxReturn(1,'success',$list);
    }
		/**
	 * 首页广告
	 */
	public function get_index_ads(){
		$index_focus_where['starttime'] = array(array('elt',time()),array('eq',0),'or');
		$index_focus_where['deadline'] = array(array('gt',time()),array('eq',0),'or');
		$index_focus_where['is_display'] = 1;
      	$index_banner_where = $index_focus_where;
		$index_focus_where['alias'] = 'QS_weixinapp_indexfocus';
		$indexfocus = D('AdWeixinapp')->get_ad_list($index_focus_where);
		foreach ($indexfocus as $key => $value) {
			$list['indexfocus'][$key] = $value;
			$list['indexfocus'][$key]['content'] = attach($value['content'],'attach_img');
		}
		$index_banner_where['alias'] = 'QS_weixinapp_indexbanner';
		$indexbanner = D('AdWeixinapp')->get_ad_list($index_banner_where);
      //print_r($indexbanner);
		foreach ($indexbanner as $key => $value) {
			$list['indexbanner'][$key] = $value;
			$list['indexbanner'][$key]['content'] = attach($value['content'],'attach_img');
		}
		$list['indexfocus'] = $list['indexfocus']?$list['indexfocus']:"";
		$list['indexbanner'] = $list['indexbanner']?$list['indexbanner']:"";
        $this->ajaxReturn(1,'success',$list);
	}
	/*
	首页热门职位
	*/
    public function index_hotword(){
		$where['显示数目'] = 12;
    	$class = new \Common\qscmstag\hotwordTag($where);
    	$list = $class->run();
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	/*
	首页公告
	*/
	public function index_notice(){
		$where['显示数目'] = 2;
		$where['分类'] = 1;
		$where['排序'] = 'addtime:desc';
      	$where['标题长度'] = 22;
      	$where['填补字符'] = '...';
    	$class = new \Common\qscmstag\notice_listTag($where);
    	$list = $class->run();
		$list['addtime']=date('Y-m-d',$list['addtime']);
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	/*
	公告详情
	*/
	public function notice_show($id){
        !$id && $this->ajaxReturn(0,'请选择要查看的公告！');
        M('Notice')->where(array('id'=>$id))->setInc('click',1);
		$where['公告id'] = $id;
    	$class = new \Common\qscmstag\notice_showTag($where);
    	$list = $class->run();
		$list['addtime']=date('Y-m-d',$list['addtime']);
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	/*
	资讯列表
	*/
	public function news_list(){
		$where['排序'] = 'addtime:desc';
		$where['分页显示'] = 1;
      	$where['摘要长度'] = '200';
    	$class = new \Common\qscmstag\news_listTag($where);
    	$list = $class->run();
		foreach($list['list'] as $key=>$val){
			$news_list['list'][$key]=$val;
			$news_list['list'][$key]['addtime']=date('Y-m-d',$val['addtime']);
		}
    		if(!$list['list']){
              $list['list']='';
			}	
			$news_list['total']=$list['total'];
			$news_list['page_params']=$list['page_params'];
			$news_list['page']=$list['page'];
		 if(!$list['page_params']){
      		$news_list['page_params']['nowPage'] = '';
      		$news_list['page_params']['totalPages'] = '';
      		$news_list['page_params']['totalRows'] = '';
      }
    	$this->ajaxReturn(1,'获取数据成功',$news_list);
    }
	/*
	资讯详情
	*/
	public function news_show($id){
        !$id && $this->ajaxReturn(0,'请选择要查看的资讯！');
        M('Article')->where(array('id'=>$id))->setInc('click',1);
		$where['资讯id'] = $id;
    	$class = new \Common\qscmstag\news_showTag($where);
    	$list = $class->run();
		$list['addtime']=date('Y-m-d',$list['addtime']);
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	/*
	首页职位推荐
	*/
	public function index_jobslist(){
		$type = I('get.jobstype','new','trim'); // new：最新职位 ；//nearby：附近的职位；//allowance：红包职位
		$where['显示数目'] = 10;
		if($type == 'new'){
			$where['排序'] = "rtime";
			$title = '最新职位';
		}elseif($type == 'nearby'){
      $locale=$this->Convert_GCJ02_To_BD09(I('get.latitude','','trim'),I('get.longitude','','trim'));
      $lat=$locale['lat'];
      $lng=$locale['lng'];
			$title = '附近的职位';
			$where['经度'] = $lng;
			$where['纬度'] = $lat;
			$where['搜索范围'] = 3;
			//经度="$_GET['lng']" 纬度="$_GET['lat']"
		}elseif($type == 'allowance'){
			$where['排序'] = "rtime";
			$where['搜索内容'] = "allowance";
			$title = '红包职位';
		}
		$class = new \Common\qscmstag\jobs_listTag($where);
        $joblist = $class->run();
		foreach($joblist['list'] as $key=>$val){
          
          	$list['list'][$key]['id']=strip_tags($val['id']);
			$list['list'][$key]['jobs_name']=strip_tags($val['jobs_name']);
			$list['list'][$key]['allowance_id']=strip_tags($val['allowance_id']);
			//$list['list'][$key]['allowance_info']=$val['allowance_info'];

			$list['list'][$key]['allowance_info']['per_amount']=strip_tags($val['allowance_info']['per_amount']);
			$list['list'][$key]['allowance_info']['type_alias']=strip_tags($val['allowance_info']['type_alias']);
          
			$list['list'][$key]['stick']=strip_tags($val['stick']);
			$list['list'][$key]['wage_cn']=strip_tags($val['wage_cn']);
			$list['list'][$key]['tag_cn']=$val['tag_cn'];
			$list['list'][$key]['education_cn']=strip_tags($val['education_cn']);
			$list['list'][$key]['experience_cn']=strip_tags($val['experience_cn']);
			$list['list'][$key]['age_cn']=strip_tags($val['age_cn']);
			$list['list'][$key]['companyname']=strip_tags($val['companyname']);
			$list['list'][$key]['map_range']=strip_tags($val['map_range']);
			$list['list'][$key]['district_cn']=strip_tags($val['district_cn']);

			$setmeal = D('MembersSetmeal')->get_user_setmeal($val['uid']);
			$list['list'][$key]['company']['audit']=$val['company_audit'];
			$list['list'][$key]['company']['setmeal_id']=$setmeal['setmeal_id'];
			$list['list'][$key]['company']['auditurl'] = attach('auth-1.png','resource');
			$list['list'][$key]['company']['setmealurl'] = attach($setmeal['setmeal_id'].'.png','setmeal_img');
			$list['list'][$key]['refreshtime']=date('Y-m-d',$val['refreshtime']);
            $list['list'][$key]['refreshtime_cn']=strip_tags($val['refreshtime_cn']);
            $list['list'][$key]['emergency']=strip_tags($val['emergency']);
            $list['list'][$key]['category_cn']=strip_tags($val['category_cn']);
            $list['list'][$key]['district_cn']=strip_tags($val['district_cn']);
            $list['list'][$key]['scale_cn']=strip_tags($val['scale_cn']);
            $list['list'][$key]['trade_cn']=strip_tags($val['trade_cn']);
            $list['list'][$key]['amount']=strip_tags($val['amount']);
            $list['list'][$key]['sex_cn']=$val['sex_cn']=='不限' ? '性别不限':$val['sex_cn'].'性';
		}
		$list['title'] = $title;
		$this->ajaxReturn(1,'获取数据成功',$list);
	}
	/*
	首页名企
	*/
	public function index_companylist(){
		//$where['职位数量'] = 0;
		$where['排序'] = "rtime";
		$where['名企'] = 1;
		$where['显示数目'] = 6;
		$class = new \Common\qscmstag\company_jobs_listTag($where);
		$comlist = $class->run();
		foreach($comlist['list'] as $k => $v){
				$list['list'][$k]['id'] = $v['id'];
			if($v['logo']){
				$list['list'][$k]['logo'] = $this->attach($v['logo'],'company_logo');
			}
			else{
				$list['list'][$k]['logo'] = $this->attach('no_logo.png','resource');
			}
				$list['list'][$k]['short_name'] = $v['short_name'];
		}
		$this->ajaxReturn(1,'获取数据成功',$list);
	}


    public function index_jobscategory(){
    	$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_jobs'));
    	$list = $class->run();
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	public function index_major(){
    	$major_arr=M('category_major')->where(' parentid > 0 ')->select();
    	$this->ajaxReturn(1,'获取数据成功',$major_arr);
    }
	public function index_jobcategory_info(){
    	$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_jobs_info'));
    	$list = $class->run();
		$this->ajaxReturn(1,'获取数据成功',$list);


    }
	public function other_category(){
    	$type = I('get.type','','trim');
		$class = new \Common\qscmstag\classifyTag(array('类型'=>$type));
    	$list = $class->run();
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	public function city_category(){
    	$district = I('get.district','','trim');
    	$sdistrict = I('get.sdistrict','','trim');
		if(intval($district)>0){
			$citys=M('category_district')->where(' parentid = '.$district)->select();
		}elseif(intval($sdistrict)>0){
			$citys=M('category_district')->where(' parentid = '.$sdistrict)->select();
		}else{
			$citys=M('category_district')->where(' parentid = 0 ')->select();
		}
    	$this->ajaxReturn(1,'获取数据成功',$citys);

    }
	private function Convert_GCJ02_To_BD09($lat,$lng){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng;
        $y = $lat;
        $z =sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta) + 0.0065;
        $lat = $z * sin($theta) + 0.006;
        return array('lng'=>$lng,'lat'=>$lat);
	}
	public function jobslist(){
      $locale=$this->Convert_GCJ02_To_BD09(I('get.latitude','','trim'),I('get.longitude','','trim'));
      $lat=$locale['lat'];
      $lng=$locale['lng'];
      
		$key = I('get.key','','trim');

		$range = I('get.range',0,'intval');
		$allowance = I('get.allowance',0,'intval');
		if(!empty($key)){
			$where['关键字'] = $key;
		}
		if($range>0){
			$where['搜索范围'] = $range;
			$where['经度'] = $lng;
			$where['纬度'] = $lat;
		}
		if($allowance == 1){
			$where['排序'] = "rtime";
			$where['搜索内容'] = "allowance";
		}
		$where['描述长度'] = '100';
		$where['显示数目'] = 10;
		$where['分页显示'] = 1;
		$where['排序'] = 'desc';
        $class = new \Common\qscmstag\jobs_listTag($where);
        $joblist = $class->run();
		foreach($joblist['list'] as $key=>$val){
          
          	$list['list'][$key]['id']=strip_tags($val['id']);
			$list['list'][$key]['jobs_name']=strip_tags($val['jobs_name']);
			$list['list'][$key]['allowance_id']=strip_tags($val['allowance_id']);
			//$list['list'][$key]['allowance_info']=$val['allowance_info'];

			$list['list'][$key]['allowance_info']['per_amount']=strip_tags($val['allowance_info']['per_amount']);
			$list['list'][$key]['allowance_info']['type_alias']=strip_tags($val['allowance_info']['type_alias']);
          
			$list['list'][$key]['stick']=strip_tags($val['stick']);
			$list['list'][$key]['wage_cn']=strip_tags($val['wage_cn']);
			$list['list'][$key]['tag_cn']=$val['tag_cn'];
			$list['list'][$key]['education_cn']=strip_tags($val['education_cn']);
			$list['list'][$key]['experience_cn']=strip_tags($val['experience_cn']);
			$list['list'][$key]['age_cn']=strip_tags($val['age_cn']);
			$list['list'][$key]['companyname']=strip_tags($val['companyname']);
			$list['list'][$key]['companylogo']=strip_tags($val['logo']);
			$list['list'][$key]['map_range']=strip_tags($val['map_range']);
			$list['list'][$key]['district_cn']=strip_tags($val['district_cn']);
          
			$list['list'][$key]['refreshtime']=date('Y-m-d',$val['refreshtime']);
            $list['list'][$key]['refreshtime_cn']=strip_tags($val['refreshtime_cn']);
          
			$setmeal = D('MembersSetmeal')->get_user_setmeal($val['uid']);
			$list['list'][$key]['company']['audit']=$val['company_audit'];
			$list['list'][$key]['company']['setmeal_id']=$setmeal['setmeal_id'];
			$list['list'][$key]['company']['auditurl'] = attach('auth-1.png','resource');
			$list['list'][$key]['company']['setmealurl'] = attach($setmeal['setmeal_id'].'.png','setmeal_img');
            $list['list'][$key]['emergency']=strip_tags($val['emergency']);
            $list['list'][$key]['category_cn']=strip_tags($val['category_cn']);
            $list['list'][$key]['district_cn']=strip_tags($val['district_cn']);
            $list['list'][$key]['scale_cn']=strip_tags($val['scale_cn']);
            $list['list'][$key]['trade_cn']=strip_tags($val['trade_cn']);
            $list['list'][$key]['amount']=strip_tags($val['amount']);
            $list['list'][$key]['sex_cn']=$val['sex_cn']=='不限' ? '性别不限':$val['sex_cn'].'性';
		}
    		if(!$list['list']){
              $list['list']='';
			}	
			$list['total']=$joblist['total'];
			$list['page_params']=$joblist['page_params'];
			$list['page']=$joblist['page'];
			  if(!$joblist['page_params']){
      		$list['page_params']['nowPage'] = '';
      		$list['page_params']['totalPages'] = '';
      		$list['page_params']['totalRows'] = '';
      }
		if(!empty($list)){
			$this->ajaxReturn(1,'获取数据成功',$list);
		}else{
			$this->ajaxReturn(0,'获取数据失败',$list);
		}

	}
    public function jobsshow($id,$uid=0){
        //$class = new \Common\qscmstag\jobs_showTag(array('职位id'=>$id));
        //$info = $class->run();
		$info = D('Jobs')->get_jobs_one(array('id'=>$id));
//		var_dump($info);
        if($info){
            if($uid>0){
                $favorites = D('personal_favorites')->where(array('jobs_id'=>$id,'personal_uid'=>$uid))->find();
            }
          	$info['is_favorites'] = 0;
            if(!empty($favorites)) $info['is_favorites'] = 1;
			$profile=M('CompanyProfile')->where(array('id'=>$info['company_id']))->find();
			$info['company']=$profile;
			$setmeal = D('MembersSetmeal')->get_user_setmeal($info['uid']);
			$info['show_contact_direct'] = $setmeal['show_contact_direct'];
			$info['company']['setmeal_id'] = $setmeal['setmeal_id'];
			$info['company']['setmeal_name'] = $setmeal['setmeal_name'];
			$info['company']['auditurl'] = attach('auth-1.png','resource');
			$info['company']['setmealurl'] = attach($setmeal['setmeal_id'].'.png','setmeal_img');
			$info['contact']=M('JobsContact')->where(array('pid'=>$info['id']))->find();
			$info['expire']=sub_day($info['deadline'],time());  
			$info['contents2'] = $info['contents'];
      		$info['contents'] = nl2br($info['contents']).'<p>-------------------------</p><p>电话联系时，务必说明：</p><p>来自于“福清人才网” milfun.lz91.cn</p>';
          
			if ($info['company']['logo'])
			{
				$info['company']['logo']=$this->attach($info['company']['logo'],'company_logo');
				$info['company']['ylogo']=1;
			}
			else
			{
				$info['company']['logo']=$this->attach('no_logo.png','resource');
			}
			$info['refreshtime_cn']=daterange(time(),$info['refreshtime'],'Y-m-d',"#FF3300");
			
			if($info['negotiable']==0){
				if(C('qscms_wage_unit') == 1){
					$info['minwage'] = $info['minwage']%1000==0?(($info['minwage']/1000).'K'):(round($info['minwage']/1000,1).'K');
					$info['maxwage'] = $info['maxwage']?($info['maxwage']%1000==0?(($info['maxwage']/1000).'K'):(round($info['maxwage']/1000,1).'K')):0;
				}elseif(C('qscms_wage_unit') == 2){
					if($info['minwage']>=10000){
						if($info['minwage']%10000==0){
						   $info['minwage'] = ($info['minwage']/10000).'万';
						}else{
							$info['minwage'] = round($info['minwage']/10000,1);
							$info['minwage'] = strpos($info['minwage'],'.') ? str_replace('.','万',$info['minwage']) : $info['minwage'].'万';
						}
					}else{
						if($info['minwage']%1000==0){
							$info['minwage'] = ($info['minwage']/1000).'千';
						}else{
							$info['minwage'] = round($info['minwage']/1000,1);
							$info['minwage'] = strpos($info['minwage'],'.') ? str_replace('.','千',$info['minwage']) : $info['minwage'].'千';
						}
					}
					if($info['maxwage']>=10000){
						if($info['maxwage']%10000==0){
						   $info['maxwage'] = ($info['maxwage']/10000).'万';
						}else{
							$info['maxwage'] = round($info['maxwage']/10000,1);
							$info['maxwage'] = strpos($info['maxwage'],'.') ? str_replace('.','万',$info['maxwage']) : $info['maxwage'].'万';
						}
					}elseif($info['maxwage']){
						if($info['maxwage']%1000==0){
						   $info['maxwage'] = ($info['maxwage']/1000).'千';
						}else{
							$info['maxwage'] = round($info['maxwage']/1000,1);
							$info['maxwage'] = strpos($info['maxwage'],'.') ? str_replace('.','千',$info['maxwage']) : $info['maxwage'].'千';
						}
					}else{
						$info['maxwage'] = 0;
					}
				}
				if($info['maxwage']==0){
					$info['wage_cn'] = '面议';
				}else{
					if($info['minwage']==$info['maxwage']){
						$info['wage_cn'] = $info['minwage'].'/月';
					}else{
						$info['wage_cn'] = $info['minwage'].'-'.$info['maxwage'].'/月';
					}
				}
			}else{
				$info['wage_cn'] = '面议';
			}
			$age = explode('-',$info['age']);
			if(!$age[0] && !$age[1]){
				$info['age_cn'] = '不限';
			}else{
				$age[0] && $info['age_cn'] = $age[0].'岁以上';
				$age[1] && $info['age_cn'] = $age[1].'岁以下';
			}
			if ($info['tag_cn'])
			{
				$tag_cn=explode(',',$info['tag_cn']);
				$info['tag_cn']=$tag_cn;
			}
			else
			{
				$info['tag_cn']=array();
			}
			//简历处理率
			$apply = M('PersonalJobsApply')->where(array('company_uid'=>$info['uid'],'apply_addtime'=>array('gt',strtotime("-14day"))))->select();
			foreach ($apply as $key => $v) {
				if($v['is_reply']){
					$reply++;
					$v['reply_time'] && $reply_time += $v['reply_time'] - $v['apply_addtime'];
				}
			}
			$info['company']['reply_ratio'] = !$apply ? 100 : intval($reply / count($apply) * 100);
        	$info['company']['reply_time'] = !$reply_time ? '0天' : sub_day(intval($reply_time / count($apply)),0);
			$last_login_time = M('Members')->where(array('uid'=>$info['uid']))->getfield('last_login_time');
        	$info['company']['last_login_time'] = $last_login_time ? fdate($last_login_time) : '未登录';
        $hide = true;
        if($setmeal['show_contact_direct']==0){
            $showjobcontact = C('LOG_SOURCE')==2?C('qscms_showjobcontact_wap'):C('qscms_showjobcontact');
            if($showjobcontact == 0)
            {
                $hide = false;
            }
            else
            {
                if($uid>0){
                    $hide = false;
                }
            }
        }else{
            $hide = false;
        }
        if($hide){
            if($info['contact']['telephone']){
                $info['contact']['telephone'] = contact_hide($info['contact']['telephone']);
            }else{
                $info['contact']['telephone'] = contact_hide(trim($info['contact']['landline_tel'],'-'),1);
            }
        }else{
            if($info['contact']['telephone_show']==0 && $info['contact']['landline_tel_show']==0){
                $info['contact']['telephone_show'] = 0;
            }
            elseif($info['contact']['telephone_show']==1 && $info['contact']['telephone'])
            {
                $info['contact']['telephone_show'] = $info['contact']['telephone_show'];
            }
            elseif($info['contact']['telephone'] && !trim($info['contact']['landline_tel'],'-') && $info['contact']['telephone_show']==0)
            {
                $info['contact']['telephone_show'] = $info['contact']['telephone_show'];
            }
            else
            {
                $info['contact']['telephone'] = trim($info['contact']['landline_tel'],'-');
                $info['contact']['telephone_show'] = $info['contact']['landline_tel_show'];
            }
        }
        $info['contact']['hide'] = $hide;
          
          	//$info['contact']['telephone']=$info['contact']['telephone']?$info['contact']['telephone']:$info['contact']['landline_tel'];
			$info['refreshtime_cn'] = strip_tags($info['refreshtime_cn']);

			if(C('apply.Allowance')){
				$info['allowance_open'] = 1;
				if($info['allowance_id']>0){
					$allowance = D('Allowance/AllowanceInfo')->find($info['allowance_id']);
					$allowance['type_cn'] = D('Allowance/AllowanceInfo')->get_alias_cn($allowance['type_alias']);
					if(C('visitor.uid') && C('visitor.utype')==2){
						$allowance_record = D('Allowance/AllowanceRecord')->where(array('personal_uid'=>C('visitor.uid'),'info_id'=>$info['allowance_id']))->find();
					}
				}else{
					$allowance = array();
				}
			}else{
				$info['allowance_open'] = 0;
				$info['allowance_id'] = 0;
			}
			$info['allowance'] = $allowance;
        	$info['allowance_record'] = $allowance_record;
        if(APP_SPELL){
            if(false === $jobs_cate = F('jobs_cate_list')) $jobs_cate = D('CategoryJobs')->jobs_cate_cache();
            $spell = $info['category'] ? $info['category'] : $info['topclass'];
            $info['jobcategory'] = $jobs_cate['id'][$spell]['spell'];
        }else{
            $info['jobcategory'] = intval($info['topclass']).".".intval($info['category']).".0";
        }
		
            $list_class = new \Common\qscmstag\jobs_listTag(array('显示数目'=>6,'去除id'=>$info['id'],'职位分类'=>$info['jobcategory']));
            $jobslist = $list_class->run();
			foreach($jobslist['list'] as $k => $v){
				$info['jobslist']['list'][$k]['id'] = strip_tags($v['id']);
				$info['jobslist']['list'][$k]['jobs_name'] = strip_tags($v['jobs_name']);
				$info['jobslist']['list'][$k]['companyname'] = strip_tags($v['companyname']);
				$info['jobslist']['list'][$k]['district_cn'] = strip_tags($v['district_cn']);
				$info['jobslist']['list'][$k]['wage_cn'] = strip_tags($v['wage_cn']);
				$info['jobslist']['list'][$k]['refreshtime_cn'] = strip_tags($v['refreshtime_cn']);
			}
        	$where = array('id'=>$id);
        	if(C('apply.Jobclickup')){
            	$range = explode(",", C('qscms_job_clickup_range'));
            	$inc_num = rand($range[0],$range[1]);
        	}else{
            	$inc_num = 1;
        	}
        	if(M('Jobs')->where($where)->find()){
           	$mod = M('Jobs');
            	M('JobsSearch')->where($where)->setInc('click',$inc_num);
            	M('JobsSearchKey')->where($where)->setInc('click',$inc_num);
        	}else{
            	$mod = M('JobsTmp');
        	}
        	$mod->where($where)->setInc('click',$inc_num); 
        }
        $this->ajaxReturn(1,'获取数据成功',$info);
    }
	public function jobscontact($id){
		//$id = I('get.id','','intval');
		$jobscontact = D('jobs_contact')->where(array("id"=>$id))->find();
		$this->ajaxReturn(1,'获取数据成功',$jobscontact['telephone']);

	}
    public function resumeslist(){
		
		$key = I('get.key','','trim');
    	$p = I('get.page',1,'intval');
    	$where['排序'] = 'desc';
    	$resume = new \Common\qscmstag\resume_listTag(array('关键字'=>$key,'显示数目'=>10,'分页显示'=>1,'排序'=>'desc'));
    	$resume_list = $resume->run();

		foreach($resume_list['list'] as $key=>$val){
            $list['list'][$key]['refreshtime_cn']=strip_tags($val['refreshtime_cn']);
            $list['list'][$key]['id']=strip_tags($val['id']);
            $list['list'][$key]['photosrc']=strip_tags($val['photo_img']);
            $list['list'][$key]['sex']=strip_tags($val['sex']);
            $list['list'][$key]['sex_cn']=strip_tags($val['sex_cn']);
            $list['list'][$key]['fullname']=strip_tags($val['fullname']);
            $list['list'][$key]['age']=strip_tags($val['age']);
            $list['list'][$key]['stick']=strip_tags($val['stick']);
            $list['list'][$key]['wage_cn']=strip_tags($val['wage_cn']);
            $list['list'][$key]['experience_cn']=strip_tags($val['experience_cn']);
            $list['list'][$key]['education_cn']=strip_tags($val['education_cn']);
            $list['list'][$key]['district_cn']=strip_tags($val['district_cn']);
            $list['list'][$key]['intention_jobs']=strip_tags($val['intention_jobs']);
            $list['list'][$key]['specialty']=strip_tags($val['specialty']);
            $list['list'][$key]['strong_tag']=strip_tags($val['strong_tag']);
			if(!$val['tag_cn']){
				$list['list'][$key]['tag_cn']='';
			}else{
				$list['list'][$key]['tag_cn']=$val['tag_cn'];
			}
		}
    		if(!$list['list']){
              $list['list']='';
			}	
			$list['total']=$resume_list['total'];
			$list['page_params']=$resume_list['page_params'];
			$list['page']=$resume_list['page'];
			  if(!$resume_list['page_params']){
      		$list['page_params']['nowPage'] = '';
      		$list['page_params']['totalPages'] = '';
      		$list['page_params']['totalRows'] = '';
      }
		if(!empty($list)){
			$this->ajaxReturn(1,'获取数据成功',$list);
		}else{
			$this->ajaxReturn(0,'获取数据失败',$list);
		}	
    }
    /**
    * @ 找人才 Resume
    */
    public function resumeslist2()
    {
        # code...
        $p  = I('get.page');
        $keyword  = I('get.key', '', 'trim');
        
        if (empty($p)) {
            $p = 1;
        }

        // 分页数据条数 6条
        $spage_size = 6;

        $r = M('resume');
        $count = $r->count();
        //找出 对应条数的记录  返回$list
        $firstRow = abs($p - 1) * $spage_size;
        $endRow = $firstRow + $spage_size;
        $pager = pager($count,$spage_size);
        $list = $r->order('refreshtime desc')->limit($firstRow.','.$endRow)->select();


        $result['list'] = $list;
        $result['page'] = $pager->fshow();
        $result['page_params'] = $pager->get_page_params();
        if (!empty($result['list'])) {
            $this->ajaxReturn(1, '获取数据成功', $result);
        } else {
            $this->ajaxReturn(0, '获取数据失败', $result);
        }

        /*$data['errcode']=40029;
        $data['errmsg']='invalid';
        $this->ajaxReturn(1,'',$data);*/

    }
    /***
    *  getresume()
    ***/
    public function getresume()
    {
    	# code...
    	$id = I('get.id',1,'intval');
    	$m=M('resume');
    	$result=$m->where(array('id'=>$id))->find();
    	//计算年龄，-19因为显示默认是1970
    	$result['age']=date("Y",time())- date("Y",$result['birthday'])+1-19;
    	if (!empty($result)) {
            $this->ajaxReturn(1, '获取数据成功', $result);
        } else {
            $this->ajaxReturn(0, '获取数据失败', $result);
        }

    }
    public function companylist(){
		
		$key = I('get.key','','trim');
    	$p = I('get.page',1,'intval');
    	$company = new \Common\qscmstag\company_listTag(array('关键字'=>$key,'显示数目'=>10,'分页显示'=>1));
    	$company_list = $company->run();

		foreach($company_list['list'] as $key=>$val){
          	$list['list'][$key]=$val;
			$list['list'][$key]['refreshtime']=date('Y-m-d',$val['refreshtime']);
            $list['list'][$key]['refreshtime_cn']=strip_tags($val['refreshtime_cn']);
		}
    		if(!$list['list']){
              $list['list']='';
			}
			$list['total']=$company_list['total'];
			$list['page_params']=$company_list['page_params'];
			$list['page']=$company_list['page'];
			 if(!$company_list['page_params']){
      		$list['page_params']['nowPage'] = '';
      		$list['page_params']['totalPages'] = '';
      		$list['page_params']['totalRows'] = '';
      }
		if(!empty($list)){
			$this->ajaxReturn(1,'获取数据成功',$list);
		}else{
			$this->ajaxReturn(0,'获取数据失败',$list);
		}	
    }
	public function resumeshow($id,$uid=0){
        $class = new \Common\qscmstag\resume_showTag(array('简历id'=>$id));
        $info = $class->run();
        if($info){
          
        	if($uid){
          		$info['is_favorites'] = $this->_check_favor($id,$uid);
            	$info['down_resume'] = D('CompanyDownResume')->check_down_resume($id,$uid);
			}
			$info['refreshtime_cn'] = strip_tags($info['refreshtime_cn']);
          if($info['down_resume']){
            $info['telephone'] = $info['telephone_'];
          }
		  	$info['education_count'] = count($info['education_list']);
        	$info['training_count'] = count($info['training_list']);
          
          /*
            $list_class = new \Common\qscmstag\jobs_listTag(array('显示数目'=>6,'去除id'=>$info['id'],'职位分类'=>$info['jobcategory']));
            $info['jobslist'] = $list_class->run();
			foreach($info['jobslist']['list'] as $k => $v){
				$info['jobslist']['list'][$k]['refreshtime_cn'] = strip_tags($v['refreshtime_cn']);
			}
			 */
			foreach($info['work_list'] as $k => $v){
                $oldjobs .= $v['jobs'].',';
			}
			foreach($info['img_list'] as $k => $v){
                 $info['wximg'][] .= $v['img'];
			}
            $info['oldjobs'] = $oldjobs ? substr($oldjobs, 0, -1) : '未填';
            $info['current_cn'] = $info['current_cn'] ? $info['current_cn'] : '我目前已离职，可快速到岗！';
            $info['district_cn'] = $info['district_cn'] ? $info['district_cn'] : '广汉';
            $info['intention_jobs'] = $info['intention_jobs'] ? $info['intention_jobs'] : '期望职位未填';
            $info['householdaddress'] = $info['householdaddress'] ? $info['householdaddress'] : '保密';
            $info['residence'] = $info['residence'] ? $info['residence'] : '保密';
            $info['major_cn'] = $info['major_cn'] ? $info['major_cn'] : '专业未填';
            $info['trade_cn'] = $info['trade_cn'] ? $info['trade_cn'] : '不限行业';
            $info['telephone'] = str_replace('<img src="','',$info['telephone']);
            $info['telephone'] = str_replace('"/>','',$info['telephone']);
        }
        $this->ajaxReturn(1,'获取数据成功',$info);
    }
    //检测是否已收藏
    protected function _check_favor($resume_id,$uid){
        $r = D('CompanyFavorites')->where(array('resume_id'=>$resume_id,'company_uid'=>$uid))->find();
        if($r){
            return 1;
        }else{
            return 0;
        }
    }
	public function companyshow($id,$uid=0){
//        $class = new \Common\qscmstag\company_showTag(array('企业id'=>$id));
//        $info = $class->run();
		$info = M('company_profile')->find($id);
		if($info){
			if($info['map_x']>0 && $info['map_y']>0){
				$info['map_x'] = $info['map_x']-0.0064;
				$info['map_y'] = $info['map_y']-0.0064;
			}
			if($info['tag']){
				$arr = explode(",",$info['tag']);
				foreach($arr as $k => $v){
					$temp = explode("|",$v);
					$tag[] = $temp[1];
				}
				$info['tag_arr'] = $tag;
			}
			$info['contents'] = nl2br($info['contents']);
        $hide = true; 
		$setmeal = D('MembersSetmeal')->get_user_setmeal($info['uid']);
			$info['auditurl'] = attach('auth-1.png','resource');
			$info['setmealurl'] = attach($setmeal['setmeal_id'].'.png','setmeal_img');
        if($setmeal['show_contact_direct']==0){
            $showjobcontact = C('LOG_SOURCE')==2?C('qscms_showjobcontact_wap'):C('qscms_showjobcontact');
            if($showjobcontact == 0)
            {
                $hide = false;
            }
            else
            {
                if($uid>0){
                    $hide = false;
                }
            }
        }else{
            $hide = false;
        }
		  $img_map['uid'] = $info['uid'];
        if(C('qscms_companyimg_display')==1){
            $img_map['audit'] = 1;
        }else{
            $img_map['audit'] = array(array('eq',1),array('eq',2),'or');
        }
        $profile['img'] = D('CompanyImg')->where($img_map)->select();
        foreach ($profile['img'] as $key => $value) {
            $info['img'][$key]['img'] = attach($value['img'],'company_img');
            $info['wximg'][] .= attach($value['img'],'company_img');
        }
        $info['landline_tel'] = trim($info['landline_tel'],'-');
        $hide && $info['telephone'] = contact_hide($info['telephone'],2);
        $hide && $info['landline_tel'] = contact_hide($info['landline_tel'],1);
        $hide && $info['email'] = contact_hide($info['email'],3);
        $info['hide'] = $hide;
            if($info['logo']){
                $info['logo'] = $this->attach($info['logo'],'company_logo');
            }
            else{
                $info['logo'] = $this->attach('no_logo.png','resource');
            }
			
            $list_class = new \Common\qscmstag\jobs_listTag(array('显示数目'=>6,'会员uid'=>$info['uid']));
            $jobslist = $list_class->run();
			foreach($jobslist['list'] as $k => $v){
				$info['jobslist']['list'][$k]['id'] = strip_tags($v['id']);
				$info['jobslist']['list'][$k]['jobs_name'] = strip_tags($v['jobs_name']);
				$info['jobslist']['list'][$k]['companyname'] = strip_tags($v['companyname']);
				$info['jobslist']['list'][$k]['district_cn'] = strip_tags($v['district_cn']);
				$info['jobslist']['list'][$k]['wage_cn'] = strip_tags($v['wage_cn']);
				$info['jobslist']['list'][$k]['refreshtime_cn'] = strip_tags($v['refreshtime_cn']);
			}
        }
        $this->ajaxReturn(1,'获取数据成功',$info);
    }
    public function get_openid($code){
    	 $appid = 'wxff9826660786521d';
		$appsecret = 'aec07cbdcd186bdbda96ae8a9b7a561c';
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';
      //$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=wx653988c2bc5d543f&secret=53816d431f4adbd1c16ab670888e8199&js_code='.$code.'&grant_type=authorization_code';
        $result = https_request($url);
        $jsoninfo = json_decode($result, true);
        $openid = $jsoninfo["openid"];
        if($openid){ 
            $this->ajaxReturn(1,'获取数据成功',$openid);
        }else{
            $this->ajaxReturn(0,'获取数据失败');
        }
        //$this->ajaxReturn(1,'获取数据成功',$openid);
    } 
    public function getWxUerinfo($code){
    	 $appid = 'wxff9826660786521d';
		$appsecret = 'aec07cbdcd186bdbda96ae8a9b7a561c';
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';
      	//$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=wx653988c2bc5d543f&secret=53816d431f4adbd1c16ab670888e8199&js_code='.$code.'&grant_type=authorization_code';C('qscms_weixinapp_appid')
        
        $result = https_request($url);
        $jsoninfo = json_decode($result, true);
        if($jsoninfo){
            $this->ajaxReturn(1,'获取数据成功',$jsoninfo);
        }else{
            $this->ajaxReturn(0,'获取数据失败');
        }
    } 
    public function getPhoneNumber(){
    	/**/
		//$post_data['appid'] =C('qscms_weixinapp_appid');
		$post_data['appid'] =  'wxff9826660786521d';
		$post_data['sessionKey'] = I('request.sessionKey','','trim');
		$post_data['encryptedData']=I('request.encryptedData','','trim');
		$post_data['iv'] = I('request.iv','','trim');
 		$jsoninfo = $this->decryptData($post_data);
        $jsoninfo =  json_decode($jsoninfo);
        if($jsoninfo){
            $this->ajaxReturn(1,'获取号码成功',$jsoninfo);
        }else{
            $this->ajaxReturn(0,'获取号码失败，请重试！');
        }
       
    }
    /**************小程序解密****************/
    /**
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
     *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptData( $data)
	{
		$OK = 0;
		$IllegalAesKey = -41001;
		$IllegalIv = -41002;
		$IllegalBuffer = -41003;
		$DecodeBase64Error = -41004;
		if (strlen($data['sessionKey']) != 24) {
			return $IllegalAesKey ;
		}
		$aesKey=base64_decode($data['sessionKey']);
		if (strlen($data['iv']) != 24) {
			return $IllegalIv;
		}
		$aesIV=base64_decode($data['iv']);
		$aesCipher=base64_decode($data['encryptedData']);
		$result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
		$dataObj=json_decode( $result );
		if( $dataObj  == NULL )
		{
			return $IllegalBuffer;
		}
		if( $dataObj->watermark->appid != $data['appid'] )
		{
			return $IllegalBuffer;
		}
		$data = $result;
		return $data;
	}


	public function favoritejobs(){
		$jid = I('request.id','','intval');
		!$jid && $this->ajaxReturn(0,'请选择职位！');
		$uid = I("get.uid","intval");
		$has = D('PersonalFavorites')->where(array('jobs_id'=>$jid,'personal_uid'=>$uid))->find();
		$user = D("members")->where(array("uid"=>$uid))->find();
		if($has){
			D('PersonalFavorites')->where(array('jobs_id'=>$jid,'personal_uid'=>$uid))->delete();
			$this->ajaxReturn(1,'取消收藏成功！','cancel');
		}else{
			$reg = D('PersonalFavorites')->add_favorites($jid,$user);
	        !$reg['state'] && $this->ajaxReturn(0,$reg['error']);
			$this->ajaxReturn(1,'收藏成功！');
		}

	}
	public function applyjobs(){
		$jobsid = I("get.id","intval");
		$uid = I("get.uid","intval");
		$user = D("members")->where(array("uid"=>$uid))->find();
		$resume=D('resume')->where(array("uid"=>$uid))->select();
		!$resume && $this->ajaxReturn(0,"亲，快去填份简历吧！");
		$reg = D('PersonalJobsApply')->jobs_apply_add($jobsid,$user);
        !$reg['state'] && $this->ajaxReturn(0,"亲，您已经申请过这个职位了",$reg['create']);
        $reg['data']['failure'] && $this->ajaxReturn(0,$reg['data']['list'][$jid]['tip']);
		$this->ajaxReturn(1,'投递成功！');

	}
	/**
	 * 关注企业/取消关注
	 */
	public function favoritecompany($id){
		$id = I("get.id","intval");
		$uid = I("get.uid","intval");
		if(!$id){
			$this->ajaxReturn(0,'请选择企业！');
		}
		$user = D("members")->where(array("uid"=>$uid))->find();
		$r = D('PersonalFocusCompany')->add_focus($id,$user);
		$this->ajaxReturn($r['state'],$r['msg'],$r['data']);
	}
	public function explain_show($id){
        $class = new \Common\qscmstag\explain_showTag(array('说明页id'=>$id));
        $info = $class->run();
        $info['content']=htmlspecialchars_decode($info['content'],ENT_QUOTES);
        $this->ajaxReturn(1,'获取数据成功',$info);
    }
		public function  getqrimg($id){
   		$wname = $id . ".jpeg";
		$file = QSCMS_DATA_PATH.'upload/JobsQr/'.$wname;
		$file2 = attach($wname,'JobsQr');
        $filesize = $this->getHttpStatus($file2);
        if ($filesize!=200) {
			$appid = C('qscms_weixinapp_appid');
			$secret = C('qscms_weixinapp_appsecret');
				$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret . "";
          		$data = $this->https_request($url);
				$data = json_decode($data, true);
				$data2 = array(
 				   "scene" => $id,
 				   "page" => "pages/jobshow/jobshow",
 				   "width" => 100
				);
				$data2 = json_encode($data2);
				$url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $data['access_token'] . "";
          		$data = $this->https_request($url,$data2);
				$img = base64_encode($data);
				$base64_image_content = "data:image/jpeg;base64," . $img;
				if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
			    	$type = $result[2];
   			  		//$wname = $id . "." . $type;
					//$file = QSCMS_DATA_PATH.'upload/JobsQr/'.$wname;
    				file_put_contents($file, base64_decode(str_replace($result[1], '', $base64_image_content)));

   			 		return $qrimg = $this->attach($wname,'JobsQr');
				}
        }else{
   			 	return $qrimg = $this->attach($wname,'JobsQr');
    	}
    }

    //set_time_limit(0);
	public function getHttpStatus($url) {
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_NOBODY,1);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_TIMEOUT,5);
        curl_exec($curl);
        $re = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        return  $re;
    }
	public function  getjobsqr($id){
		$appid = C('qscms_weixinapp_appid');
		$secret = C('qscms_weixinapp_appsecret');
		$info = D('Jobs')->get_jobs_one(array('id'=>$id));
        if($info){
      		$info2['jobs_name'] = $info['jobs_name'];
      		$info2['companyname'] = $info['companyname'];
      		$info2['contents'] = $info['contents'];
      		$info2['click'] = $info['click'];
      		$info2['district_cn'] = $info['district_cn']?$info['district_cn']:C('qscms_default_district');

			$profile=M('CompanyProfile')->where(array('id'=>$info['company_id']))->find();
			$info['company']=$profile;
			if ($info['company']['logo'])
			{
				$info2['logo']=$this->attach($info['company']['logo'],'company_logo');
				$info2['ylogo']=1;
			}
			else
			{
				$info2['logo']=$this->attach('no_logo.png','resource');
			}
			if($info['negotiable']==0){
				if($info['maxwage']==0){
					$info2['wage_cn'] = '面议';
				}else{
					if($info['minwage']==$info['maxwage']){
						$info2['wage_cn'] = $info['minwage'].'元';
					}else{
						$info2['wage_cn'] = $info['minwage'].'-'.$info['maxwage'].'元';
					}
				}
			}else{
				$info2['wage_cn'] = '面议';
			}
        	$where = array('id'=>$id);
        	if(C('apply.Jobclickup')){
            	$range = explode(",", C('qscms_job_clickup_range'));
            	$inc_num = rand($range[0],$range[1]);
        	}else{
            	$inc_num = 1;
        	}
        	if(M('Jobs')->where($where)->find()){
           	$mod = M('Jobs');
            	M('JobsSearch')->where($where)->setInc('click',$inc_num);
            	M('JobsSearchKey')->where($where)->setInc('click',$inc_num);
        	}else{
            	$mod = M('JobsTmp');
        	}
   			$info2['qrimg'] = $this->getqrimg($id);
        	$this->ajaxReturn(1,'获取数据成功',$info2);
        }
    }
    protected function https_request($url,$data = null){
        if(function_exists('curl_init')){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        }else{
            return false;
        }
    }
  /**
 * 获取图片
 */
    public function attach($attach, $type) {
    if(empty($attach)) return false;
    if (false === strpos($attach, 'http://') && false === strpos($attach, 'https://')) {
        //本地附件
        return 'https://www.clrcw.com.cn/'.__ROOT__ . '/data/upload/' . $type . '/' . $attach;
        //远程附件
        //todo...
    } else {
        //URL链接
        return $attach;
    }
}
  /**
     * 验证码
     */
    public function get_vcode(){
        import("Library.Org.Verify.Verify",dirname(__FILE__). '/../');
        $config =    array(
            'fontSize'    =>    30,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    true, // 关闭验证码杂点
        );

        $Verify = new \Verify($config);
        $res = $Verify->entry();
       $this->ajaxReturn(1,'获取成功',$res) ;

    }
}
?>