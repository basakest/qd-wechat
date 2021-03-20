<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Symfony\Component\HttpFoundation\Response;
use EasyWeChat\Factory;

class Menu extends Form
{
    protected $config = [
        'app_id' => 'wx49f960539fb6da0c',
        'secret' => 'da7c7ae0a568e41e81322d0eef0b7bf4',
        'response_type' => 'array',
    ];

    protected $app;

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
        $parentName = $input['parent'];
        $new = [];
        $new['type'] = $input['type'];
        $new['title'] = $input['title'];
        $new['key'] = $input['key'];
        $new['url'] = $input['url'];
        $res = $this->create($parentName, $new);
        if ($res) {
            return $this->success('Processed successfully.', '/');
        }
        // return $this->error('Your error message.');
        //$this->createMenu();
        /*
        if ($input) {
            return $this->success('Processed successfully.', '/');
        }
        */
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('parent', '父级')->options($this->getNameOfParentMenus())->required();
        $this->text('title', '菜单标题')->placeholder('输入 菜单标题')->required();
        $this->text('key', '菜单 Key')->placeholder('输入 菜单 Key');
        $this->radio('type', '菜单类型')->options(['view' => '打开网址', 'text' => '响应文本', 'image' => '响应图片', 'miniprogram' => '打开小程序']);
        $this->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
        $this->confirm('您确定要提交表单吗');
    }

    /**
     * 获取所有的父级标题的名字
     *
     * @return array
     */
    public function getNameOfParentMenus() : array
    {
        $this->app = Factory::officialAccount($this->config);
        $menus = $this->app->menu->list();
        $result = [];
        foreach($menus['menu']['button'] as $k => $v) {
            $result[$k] = $v['name'];
        }
        return $result;
    }

    public function determineWhetherTiTleExists($title, $h1Titles)
    {
        $index = null;
        foreach($h1Titles as $i => $v) {
            if ($v == $title) {
                $index = $i;
                break;
            }
        }
        return $index;
    }

    public function checkNum($index)
    {
        $num = count($this->app->menu->list()['menu']['button'][$index]['sub_button']);
        if ($num >= 5) {
            echo '超过上限，无法创建';
            return false;
        }
        return true;
    }

    public function insert($index, array $new)
    {
        $this->app = Factory::officialAccount($this->config);
        $origin = $this->app->menu->list()['menu']['button'][$index]['sub_button'];
        $origin[] = $new;
        $this->app->menu->create($this->app->menu->list()['menu']['button']);
        return true;
    }

    public function create($parent, $new)
    {
        $parentTitles = $this->getNameOfParentMenus();
        $index = $this->determineWhetherTiTleExists($parent, $parentTitles);
        if ($index !== null) {
            // 执行到这里说明该父级标题存在
            // 接下来判断该父级标题下的二级标题数量是否超过上限
            if ($this->checkNum($index)) {
                // $this->success('Processed successfully.', '/menu');
                // 执行到这里说明该父级标题下的二级标题数量没有超过上限，可以执行创建操作
                $this->insert($index, $new);
            }
        }
    }

    public function createMenu()
    {
        $this->app = Factory::officialAccount($this->config);
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'type' => 'type1',
            'url' => 'http://wechat.test/admin/menus',
        ];
    }
}
