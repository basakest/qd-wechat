<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Row;
use EasyWeChat\Factory;
use App\Admin\Forms\Menu;
use Dcat\Admin\Widgets\Callout;

class MenuController extends Controller
{
    protected $app;

    protected $config = [
        'app_id' => 'wx49f960539fb6da0c',
        'secret' => 'da7c7ae0a568e41e81322d0eef0b7bf4',
        'response_type' => 'array',
    ];

    protected $menuTypes = [
        'view' => '打开网址',
        'text' => '响应文本',
        'image' => '响应图片',
        'miniprogram' => '打开小程序'
    ];

    public function __construct()
    {
        $this->app = Factory::officialAccount($this->config);
    }

    public function index(Content $content, Form $form)
    {
        $content->header('公众号菜单');
        $content->description('列表');
        $content->breadcrumb(
            ['text' => '用户管理', 'url' => '/admin/users']
        );
        $form->disableHeader();

        $form->select('parent', '父级')->required()->options($this->getNameOfParentMenus());
        $form->text('name', '菜单标题')->placeholder('输入 菜单标题')->required();
        $form->text('key', '菜单 Key')->placeholder('输入 菜单 Key');
        // $form->radio('type', '菜单类型')->options($this->menuTypes);
        $form->radio('type', '菜单类型')
            ->when('view', function(Form $form) {
                $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->when('text', function(Form $form) {
                $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->when('image', function(Form $form) {
                $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->when('miniprogram', function(Form $form) {
                $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->options($this->menuTypes)
            ->default('view');
        // $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
        $form->confirm('您确定要提交表单吗');
        $form->action('test');
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        // 最上面的警告框
        $callout = Callout::make('提示', '公众号菜单只分为一级和二级，一级最大个数是3个，二级最大个数是5个');
        // 颜色
        $callout->light();
        $callout->primary();
        // 可移除按钮
        $callout->removable();
        $content->row(function(Row $row) {
            $row->column(12, 'section1');
        });
        $content->row(function(Row $row) use ($form) {
            $row->column(6, 'section1');
            $row->column(6, $form);
        });
        return $content;
    }

    public function test(Form $form)
    {
        return response($form);
    }

    public function menu(Content $content, Menu $form)
    {
        return $content
            ->header('公众号菜单')
            ->description('列表')
            ->breadcrumb(['text' => '用户管理', 'url' => '/admin/users'])
            ->row(function(Row $row) use ($form) {
                $row->column(6, 'section1');
                $row->column(6, $form);
            });
    }

    public function getNameOfParentMenus()
    {
        $menus = $this->app->menu->list();
        $result = [];
        foreach($menus['menu']['button'] as $k => $v) {
            $result[$k] = $v['name'];
        }
        return $result;
    }
}
