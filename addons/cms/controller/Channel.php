<?php

namespace addons\cms\controller;

use addons\cms\library\Service;
use addons\cms\model\Archives;
use addons\cms\model\Channel as ChannelModel;
use addons\cms\model\Fields;
use addons\cms\model\Modelx;
use think\Config;

/**
 * 栏目控制器
 * Class Channel
 * @package addons\cms\controller
 */
class Channel extends Base
{
    public function index()
    {
        $config = get_addon_config('cms');

        $diyname = $this->request->param('diyname');

        if ($diyname && !is_numeric($diyname)) {
            $channel = ChannelModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->param('id', '');
            $channel = ChannelModel::get($id);
        }
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }

        $filter = $this->request->get('filter/a', []);
        $orderby = $this->request->get('orderby', '');
        $orderway = $this->request->get('orderway', '', 'strtolower');
        $multiple = $this->request->get('multiple/d', 0);

        $params = [];
        $filter = $this->request->get();
        $filter = array_diff_key($filter, array_flip(['orderby', 'orderway', 'page', 'multiple']));
        if (isset($filter['filter'])) {
            $filter = array_merge($filter, $filter['filter']);
        }
        if ($filter) {
            $filter = array_filter($filter, 'strlen');
            $params['filter'] = $filter;
            $params = $filter;
        }
        if ($orderby) {
            $params['orderby'] = $orderby;
        }
        if ($orderway) {
            $params['orderway'] = $orderway;
        }
        if ($multiple) {
            $params['multiple'] = $multiple;
        }
        if ($channel['type'] === 'link') {
            $this->redirect($channel['outlink']);
        }

        //加载模型数据
        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error(__('No specified model found'));
        }

        //默认排序字段
        $orders = [
            ['name' => 'default', 'field' => 'weigh DESC,id DESC', 'title' => __('Default')],
        ];

        //合并主表筛选字段
        $orders = array_merge($orders, $model->getOrderFields());

        //获取过滤列表
        list($filterList, $filter, $params, $fields, $multiValueFields, $fieldsList) = Service::getFilterList('model', $model['id'], $filter, $params, $multiple);

        //获取排序列表
        list($orderList, $orderby, $orderway) = Service::getOrderList($orderby, $orderway, $orders, $params, $fieldsList);

        //获取过滤的条件和绑定参数
        list($filterWhere, $filterBind) = Service::getFilterWhereBind($filter, $multiValueFields, $multiple);

        //加载列表数据
        $pageList = Archives::with(['channel', 'user'])->alias('a')
            ->where('a.status', 'normal')
            ->whereNull('a.deletetime')
            ->where($filterWhere)
            ->bind($filterBind)
            ->join($model['table'] . ' n', 'a.id=n.id', 'LEFT')
            ->field('a.*')
            ->field('id,content', true, config('database.prefix') . $model['table'], 'n')
            ->where(function ($query) use ($channel) {
                $query->where(function ($query) use ($channel) {
                    if ($channel['listtype'] <= 2) {
                        $query->whereOr("channel_id", $channel['id']);
                    }
                    if ($channel['listtype'] == 1 || $channel['listtype'] == 3) {
                        $query->whereOr('channel_id', 'in', function ($query) use ($channel) {
                            $query->name("cms_channel")->where('parent_id', $channel['id'])->field("id");
                        });
                    }
                    if ($channel['listtype'] == 0 || $channel['listtype'] == 4) {
                        $childrenIds = \addons\cms\model\Channel::getChannelChildrenIds($channel['id'], false);
                        if ($childrenIds) {
                            $query->whereOr('channel_id', 'in', $childrenIds);
                        }
                    }
                })
                    ->whereOr("(`channel_ids`!='' AND FIND_IN_SET('{$channel['id']}', `channel_ids`))");
            })
            ->where('model_id', $channel->model_id)
            ->order($orderby, $orderway)
            ->paginate($channel['pagesize'], $config['pagemode'] == 'simple', ['type' => '\\addons\\cms\\library\\Bootstrap']);

        $fieldsContentList = Fields::getFieldsContentList('model', $model->id);
        foreach ($pageList as $index => $item) {
            Service::appendTextAttr($fieldsContentList, $item);
        }

        $fieldsContentList = Fields::getFieldsContentList('channel');
        Service::appendTextAttr($fieldsContentList, $channel);

        $pageList->appends(array_filter($params));
        $this->view->assign("__FILTERLIST__", $filterList);
        $this->view->assign("__ORDERLIST__", $orderList);
        $this->view->assign("__PAGELIST__", $pageList);
        $this->view->assign("__CHANNEL__", $channel);

        //设置TKD
        Config::set('cms.title', isset($channel['seotitle']) && $channel['seotitle'] ? $channel['seotitle'] : $channel['name']);
        Config::set('cms.keywords', $channel['keywords']);
        Config::set('cms.description', $channel['description']);

        //读取模板
        $template = preg_replace('/\.html$/', '', $channel["{$channel['type']}tpl"]);

        if ($this->request->isAjax()) {
            $this->success("", "", $this->view->fetch('common/' . $template . '_ajax'));
        }
        return $this->view->fetch('/' . $template);
    }
}
