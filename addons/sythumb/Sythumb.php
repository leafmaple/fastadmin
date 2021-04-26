<?php

namespace addons\sythumb;

use think\Addons;
use think\Config;
use think\Loader;
use addons\sythumb\library\imgclass\Image;

/**
 * 缩略图插件
 * 2018-02-12
 * author: dilu
 * 253407587@qq.com
 */
class Sythumb extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }


    /**
     * 添加命名空间
     */
    public function appInit()
    {
        //添加命名空间
        
        
       
    }

    /**
     * 实现钩子方法
     * @return mixed
     */
    public function uploadAfter($param)
    {
        // 获取传进来的附件模型数据
        $data = $param->getData();

        //对文件进行检测 不是图片类型的不做处理
        if(!strpos($data['mimetype'],'image') === true && strpos($data['mimetype'],'image') === 0)
        {

           //附件id
           	if(array_key_exists("id",$data)){
           	    $attachment_id = $param['id'];
           	}else{
           		$imgdate = $param->where('url',$param['url'])->find();
           		$attachment_id = $imgdate['id'];
           	}
               
               //获取配置
               $config = $this->getConfig();
               //图片质量
               $quality = isset($config['quality']) ? $config['quality'] : '100';
               if ($quality > 100 || $quality < 10) {
                   $quality = 100;
               }
           	//打开图片
           	$image = Image::open(ROOT_PATH . '/public' . $data['url']);
           	
           	//获取后缀
           	$ext = isset($config['ext']) ? $config['ext'] : '-thumb';
           	$url = explode('.', $data['url']);
           	
           	//判断是否图片缩放
           	if($config['deploy'] > 0){
           		
           		$image->thumb($config['zoomwith'],$config['zoomheight'],$config['deploy']);
           		
           	}
           	
           	//判断是否增加水印
           	if ($config['type'] > 0){
           		
           		//判断是图片还是文字
           		if($config['type'] == 1){ //图片
           			
           			//地址
           			$imgurl = ROOT_PATH . '/public/'. DS . $config['images'];
           			
           			$image->water($imgurl,$config['location'],$config['diaphaneity']);
           			
           		}else{
           			
           			//地址
           			$fonturl = ROOT_PATH . '/public/'. DS . $config['font'];;
           			
           			$image->text($config['text'],$fonturl,$config['size'],$config['color'],$config['location'],$config['offset']);
           			
           		}
           		
           	}
           	
           	//判断是否替换文件
           	
               if (1 == $config['replace'] && !empty($attachment_id)) //如果是选择替换原文件
               {
                  
                   $image->save(ROOT_PATH . '/public' . $data['url'], null, $quality);
           
                   $data = array(
                       'filesize' => filesize(ROOT_PATH . '/public' . $data['url']),
                       'imagewidth' => $image->width(),
                       'imageheight' => $image->height(),
                       'imagetype' => $image->type(),
                       'imageframes' => 1,
                       'mimetype' => $image->mime(),
                       'url' => $data['url'],
                       'uploadtime' => time(),
                       'storage' => 'local',
                       'sha1' => sha1_file(ROOT_PATH . '/public' . $data['url']),
                       'updatetime' => time(),
                   );
           
                   $param->where('id', $attachment_id)->update($data);
           		
               } else {
           
                   //组装缩略图的url
                   $url = $url[0] . $ext . '.' . $url[1];
                   $image->save(ROOT_PATH . '/public' . $url, null, $quality);
           
                   $data = array(
                       'filesize' => filesize(ROOT_PATH . '/public' . $url),
                       'imagewidth' => $image->width(),
                       'imageheight' => $image->height(),
                       'imagetype' => $image->type(),
                       'imageframes' => 1,
                       'filename'=>substr($data['filename'], 0, strripos($data['filename'], '.')).'-thumb',
                       'mimetype' => $image->mime(),
                       'url' => $url,
                       'uploadtime' => time(),
                       'storage' => 'local',
                       'sha1' => sha1_file(ROOT_PATH . '/public' . $url),
                       'createtime' => time(),
                       'updatetime' => time(),
                   );
                   $param->insert($data);
           		
               }
           
           }
        }
        

}
