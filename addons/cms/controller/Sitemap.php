<?php

namespace addons\cms\controller;

use think\Config;

/**
 * Sitemap控制器
 * Class Sitemap
 * @package addons\cms\controller
 */
class Sitemap extends Base
{
    protected $noNeedLogin = ['*'];
    protected $options = [
        'item_key'  => '',
        'root_node' => 'urlset',
        'item_node' => 'url',
        'root_attr' => 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.baidu.com/schemas/sitemap-mobile/1/"'
    ];
    //默认配置
    protected $config = [
        'pagesize' => 50000,
        'cache'    => 3600
    ];

    public function _initialize()
    {
        parent::_initialize();
        Config::set('default_return_type', 'xml');
    }

    /**
     * Sitemap集合
     */
    public function index()
    {
        $pagesize = $this->request->param('pagesize/d', $this->config['pagesize']);
        $type = $this->request->param('type', '');
        $type = str_replace('.xml', '', $type);
        $list = [];
        $pagesizeStr = $pagesize != $this->config['pagesize'] ? "pagesize/{$pagesize}/" : '';
        if (!$type || $type == 'channel') {
            $path = "/addons/cms/sitemap/channels/{$pagesizeStr}page/[PAGE]";
            $channelsList = \addons\cms\model\Channel::where('status', 'normal')->field('id,name,diyname,createtime')->paginate($pagesize, false, ['path' => $path]);
            $lastPage = $channelsList->lastPage();
            foreach ($channelsList->getUrlRange(1, $lastPage) as $index => $item) {
                $list[] = ['loc' => url($item, '', 'xml', true)];
            }
        }
        if (!$type || $type == 'archives') {
            $path = "/addons/cms/sitemap/archives/{$pagesizeStr}page/[PAGE]";
            $archivesList = \addons\cms\model\Archives::where('status', 'normal')->field('id,channel_id,diyname,createtime,publishtime')->paginate($pagesize, false, ['path' => $path]);
            $lastPage = $archivesList->lastPage();
            foreach ($archivesList->getUrlRange(1, $lastPage) as $index => $item) {
                $list[] = ['loc' => url($item, '', 'xml', true)];
            }
        }
        if (!$type || $type == 'tags') {
            $path = "/addons/cms/sitemap/tags/{$pagesizeStr}page/[PAGE]";
            $tagsList = \addons\cms\model\Tag::where('status', 'normal')->field('id,name')->paginate($pagesize, false, ['path' => $path]);
            $lastPage = $tagsList->lastPage();
            foreach ($tagsList->getUrlRange(1, $lastPage) as $index => $item) {
                $list[] = ['loc' => url($item, '', 'xml', true)];
            }
        }
        if (!$type || $type == 'users') {
            $path = "/addons/cms/sitemap/users/{$pagesizeStr}page/[PAGE]";
            $usersList = \addons\cms\model\User::where('status', 'normal')->field('id')->paginate($pagesize, false, ['path' => $path]);
            $lastPage = $usersList->lastPage();
            foreach ($usersList->getUrlRange(1, $lastPage) as $index => $item) {
                $list[] = ['loc' => url($item, '', 'xml', true)];
            }
        }
        $this->options = [
            'item_key'  => '',
            'root_node' => 'sitemapindex',
            'item_node' => 'sitemap',
            'root_attr' => ''
        ];
        return xml($list, 200, [], $this->options);
    }

    /**
     * 栏目
     */
    public function channels()
    {
        $pagesize = $this->request->param('pagesize/d', $this->config['pagesize']);
        $archivesList = \addons\cms\model\Channel::where('status', 'normal')->cache($this->config['cache'])->field('id,name,diyname,createtime')->paginate($pagesize);
        $list = [];
        foreach ($archivesList as $index => $item) {
            $list[] = [
                'loc'      => $item->fullurl,
                'priority' => 0.6
            ];
        }
        return xml($list, 200, [], $this->options);
    }

    /**
     * 文章
     */
    public function archives()
    {
        $pagesize = $this->request->param('pagesize/d', $this->config['pagesize']);
        $archivesList = \addons\cms\model\Archives::where('status', 'normal')->cache($this->config['cache'])->field('id,channel_id,diyname,createtime')->paginate($pagesize);
        $list = [];
        foreach ($archivesList as $index => $item) {
            $list[] = [
                'loc'      => $item->fullurl,
                'priority' => 0.8
            ];
        }
        return xml($list, 200, [], $this->options);
    }

    /**
     * 标签
     */
    public function tags()
    {
        $pagesize = $this->request->param('pagesize/d', $this->config['pagesize']);
        $tagsList = \addons\cms\model\Tag::where('status', 'normal')->cache($this->config['cache'])->field('id,name')->paginate($pagesize);
        $list = [];
        foreach ($tagsList as $index => $item) {
            $list[] = [
                'loc'      => $item->fullurl,
                'priority' => 0.6
            ];
        }
        return xml($list, 200, [], $this->options);
    }

    /**
     * 用户
     */
    public function users()
    {
        $pagesize = $this->request->param('pagesize/d', $this->config['pagesize']);
        $userList = \addons\cms\model\User::where('status', 'normal')->cache($this->config['cache'])->field('id')->paginate($pagesize);
        $list = [];
        foreach ($userList as $index => $item) {
            $list[] = [
                'loc'      => $item->fullurl,
                'priority' => 0.6
            ];
        }
        return xml($list, 200, [], $this->options);
    }
}
