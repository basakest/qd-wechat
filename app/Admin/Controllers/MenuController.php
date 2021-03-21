<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Row;
use EasyWeChat\Factory;
use App\Admin\Forms\Menu;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Widgets\Alert;

class MenuController extends Controller
{
    protected $app;

    protected $menuTypes = [
        'view' => '打开网址',
        'text' => '响应文本',
        'image' => '响应图片',
        'miniprogram' => '打开小程序'
    ];

    public function __construct()
    {
        if (!($this->app instanceof \EasyWeChat\OfficialAccount\Application)) {
            $this->app = Factory::officialAccount([
                'app_id' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'response_type' => env('WECHAT_RESPONSE_TYPE', 'array')
            ]);
        }
    }

    public function index(Content $content, Form $form)
    {
        $content->header('公众号菜单');
        $content->description('列表');
        $content->breadcrumb(
            ['text' => '用户管理', 'url' => '/admin/users']
        );
        // 表单
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
        $form->action('create');
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();

        // 最上面的警告框
        $alert = Alert::make('公众号菜单只分为一级和二级，一级最大个数是3个，二级最大个数是5个', '提示');
        $alert->info();
        $alert->icon('feather icon-info');

        // 内容布局
        $content->row(function(Row $row) use ($form, $alert) {
            $row->column(12, function(Column $column) use ($alert) {
                $column->row($alert);
            });
            $row->column(6, 'section1');
            $row->column(6, $form);
        });
        return $content;
    }

     /**
     * 获取所有的父级标题的名字
     *
     * @return array
     */
    public function getNameOfParentMenus() : array
    {
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

    public function insert($index, array $new) : bool
    {
        $this->app = Factory::officialAccount($this->config);
        $list = $this->app->menu->list();
        $temp = $list['menu']['button'][$index]['sub_button'];
        $temp[] = $new;
        $list['menu']['button'][$index]['sub_button'] = $temp;
        $this->app->menu->create($list['menu']['button']);
        return true;
    }

    public function createMenu(Form $form)
    {
        $form->saving(function (Form $form) {
            $new = [];
            $parent = $form->parent;
            $new['name'] = $form->name;
            $new['key'] = $form->key;
            $new['type'] = $form->type;
            switch ($new['type']) {
                case 'view':
                    $new['url'] = $form->url;
                    break;
            }
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
        });
    }

    public function createMenu2($parent, $new)
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
}
