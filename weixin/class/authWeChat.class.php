<?php
	header("Content-type: text/html; charset=utf-8");
	session_start();
	$authWeChatObj = new AuthWechat();
	$userinfo = $authWeChatObj->getUserInfo();
	$userinfo = json_decode($userinfo, true);
	if($userinfo != null){
		//拿openid分别去教师和学生表去查询
		if($authWeChatObj->getTeacherOrStudent($userinfo['openid'])){
			//已经绑定了账号
			echo "<script>alert('该账号已绑定');</script>";
		}else{
			if(@$_GET['state'] == 'teacher'){
				$_SESSION['teacher'] = $userinfo;
				echo "<script>window.location.href = '../../teacher/bind_account.html?id=teacher';</script>";
			}else if(@$_GET['state'] == 'student'){
				$_SESSION['student'] = $userinfo;
				echo "<script>window.location.href = '../../student/bind_account.html?id=student';</script>";
			}else{
				echo "<script>window.location.href = '../../error.html';</script>";
			}
		}
	}


	/**
	* 微信授权类
	*/
	class AuthWechat {
		
		function __construct(){
			require_once('../config/config.php');
		}

		function http_curl($url){
	        //1.初始化curl
	        $ch = curl_init();
	        //2.设置curl的参数
	        curl_setopt($ch, CURLOPT_URL, $url);
	        
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	        //3.采集
	        $output = curl_exec($ch);

	        //4.关闭
	        curl_close($ch);
	        return $output;
	    }


		/**
		 * 获得code
		 * @param  string $value [description]
		 * @return [type]        [description]
		 */
		function getCode(){
			$code = null;
			if(@isset($_GET['code'])){
				$code = $_GET['code'];
			}
			return $code;
		}

		/**
		 * 获取access_token
		 * @param  [type] $code [description]
		 * @return [type]       [description]
		 */
		function getAuthAccessToken(){
			$code = $this->getCode();
			$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . APPID . '&secret=' . APPSECRET . '&code=' . $code . '&grant_type=authorization_code';
			$access = $this->http_curl($url);
			return $access;
		}

		/**
		 * 获得用户信息
		 * @param  [type] $access_token [description]
		 * @return [type]               [description]
		 */
		function getUserInfo(){
			$access = $this->getAuthAccessToken();
			$access = json_decode($access, true);
			$access_token = $access['access_token'];
			$openid = $access['openid'];
			$url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';

			$userinfo = $this->http_curl($url);
			return $userinfo;
		}

		/**
		 * 使用openid去学生表和教师表查询
		 * @param  [type] $openid [description]
		 * @return [type]         [description]
		 */
		function getTeacherOrStudent($openid){
			if($openid != null){
				require_once('../../php/config/config.php');
				require_once('../../php/util/HandleMysql.class.php');
				$handleMysqlObj = new HandleMysql(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_CODING);
				$sql1 = "select * from student where stu_wechat_code='" . $openid . "'";
				$sql2 = "select * from teacher where tea_wechat_code='" . $openid . "'";
				$flag1 = $handleMysqlObj->getOne($sql1);
				$flag2 = $handleMysqlObj->getOne($sql2);

				if ($flag1 || $flag2) {
					return true;
				}else{
					return false;
				}
			}
			return false;
		}
	}