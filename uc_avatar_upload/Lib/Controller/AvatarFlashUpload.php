<?php

!defined('IN_INTER') && exit('Fobbiden!');

/**
 * flash头像上传类，剥离自UCenter
 * 适用环境：PHP 5.0和以上
 *
 */

class Controller_AvatarFlashUpload extends Controller_Base{

    /**
     * 构造函数。(ok)
     * 
     */
    public function __construct(){
        parent::__construct();
    }

    
    /**
     * 获取显示上传flash的代码(ok)
     * 来源：Ucenter的uc_avatar函数
     * 依赖性：
     *     逻辑代码上为依赖本类和common类；实际操作中还须配合如下文件/组件：
     *         - Ucenter的头像上传flash文件（swf文件）
     */
    public function showuploadAction() {
        $uid = abs((int)common::getgpc('uid', 'G'));
        if( $uid === null || $uid == 0 ){
            return -1;
        }
        $type = common::getgpc('type', 'G') !== null ? common::getgpc('type', 'G') : 'virtual';
        if( $type === null ){
            $type = 'virtual';
        }
        $returnhtml = common::getgpc('returnhtml', 'G') !== null ?common::getgpc('returnhtml', 'G') : 1;
        if( $returnhtml === null  ){
            $returnhtml =  1;
        }
        
        $uc_input = urlencode(common::authcode('uid='.$uid.
                                               '&agent='.md5($_SERVER['HTTP_USER_AGENT']).
                                               "&time=".time(), 
                                                   'ENCODE', $this->config->authkey)
                             );
        
        $uc_avatarflash = $this->config->uc_api.'/images/camera.swf?input='.$uc_input.'&agent='.md5($_SERVER['HTTP_USER_AGENT']).'&ucapi='.urlencode(str_replace( ( $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' ), '', $this->config->uc_api)).'&avatartype='.$type.'&uploadSize='.$this->config->uploadsize;
        if($returnhtml) {
            return '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="450" height="253" id="mycamera" align="middle">
			<param name="allowScriptAccess" value="always" />
			<param name="scale" value="exactfit" />
			<param name="wmode" value="transparent" />
			<param name="quality" value="high" />
			<param name="bgcolor" value="#ffffff" />
			<param name="movie" value="'.$uc_avatarflash.'" />
			<param name="menu" value="false" />
			<embed src="'.$uc_avatarflash.'" quality="high" bgcolor="#ffffff" width="450" height="253" name="mycamera" align="middle" allowScriptAccess="always" allowFullScreen="false" scale="exactfit"  wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>';
        } else {
            return array(
            'width', '450',
            'height', '253',
            'scale', 'exactfit',
            'src', $uc_avatarflash,
            'id', 'mycamera',
            'name', 'mycamera',
            'quality','high',
            'bgcolor','#ffffff',
            'wmode','transparent',
            'menu', 'false',
            'swLiveConnect', 'true',
            'allowScriptAccess', 'always'
            );
        }
    }

    /**
     * 头像上传第一步，上传到临时文件夹（ok）
     *
     * @return string
     */
    function uploadavatarAction() {
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");
        //header("Content-type: application/xml; charset=utf-8");
        $this->init_input(common::getgpc('agent', 'G'));
        $uid = $this->input('uid');
        if(empty($uid)) {
            return -1;
        }
        if(empty($_FILES['Filedata'])) {
            return -3;
        }
        $imgtype = array(1 => '.gif', 2 => '.jpg', 3 => '.png');
        $imgext = strtolower('.'. common::fileext($_FILES['Filedata']['name']));
        if(!in_array($imgext, $imgtype)) {
            @unlink($_FILES['Filedata']['tmp_name']);
            return -2;
        }
        
        if( $_FILES['Filedata']['size'] > ($this->config->uploadsize * 1024) ){
            @unlink($_FILES['Filedata']['tmp_name']);
            return 'Inage is TOO BIG, PLEASE UPLOAD NO MORE THAN '. $this->config->uploadsize .'KB';
        }
        
        list($width, $height, $type, $attr) = getimagesize($_FILES['Filedata']['tmp_name']);
        
        $filetype = $imgtype[$type];
        //此处的UC_DATADIR被$this->config->tmpdir替代
        $tmpavatar = realpath($this->config->tmpdir).'/upload'.$uid.$filetype;
        file_exists($tmpavatar) && @unlink($tmpavatar);
        if(@is_uploaded_file($_FILES['Filedata']['tmp_name']) && @move_uploaded_file($_FILES['Filedata']['tmp_name'], $tmpavatar)) {
            list($width, $height, $type, $attr) = getimagesize($tmpavatar);
            if($width < 10 || $height < 10 || $type == 4) {
                @unlink($tmpavatar);
                return -2;
            }
        } else {
            @unlink($_FILES['Filedata']['tmp_name']);
            return -4;
        }
        $avatarurl = $this->config->uc_api. '/'. $this->config->tmpdir.'/upload'.$uid.$filetype;

        return $avatarurl;
    }
    
    /**
     * 头像上传第二步，上传到实际位置
     *
     * @return string
     */
    function rectavatarAction() {
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");
        header("Content-type: application/xml; charset=utf-8");
        $this->init_input(common::getgpc('agent'));
        $uid = abs((int)$this->input('uid'));
        if( empty($uid) || 0 == $uid ) {
            return '<root><message type="error" value="-1" /></root>';
        }
        
        return '<?xml version="1.0" ?><root><face success="1"/></root>';
        //继续调试
        
        $home = $this->get_home($uid);
        if(!is_dir(UC_DATADIR.'./avatar/'.$home)) {
            $this->set_home($uid, UC_DATADIR.'./avatar/');
        }
        $avatartype = common::getgpc('avatartype', 'G') == 'real' ? 'real' : 'virtual';
        $bigavatarfile = UC_DATADIR.'./avatar/'.$this->get_avatar($uid, 'big', $avatartype);
        $middleavatarfile = UC_DATADIR.'./avatar/'.$this->get_avatar($uid, 'middle', $avatartype);
        $smallavatarfile = UC_DATADIR.'./avatar/'.$this->get_avatar($uid, 'small', $avatartype);
        $bigavatar = $this->_flashdata_decode(common::getgpc('avatar1', 'P'));
        $middleavatar = $this->_flashdata_decode(common::getgpc('avatar2', 'P'));
        $smallavatar = $this->_flashdata_decode(common::getgpc('avatar3', 'P'));
        if(!$bigavatar || !$middleavatar || !$smallavatar) {
            return '<root><message type="error" value="-2" /></root>';
        }
        
        $success = 1;
        $fp = @fopen($bigavatarfile, 'wb');
        @fwrite($fp, $bigavatar);
        @fclose($fp);

        $fp = @fopen($middleavatarfile, 'wb');
        @fwrite($fp, $middleavatar);
        @fclose($fp);

        $fp = @fopen($smallavatarfile, 'wb');
        @fwrite($fp, $smallavatar);
        @fclose($fp);

        $biginfo = @getimagesize($bigavatarfile);
        $middleinfo = @getimagesize($middleavatarfile);
        $smallinfo = @getimagesize($smallavatarfile);
        if(!$biginfo || !$middleinfo || !$smallinfo || $biginfo[2] == 4 || $middleinfo[2] == 4 || $smallinfo[2] == 4) {
            file_exists($bigavatarfile) && unlink($bigavatarfile);
            file_exists($middleavatarfile) && unlink($middleavatarfile);
            file_exists($smallavatarfile) && unlink($smallavatarfile);
            $success = 0;
        }

        $filetype = '.jpg';       //bug gif上传之后不能删除
        @unlink($this->config->tmpdir.'/upload'.$uid.$filetype);

        if($success) {
            return '<?xml version="1.0" ?><root><face success="1"/></root>';
        } else {
            return '<?xml version="1.0" ?><root><face success="0"/></root>';
        }
    }
    
    /**
     * flash data decode
     * 来源：Ucenter
     * 
     * @param string $s
     * @return unknown
     */
    protected function _flashdata_decode($s) {
        $r = '';
        $l = strlen($s);
        for($i=0; $i<$l; $i=$i+2) {
            $k1 = ord($s[$i]) - 48;
            $k1 -= $k1 > 9 ? 7 : 0;
            $k2 = ord($s[$i+1]) - 48;
            $k2 -= $k2 > 9 ? 7 : 0;
            $r .= chr($k1 << 4 | $k2);
        }
        return $r;
    }
    
}