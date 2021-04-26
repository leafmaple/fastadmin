<?php

namespace addons\cms\controller;

use think\Config;

/**
 * CMS首页控制器
 * Class Index
 * @package addons\cms\controller
 */
class Index extends Base
{
    public function index()
    {
        $config = get_addon_config('cms');

        //设置TKD
        Config::set('cms.title', $config['title'] ?: __('Home'));
        Config::set('cms.keywords', $config['keywords']);
        Config::set('cms.description', $config['description']);

        if ($this->request->isAjax()) {
            $this->success("", "", $this->view->fetch('common/index_list'));
        }
        return $this->view->fetch('/index');
    }

}
