<?php
namespace Weixinapp\Controller;
use Common\Controller\FrontendController;
use Common\ORG\UploadFile;
class WxPersonalController extends FrontendController{

	private $userInfo='';
	private $dns;

	public function _initialize() {
        parent::_initialize();
		/**********服务器调试**********/
		$secretKey = C('qscms_weixinapp_secretkey');
		/**********本地调试**********/
		//$secretKey = 'MilFun';
		$secret = I('request.secretKey',"","trim");
		if($secret != $secretKey){
			$this->ajaxReturn(2,'秘钥错误！',$list);
		}
		$openid = I('request.openid',"","trim");
		if($openid == ''){
			$this->ajaxReturn(2,'读取用户OpenId失败！');
		}

		//$openid='owvvu0CgTrEpsQuX5YDy8iZzqnto';//正式用时删除此行
		$bindInfo=M('MembersBind')->where(array('keyid'=>$openid,'type'=>'wxapp','uid'=>array('gt',0)))->find();
		$this->dns = C('qscms_site_domain').rtrim(C('qscms_site_dir'),'/');
        $userInfo=D('Resume')->where(array('uid'=>$bindInfo['uid'],'def'=>1))->find();
        if(empty($userInfo)){
            $userInfo = D('members')->where(array('uid'=>$bindInfo['uid']))->find();
            $userInfo['realname'] = $userInfo['username'];
            $userInfo['avatar']=attach('no_photo_male.png','resource');
        }else{
            $userInfo['avatar']=$userInfo['photo_img']!==''?attach($userInfo['photo_img'],'avatar'):attach('no_photo_male.png','resource');
        }

		$user_info=D('Members')->get_user_one(array('uid'=>$bindInfo['uid']));
		$this->userInfo=$userInfo;
		$this->userInfo['uid']=$bindInfo['uid'];
		$this->userInfo['utype']=$user_info['utype'];
		$this->userInfo['state']=$user_info['state'];
		$this->userInfo['username']=$user_info['username'];
		
    }



	/*
	获取用户信息
	*/
	public function getUserInfo(){
      
    	$resume = D('Resume')->get_resume_list(array('where'=>array('uid'=>$this->userInfo['uid'],'def'=>1),'limit'=>1));
        $this->userInfo['is_resume_max']= 1;
			$this->userInfo['realname']='游客';
		if(empty($resume)){
			$this->userInfo['is_resume']=0;
		} else {
			$this->userInfo['realname']=$resume[0]['fullname'];
			$this->userInfo['is_resume']='1';
			$this->userInfo['resume_id']=$resume[0]['id'];
			$this->userInfo['percent']=$resume[0]['complete_percent'];
			$sort=M('Resume')->where('refreshtime > '.$resume[0]['refreshtime'])->count();
			$this->userInfo['sort']=$sort+1;
            $this->userInfo['audit']=$resume[0]['_audit'];
          	$ref['uid']=$this->userInfo['uid'];
          	$ref['utype']='2';
            $user_visitor = new \Common\qscmslib\user_visitor;
            $user_visitor->assign_info($ref);
		}
      		//file_put_contents("1.txt",var_export($bindInfo,TRUE));
		//echo "<pre>";print_r($this->userInfo);echo M('Resume')->getLastSql();
    	$this->ajaxReturn(1,'获取数据成功',$this->userInfo);
    }

	public function recommendjobs(){
      	if($this->userInfo['utype']==2){
			$resume = D('Resume')->get_resume_list(array('where'=>array('uid'=>$this->userInfo['uid'],'def'=>1),'limit'=>1));
      	}
		if(empty($resume)){
			$this->ajaxReturn(2,'无简历');
		} else {
			$where['显示数目'] = 10;
			$where['职位分类']=$resume[0]['intention_jobs_id'];
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
			$list['title'] = '推荐职位';
			//echo "<pre>";print_r($list);
			$this->ajaxReturn(1,'获取数据成功',$list);
		}
	}
	public function editsave_resume_title(){
		$map['id']=$_GET['id'];
		$setsqlarr['title']=$_GET['title'];
		$list = D('Resume')->where($map)->save($setsqlarr);
    	$this->ajaxReturn(1,'修改成功',$list);
	}
	public function Gworker(){
         $category = D('Category')->get_category_cache();
			/*-------------------------------------------------------------------------------------------*/
			$cate_1=array();
			$cate_2=array();
			$cate_3=array();
			$first_cate=M('category_district')->where(array('parentid'=>0))->order('category_order desc,id asc')->select();
			foreach($first_cate as $k=>$v){
				$cate_1[$k]['id']=$v['id'];
				$cate_1[$k]['name']=$v['categoryname'];
			}
			$second_cate=M('category_district')->where(array('parentid'=>$first_cate[0]['id']))->order('category_order desc,id asc')->select();
			foreach($second_cate as $k=>$v){
				$cate_2[$k]['id']=$v['id'];
				$cate_2[$k]['name']=$v['categoryname'];
			}
			$third_cate=M('category_district')->where(array('parentid'=>$second_cate[0]['id']))->order('category_order desc,id asc')->select();
			foreach($third_cate as $k=>$v){
				$cate_3[$k]['id']=$v['id'];
				$cate_3[$k]['name']=$v['categoryname'];
			}
			$data['first_cate_dis'][]=$cate_1;
			$data['first_cate_dis'][]=$cate_2;
			$data['first_cate_dis'][]=$cate_3;

			foreach($cate_1 as $k=>$v){
				$tmp=M('category_district')->where(array('parentid'=>$v['id']))->order('category_order desc,id asc')->select();
				foreach($tmp as $sk=>$sv){
					$arr['id']=$sv['id'];
					$arr['name']=$sv['categoryname'];
					$cate_sub_dis[$k][]=$arr;
				}
			}
			foreach($cate_1 as $k1=>$v1){
				foreach($cate_sub_dis[$k1] as $k=>$v){
					$tmp=M('category_district')->where(array('parentid'=>$v['id']))->order('category_order desc,id asc')->select();
					if($tmp){
						foreach($tmp as $sk=>$sv){
							
							$arr['id']=$sv['id'];
							$arr['name']=$sv['categoryname'];
							$cate_sub_dis1[$k1][$k][]=$arr;
						}
					} else {
						foreach($cate_sub_dis[$k1] as $sk=>$sv){
							
							$arr['id']=0;
							$arr['name']='不限';
							$cate_sub_dis1[$k1][$k][0]=$arr;
						}
					}
				}
			}

			$data['cate_sub_dis1']=$cate_sub_dis;
			$data['cate_sub_dis2']=$cate_sub_dis1;
			//--------------------------------------------------------------------------------------
      

            $category_wage = D('Gworker/GworkerCategory')->get_category_cache('QS_wage');
			foreach($category_wage as $k=>$v){
				$arr5[]=$k.",".$v;
			}
			$data['wage']=$arr5;
			$data['wagelen']=count($category_wage);
			$data['ad1']='https://www.edrc.cn/data/upload/attach_img/weituo.png';
			$data['ad2']='说明：发布委托职位，联系客服后发布。';
      
			$this->ajaxReturn(1,'',$data);
	}
	public function GworkerSubmit(){
        $model = D('Gworker/GworkerPublishApply');
            $post_data = I('get.');
            if(false === $reg = $model->create($post_data)){
                $this->ajaxReturn(0,$model->getError());
            }else{
                if(false === $model->add($reg)){
                    $this->ajaxReturn(0,$model->getError());
                }else{
		if(C('apply.Weixin')){
          	$wxdata['jname']	= $post_data['jobs_name'];
          	$wxdata['cname']	= $post_data['contact'];
          	$wxdata['msg']	= $post_data['mobile'];
          	$wxdata['title']	= "委托职位发布";
			D('Weixin/TplMsg')->set_admin_msg($wxdata);
		}
                    $this->ajaxReturn(1,'提交成功！业务受理电话：0377-60585288');
            	}
            }
	}
	/*
	添加简历信息
	*/
	public function resumeAdd(){
		$uid=$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));


         $category = D('Category')->get_category_cache();
            $get_userprofile=D('Resume')->where(array('uid'=>$uid,'def'=>1))->find();
      		if(!$get_userprofile){
				$get_userprofile['telephone']=$user_info['mobile'];
				$get_userprofile['email']=$user_info['email'];
      		}else{
				$get_userprofile['telephone']=$get_userprofile['phone'];
            }
				$get_userprofile['mobile_audit']=$user_info['mobile_audit'];
				$get_userprofile['email_audit']=$user_info['email_audit'];
			$get_userprofile['qscms_login_per_audit_mobile']=0;
			$data['userprofile']=$get_userprofile;

            $birthdate_arr = range(date('Y')-16,date('Y')-65);
			/*-------------------------------------------------------------------------------------------*/
			$first_cate=M('category_jobs')->where(array('parentid'=>0))->select();
			//$first_count = M('category_jobs')->where(array('parentid'=>0))->count();
			//echo "<pre>";print_r($first_cate);
			foreach($first_cate as $k=>$v){
				$cate_1[$k]['id']=$v['id'];
				$cate_1[$k]['name']=$v['categoryname'];
			}
			//echo "<pre>";print_r($first_cate);
			$second_cate=M('category_jobs')->where(array('parentid'=>$first_cate[0]['id']))->select();
			foreach($second_cate as $k=>$v){
				$cate_2[$k]['id']=$v['id'];
				$cate_2[$k]['name']=$v['categoryname'];
			}


			//echo "<pre>";print_r($second_cate);
			$third_cate=M('category_jobs')->where(array('parentid'=>$second_cate[0]['id']))->select();
			foreach($third_cate as $k=>$v){
				$cate_3[$k]['id']=$v['id'];
				$cate_3[$k]['name']=$v['categoryname'];
			}
			$data['first_cate'][]=$cate_1;
			$data['first_cate'][]=$cate_2;
			/*$data['first_cate'][]=$cate_3;
*/

			foreach($cate_1 as $k=>$v){
				$tmp=M('category_jobs')->where(array('parentid'=>$v['id']))->select();
				foreach($tmp as $sk=>$sv){
					$arr['id']=$sv['id'];
					$arr['name']=$sv['categoryname'];
					$cate_sub[$k][]=$arr;
				}
			}
			foreach($cate_1 as $k1=>$v1){
				foreach($cate_sub[$k1] as $k=>$v){
					$tmp=M('category_jobs')->where(array('parentid'=>$v['id']))->select();
					if($tmp){
						foreach($tmp as $sk=>$sv){
							
							$arr['id']=$sv['id'];
							$arr['name']=$sv['categoryname'];
							$cate_sub1[$k1][$k][]=$arr;
						}
					} else {
						foreach($cate_sub[$k1] as $sk=>$sv){
							
							$arr['id']=0;
							$arr['name']='不限';
							$cate_sub1[$k1][$k][0]=$arr;
						}
					}
				}
			}

			$data['cate_sub1']=$cate_sub;
			$data['cate_sub2']=$cate_sub1;
			//echo "<pre>";print_r($cate_1);
			//echo "<pre>";print_r($cate_sub1);
			/*-------------------------------------------------------------------------------------------*/

			/*-------------------------------------------------------------------------------------------*/
			$cate_1=array();
			$cate_2=array();
			$cate_3=array();
			$first_cate=M('category_district')->where(array('parentid'=>0))->order('category_order desc,id asc')->select();
			foreach($first_cate as $k=>$v){
				$cate_1[$k]['id']=$v['id'];
				$cate_1[$k]['name']=$v['categoryname'];
			}
			$second_cate=M('category_district')->where(array('parentid'=>$first_cate[0]['id']))->order('category_order desc,id asc')->select();
			foreach($second_cate as $k=>$v){
				$cate_2[$k]['id']=$v['id'];
				$cate_2[$k]['name']=$v['categoryname'];
			}
			$third_cate=M('category_district')->where(array('parentid'=>$second_cate[0]['id']))->order('category_order desc,id asc')->select();
			foreach($third_cate as $k=>$v){
				$cate_3[$k]['id']=$v['id'];
				$cate_3[$k]['name']=$v['categoryname'];
			}
			$data['first_cate_dis'][]=$cate_1;
			$data['first_cate_dis'][]=$cate_2;
			$data['first_cate_dis'][]=$cate_3;


			foreach($cate_1 as $k=>$v){
				$tmp=M('category_district')->where(array('parentid'=>$v['id']))->order('category_order desc,id asc')->select();
				foreach($tmp as $sk=>$sv){
					$arr['id']=$sv['id'];
					$arr['name']=$sv['categoryname'];
					$cate_sub_dis[$k][]=$arr;
				}
			}
			foreach($cate_1 as $k1=>$v1){
				foreach($cate_sub_dis[$k1] as $k=>$v){
					$tmp=M('category_district')->where(array('parentid'=>$v['id']))->order('category_order desc,id asc')->select();
					if($tmp){
						foreach($tmp as $sk=>$sv){
							
							$arr['id']=$sv['id'];
							$arr['name']=$sv['categoryname'];
							$cate_sub_dis1[$k1][$k][]=$arr;
						}
					} else {
						foreach($cate_sub_dis[$k1] as $sk=>$sv){
							
							$arr['id']=0;
							$arr['name']='不限';
							$cate_sub_dis1[$k1][$k][0]=$arr;
						}
					}
				}
			}

			$data['cate_sub_dis1']=$cate_sub_dis;
			$data['cate_sub_dis2']=$cate_sub_dis1;
			//--------------------------------------------------------------------------------------



			foreach($category['QS_education'] as $k=>$v){
				$arr[]=$k.",".$v;
			}
			foreach($category['QS_experience'] as $k=>$v){
				$arr1[]=$k.",".$v;
			}
			foreach($category['QS_current'] as $k=>$v){
				$arr2[]=$k.",".$v;
			}
			foreach($category['QS_jobs_nature'] as $k=>$v){
				$arr3[]=$k.",".$v;
			}
			foreach($category['QS_trade'] as $k=>$v){
				$arr4[]=$k.",".$v;
			}
			foreach($category['QS_wage'] as $k=>$v){
				$arr5[]=$k.",".$v;
			}
			foreach($category['QS_first_cate'] as $k=>$v){
				$arr6[]=$k.",".$v;
			}
			$data['education']=$arr;
			$data['edulen']=count($category['QS_education']);
			$data['experience']=$arr1;
			$data['explen']=count($category['QS_experience']);
			$data['current']=$arr2;
			$data['curlen']=count($category['QS_current']);
			$data['jobs_nature']=$arr3;
			$data['natlen']=count($category['QS_jobs_nature']);
			$data['trade']=$arr4;
			$data['tralen']=count($category['QS_trade']);
			$data['wage']=$arr5;
			$data['wagelen']=count($category['QS_wage']);
			
			//echo "<pre>";print_r($data);
			$this->ajaxReturn(1,'',$data);
	}

	public function resumeAddSubmit(){
		$uid=$this->userInfo['uid'];
		$user_info=$this->userInfo;


			$intention_jobs_id=I('get.intention_jobs_id','','trim,badword');

			$intention=M('category_jobs')->where(array('id'=>$intention_jobs_id))->find();
			$tmpdata=M('category_jobs')->where(array('id'=>$intention['parentid']))->find();
			$_GET['intention_jobs_id']=$tmpdata['parentid'].'.'.$tmpdata['id'].'.'.$intention_jobs_id;



            $ints = array('sex','birthdate','education','experience','nature','current','wage');
            $trims = array('telephone','fullname','intention_jobs_id','trade','district','residence');
            foreach ($ints as $val) {
                $setsqlarr[$val] = I('get.'.$val,0,'intval');
            }
			//$this->ajaxReturn(0,$setsqlarr[$fullname]);
            foreach ($trims as $val) {
                $setsqlarr[$val] = I('get.'.$val,'','trim,badword');
            }
            foreach(array('major','marriage','householdaddress','residence','qq','weixin') as $val){
            	C('visitor.'.$val) && $setsqlarr[$val] = C('visitor.'.$val);
            }
            $resume_count == 0 && $setsqlarr['def'] = 1;
            $setsqlarr['display_name'] = C('qscms_default_display_name');
      		if(!$setsqlarr['fullname']) $this->ajaxReturn(0,'请填写真实姓名！');
      		if(!$setsqlarr['birthdate']) $this->ajaxReturn(0,'请选择出生年份！');
      		if(!$setsqlarr['education']) $this->ajaxReturn(0,'请选择最高学历！');
      		if(!$setsqlarr['experience']) $this->ajaxReturn(0,'请选择工作经验！');
        	$email = I('get.email',0,'trim');
      		//if(!filter_var($email, FILTER_VALIDATE_EMAIL) && $email) $this->ajaxReturn(0,'请填写正确邮箱！abc123@qq.com');
      		if(filter_var(I('get.email',0,'trim'), FILTER_VALIDATE_EMAIL)) $data['email']=$setsqlarr['email']=$email;
      
            if(!$setsqlarr['telephone']) $this->ajaxReturn(0,'请填写联系手机！');
            if(!$setsqlarr['current']) $this->ajaxReturn(0,'请选择目前状态！');
            if(!$setsqlarr['nature']) $this->ajaxReturn(0,'请选择工作性质！');
            if(!$setsqlarr['trade']) $this->ajaxReturn(0,'请选择期望行业！');
            if($setsqlarr['intention_jobs_id']=='..') $this->ajaxReturn(0,'请选择期望职位！');
            if(!$setsqlarr['wage']) $this->ajaxReturn(0,'请选择期望薪资！');
            if($setsqlarr['district']=='..') $this->ajaxReturn(0,'请选择工作地区！');

			$user = M('Members')->where(array('mobile'=>$setsqlarr['telephone']))->find();
			if($user['utype']==1){
				    $this->ajaxReturn(2,'检测到手机已绑定企业账户；请登陆网页解绑：www.edrc.cn');
      		}
      	if(!$uid){
        	$data['reg_type'] = 1;
        	$data['utype'] = 2;
      		if(filter_var(I('get.email',0,'trim'), FILTER_VALIDATE_EMAIL)) $data['email']=I('get.email',0,'trim');
        	//$data['email'] = I('get.email',0,'trim');
        	$data['mobile'] = I('get.telephone',0,'trim');
			$passport = $this->_user_server();
        	if(false === $data = $passport->register($data)) $this->ajaxReturn(2,$passport->get_error());
        	D('Members')->user_register($data);
        	if(false === $this->visitor->login($data['uid'])) $this->ajaxReturn(2,$this->visitor->getError());
        $sendSms['tpl']='set_register_resume';
        $sendSms['data']=array('username'=>$data['username'].'','password'=>$data['password']);
        $sendSms['mobile']=$data['mobile'];
        D('Sms')->sendSms('captcha',$sendSms);
        	$user_info = $this->visitor->info;
		file_put_contents("Log/WxappLog.txt", date("Y-m-d H:i:s") .'-------------------'.$data['mobile'].PHP_EOL, FILE_APPEND);
            $nickName	= I('get.nickName');
			$openid		= I('get.openid','','trim');
					$condition['uid'] = $condition2['uid'] = $user_info['uid'];
					$condition['keyid'] = array('neq',$openid);
					$condition['type'] = $condition2['type'] = 'wxapp';
					$bindInfo=M('MembersBind')->where($condition)->find();
					if($bindInfo){
						M('MembersBind')->where($condition2)->delete();
					}
                    $user_bind_info['uid'] = $user_info['uid'];
                    $user_bind_info['type'] ='wxapp';
                    $user_bind_info['keyid'] =$openid;
                    $user_bind_info['info']=serialize(array('keyid'=>$openid,'keyname'=>$nickName));
                    $user_bind_info['bindingtime']=time();
                    M("MembersBind")->add($user_bind_info);
        }
      
            $rst=D('Resume')->add_resume($setsqlarr,$user_info);
            if(!$rst['state']) $this->ajaxReturn(0,$rst['error']);
        	$jobid = I('get.jobid',0,'intval');
        	$resume_job_apply = I('get.resume_job_apply',0,'intval');
        	if($resume_job_apply=='1'){
        	    $this->_resume_apply_exe($jobid,$rst['id'],$user_info['uid']);
        	}
            $this->ajaxReturn(1,'简历创建成功！',array('pid'=>$rst['id']));
	}

	public function resumeshow(){
		$pid=$_GET['pid'];
    	$list = D('Resume')->get_resume_one($pid);
		$userwhere['uid']=$list['uid'];
    	$user = D('Members')->where($userwhere)->find();
		$list['age']=date("Y",time())-$list['birthdate'];
		$list['tags']=explode(",",$list['tag']);
		$list['tags_cn']=explode(",",$list['tag_cn']);
		foreach($list['tags'] as $k=>$v){
			$list['tags_'][$k]['tags']=$v;
		}
		foreach($list['tags_cn'] as $key=>$val){
			$list['tags_'][$key]['tags_cn']=$val;
		}
		if($list['photo_img']){
			$list['photoimg']=attach($list['photo_img'],'avatar');
		}else{
			if($list['sex']==1){
				$list['photoimg']=attach('no_photo_male.png','resource');
			}else{
				$list['photoimg']=attach('no_photo_female.png','resource');
			}
		}
		//最高学历
		$class_edu = new \Common\qscmstag\classifyTag(array('类型'=>'QS_education'));
    	$list_edu = $class_edu->run();
		foreach($list_edu as $k=>$v){
			$lists_edu[]=$k;
		}
		foreach($lists_edu as $k=>$v){
			if($v==$list['education']){
				$list['education']=$k;
			}
		}
		//工作经验
		$class_exp = new \Common\qscmstag\classifyTag(array('类型'=>'QS_experience'));
    	$list_exp = $class_exp->run();
		foreach($list_exp as $k=>$v){
			$lists_exp[]=$k;
		}
		foreach($lists_exp as $key=>$val){
			if($val==$list['experience']){
				$list['experience']=$key;
			}
		}
		//出生年份
		for($i=0;$i<=100;$i++){
			if($i+1950==$list['birthdate']){
				$list['birthdate']=$i;
			}
		}
		//目前状态
		$class_current = new \Common\qscmstag\classifyTag(array('类型'=>'QS_current'));
    	$list_current = $class_current->run();
		foreach($list_current as $k=>$v){
			$lists_current[]=$k;
		}
		foreach($lists_current as $key=>$val){
			if($val==$list['current']){
				$list['current']=$key;
			}
		}
		//工作性质
		$class_jobsnature = new \Common\qscmstag\classifyTag(array('类型'=>'QS_jobs_nature'));
    	$list_jobsnature = $class_jobsnature->run();
		foreach($list_jobsnature as $k=>$v){
			$lists_jobsnature[]=$k;
		}
		foreach($lists_jobsnature as $key=>$val){
			if($val==$list['nature']){
				$list['nature']=$key;
			}
		}
		//期望行业
		$class_trade = new \Common\qscmstag\classifyTag(array('类型'=>'QS_trade'));
    	$list_trade = $class_trade->run();
		foreach($list_trade as $k=>$v){
			$lists_trade[]=$k;
		}
		foreach($lists_trade as $key=>$val){
			if($val==$list['trade']){
				$list['trade']=$key;
			}
		}
		//期望薪资
		$class_wage = new \Common\qscmstag\classifyTag(array('类型'=>'QS_wage'));
    	$list_wage = $class_wage->run();
		foreach($list_wage as $k=>$v){
			$lists_wage[]=$k;
		}
		foreach($lists_wage as $key=>$val){
			if($val==$list['wage']){
				$list['wage']=$key;
			}
		}

		//--------------------*************************************
		$category = D('Category')->get_category_cache();
		$get_userprofile=D('Resume')->where(array('uid'=>$list['uid'],'def'=>1))->find();
		$get_userprofile['mobile_audit']=$user['mobile_audit'];
		$get_userprofile['email_audit']=$user['email_audit'];

		$get_userprofile['qscms_login_per_audit_mobile']=1;
		$birthdate_arr = range(date('Y')-16,date('Y')-65);
		$list['userprofile']=$get_userprofile;
		/*-------------------------------------------------------------------------------------------*/
		$first_cate=M('category_jobs')->where(array('parentid'=>0))->select();
		foreach($first_cate as $k=>$v){
			$cate_1[$k]['id']=$v['id'];
			$cate_1[$k]['name']=$v['categoryname'];
		}
		$second_cate=M('category_jobs')->where(array('parentid'=>$first_cate[0]['id']))->select();
		foreach($second_cate as $k=>$v){
			$cate_2[$k]['id']=$v['id'];
			$cate_2[$k]['name']=$v['categoryname'];
		}
		$third_cate=M('category_jobs')->where(array('parentid'=>$second_cate[0]['id']))->select();
		foreach($third_cate as $k=>$v){
			$cate_3[$k]['id']=$v['id'];
			$cate_3[$k]['name']=$v['categoryname'];
		}
		$list['first_cate'][]=$cate_1;
		$list['first_cate'][]=$cate_2;
		$list['first_cate'][]=$cate_3;


		foreach($cate_1 as $k=>$v){
			$tmp=M('category_jobs')->where(array('parentid'=>$v['id']))->select();
			foreach($tmp as $sk=>$sv){
				$arr['id']=$sv['id'];
				$arr['name']=$sv['categoryname'];
				$cate_sub[$k][]=$arr;
			}
		}
		foreach($cate_1 as $k1=>$v1){
			foreach($cate_sub[$k1] as $k=>$v){
				$tmp=M('category_jobs')->where(array('parentid'=>$v['id']))->select();
				if($tmp){
					foreach($tmp as $sk=>$sv){
						
						$arr['id']=$sv['id'];
						$arr['name']=$sv['categoryname'];
						$cate_sub1[$k1][$k][]=$arr;
					}
				} else {
					foreach($cate_sub[$k1] as $sk=>$sv){
						
						$arr['id']=0;
						$arr['name']='不限';
						$cate_sub1[$k1][$k][0]=$arr;
					}
				}
			}
		}

		$list['cate_sub1']=$cate_sub;
		$list['cate_sub2']=$cate_sub1;
		//echo "<pre>";print_r($cate_1);
		//echo "<pre>";print_r($cate_sub1);
		/*-------------------------------------------------------------------------------------------*/

		/*-------------------------------------------------------------------------------------------*/
		$cate_1=array();
		$cate_2=array();
		$cate_3=array();
		$first_cate=M('category_district')->where(array('parentid'=>0))->order('category_order desc,id asc')->select();
		foreach($first_cate as $k=>$v){
			$cate_1[$k]['id']=$v['id'];
			$cate_1[$k]['name']=$v['categoryname'];
		}
		$second_cate=M('category_district')->where(array('parentid'=>$first_cate[0]['id']))->order('category_order desc,id asc')->select();
		foreach($second_cate as $k=>$v){
			$cate_2[$k]['id']=$v['id'];
			$cate_2[$k]['name']=$v['categoryname'];
		}
		$third_cate=M('category_district')->where(array('parentid'=>$second_cate[0]['id']))->order('category_order desc,id asc')->select();
		foreach($third_cate as $k=>$v){
			$cate_3[$k]['id']=$v['id'];
			$cate_3[$k]['name']=$v['categoryname'];
		}
		$list['first_cate_dis'][]=$cate_1;
		$list['first_cate_dis'][]=$cate_2;
		$list['first_cate_dis'][]=$cate_3;


		foreach($cate_1 as $k=>$v){
			$tmp=M('category_district')->where(array('parentid'=>$v['id']))->order('category_order desc,id asc')->select();
			foreach($tmp as $sk=>$sv){
				$arr['id']=$sv['id'];
				$arr['name']=$sv['categoryname'];
				$cate_sub_dis[$k][]=$arr;
			}
		}
		foreach($cate_1 as $k1=>$v1){
			foreach($cate_sub_dis[$k1] as $k=>$v){
				$tmp=M('category_district')->where(array('parentid'=>$v['id']))->order('category_order desc,id asc')->select();
				if($tmp){
					foreach($tmp as $sk=>$sv){
						
						$arr['id']=$sv['id'];
						$arr['name']=$sv['categoryname'];
						$cate_sub_dis1[$k1][$k][]=$arr;
					}
				} else {
					foreach($cate_sub_dis[$k1] as $sk=>$sv){
						
						$arr['id']=0;
						$arr['name']='不限';
						$cate_sub_dis1[$k1][$k][0]=$arr;
					}
				}
			}
		}

		$list['cate_sub_dis1']=$cate_sub_dis;
		$list['cate_sub_dis2']=$cate_sub_dis1;
		
		
		//--------------------------------------------------------------------------------------
		$first_major=M('category_major')->where(array('parentid'=>0))->select();
		foreach($first_major as $k=>$v){
			$major_1[$k]['id']=$v['id'];
			$major_1[$k]['name']=$v['categoryname'];
		}
		$second_major=M('category_major')->where(array('parentid'=>$first_major[0]['id']))->select();
		foreach($second_major as $k=>$v){
			$major_2[$k]['id']=$v['id'];
			$major_2[$k]['name']=$v['categoryname'];
		}
		$list['first_major'][]=$major_1;
		$list['first_major'][]=$major_2;

		foreach($major_1 as $k=>$v){
			$tmp=M('category_major')->where(array('parentid'=>$v['id']))->select();
			foreach($tmp as $sk=>$sv){
				$arr['id']=$sv['id'];
				$arr['name']=$sv['categoryname'];
				$major_sub[$k][]=$arr;
			}
		}

		$list['major_sub']=$major_sub;

		//echo "<pre>";print_r($major_sub);
		/*-------------------------------------------------------------------------------------------*/
    	$this->ajaxReturn(1,'获取数据成功',$list);
    }
	public function get_resume_education(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$listedu = D('ResumeEducation')->where($map)->find();
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_education'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$k;
		}
		foreach($lists as $k=>$v){
			if($v==$listedu['education']){
				$listedu['education']=$k;
			}
		}
		$listedu['startdate']=$listedu['startyear'].'-'.$listedu['startmonth'];
		$listedu['enddate']=$listedu['endyear'].'-'.$listedu['endmonth'];

    	$this->ajaxReturn(1,'获取数据成功',$listedu);
	}
	public function get_resume_work(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeWork')->where($map)->find();
		$list['startdate']=$list['startyear'].'-'.$list['startmonth'];
		$list['enddate']=$list['endyear'].'-'.$list['endmonth'];
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_educations($pid){
		$map['pid'] = $pid;
    	$list = D('ResumeEducation')->where($map)->select();
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_works($pid){
		$map['pid'] = $pid;
    	$list = D('ResumeWork')->where($map)->select();
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_trains($pid){
		$map['pid'] = $pid;
    	$list = D('ResumeTraining')->where($map)->select();
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_certificates($pid){
		$map['pid'] = $pid;
    	$list = D('ResumeCredent')->where($map)->select();
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_certificate(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeCredent')->where($map)->find();
		$list['startdate']=$list['year'].'-'.$list['month'];
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_language(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$listlang = D('ResumeLanguage')->where($map)->find();
		$language_class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language'));
    	$language_list = $language_class->run();
		foreach($language_list as $k=>$v){
			$language_lists[]=$k;
		}
		foreach($language_lists as $k=>$v){
			if($v==$listlang['language']){
				$listlang['language']=$k;
			}
		}
		$level_class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language_level'));
    	$level_list = $level_class->run();
		foreach($level_list as $k=>$v){
			$level_lists[]=$k;
		}
		foreach($level_lists as $k=>$v){
			if($v==$listlang['level']){
				$listlang['level']=$k;
			}
		}
    	$this->ajaxReturn(1,'获取数据成功',$listlang);
	}
	public function get_resume_languages($pid){
		$map['pid'] = $pid;
    	$list = D('ResumeLanguage')->where($map)->select();
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_resume_imgs($pid){
		$map['resume_id'] = $pid;
    	$list = D('ResumeImg')->where($map)->select();
		foreach($list as $k=>$v){
			$list[$k]['img']=attach($v['img'],'resume_img');
		}
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	public function get_current(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_current'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_language(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_level(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language_level'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_jobs_nature(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_jobs_nature'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_trade(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_trade'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_wage(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_wage'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function del_resume_education(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeEducation')->where($map)->delete();
		if($list){
			$this->ajaxReturn(1,'删除成功！',$list);
		}else{
			$this->ajaxReturn(2,'删除失败！',$list);
		}
	}
	public function del_resume_work(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeWork')->where($map)->delete();
		if($list){
			$this->ajaxReturn(1,'删除成功！',$list);
		}else{
			$this->ajaxReturn(2,'删除失败！',$list);
		}
	}
	public function del_resume_train(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeTraining')->where($map)->delete();
		if($list){
			$this->ajaxReturn(1,'删除成功！',$list);
		}else{
			$this->ajaxReturn(2,'删除失败！',$list);
		}
	}
	public function del_resume_certificate(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeCredent')->where($map)->delete();
		if($list){
			$this->ajaxReturn(1,'删除成功！',$list);
		}else{
			$this->ajaxReturn(2,'删除失败！',$list);
		}
	}
	public function del_resume_language(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeLanguage')->where($map)->delete();
		if($list){
			$this->ajaxReturn(1,'删除成功！',$list);
		}else{
			$this->ajaxReturn(2,'删除失败！',$list);
		}
	}
	public function del_resume_img(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeImg')->where($map)->delete();
		if($list){
			$this->ajaxReturn(1,'删除成功！',$list);
		}else{
			$this->ajaxReturn(2,'删除失败！',$list);
		}
	}
	public function del_resume_speciality(){
		$id=$_GET['id'];
		$pid=$_GET['pid'];
    	$resume = D('Resume')->get_resume_one($pid);
		if(strpos($resume['tag'],',')){
			$tags=explode(",",$resume['tag']);
			$tags_cn=explode(",",$resume['tag_cn']);
			foreach($tags as $k=>$v){
				if($v==$id){
					unset($tags[$k]);
					unset($tags_cn[$k]);
				}
			}
			$data['tag']=implode(',',$tags);
			$data['tag_cn']=implode(',',$tags_cn);
			$res=M("Resume")->where(array('id'=>$pid))->save($data);
		} else {
			$data['tag']="";
			$data['tag_cn']="";
			$res=M("Resume")->where(array('id'=>$pid))->save($data);
		}
		if($res!==false){
			D('Resume')->check_resume($resume['uid'],$resume['id']);//更新简历完成状态
			//写入会员日志
			write_members_log(C('visitor'),2028,$resume['id']);
			$this->ajaxReturn(1,'删除成功！');

		}else{
			$this->ajaxReturn(2,'删除失败！');
		}
	}

	public function editsave_resume_basic(){
		$map['id']=$_GET['id'];
    	$setsqlarr = D('Resume')->get_resume_one($map['id']);
		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();
        unset($setsqlarr['district']);
		$setsqlarr['fullname']=$_GET['fullname'];
		//性别
		$setsqlarr['sex']=$_GET['sex'];
		if($_GET['sex']==1){
			$setsqlarr['sex_cn']='男';
		}
		if($_GET['sex']==2){
			$setsqlarr['sex_cn']='女';
		}
		//出生年份
		$setsqlarr['birthdate']=$_GET['birthdate']+1950;
		//最高学历
		$class_edu = new \Common\qscmstag\classifyTag(array('类型'=>'QS_education'));
    	$list_edu = $class_edu->run();
		foreach($list_edu as $k=>$v){
			$lists_edu[]=$k.'*'.$v;
		}
		$index_edu=$_GET['education'];
		$edu = explode('*',$lists_edu[$index_edu]);
		$setsqlarr['education']=$edu[0];
		$setsqlarr['education_cn']=$edu[1];
		//工作经验
		$class_exp = new \Common\qscmstag\classifyTag(array('类型'=>'QS_experience'));
    	$list_exp = $class_exp->run();
		foreach($list_exp as $key=>$val){
			$lists_exp[]=$key.'*'.$val;
		}
		$index_exp=$_GET['experience'];
		$exp = explode('*',$lists_exp[$index_exp]);
		$setsqlarr['experience']=$exp[0];
		$setsqlarr['experience_cn']=$exp[1];

		$setsqlarr['residence']=$_GET['residence'];
		$setsqlarr['telephone']=$_GET['telephone'];
		$setsqlarr['email']=$_GET['email'];
		$setsqlarr['height']=$_GET['height'];
		$setsqlarr['householdaddress']=$_GET['householdaddress'];
		//婚姻状况
		$setsqlarr['marriage']=$_GET['marriage'];
		if($_GET['marriage']==1){
			$setsqlarr['marriage_cn']='未婚';
		}
		if($_GET['marriage']==2){
			$setsqlarr['marriage_cn']='已婚';
		}
		if($_GET['marriage']==3){
			$setsqlarr['marriage_cn']='保密';
		}
		$setsqlarr['qq']=$_GET['qq'];
		$setsqlarr['weixin']=$_GET['weixin'];
		$setsqlarr['major']=$_GET['major'];
		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$ints = array('sex','birthdate','education','major','experience','email_notify','height','marriage');
		$trims = array('telephone','fullname','residence','email','householdaddress','qq','weixin');
		foreach ($ints as $val) {
			$setsqlarr[$val] = I('get.'.$val,0,'intval');
		}
		foreach ($trims as $val) {
			$setsqlarr[$val] = I('get.'.$val,'','trim,badword');
		}
		//unset($setsqlarr['qq']);
		//echo "<pre>";print_r($setsqlarr);
		if(C('qscms_audit_edit_resume')!="-1") D('ResumeEntrust')->set_resume_entrust($resume['id'],$uid);//添加简历自动投递功能
        //if(!$setsqlarr['email']) $this->ajaxReturn(2,'亲，请填写联系邮箱！');
		$rst=D('Resume')->save_resume($setsqlarr,$map['id'],$user);
		if($rst['state']) $this->ajaxReturn(1,'保存成功！',$rst);
		$this->ajaxReturn(2,$rst['error']);
	}

	public function editsave_resume_intention(){
		$map['id']=$_GET['id'];
    	$resume = $setsqlarr = D('Resume')->get_resume_one($map['id']);
		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();
		//目前状态
		$class_current = new \Common\qscmstag\classifyTag(array('类型'=>'QS_current'));
    	$list_current = $class_current->run();
		foreach($list_current as $k=>$v){
			$lists_current[]=$k.'*'.$v;
		}
		$index_current=$_GET['current'];
		$current = explode('*',$lists_current[$index_current]);
		$_GET['current']=$setsqlarr['current']=$current[0];
		$_GET['current_cn']=$setsqlarr['current_cn']=$current[1];
		//工作性质
		$class_jobsnature = new \Common\qscmstag\classifyTag(array('类型'=>'QS_jobs_nature'));
    	$list_jobsnature = $class_jobsnature->run();
		foreach($list_jobsnature as $k=>$v){
			$lists_jobsnature[]=$k.'*'.$v;
		}
		$index_jobsnature=$_GET['jobsnature'];
		$jobsnature = explode('*',$lists_jobsnature[$index_jobsnature]);
		$_GET['nature']=$setsqlarr['nature']=$jobsnature[0];
		$_GET['nature_cn']=$setsqlarr['nature_cn']=$jobsnature[1];
		//期望行业
		$class_trade = new \Common\qscmstag\classifyTag(array('类型'=>'QS_trade'));
    	$list_trade = $class_trade->run();
		foreach($list_trade as $k=>$v){
			$lists_trade[]=$k.'*'.$v;
		}
		$index_trade=$_GET['trade'];
		$trade = explode('*',$lists_trade[$index_trade]);
		$_GET['trade']=$setsqlarr['trade']=$trade[0];
		$_GET['trade_cn']=$setsqlarr['trade_cn']=$trade[1];
		//期望薪资
		$class_wage = new \Common\qscmstag\classifyTag(array('类型'=>'QS_wage'));
    	$list_wage = $class_wage->run();
		foreach($list_wage as $k=>$v){
			$lists_wage[]=$k.'*'.$v;
		}
		$index_wage=$_GET['wage'];
		$wage = explode('*',$lists_wage[$index_wage]);
		$_GET['wage']=$setsqlarr['wage']=$wage[0];
		$_GET['wage_cn']=$setsqlarr['wage_cn']=$wage[1];

		$uid=$user['uid'];
		$pid = $resume['id'];
		!$pid && $this->ajaxReturn(2,'请选择简历！');
		$intention_jobs_id=I('get.intention_jobs_id','','trim,badword');
		if(strpos($intention_jobs_id,'.')){
			$setsqlarr['intention_jobs_id']=$intention_jobs_id;
		} else {
			$level=$this->getLevel($intention_jobs_id,'category_jobs');
			if($level=='2'){
				$intention=M('category_district')->where(array('id'=>$intention_jobs_id))->find();
				$setsqlarr['intention_jobs_id']=$intention['parentid'].'.'.$intention['id'].'.0';
			} else {
				$intention=M('category_jobs')->where(array('id'=>$intention_jobs_id))->find();
				$tmpdata=M('category_jobs')->where(array('id'=>$intention['parentid']))->find();
				$setsqlarr['intention_jobs_id']=$tmpdata['parentid'].'.'.$tmpdata['id'].'.'.$intention_jobs_id;
			}
			
		}
//		var_dump($setsqlarr['intention_jobs_id']);die;
		$district=I('get.district','','trim,badword');
		if(strpos($district,'.')){
            $arr = explode(".",$district);
			$setsqlarr['district']=$arr[count($arr)-1];
		}
        if(strpos($setsqlarr['district'],'.')){
            $arr = explode(".",$district);
            $setsqlarr['district']=$arr[count($arr)-1];
        }
		$setsqlarr['trade']=I('get.trade',0,'intval');//期望行业
		$setsqlarr['nature']=I('get.nature',0,'intval');//工作性质
		$setsqlarr['current']=I('get.current',0,'intval');
		$setsqlarr['wage']=I('get.wage',0,'intval');//期望薪资
		if(C('qscms_audit_edit_resume')!="-1") D('ResumeEntrust')->set_resume_entrust($resume['id'],$uid);//添加简历自动投递功能
		$rst=D('Resume')->save_resume($setsqlarr,$resume['id'],$user);
		$rst=D('Resume')->getLastSql();
		if($rst['state']) $this->ajaxReturn(1,'修改成功！',$_GET);
		$this->ajaxReturn(2,$rst['error'],$_GET);
	}
	
	public function getLevel($id,$table=''){
		$dis=M($table)->where(array('id'=>$id))->find();
		$tmp=M($table)->where(array('id'=>$dis['parentid']))->find();
		if($tmp['parentid']==0){
			return '2';
		} else {
			return '3';
		}
	}
	
	public function editsave_resume_description(){
		$map['id']=$_GET['id'];
    	$resume = $setsqlarr = D('Resume')->get_resume_one($map['id']);
		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();
		$setsqlarr['specialty']=$_GET['specialty'];
		$specialty = I('get.specialty','','trim,badword');
		!$specialty && $this->ajaxReturn(2,'请输入自我描述!');
		$rst=D('Resume')->save_resume(array('specialty'=>$specialty),$resume['id'],$user);
		//$rst=D('Resume')->getLastSql();
		if(!$rst['state']) $this->ajaxReturn(2,$rst['error']);
		write_members_log($user,2027,$resume['id']);
		$this->ajaxReturn(1,'简历自我描述修改成功',$rst);
	}
	public function addsave_resume_education(){
		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$enddate = $_GET['enddate'];
		$end = explode('-',$enddate);
		$map['id']=$_GET['pid'];
		$resume = D('Resume')->where($map)->find();
		$setsqlarr['pid']=$_GET['pid'];
		$setsqlarr['uid']=$resume['uid'];
		$setsqlarr['school']=$_GET['school'];
		$setsqlarr['speciality']=$_GET['speciality'];

		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_education'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$k.'*'.$v;
		}
		$eduindex=$_GET['education'];
		$edu = explode('*',$lists[$eduindex]);
		$setsqlarr['education']=$edu[0];
		$setsqlarr['education_cn']=$edu[1];
		$setsqlarr['startyear']=$start[0];
		$setsqlarr['startmonth']=$start[1];
		$setsqlarr['endyear']=$end[0];
		$setsqlarr['endmonth']=$end[1];


		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();
		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['school'] = I('get.school','','trim,badword');
		$setsqlarr['speciality'] = I('get.speciality','','trim,badword');
		$setsqlarr['education'] = I('get.education',0,'intval');
		$setsqlarr['startyear'] = I('get.startyear',0,'intval');
		$setsqlarr['startmonth'] = I('get.startmonth',0,'intval');
		$setsqlarr['endyear'] = I('get.endyear',0,'intval');
		$setsqlarr['endmonth'] = I('get.endmonth',0,'intval');
		$setsqlarr['todate'] = I('get.todate',0,'intval'); // 至今
		// 选择至今就不判断结束时间了
		if ($setsqlarr['todate'] == 1) {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth']) $this->ajaxReturn(2,'请选择就读时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'就读开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'就读开始时间需小于毕业时间！');
		} else {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth'] || !$setsqlarr['endyear'] || !$setsqlarr['endmonth']) $this->ajaxReturn(2,'请选择就读时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'就读开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'就读开始时间需小于当前时间！');
			if($setsqlarr['endyear'] > intval(date('Y'))) $this->ajaxReturn(2,'就读结束时间不允许大于当前时间！');
			if($setsqlarr['endyear'] == intval(date('Y')) && $setsqlarr['endmonth'] > intval(date('m'))) $this->ajaxReturn(2,'就读结束时间不允许大于当前时间！');

			if($setsqlarr['startyear'] > $setsqlarr['endyear']) $this->ajaxReturn(2,'就读开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == $setsqlarr['endyear'] && $setsqlarr['startmonth'] >= $setsqlarr['endmonth']) $this->ajaxReturn(2,'就读开始时间需小于毕业时间！');
		}
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');
		$setsqlarr['pid'] = $resume['id'];
		$educations=M('ResumeEducation')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取教育经历数量
		if (count($educations)>=6) $this->ajaxReturn(2,'教育经历不能超过6条！');
		$reg = D('ResumeEducation')->add_resume_education($setsqlarr,$user);
		//$reg = D('ResumeEducation')->getLastSql();
		if($reg['state']) {
			$this->ajaxReturn(1,'教育经历添加成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}

	public function get_experience(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_experience'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_birthdate(){
		for($i=0;$i<=50;$i++){
			$lists[$i]=$i+1950;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_education(){
		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_education'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$v;
		}
    	$this->ajaxReturn(1,'获取数据成功',$lists);
	}
	public function get_major(){
		/*-------------------------------------------------------------------------------------------*/
			$first_cateDis=M('category_major')->where(array('parentid'=>0))->select();
			$second_cateDis=M('category_major')->where("parentid=".$first_cateDis[0]['id'])->select();

			foreach($first_cateDis as $k=>$v){
				$arrDis_1[]=$v['id'].",".$v['categoryname'];
			}
			$data['first_cateDis']=$arrDis_1;
			$data['first_cateDislen']=count($arrDis_1);

			foreach($second_cateDis as $k=>$v){
				$arrDis_2[]=$v['id'].",".$v['categoryname'];
			}



			$data['second_cateDis']=$arrDis_2;
			$data['second_cateDislen']=count($arrDis_2);



			$all_cate=M('category_major')->where("parentid <> 0")->select();
			foreach($all_cate as $k=>$v){
				$arrDis_all[]=$v['parentid'].",".$v['categoryname'].",".$v['id'];
			}
			$data['all_cateDis']=$arrDis_all;
			$data['all_cateDislen']=count($arrDis_all);
			//echo "<pre>";print_r($data);
			$this->ajaxReturn(1,'获取数据成功',$data);
			/*-------------------------------------------------------------------------------------------*/
	}

	public function uploadPhoto(){
		$config_params = array(
			'upload_ok'=>false,
			'save_path'=>'',
			'show_path'=>''
		);
		$uid=$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));
		$saveRule = uniqid();
		//$savePicName = $saveRule.'.jpg';




		$date = date('ym/d/');
	    	$save_avatar=C('qscms_attach_path').'avatar/'.$date;//图片存储路径

	    	if(!is_dir($save_avatar)){
		    	mkdir($save_avatar,0777,true);
		    }
			$size = explode(',',C('qscms_avatar_size'));


			$upload=new UploadFile();
           $upload->maxSize='2000000';//默认为-1，不限制上传大小
             $upload->savePath=$save_avatar;//保存路径建议与主文件平级目录或者平级目录的子目录来保存
             $upload->saveRule=$saveRule;//上传文件的文件名保存规则
            $upload->uploadReplace=true;//如果存在同名文件是否进行覆盖
            $upload->allowExts=array('jpg','jpeg','png','gif');//准许上传的文件类型
             $upload->allowTypes=array('image/png','image/jpg','image/jpeg','image/gif');//检测mime类型



             if($upload->upload()){
                $info=$upload->getUploadFileInfo();


             }else{
                $config_params['info']=$upload->getErrorMsg();//专门用来获取上传的错误信息的
             }

			$config_params['save_path'] = $date.$info['0']['savename'];
			$config_params['show_path'] = attach($img,'avatar').'?'.time();
			$config_params['upload_ok'] = true;

		if($config_params['upload_ok']){
			$setsqlarr['avatars']=$config_params['save_path'];
			$old_avatar = D('Members')->where(array('uid'=>$uid))->getfield('avatars');
			$photo = M('Resume')->field('photo_audit,photo_display')->where(array('uid'=>$uid,'def'=>1))->find();
			if($photo['photo_display'] == 1){
				$setsqlarr['photo'] = 1;
			}else{
				$setsqlarr['photo'] = 0;
			}
			if(true !== $reg = D('Members')->update_user_info($setsqlarr,$user_info)) $this->ajaxReturn(0,$reg);
			$user_resume_list = D('Resume')->where(array('uid'=>$uid))->select();
			foreach ($user_resume_list as $key => $value) {
				D('Resume')->check_resume($uid,$value['id']);//更新简历完成状态
			}
			D('TaskLog')->do_task($user_info,5);
			write_members_log($user_info,2044);
			$data = array('path'=>$config_params['show_path'],'img'=>$config_params['save_path']);
			$this->ajaxReturn(1, L('upload_success'), $data);
        }else{
        	$this->ajaxReturn(0, $config_params['info']);
        }
	}

	public function editsave_resume_education(){
		$map['id']=$_GET['id'];
		$education = $setsqlarr = D('ResumeEducation')->where($map)->find();
    	$resume = D('Resume')->get_resume_one($education['pid']);
		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_education'));
    	$list = $class->run();
		foreach($list as $k=>$v){
			$lists[]=$k.'*'.$v;
		}
		$eduindex=$_GET['education'];
		$edu = explode('*',$lists[$eduindex]);
		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$enddate = $_GET['enddate'];
		$end = explode('-',$enddate);
		$setsqlarr['school']=$_GET['school'];
		$setsqlarr['speciality']=$_GET['speciality'];
		$setsqlarr['education']=$edu[0];
		$setsqlarr['education_cn']=$edu[1];
		$setsqlarr['startyear']=$start[0];
		$setsqlarr['startmonth']=$start[1];
		$setsqlarr['endyear']=$end[0];
		$setsqlarr['endmonth']=$end[1];
		$setsqlarr['todate']=empty($_GET['todate'])?0:$_GET['todate'];


		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['school'] = I('get.school','','trim,badword');
		$setsqlarr['speciality'] = I('get.speciality','','trim,badword');
		$setsqlarr['education'] = I('get.education',0,'intval');
		$setsqlarr['startyear'] = I('get.startyear',0,'intval');
		$setsqlarr['startmonth'] = I('get.startmonth',0,'intval');
		$setsqlarr['endyear'] = I('get.endyear',0,'intval');
		$setsqlarr['endmonth'] = I('get.endmonth',0,'intval');
		$setsqlarr['todate'] = I('get.todate',0,'intval'); // 至今
		// 选择至今就不判断结束时间了
		if ($setsqlarr['todate'] == 1) {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth']) $this->ajaxReturn(2,'请选择就读时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'就读开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'就读开始时间需小于毕业时间！');
		} else {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth'] || !$setsqlarr['endyear'] || !$setsqlarr['endmonth']) $this->ajaxReturn(2,'请选择就读时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'就读开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'就读开始时间需小于当前时间！');
			if($setsqlarr['endyear'] > intval(date('Y'))) $this->ajaxReturn(2,'就读结束时间不允许大于当前时间！');
			if($setsqlarr['endyear'] == intval(date('Y')) && $setsqlarr['endmonth'] > intval(date('m'))) $this->ajaxReturn(2,'就读结束时间不允许大于当前时间！');

			if($setsqlarr['startyear'] > $setsqlarr['endyear']) $this->ajaxReturn(2,'就读开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == $setsqlarr['endyear'] && $setsqlarr['startmonth'] >= $setsqlarr['endmonth']) $this->ajaxReturn(2,'就读开始时间需小于毕业时间！');
		}
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');
		$setsqlarr['pid'] = $resume['id'];
		$educations=M('ResumeEducation')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取教育经历数量
		if (count($educations)>=6) $this->ajaxReturn(2,'教育经历不能超过6条！');
		$setsqlarr['id'] = $map['id'];
		$reg = D('ResumeEducation')->save_resume_education($setsqlarr,$user);
		//$reg = D('ResumeEducation')->getLastSql();
		if($reg['state']) {
			$this->ajaxReturn(1,'教育经历修改成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}

	public function addsave_resume_work(){
		$map['id']=$_GET['pid'];
		$resume = D('Resume')->where($map)->find();
		$userwhere['uid']=$resume['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$enddate = $_GET['enddate'];
		$end = explode('-',$enddate);
		$setsqlarr['pid']=$_GET['pid'];
		$setsqlarr['uid']=$resume['uid'];
		$setsqlarr['companyname']=$_GET['companyname'];
		$setsqlarr['jobs']=$_GET['jobs'];
		$setsqlarr['achievements']=$_GET['achievements'];
		$setsqlarr['startyear']=$start[0];
		$setsqlarr['startmonth']=$start[1];
		$setsqlarr['endyear']=$end[0];
		$setsqlarr['endmonth']=$end[1];
		$setsqlarr['todate']=empty($_GET['todate'])?0:$_GET['todate'];


		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['companyname'] = I('get.companyname','','trim,badword');
		$setsqlarr['achievements'] = I('get.achievements','','trim,badword');
		$setsqlarr['jobs'] = I('get.jobs','','trim,badword');
		$setsqlarr['startyear'] = I('get.startyear',0,'intval');
		$setsqlarr['startmonth'] = I('get.startmonth',0,'intval');
		$setsqlarr['endyear'] = I('get.endyear',0,'intval');
		$setsqlarr['endmonth'] = I('get.endmonth',0,'intval');
		$setsqlarr['todate'] = I('get.todate',0,'intval'); // 至今
		// 选择至今就不判断结束时间了
		if ($setsqlarr['todate'] == 1) {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth']) $this->ajaxReturn(2,'请选择工作时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'工作开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'工作开始时间需小于当前时间！');
		} else {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth'] || !$setsqlarr['endyear'] || !$setsqlarr['endmonth']) $this->ajaxReturn(2,'请选择工作时间！');

			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'工作开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'工作开始时间需小于当前时间！');
			if($setsqlarr['endyear'] > intval(date('Y'))) $this->ajaxReturn(2,'工作结束时间不允许大于当前时间！');
			if($setsqlarr['endyear'] == intval(date('Y')) && $setsqlarr['endmonth'] > intval(date('m'))) $this->ajaxReturn(2,'工作结束时间不允许大于当前时间！');

			if($setsqlarr['startyear'] > $setsqlarr['endyear']) $this->ajaxReturn(2,'工作开始时间不允许大于结束时间！');
			if($setsqlarr['startyear'] == $setsqlarr['endyear'] && $setsqlarr['startmonth'] >= $setsqlarr['endmonth']) $this->ajaxReturn(2,'工作开始时间需小于结束时间！');
		}
        if(!$setsqlarr['achievements']) $this->ajaxReturn(2,'请填写工作职责！');
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');
		$setsqlarr['pid'] = $resume['id'];
		$works=M('ResumeWork')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取教育经历数量
		if(count($works)>=6) $this->ajaxReturn(2,'工作经历不能超过6条！');
		$reg=D('ResumeWork')->add_resume_work($setsqlarr,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'工作经历添加成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}

	public function editsave_resume_work(){
		$map['id']=$_GET['id'];
		$work = $setsqlarr = D('ResumeWork')->where($map)->find();
    	$resume = D('Resume')->get_resume_one($work['pid']);
		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$enddate = $_GET['enddate'];
		$end = explode('-',$enddate);
		$setsqlarr['companyname']=$_GET['companyname'];
		$setsqlarr['jobs']=$_GET['jobs'];
		$setsqlarr['achievements']=$_GET['achievements'];
		$setsqlarr['startyear']=$start[0];
		$setsqlarr['startmonth']=$start[1];
		$setsqlarr['endyear']=$end[0];
		$setsqlarr['endmonth']=$end[1];
		$setsqlarr['todate']=empty($_GET['todate'])?0:$_GET['todate'];

		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['companyname'] = I('get.companyname','','trim,badword');
		$setsqlarr['achievements'] = I('get.achievements','','trim,badword');
		$setsqlarr['jobs'] = I('get.jobs','','trim,badword');
		$setsqlarr['startyear'] = I('get.startyear',0,'intval');
		$setsqlarr['startmonth'] = I('get.startmonth',0,'intval');
		$setsqlarr['endyear'] = I('get.endyear',0,'intval');
		$setsqlarr['endmonth'] = I('get.endmonth',0,'intval');
		$setsqlarr['todate'] = I('get.todate',0,'intval'); // 至今
		// 选择至今就不判断结束时间了
		if ($setsqlarr['todate'] == 1) {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth']) $this->ajaxReturn(2,'请选择工作时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'工作开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'工作开始时间需小于当前时间！');
		} else {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth'] || !$setsqlarr['endyear'] || !$setsqlarr['endmonth']) $this->ajaxReturn(2,'请选择工作时间！');

			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'工作开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'工作开始时间需小于当前时间！');
			if($setsqlarr['endyear'] > intval(date('Y'))) $this->ajaxReturn(2,'工作结束时间不允许大于当前时间！');
			if($setsqlarr['endyear'] == intval(date('Y')) && $setsqlarr['endmonth'] > intval(date('m'))) $this->ajaxReturn(2,'工作结束时间不允许大于当前时间！');

			if($setsqlarr['startyear'] > $setsqlarr['endyear']) $this->ajaxReturn(2,'工作开始时间不允许大于结束时间！');
			if($setsqlarr['startyear'] == $setsqlarr['endyear'] && $setsqlarr['startmonth'] >= $setsqlarr['endmonth']) $this->ajaxReturn(2,'工作开始时间需小于结束时间！');
		}
        if(!$setsqlarr['achievements']) $this->ajaxReturn(2,'请填写工作职责！');
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');
		$setsqlarr['pid'] = $resume['id'];
		$works=M('ResumeWork')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取教育经历数量
		if(count($works)>=6) $this->ajaxReturn(2,'工作经历不能超过6条！');
		$setsqlarr['id'] = $map['id'];
		$reg=D('ResumeWork')->save_resume_work($setsqlarr,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'工作经历修改成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error'],$reg);
		}
	}
	public function addsave_resume_train(){
		$map['id']=$_GET['pid'];
		$resume = D('Resume')->where($map)->find();
		$userwhere['uid']=$resume['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$enddate = $_GET['enddate'];
		$end = explode('-',$enddate);
		$setsqlarr['agency']=$_GET['agency'];
		$setsqlarr['course']=$_GET['course'];
		$setsqlarr['description']=$_GET['description'];
		$setsqlarr['startyear']=$start[0];
		$setsqlarr['startmonth']=$start[1];
		$setsqlarr['endyear']=$end[0];
		$setsqlarr['endmonth']=$end[1];
		$setsqlarr['todate']=empty($_GET['todate'])?0:$_GET['todate'];

		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['agency'] = I('get.agency','','trim,badword');
		$setsqlarr['course'] = I('get.course','','trim,badword');
		$setsqlarr['description'] = I('get.description','','trim,badword');
		$setsqlarr['startyear'] = I('get.startyear',0,'intval');
		$setsqlarr['startmonth'] = I('get.startmonth',0,'intval');
		$setsqlarr['endyear'] = I('get.endyear',0,'intval');
		$setsqlarr['endmonth'] = I('get.endmonth',0,'intval');
		$setsqlarr['todate'] = I('get.todate',0,'intval'); // 至今
		// 选择至今就不判断结束时间了
		if(!$setsqlarr['agency']) $this->ajaxReturn(2,'请填写培训机构！');
		if(!$setsqlarr['course']) $this->ajaxReturn(2,'请填写培训课程！');
		if ($setsqlarr['todate'] == 1) {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth']) $this->ajaxReturn(2,'请选择培训时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'培训开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'培训开始时间需小于毕业时间！');
		} else {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth'] || !$setsqlarr['endyear'] || !$setsqlarr['endmonth']) $this->ajaxReturn(2,'请选择培训时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'培训开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'培训开始时间需小于当前时间！');
			if($setsqlarr['endyear'] > intval(date('Y'))) $this->ajaxReturn(2,'培训结束时间不允许大于当前时间！');
			if($setsqlarr['endyear'] == intval(date('Y')) && $setsqlarr['endmonth'] > intval(date('m'))) $this->ajaxReturn(2,'培训结束时间不允许大于当前时间！');
			if($setsqlarr['startyear'] > $setsqlarr['endyear']) $this->ajaxReturn(2,'培训开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == $setsqlarr['endyear'] && $setsqlarr['startmonth'] >= $setsqlarr['endmonth']) $this->ajaxReturn(2,'培训开始时间需小于毕业时间！');
		}
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');
		$setsqlarr['pid'] = $resume['id'];
		$trainings=M('ResumeTraining')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取教育经历数量
		if(count($trainings)>=6) $this->ajaxReturn(2,'培训经历不能超过6条！');
		$reg=D('ResumeTraining')->add_resume_training($setsqlarr,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'培训经历添加成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error'],$reg);
		}
	}
	public function editsave_resume_train(){
		$map['id']=$_GET['id'];
		$train = $setsqlarr = D('ResumeTraining')->where($map)->find();
    	$resume = D('Resume')->get_resume_one($train['pid']);
		$userwhere['uid']=$setsqlarr['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$enddate = $_GET['enddate'];
		$end = explode('-',$enddate);
		$setsqlarr['agency']=$_GET['agency'];
		$setsqlarr['course']=$_GET['course'];
		$setsqlarr['description']=$_GET['description'];
		$setsqlarr['startyear']=$start[0];
		$setsqlarr['startmonth']=$start[1];
		$setsqlarr['endyear']=$end[0];
		$setsqlarr['endmonth']=$end[1];
		$setsqlarr['todate']=empty($_GET['todate'])?0:$_GET['todate'];

		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['agency'] = I('get.agency','','trim,badword');
		$setsqlarr['course'] = I('get.course','','trim,badword');
		$setsqlarr['description'] = I('get.description','','trim,badword');
		$setsqlarr['startyear'] = I('get.startyear',0,'intval');
		$setsqlarr['startmonth'] = I('get.startmonth',0,'intval');
		$setsqlarr['endyear'] = I('get.endyear',0,'intval');
		$setsqlarr['endmonth'] = I('get.endmonth',0,'intval');
		$setsqlarr['todate'] = I('get.todate',0,'intval'); // 至今
		// 选择至今就不判断结束时间了
		if ($setsqlarr['todate'] == 1) {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth']) $this->ajaxReturn(2,'请选择培训时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'培训开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'培训开始时间需小于毕业时间！');
		} else {
			if(!$setsqlarr['startyear'] || !$setsqlarr['startmonth'] || !$setsqlarr['endyear'] || !$setsqlarr['endmonth']) $this->ajaxReturn(2,'请选择培训时间！');
			if($setsqlarr['startyear'] > intval(date('Y'))) $this->ajaxReturn(2,'培训开始时间不允许大于当前时间！');
			if($setsqlarr['startyear'] == intval(date('Y')) && $setsqlarr['startmonth'] >= intval(date('m'))) $this->ajaxReturn(2,'培训开始时间需小于当前时间！');
			if($setsqlarr['endyear'] > intval(date('Y'))) $this->ajaxReturn(2,'培训结束时间不允许大于当前时间！');
			if($setsqlarr['endyear'] == intval(date('Y')) && $setsqlarr['endmonth'] > intval(date('m'))) $this->ajaxReturn(2,'培训结束时间不允许大于当前时间！');
			if($setsqlarr['startyear'] > $setsqlarr['endyear']) $this->ajaxReturn(2,'培训开始时间不允许大于毕业时间！');
			if($setsqlarr['startyear'] == $setsqlarr['endyear'] && $setsqlarr['startmonth'] >= $setsqlarr['endmonth']) $this->ajaxReturn(2,'培训开始时间需小于毕业时间！');
		}
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');
		$setsqlarr['pid'] = $resume['id'];
		$trainings=M('ResumeTraining')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取教育经历数量
		if(count($trainings)>=6) $this->ajaxReturn(2,'培训经历不能超过6条！');
		$setsqlarr['id'] = $map['id'];
		$reg=D('ResumeTraining')->save_resume_training($setsqlarr,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'培训经历修改成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error'],$reg);
		}
	}

	public function addsave_resume_certificate(){
		$map['id']=$_GET['pid'];
    	$resume = D('Resume')->get_resume_one($map['id']);
		$userwhere['uid']=$resume['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$setsqlarr['name']=$_GET['name'];
		$setsqlarr['year']=$start[0];
		$setsqlarr['month']=$start[1];

		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['pid']=$_GET['pid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['name'] = I('get.name','','trim,badword');
		$setsqlarr['year'] = I('get.year','','trim,badword');
		$setsqlarr['month'] = I('get.month','','trim,badword');
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');

		if(!$setsqlarr['year'] || !$setsqlarr['month']) $this->ajaxReturn(2,'请选择获得证书时间！');
		if($setsqlarr['year'] > intval(date('Y'))) $this->ajaxReturn(2,'获得证书时间不能大于当前时间！');
		if($setsqlarr['year'] == intval(date('Y')) && $setsqlarr['month'] > intval(date('m'))) $this->ajaxReturn(2,'获得证书时间不能大于当前时间！');

		$setsqlarr['pid'] = $resume['id'];
		$credent=M('ResumeCredent')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取证书数量
		if(count($credent)>=6) $this->ajaxReturn(2,'证书不能超过6条！');
		$reg=D('ResumeCredent')->add_resume_credent($setsqlarr,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'证书添加成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}
	public function editsave_resume_certificate(){
		$map['id']=$_GET['id'];
		$credent = $setsqlarr = D('ResumeCredent')->where($map)->find();
    	$resume = D('Resume')->get_resume_one($credent['pid']);
		$userwhere['uid']=$resume['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$startdate = $_GET['startdate'];
		$start = explode('-',$startdate);
		$setsqlarr['name']=$_GET['name'];
		$setsqlarr['year']=$start[0];
		$setsqlarr['month']=$start[1];

		$_GET=$setsqlarr;
		$uid=$user['uid'];
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['name'] = I('get.name','','trim,badword');
		$setsqlarr['year'] = I('get.year','','trim,badword');
		$setsqlarr['month'] = I('get.month','','trim,badword');
		if(false === $resume) $this->ajaxReturn(2,'请先填写简历基本信息！');

		if(!$setsqlarr['year'] || !$setsqlarr['month']) $this->ajaxReturn(2,'请选择获得证书时间！');
		if($setsqlarr['year'] > intval(date('Y'))) $this->ajaxReturn(2,'获得证书时间不能大于当前时间！');
		if($setsqlarr['year'] == intval(date('Y')) && $setsqlarr['month'] > intval(date('m'))) $this->ajaxReturn(2,'获得证书时间不能大于当前时间！');

		$setsqlarr['pid'] = $resume['id'];
		$credent=M('ResumeCredent')->where(array('pid'=>$setsqlarr['pid'],'uid'=>$setsqlarr['uid']))->count();//获取证书数量
		if(count($credent)>=6) $this->ajaxReturn(2,'证书不能超过6条！');
		$reg=D('ResumeCredent')->save_resume_credent($setsqlarr,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'证书修改成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}
	public function addsave_resume_lang(){
		$lang_class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language'));
    	$lang_list = $lang_class->run();
		foreach($lang_list as $k=>$v){
			$lang_lists[]=$k.'*'.$v;
		}
		$languageindex=$_GET['language'];
		$language = explode('*',$lang_lists[$languageindex]);
		$level_class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language_level'));
    	$level_list = $level_class->run();
		foreach($level_list as $k=>$v){
			$level_lists[]=$k.'*'.$v;
		}
		$levelindex=$_GET['level'];
		$level = explode('*',$level_lists[$levelindex]);

		$map['id']=$_GET['pid'];
		$resume = D('Resume')->where($map)->find();
		$userwhere['uid']=$resume['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$setsqlarr['pid']=$_GET['pid'];
		$setsqlarr['uid']=$resume['uid'];
		$setsqlarr['language']=$language[0];
		$setsqlarr['language_cn']=$language[1];
		$setsqlarr['level']=$level[0];
		$setsqlarr['level_cn']=$level[1];

		$_GET=$setsqlarr;
		$count = M('ResumeLanguage')->where(array('pid'=>$resume['id'],'uid'=>$user['uid']))->count('id');
		if ($count>6) $this->ajaxReturn(2,'语言能力不能超过6条！');
		$languages['uid'] = $user['uid'];
		$languages['pid'] = $resume['id'];
		$languages['language'] = I('get.language',0,'intval');
		$languages['level'] = I('get.level',0,'intval');
		$languages['language_cn'] = $setsqlarr['language_cn'];
		$languages['level_cn'] = $setsqlarr['level_cn'];
		$is = M('ResumeLanguage')->where(array('pid'=>$resume['id'],'uid'=>$user['uid'],'language'=>$languages['language']))->find();
		$is && $this->ajaxReturn(2,'语言能力不能重复添加！');
		$reg=D('ResumeLanguage')->add_resume_language($languages,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'语言能力添加成功！',$reg);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}
	public function editsave_resume_lang(){
		$lang_class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language'));
    	$lang_list = $lang_class->run();
		foreach($lang_list as $k=>$v){
			$lang_lists[]=$k.'*'.$v;
		}
		$languageindex=$_GET['language'];
		$language = explode('*',$lang_lists[$languageindex]);
		$level_class = new \Common\qscmstag\classifyTag(array('类型'=>'QS_language_level'));
    	$level_list = $level_class->run();
		foreach($level_list as $k=>$v){
			$level_lists[]=$k.'*'.$v;
		}
		$levelindex=$_GET['level'];
		$level = explode('*',$level_lists[$levelindex]);

		$map['id']=$_GET['id'];
		$lang = $setsqlarr = D('ResumeLanguage')->where($map)->find();
    	$resume = D('Resume')->get_resume_one($lang['pid']);
		$userwhere['uid']=$resume['uid'];
    	$user = D('Members')->where($userwhere)->find();

		$setsqlarr['pid']=$lang['pid'];
		$setsqlarr['uid']=$resume['uid'];
		$setsqlarr['language']=$language[0];
		$setsqlarr['language_cn']=$language[1];
		$setsqlarr['level']=$level[0];
		$setsqlarr['level_cn']=$level[1];

		$_GET=$setsqlarr;
		$count = M('ResumeLanguage')->where(array('pid'=>$resume['id'],'uid'=>$user['uid']))->count('id');
		if ($count>6) $this->ajaxReturn(2,'语言能力不能超过6条！');
		$languages['uid'] = $user['uid'];
		$languages['pid'] = $resume['id'];
		$languages['language'] = I('get.language',0,'intval');
		$languages['level'] = I('get.level',0,'intval');
		$languages['language_cn'] = $setsqlarr['language_cn'];
		$languages['level_cn'] = $setsqlarr['level_cn'];
		$is = M('ResumeLanguage')->where(array('pid'=>$resume['id'],'uid'=>$user['uid'],'language'=>$languages['language']))->find();
		$is && $this->ajaxReturn(2,'语言能力不能重复添加！');
		$languages['id'] = $map['id'];
		$reg=D('ResumeLanguage')->save_resume_language($languages,$user);
		if($reg['state']) {
			$this->ajaxReturn(1,'语言能力修改成功！',$_GET);
		}else{
			$this->ajaxReturn(2,$reg['error']);
		}
	}
	/*
    *	简历-修改 - -特长标签
    */
	public function resume_edit_speciality(){
		$pid=I('get.pid','0','intval,badword');

        $resume = D('Resume')->get_resume_list(array('where'=>array('id'=>$pid),'limit'=>1));
        $tags=D('Category')->get_category_cache('QS_resumetag');

        $resume['tag_key'] = $resume['tag']?explode(',',$resume['tag']):array();
        $resume['tag_cn'] = $resume['tag_cn']?explode(',',$resume['tag_cn']):array();
        //$tag_arr = array_chunk($tags,12,true);

		$data['tags']=$tags;
		$data['resume']=$resume[0];
		$data['has_tags']=explode(",",$resume[0]['tag']);
		//echo "<pre>";print_r($data);
		$this->ajaxReturn(1,'语言能力添加成功！',$data);


    }
	public function resume_save_speciality(){
		$uid=$this->userInfo['uid'];
		$pid=I('get.pid','0','intval,badword');
		$tags_cache=D('Category')->get_category_cache('QS_resumetag');

		$tags = I('get.tags');
		$arr=explode(',',$tags);

		foreach($arr as $k=>$v){
			$tags_cn[]=$tags_cache[$v];
		}

		$setarr['tag']=$tags;
		$setarr['tag_cn']=implode(",",$tags_cn);
		$resume_mod = D('Resume');
		if(false !== $resume_mod->where(array('id'=>$pid,'uid'=>$uid))->save($setarr)){
			$resume_mod->check_resume($uid,$pid);//更新简历完成状态
			//写入会员日志
			write_members_log($this->userInfo,2028,$pid);
			$this->ajaxReturn(1,'修改成功！',$data);
		}
		$this->ajaxReturn(0,'保存失败！');


    }
	public function get_resume_img(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeImg')->where($map)->find();
		$list['img_']=$list['img'] ;

    	$this->ajaxReturn(1,'获取数据成功',$list);
	}

	public function uploadResumeImg(){
		$config_params = array(
			'upload_ok'=>false,
			'save_path'=>'',
			'show_path'=>''
		);
		$uid=$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));
		$saveRule = uniqid();
		//$savePicName = $saveRule.'.jpg';

		$date = date('ym/d/');
	    	$save_avatar=C('qscms_attach_path').'resume_img/'.$date;//图片存储路径

	    	if(!is_dir($save_avatar)){
		    	mkdir($save_avatar,0777,true);
		    }
			$size = explode(',',C('qscms_avatar_size'));
			$upload=new UploadFile();
           $upload->maxSize='2000000';//默认为-1，不限制上传大小
             $upload->savePath=$save_avatar;//保存路径建议与主文件平级目录或者平级目录的子目录来保存
             $upload->saveRule=$saveRule;//上传文件的文件名保存规则
            $upload->uploadReplace=true;//如果存在同名文件是否进行覆盖
            $upload->allowExts=array('jpg','jpeg','png','gif');//准许上传的文件类型
             $upload->allowTypes=array('image/png','image/jpg','image/jpeg','image/gif');//检测mime类型

             if($upload->upload()){
                $info=$upload->getUploadFileInfo();
             }else{
                $config_params['info']=$upload->getErrorMsg();//专门用来获取上传的错误信息的
             }
			$config_params['save_path'] = $date.$info['0']['savename'];
			$config_params['show_path'] = attach($img,'avatar').'?'.time();
			$config_params['upload_ok'] = true;

		if($config_params['upload_ok']){

			$data = array('path'=>$config_params['show_path'],'img'=>$config_params['save_path']);
			$this->ajaxReturn(1, L('upload_success'), $data);
        }else{
        	$this->ajaxReturn(0, $config_params['info']);
        }
	}
	/*
    *	简历-修改 - -照片作品
    */
	public function resume_edit_img(){
		$uid=$this->userInfo['uid'];
        $pid = I('get.pid',0,'intval');
        $id = I('get.id',0,'intval');
        $img_mod = D('ResumeImg');

		$count = $img_mod->where(array('resume_id'=> $pid,'uid'=>$uid))->count('id');
		$data['resume_id'] =  $pid;
		$data['uid'] = $uid;
		$data['title'] = I('get.title','','trim,badword');
		$data['img'] = I('get.img','','trim,badword');
		$data['id'] = I('get.id',0,'intval');
		if(!$_GET['id']){
			if($count >= 6) $this->ajaxReturn(0,'简历附件最多只可上传6张！');
		}
		$reg = $img_mod->save_resume_img($data);
		if($reg['state'])
		{
			D('Resume')->check_resume($uid,intval($data['resume_id']));//更新简历完成状态
			$this->ajaxReturn(1,'附件添加成功！',$data);
		}
		$this->ajaxReturn(0,$reg['error']);
    }
	public function refreshResume(){
		$pid = I('post.pid',0,'intval');
		$uid=$this->userInfo['uid'];
		!$pid && $pid = M('Resume')->where(array('uid'=>$uid))->order('def desc')->limit(1)->getField('id');

		$user_info=D('Members')->get_user_one(array('uid'=>$uid));

		$r = D('Resume')->get_resume_list(array('where'=>array('uid'=>$uid,'id'=>$pid),'field'=>'id,title,audit,display'));
		!$r && $this->ajaxReturn(0,"选择的简历不存在！");
		$r[0]['_audit'] != 1 && $this->ajaxReturn(0,"审核中或未通过的简历无法刷新！");
		$r[0]['display'] != 1 && $this->ajaxReturn(0,"简历已关闭，无法刷新！");
		$refresh_log = M('RefreshLog');
		$refrestime = $refresh_log->where(array('uid'=>$uid,'type'=>2001))->order('addtime desc')->getfield('addtime');
		$duringtime=time()-$refrestime;
		$space = C('qscms_per_refresh_resume_space')*60;
		$today = strtotime(date('Y-m-d'));
		$tomorrow = $today+3600*24;
		$count = $refresh_log->where(array('uid'=>$uid,'type'=>2001,'addtime'=>array('BETWEEN',array($today,$tomorrow))))->count();
		if(C('qscms_per_refresh_resume_time')!=0&&($count>=C('qscms_per_refresh_resume_time'))){
			$this->ajaxReturn(0,"每天最多可刷新 ".C('qscms_per_refresh_resume_time')." 次！");
		}elseif($duringtime<=$space && $space!=0){
			$this->ajaxReturn(0,C('qscms_per_refresh_resume_space')." 分钟内不允许重复刷新！");
		}else{
			//修改目前状态
			$resume = D('Resume');
			$r = $resume->refresh_resume($pid,$this->userInfo);
			$this->ajaxReturn(1,'刷新简历成功！',$r['data']);
		}
	}
	public function get_resume_training(){
		$id=$_GET['id'];
		$map['id'] = $id;
    	$list = D('ResumeTraining')->where($map)->find();
		$list['startdate']=$list['startyear'].'-'.$list['startmonth'];
		$list['enddate']=$list['endyear'].'-'.$list['endmonth'];
    	$this->ajaxReturn(1,'获取数据成功',$list);
	}
	 /**
     * 职位收藏夹
     */
    public function getJobsFavorites(){
        $this->check_params();
        $where['personal_uid']=$this->userInfo['uid'];
        $settr=I('get.settr',0,'intval');
        $settr && $where['addtime']=array('gt',strtotime("-".$settr." day")); //筛选 收藏时间
        $favorites = D('PersonalFavorites')->get_favorites($where);
		foreach($favorites['list'] as $k=>$v){
			$favorites['list'][$k]['addtime_']=date('Y-m-d',$v['addtime']);

		}
		//echo "<pre>";print_r($favorites);
        $this->ajaxReturn(1,'',$favorites);
    }
	/**
     * 删除收藏 职位
     */
    public function delFavorites(){
        $did= I('get.did','','trim,badword');
		$uid=$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));
        !$did && $this->ajaxReturn(0,"你没有选择项目！");
        $reg=D('PersonalFavorites')->del_favorites($did,$user_info);
        if($reg['state']===true){
            $this->ajaxReturn(1,"删除成功！");
        }else{
            $this->ajaxReturn(0,$reg['error']);
        }
    }

	/**
     * [ resume_apply 简历投递]
     */
    public function resume_apply($jid){
		$jid = I('request.jid');
		!$jid && $this->ajaxReturn(0,'请选择要投递的职位！');
      	if($this->userInfo['utype']==1) $this->ajaxReturn(2,'企业账户无法投递职位！');
		$this->_resume_apply_exe($jid);
    }
    protected function _resume_apply_exe($jid,$rid,$uid){
		$uid=$uid?$uid:$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));
    	$reg = D('PersonalJobsApply')->jobs_apply_add($jid,$user_info,$rid);
        !$reg['state'] && $this->ajaxReturn(0,$reg['error'],$reg['create']);
        $reg['data']['failure'] && $this->ajaxReturn(0,$reg['data']['list'][$jid]['tip']);
		$this->ajaxReturn(1,'投递成功！');
    }
	/**
	 * [jobs_favorites 收藏职位]
	 */
	public function jobs_favorites(){
		$jid = I('request.jid','','trim');
		!$jid && $this->ajaxReturn(0,'请选择要收藏的职位！');
		$uid=$this->userInfo['uid'];
		!$uid && $this->ajaxReturn(0,'亲，你还没有登陆账号呢？');
      	if($this->userInfo['utype']==1) $this->ajaxReturn(2,'企业账户无法收藏职位哟！');
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));
		$reg = D('PersonalFavorites')->add_favorites($jid,$user_info);
        !$reg['state'] && $this->ajaxReturn(0,$reg['error']);
		$this->ajaxReturn(1,'收藏成功！');
	}
	/**
     * 领取红包检测提示
     */
    public function apply_allowance(){
        $jid = I('request.jid',0,'intval');
        !$jid && $this->ajaxReturn(0,'请选择要投递的职位！');
		$uid=$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));

      	if($this->userInfo['utype']==1) $this->ajaxReturn(2,'企业账户无法领取职位红包哟！');
        $check = D('Allowance/AllowanceRecord')->check_apply($jid,$user_info);

        if($check===false){
            $this->ajaxReturn(0,D('Allowance/AllowanceRecord')->getError());
        }else if($check=='-1'){
            $this->ajaxReturn(2,D('Allowance/AllowanceRecord')->getError());
        }
        $jobsinfo = $check['jobsinfo'];
        $resume = $check['resume'];
        $tip = D('Allowance/AllowanceInfo')->check_intention($jobsinfo['allowance_id'],$resume);
        if(false===$config=F('allowance_config')){
            $config = D('Allowance/AllowanceConfig')->config_cache();
        }
		$tip['mobile_address'] = $config['allow_signon_mobile_address']?$config['allow_signon_mobile_address']:'不限';
        $this->ajaxReturn(1,'回调成功！',$tip);
    }

	/**
     * 领取红包
     */
    public function apply_allowance_save(){
        $jid = I('request.jid',0,'intval');
        !$jid && $this->ajaxReturn(0,'请选择要投递的职位！');
		$uid=$this->userInfo['uid'];
		$user_info=D('Members')->get_user_one(array('uid'=>$uid));
        if(false===$config=F('allowance_config')){
            $config = D('Allowance/AllowanceConfig')->config_cache();
        }
        if($config['apply_need_auth_mobile']==1){
            $check_mobile_auth = M('Members')->where(array('uid'=>$uid))->find();
            if($check_mobile_auth['mobile_audit']=="0"){
                //$html=$this->fetch('ajax_auth_mobile');
                $this->ajaxReturn(3,'请先绑定手机！',$html);
            }
        }

        $reg = D('Allowance/AllowanceRecord')->jobs_apply_add($jid,$user_info);
        !$reg && $this->ajaxReturn(0,D('Allowance/AllowanceRecord')->getError());
        $this->ajaxReturn(1,'投递成功！',$reg);
    }
	 public function getJobsApply(){

		$where['personal_uid']=$this->userInfo['uid'];
		$pid = M('Resume')->where(array('uid'=>$this->userInfo['uid']))->order('def desc')->limit(1)->getField('id');
        $settr=I('get.settr',0,'intval');
        $settr && $where['apply_addtime']=array('gt',strtotime("-".$settr." day")); //筛选 申请时间
        //筛选 反馈
        $feedbackArr = array(1=>'企业未查看',2=>'待反馈',3=>'合适',4=>'不合适',5=>'待定',6=>'未接通');
        $feedback=I('get.feedback',0,'intval');
        switch ($feedback)
        {
            case 1:
                $where['personal_look']=1;
                break;
            case 2:
                $where['personal_look']=2;
                $where['is_reply']=0;
                break;
            case 3:
                $where['personal_look']=2;
                $where['is_reply']=1;
                break;
            case 4:
                $where['personal_look']=2;
                $where['is_reply']=2;
                break;
            case 5:
                $where['personal_look']=2;
                $where['is_reply']=3;
                break;
            case 6:
                $where['personal_look']=2;
                $where['is_reply']=4;
                break;
            default:
                break;
        }
        $personal_apply_mod = D('PersonalJobsApply');
        $apply_list = $personal_apply_mod->get_apply_jobs($where);

		foreach($apply_list['list'] as $k=>$v){
			$apply_list['list'][$k]['apply_addtime_']=date('Y-m-d',$v['apply_addtime']);
		}

		$apply_list['resume_list']=M('Resume')->where(array('uid'=>$this->userInfo['uid']))->group('id')->getfield('id,title');
		$t=M('Resume')->where(array('id'=>$pid))->find();
		$apply_list['resume_title']=$t['title']?$t['title']:'全部';
		//echo "<pre>";print_r($where);
		$this->ajaxReturn(1,'获取数据成功',$apply_list);
	}
	/**
     * 删除收藏 职位 职位详情页
     */
    public function del_jobs_favorites(){
        $jid= I('get.jid','','trim,badword');
		$uid=$this->userInfo['uid'];
        !$jid && $this->ajaxReturn(0,"你没有选择项目！");
		!$uid && $this->ajaxReturn(0,'亲，你还没有登陆账号呢？');
        $reg=D('PersonalFavorites')->where(array('jobs_id'=>$jid,"personal_uid"=>$uid))->delete();
        if($reg!==false){
            $this->ajaxReturn(1,"取消收藏成功！");
        }else{
            $this->ajaxReturn(0,"操作失败！");
        }
    }
	public function delbind() {
        $openid= I('get.openid','','trim,badword');
		$condition['uid'] = $this->userInfo['uid'];
		$condition['keyid'] = array('eq',$openid);
		$condition['type'] = 'wxapp';
		$res	=	M('MembersBind')->where($condition)->delete();
        if($res){
        	$this->ajaxReturn(1,'删除成功');
        }else {
			$this->ajaxReturn(0,'解除绑定失败！');
		}
    }
}
?>