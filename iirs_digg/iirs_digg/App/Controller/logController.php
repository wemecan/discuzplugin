<?php
if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

/**
 * $Id$
 *
 */

class logController extends mini_Controller {
    
    public $logModel = '';
    

    public function __construct(){
        $this->logModel = common::getInstanceOf( 'iirs_digg_logModel', 'logModel', APP_PATH. '/Model/iirs_digg_logModel.php' );
        parent::__construct();
    }
    
    
    /**
     * 显示对某pid贴进行digg的界面
     */
    
    public function diggshowAction(){
        
        //验证用户的可操作性
        $logtype = (string)common::input( 'logtype', 'GET', 'diggup' );
        if($logtype !== 'diggdown'){
            $logtype = 'diggup';
        }
        
        $credit_type = (int)common::config('get', 'credit_type');
        $credit_num = (int)common::config('get', 'credit_'. $logtype. '_num');
        $this->check_user_accessable( $credit_type, $credit_num );
        
        $pid = (int)common::input('pid');
        $this->check_pid_accessable($pid, false);

        $this->assign('logtype', $logtype);
        $this->assign('piddata', $this->logModel->piddata);
        $this->assign('page', (int)common::input('page'));
        $this->assign('credit_type', $credit_type );
        $this->assign('credit_num', $credit_num );
        $this->display('logController_diggshowAction');
    }
    
    /**
     * digg的post操作。
     *
     */
    public function diggAction(){
        $this->checkpost('diggsubmit');
        
        //验证用户的可操作性
        $logtype = (string)common::input( 'logtype', 'POST', 'diggup' );
        if($logtype !== 'diggdown'){
            $logtype = 'diggup';
        }
        $credit_type = (int)common::config('get', 'credit_type');
        $credit_num = (int)common::config('get', 'credit_'. $logtype. '_num');
        $this->check_user_accessable( $credit_type, $credit_num );
        
        $pid = (int)common::input('pid', 'POST', 0);
        $this->check_pid_accessable($pid);
        
        //验证完毕，开始写入操作
        $result = $this->logModel->diggupdate( $logtype );
        if( $result === false ){
            showmessage('操作失败！请和管理员联系。', NULL,  'HALTED');
        }else{
            $tid = (int)common::input('tid', 'POST');
            $page = (int)common::input('page', 'POST');
            $urlforward = "viewthread.php?tid={$tid}&page={$page}#pid{$pid}";
            $extramessage = '';
            if( isset($GLOBALS['inajax']) || isset($GLOBALS['infloat']) ){
                $extramessage = '你可以关闭该窗口了。<br /><br />为减轻服务器负担，页面没有立刻刷新。<br />下次浏览或者刷新页面均可查看到最新结果。<br />感谢您的谅解与合作。';
            }
            showmessage('操作成功！'.$extramessage , $urlforward);
        }
    
    }
    
    
    public function check_pid_accessable( $pid, $deep = true ){
        
        $pidCheckResult = $this->logModel->check_pid_accessable( $pid, $deep );
        switch ($pidCheckResult){
            case -1:
                showmessage('帖子不存在，请返回。', NULL);
                break;
            case -2:
                showmessage('帖子被隐藏或者在审核中，请返回。', NULL);
                break;
            case -3:
                showmessage('不能自己给自己送鲜花或者扔鸡蛋，请返回。', NULL);
                break;
            case -4:
                showmessage('帖子被删除入回收站，不能进行鲜花鸡蛋操作，请返回。', NULL);
                break;
            case -5:
                showmessage('帖子被关闭，不能进行鲜花鸡蛋操作，请返回。', NULL);
                break;
            case -6:
                showmessage('你已经给该帖子送了鲜花或者扔了鸡蛋了，不能重复操作，请返回。', NULL);
                break;
            default:
                break;
        }
        
    }
    
    
    public function check_user_accessable( $credit_type, $credit_num ){
        $result = $this->logModel->check_user_accessable( $credit_type, $credit_num );
        switch ($result){
            case -1:
                showmessage('你所在的用户组不允许鲜花鸡蛋操作，请返回。', NULL);
                break;
            case -2:
                showmessage('你没有足够的余额进行鲜花鸡蛋操作，请返回。', NULL);
                break;
            default:
                break;
        }
    }

}