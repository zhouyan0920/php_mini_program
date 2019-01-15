<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\admin\model\Comments;



class Snapshot extends Controller
{
    /**
     * 首页轮播图
     */
    public function show_home(){
        //DO 1.获取openid、unionid
        $app_accounts_id = input('app_accounts_id');
        $code = input('wx_code');
        $res = get_openid( $app_accounts_id,$code );
        $openid = $res['openid'];
        $unionid = $res['unionid'];

        //DO 2.把openid、unionid存入数据库
        db('external_user')->insert(['openid'=>$openid,'unionid'=>$unionid]);

        //DO 3.找到wu_id、tel存入session
        $user = db('external_wus')
            -> field('wu_id,tel')
            -> where('unionid',$unionid)
            -> find();
        $wu_id = $user['wu_id'];
        $tel = $user['tel'];
        $external_wu_id = $user['id'];
        session(['openid'=>$openid,'wu_id'=>$wu_id,'tel'=>$tel,'external_wu_id'=>$external_wu_id]);

        //DO 4.获取轮播图数据
        $images = db('ssp_images')
            -> alias('a')
            -> join('ssp_comments b','a.ssp_comment_id = b.id')
            -> where('a.is_top',1)
            -> select();

        //DO 3.返回结果
        if ( !empty($images) ){
            $result = [];
            foreach ($images as $k => $v){
                $res_json = [
                    'image' => $v['url'],
                    'positionTitle' => $v['name'],
                    'title' => $v['title'],
                    'latitude' => $v['lat'],
                    'longitude' => $v['lng'],
                    'address' => $v['address']
                ];
                $result[] = $res_json;
            }
//            echo "<pre>";
//            var_dump($result);
            return json([
                'code' => 1,
                'message' => 'success',
                'data' => ['imgUrls'=>$result]
            ]);
        }else{
            return json([
                'code' => 0,
                'message' => 'error',
                'data' => '数据不存在'
            ]);
        }


    }

    /**
     * 查看更多页面
     */
    public function show_more(){
        //$wu_id = session('wu_id');
        $wu_id = 1;
//        $begin_time = date("Y-m-d",time());//今天0点的时间点
//        $end_time=date("Y-m-d H:i:s",strtotime($begin_time) + 3600*24);//今天24点的时间点
//        $comments = Comments::with('images')
//            -> alias('a')
//            -> field('a.*,b.cname,b.openid,c.ssp_comment_id')
//            -> join('wus b','a.wu_id = b.id','LEFT')
//            -> join('ssp_flowers c','a.id = c.ssp_comment_id and c.wu_id = '.$wu_id,'LEFT')
//            -> order('a.created_at desc')
//            -> where('a.created_at','between',[$begin_time,$end_time])
//            //-> fetchSql(true)
//            -> select()
////            -> paginate(1)
//        ;
        $time = input('time');
        $type = input('type');
        $rank = input('rank');
        $begin_time = date("Y-m-d H:i:s",strtotime($time));//0点的时间点
        $end_time=date("Y-m-d H:i:s",strtotime($time) + 3600*24);//24点的时间点
        $comments = Comments::with('images')
            -> alias('a')
            -> field('a.*,b.cname,b.openid,c.ssp_comment_id')
            -> join('wus b','a.wu_id = b.id','LEFT')
            -> join('ssp_flowers c','a.id = c.ssp_comment_id and c.wu_id = '.$wu_id,'LEFT')
            -> where('a.created_at','between',[$begin_time,$end_time])
            //-> fetchSql(true)
            ;
           // -> paginate(20);
        if ( $type == 0 && $rank == 0 ){
            $comments = $comments ->order('a.created_at desc');
//            $comments = Comments::with('images')
//                -> alias('a')
//                -> field('a.*,b.cname,b.openid,c.ssp_comment_id')
//                -> join('wus b','a.wu_id = b.id','LEFT')
//                -> join('ssp_flowers c','a.id = c.ssp_comment_id and c.wu_id = '.$wu_id,'LEFT')
//                -> order('a.created_at desc')
//                -> where('a.created_at','between',[$begin_time,$end_time])
//                //-> fetchSql(true)
//                -> paginate(20)
//                ;
        }else if ( $type != 0 && $rank == 0 ){
            $comments = $comments ->order('a.created_at desc') ->where('a.comment_type',$type);
//            $comments = Comments::with('images')
//                -> alias('a')
//                -> field('a.*,b.cname,b.openid,c.ssp_comment_id')
//                -> join('wus b','a.wu_id = b.id','LEFT')
//                -> join('ssp_flowers c','a.id = c.ssp_comment_id and c.wu_id = '.$wu_id,'LEFT')
//                -> order('a.created_at desc')
//                -> where('a.created_at','between',[$begin_time,$end_time])
//                -> where('a.comment_type',$type)
//                -> paginate(20)
//                ;
        }else if( $type != 0 && $rank != 0 ){
            $comments = $comments ->order('a.flower_cnt desc') ->where('a.comment_type',$type);
//            $comments = Comments::with('images')
//                -> alias('a')
//                -> field('a.*,b.cname,b.openid,c.ssp_comment_id')
//                -> join('wus b','a.wu_id = b.id','LEFT')
//                -> join('ssp_flowers c','a.id = c.ssp_comment_id and c.wu_id = '.$wu_id,'LEFT')
//                -> order('a.flower_cnt desc')
//                -> where('a.created_at','between',[$begin_time,$end_time])
//                -> where('a.comment_type',$type)
//                -> paginate(20)
//                ;
        }else if ( $type == 0 && $rank != 0 ){
            $comments =  $comments ->order('a.flower_cnt desc');
//            $comments = Comments::with('images')
//                -> alias('a')
//                -> field('a.*,b.cname,b.openid,c.ssp_comment_id')
//                -> join('wus b','a.wu_id = b.id','LEFT')
//                -> join('ssp_flowers c','a.id = c.ssp_comment_id and c.wu_id = '.$wu_id,'LEFT')
//                -> order('a.flower_cnt desc')
//                -> where('a.created_at','between',[$begin_time,$end_time])
//                -> paginate(20)
//                ;
        }
        $comments = $comments->paginate(20);


        echo "<pre>";
       //var_dump($comments[0]);
//        $images = $comments[0]->images;
//        print_r($images[0]->url);

        if ( !empty( $comments[0]->id ) ){
            $result = [];
            foreach( $comments as $k => $v ){
                $imgs = $v->images;
                $tpl_json = [
                    'id' => $v->id,
                    'level' => 0,
                    'name' => $v->cname,
                    'title' => $v->title,
                    'imageUrl' => collection($imgs) -> toArray(),
                    'date' => $v->created_at,
                    'op' =>[
                        'title' => $v->name,
                        'latitude' => $v->lat,
                        'longitude' => $v->lng,
                        'address' => $v->address,
                    ],
                    'flower' => $v->flower_cnt
                ];

                if ($v->ssp_comment_id == NULL){
                    $tpl_json['spot'] = 0;
                }else{
                    $tpl_json['spot'] = 1;
                }
                $result[]=$tpl_json;

            }
            //print_r($result);
            return json([
                'code' => 1,
                'message' => 'success',
                'data' => $result
            ]);
        }else{
            return json([
                'code' => 2,
                'message' => 'error',
                'data' => '数据不存在'
            ]);
        }

    }

    /**
     * 我的发布列表
     */
    public function my_comments(){
        //$wu_id = session('wu_id');
        $wu_id = 1;
        $comments = Comments::with( 'images' )->where('wu_id',$wu_id )->select();
        if ( !empty( $comments[0]->id ) ){
            $result = [];
            $count = count($comments);
            foreach ( $comments as $k => $v ){
                $imgs = $v->images;
                $tpl_json = [
                    'id' => $v->id,
                    'title' => $v->title,
                    'idea' => $v->content,
                    'date' => $v->created_at,
                    'imageUrl' => collection($imgs) -> toArray(),
                    'dialogue' => $count
                ];
                $result[]=$tpl_json;
            }
            //echo "<pre>";
            //var_dump($comments);
            //print_r($result);
            return json([
                'code' => 1,
                'message' => 'success',
                'data' => $result
            ]);
        }else{
            return json([
                'code' => 0,
                'message' => 'error',
                'data' => '数据不存在'
            ]);
        }

    }

    /**
     * 随手拍添加评论
     */
    public function add_content(){

        $titleValue = input( 'titleValue' );
        $contentValue = input( 'contentValue' );
        $location = input( 'location' );
        $address = $location['address'];
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        $name = $location['name'];
        $array_index = input( 'array_index' );
        $phoneValue = input( 'phoneValue' );
        $external_wu_id = session('external_wu_id');
        $wu_id = session('wu_id');
        $created_at = date('Y-m-d H:i:s',time());
        $updated_at = date('Y-m-d H:i:s',time());
        $data=[
            'title' => $titleValue,
            'content' => $contentValue,
            'name' => $name,
            'lat' => $latitude,
            'lng' => $longitude,
            'address' => $address,
            'comment_type' => $array_index,
            'phone_number' => $phoneValue,
            'flower_cnt' => 0,
            'external_wu_id' => $external_wu_id,
            'wu_id' => $wu_id,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ];
        $res = db( 'ssp_comments' )->insert($data);
        if ($res){
            $newId = db( 'ssp_comments' )->getLastInsID();
            return json([
                'code' => 1,
                'message' => 'success',
                'data' => $newId
            ]);
        }else{
            return json([
                'code' => 0,
                'message' => 'error',
                'data' => '数据插入失败'
            ]);
        }
    }

    /**
     * 随手拍添加照片
     */
    public function add_pic(){

        $ssp_comment_id = input('$comment_id');
        $images = request()->file('files');
        $is_top = 0;
        $created_at = date('Y-m-d H:i:s',time());
        $updated_at = date('Y-m-d H:i:s',time());

        foreach ($images as $k => $v){
            $data = [
                'url' => $v,
                'is_top' => $is_top,
                'ssp_comment_id' => $ssp_comment_id,
                'created_at' => $created_at,
                'updated_at' => $updated_at
            ];
        }
        $res = db('ssp_comments')->insertAll($data);
        if ( $res ){
            return json([
                'code' => 1,
                'message' => 'success',
                'data' => $res
            ]);
        }else{
            return json([
                'code' => 0,
                'message' => 'error',
                'data' => '数据插入失败'
            ]);
        }

    }
}
