<?php
namespace app\admin\controller;
use think\Controller;

class Index extends Controller
{
    public function index(){
        $app_accounts_id = 1;
        $code="071s5vl00mzkWG1fCJk00JPQl00s5vl9";
        //{"session_key":"Xc+UuWSuVK9UGIw+ahJLLA==","openid":"o4sGA4izAJdL0Edr4mL1c42yxuJ4","unionid":"o6JPfsueX8N2q7oqvrho2xYWhuZE"}"
       $media_id = 1;
       // get_temp_media($app_accounts_id,$media_id);
//        $code=1;
//        customer_typing($app_accounts_id,$code,$command = "Typing");
       //get_access_token($app_accounts_id);

        //get_openid($app_accounts_id,$code);
        //customer_typing($app_accounts_id,$code,$command = "Typing");
//        $media =<<<END
//            {
//             "filename":"111",
//             "filelength":"3kb",
//             "content-type":"jhjhjh"
//            }
//END;
//        upload_temp_media( $app_accounts_id,$type="image",$media );
       // create_wx_code( $app_accounts_id,$path='128',$width = '430' );
       // get_wx_code( $app_accounts_id,$path='128',$width = '430',$auto_color = 'false',$line_color = '{"r":0,"g":0,"b":0}',$is_hyaline = 'false' );
        //get_wx_code_unlimit( $app_accounts_id,$scene='gftt543',$width = 430,$auto_color = 'false',$line_color = '{"r":0,"g":0,"b":0}',$is_hyaline = 'false' );
       // send_customer_message( $app_accounts_id,$code='',$msgtype='text',$title='',$content='hello',$media_id='',$description='',$link_url='',$thumb_url='',$pagepath='',$thumb_media_id='' );
        //add_template( $app_accounts_id,"AT0002","[3, 4, 5]");
       // delete_template( $app_accounts_id,$template_id='d-kNR3ppTFMIqnTyioVxgzd8JpKK2OrO1YSDT-05qdA' );
        //get_template_library_by_id( $app_accounts_id,$id = "AT0002" );
        //get_template_library_list( $app_accounts_id,$offset = 0,$count = 20 );
        //get_template_list( $app_accounts_id,0,1 );
    }
}
