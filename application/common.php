<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
//返回失败信息，json格式
function error_msg( $msg ){
    return json( ["state" => 0,"err_msg" => $msg] );
}
//返回成功信息
function success_msg( $key = null,$val = null ){
    $arr = ["state"=>1];
    if($key && $val) $arr[$key] = $val;
    return json($arr);
}
//执行url函数
function curl( $url,$post = null,$header = null ){
    $ch = curl_init(); //初始化curl
    curl_setopt( $ch,CURLOPT_URL,$url );//抓取指定网页
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER,true );//要求结果为字符串且输出到屏幕上
    if(strpos( $url,"https") === 0 ){
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER,false );
    }
    if($post){
        curl_setopt( $ch,CURLOPT_POST,true );//post提交方式
        curl_setopt( $ch,CURLOPT_POSTFIELDS,$post );//发送post请求时传递的参数
    }
    if($header) curl_setopt( $url, CURLOPT_HTTPHEADER, $header );//设置header头部信息
    $res = curl_exec($ch);//运行curl
    if( curl_errno($ch) ){
        curl_close($ch);
        return curl_error($ch);
    }
    curl_close($ch);
    return $res;
}
//获取appid和appsecret公共函数
function get_app( $app_accounts_id ){
    $program = db( 'accounts' ) -> where( 'id',$app_accounts_id ) -> find();
    return $program;
}

//从redis中取出$access_token方法
function get_redis_access_token( $app_accounts_id ){
    $redis=new Redis();
    $redis -> connect( '127.0.0.1',6379 );
    $program = get_app( $app_accounts_id );
    $app_id = $program['app_id'];
    $token_str = json_decode( $redis-> get($app_id),true );
    $access_token = $token_str[0];
    return $access_token;
}

//获取小程序access_token OK
function get_access_token( $app_accounts_id ){
    //DO 1.连接本地的 Redis 服务
    $redis = new Redis();
    $redis-> connect( '127.0.0.1',6379 );
    //DO 2.从数据库中找到$app_id、$app_secret
    $program = get_app( $app_accounts_id );
    $app_id = $program['app_id'];
    $app_secret = $program['app_secret'];
    $exitTime = 7100; //有效期为2个小时7200
    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$app_id.'&secret='.$app_secret;
    //DO 3.判断redis长度是否>0？
    if( $redis -> strlen($app_id ) > 0){
        $token_str = json_decode( $redis->get($app_id),true );
        $access_token = $token_str[0];
        $expires_in = $token_str[1];
    }
    //DO 4.判断access_token是否在有效期内
    if(isset( $access_token ) && ( $expires_in + $exitTime ) > time() ) {
        return $access_token;
    }else{
        //DO 5.得到access_token
        $res = curl( $url );
        $res = json_decode( $res,true );//把json格式的字符串转为数组
        $access_token = $res['access_token'];
        $expires_in = $res['expires_in'];//7200有效期
        //DO 6.把$access_token存入redis,key为$app_id，保证token的唯一性
        $redis -> set( $app_id,json_encode( [$access_token,$expires_in] ) );
        return $access_token;
    }
}

//code2Session获取openid、session_key  OK
function get_openid( $app_accounts_id,$code ){
    //DO 1.从数据库中找到$app_id、$app_secret
    $program = get_app( $app_accounts_id );
    $app_id = $program['app_id'];
    $app_secret = $program['app_secret'];
    //DO 2.请求地址
    $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$app_id.'&secret='.$app_secret.'&js_code='.$code.'&grant_type=authorization_code';
    $res = curl( $url );
    //var_dump($res);
    //DO 3.执行请求地址返回结果
    return $res;
}


//下发客服当前输入状态给用户 customerTyping
function customer_typing( $app_accounts_id,$code,$command = "Typing" ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/typing?access_token='.$access_token;
    $get_openid = get_openid( $app_accounts_id,$code );
    $openid = $get_openid['openid'];
    // $openid = 'o4sGA4izAJdL0Edr4mL1c42yxuJ4';
    //command 的合法值--Typing或CancelTyping
    $post_data =<<<END
            {
               "touser": "{$openid}", 
               "command":"{$command}"
            }            
END;
    $res = curl( $url,$post_data );
    var_dump($res);
    //return $res;
}
//下发客服当前输入状态给用户 customerTyping
//function customer_typing($app_accounts_id,$code,$command = "Typing"){
//    $access_token = get_redis_access_token($app_accounts_id);
//    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/typing?access_token='.$access_token;
//
//    $get_openid = get_openid($app_accounts_id,$code);
//    $post_data['touser'] = $get_openid['openid'];
//    //$post_data['touser'] = 'o4sGA4izAJdL0Edr4mL1c42yxuJ4';
//    $post_data['command'] = $command;//Typing或CancelTyping
//    $o = "";
//    foreach ( $post_data as $k => $v){
//        $o.="$k=" . urlencode( $v )."&"; //编码 URL 字符串
//    }
//    $post_data = substr($o,0,-1);
//
//    $res = curl($url,$post_data);
//    var_dump($res);
//    die;
//    $resp_json = [
//        'errcode' => $res['errcode'],
//        'errmsg' => $res['errmsg']
//    ];
//    return json_encode($resp_json);
//}

//获取客服消息内的临时素材getTempMedia
function get_temp_media( $app_accounts_id,$media_id ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$media_id;
    $res = curl( $url );
    var_dump($res);
    return $res;
    //如果调用成功，会直接返回图片二进制内容，如果请求失败，会返回 JSON 格式的数据。
//    $bool = json_decode($res);
//    if(!$bool){
//        return json_decode($res,true);
//    }else{
//        return $res;
//    }

}

//发送客服消息给用户sendCustomerMessage
function send_customer_message( $app_accounts_id,$code,$msgtype,$title,$content,$media_id,$description,$link_url,$thumb_url,$pagepath,$thumb_media_id ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
    $get_openid = get_openid( $app_accounts_id,$code );
    $openid = $get_openid['openid'];
    //$openid= 'o4sGA4izAJdL0Edr4mL1c42yxuJ4';
    switch ( $msgtype ){
        case 'text':
            $post_data = <<<END
            {
               "touser": "{$openid}", 
               "msgtype": "text",
               "text": {
                "content": "{$content}"
               }
            }            
END;
            break;
        case 'image':
            $post_data = <<<END
            {
               "touser": "{$openid}", 
               "msgtype": "image",
               "image": {
                "media_id": "{$media_id}"
               }
            }
END;
            break;
        case 'link':
            $post_data = <<<END
            {
              "touser": "{$openid}",
              "msgtype": "link",
              "link": {
                "title": "{$title}",
                "description": "{$description}",
                "url": "{$link_url}",
                "thumb_url": "{$thumb_url}"
              }
            }
END;
            break;
        case 'miniprogrampage':
            $post_data= <<<END
            {
              "touser": "{$openid}",
              "msgtype": "miniprogrampage",
              "miniprogrampage": {
                "title": "{$title}",
                "pagepath": "{$pagepath}",
                "thumb_media_id": "{$thumb_media_id}"
              }
            }
END;
            break;
    }
    $res = curl( $url,$post_data );
    var_dump($res);
}

//把媒体文件上传到微信服务器 uploadTempMedia
function upload_temp_media( $app_accounts_id,$type,$media ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
    $post_data = $media;
    $res = curl( $url,$post_data );
    var_dump($res);
    //return $res;
}

//组合模板并添加至帐号下的个人模板库 addTemplate  OK
function add_template( $app_accounts_id,$id,$keyword_id_list ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/add?access_token='.$access_token;
    $post_data = <<<END
        {
           "id": "{$id}",
            "keyword_id_list": "{$keyword_id_list}"
        }
END;
    $res = curl( $url,$post_data );
    var_dump($res);
    //调试成功结果{"errcode":0,"errmsg":"ok","template_id":"d-kNR3ppTFMIqnTyioVxgzd8JpKK2OrO1YSDT-05qdA"}"
    //return $res;
}


//删除帐号下的某个模板 deleteTemplate OK
function delete_template( $app_accounts_id,$template_id ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/del?access_token='.$access_token;
    $post_data = <<<END
        {
            "template_id": "{$template_id}"
        }
END;
    $res = curl( $url,$post_data );
    var_dump($res);
    // return $res;
}

//获取模板库某个模板标题下关键词库 getTemplateLibraryById OK
function get_template_library_by_id( $app_accounts_id,$id ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/get?access_token='.$access_token;
    $post_data = <<<END
        {
            "id": "{$id}"
        }
END;
    $res = curl( $url,$post_data );
    var_dump($res);
    //return $res;
}

//获取小程序模板库标题列表 getTemplateLibraryList OK
function get_template_library_list( $app_accounts_id,$offset = 0,$count = 20 ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/list?access_token='.$access_token;
    $post_data = <<<END
        {
            "offset": "{$offset}",
            "count": "{$count}"
        }
END;
    $res = curl( $url,$post_data );
    var_dump($res);
    //return $res;
}

//getTemplateList 获取帐号下已存在的模板列表 OK
function get_template_list( $app_accounts_id,$offset,$count ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/list?access_token='.$access_token;
    $post_data = <<<END
        {
            "offset": "{$offset}",
            "count": "{$count}"
        }
END;
    $res = curl( $url,$post_data );
    var_dump($res);
    //return $res;
}

//发送模板消息sendTemplateMessage
function send_template_message( $app_accounts_id,$id,$keyword_id_list,$page = 'index',$form_id,$data ){
    $access_token = get_redis_access_token( $app_accounts_id );
    $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
    $program = get_app( $app_accounts_id );
    $app_id = $program['app_id'];
    $template_data = json_decode( add_template( $app_accounts_id,$id,$keyword_id_list ),true );
    $template_id = $template_data['template_id'];
//    $post_data =<<<END
//    	{
//		  "touser": "{$app_id}",
//		  "template_id": "{$template_id}",
//		  "page": "{$page}",
//		  "form_id": "{$form_id}",
//		  "data": {
//		    "keyword1": {
//		      "value": "339208499"
//		    },
//		    "keyword2": {
//		      "value": "2015年01月05日 12:30"
//		    },
//		    "keyword3": {
//		      "value": "腾讯微信总部"
//		    },
//		    "keyword4": {
//		      "value": "广州市海珠区新港中路397号"
//		    }
//		  },
//		  "emphasis_keyword": "keyword1.DATA"
//		}
//END;
    $post_data = [
        "touser" => $app_id,
        "template_id" => $template_id,
        "page" => $page,
        "form_id" => $form_id,
        "data" => $data
    ];
    $res = curl($url,json_encode($post_data));
    return $res;
}

//获取小程序二维码createWXAQRCode OK
function create_wx_code( $app_accounts_id,$path,$width = 430 ){
    //DO 1.获取access_token
    $access_token = get_redis_access_token( $app_accounts_id );
    //DO 2.请求地址
    $url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$access_token;
    $post_data =<<<END
            {
               "path": "{$path}", 
               "width":"{$width}"
            }            
END;
    $res = curl( $url,$post_data );
    //DO 3.执行请求地址返回结果--如果调用成功，会直接返回图片二进制内容，如果请求失败，会返回 JSON 格式的数据。
    var_dump($res);
    //return $res;
}

//getWXACode
function get_wx_code( $app_accounts_id,$path,$width = 430,$auto_color = 'false',$line_color = '{"r":0,"g":0,"b":0}',$is_hyaline = 'false' ){
    //DO 1.获取access_token
    $access_token = get_redis_access_token( $app_accounts_id );
    //DO 2.请求地址/请求参数
    $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token;
    $post_data =<<<END
            {
               "path": "{$path}", 
               "width":"{$width}",
               "auto_color":"{$auto_color}",
               "line_color":"{$line_color}",
               "is_hyaline":"{$is_hyaline}"
            }            
END;
    //DO 3.执行请求地址返回结果
    $res = curl( $url,$post_data );
    var_dump($res);
    //return $res;
}

//getWXACodeUnlimit
function get_wx_code_unlimit( $app_accounts_id,$scene,$width = 430,$auto_color = 'false',$line_color = '{"r":0,"g":0,"b":0}',$is_hyaline = 'false' ){
    //DO 1.获取access_token
    $access_token = get_redis_access_token( $app_accounts_id );
    //DO 2.请求地址/请求参数
    $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
    $post_data =<<<END
            {
               "scene": "{$scene}", 
               "width":"{$width}",
               "auto_color":"{$auto_color}",
               "line_color":"{$line_color}",
               "is_hyaline":"{$is_hyaline}"
            }            
END;
    //DO 3.执行请求地址返回结果
    $res = curl( $url,$post_data );
    var_dump($res);
    // return $res;
}
