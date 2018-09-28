<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;

class IndexController extends Controller {

    public function index(){
    	$fid=I('get.fid');
    	if($fid==''){
    	    $map['id']=rand(1,108);
    	}else{
    	    $map['id']=$fid;
    	}
        //读取数据库
        $fun=D('Fun');
        //$map['id']=$fid;
        //$map['id']=rand(1,108);
        $fun_list=$fun->where($map)->select();
        $fun_count=$fun->count();
        //dump($fun_list);
 
        $this->assign('fun_list',$fun_list);
        $this->assign('fun_count',$fun_count);
   
        $this->display('Index/index');
}

	public function like(){
		$lid=I('post.lid');
        //人数+1
        $game=D('Fun');
        $map['id']= $lid;
        $game->where($map)->setInc('liked',1);
        $data['info']='';
        $data['status']=1;
        $this->AjaxReturn($data,'JSON');
    }

    
}