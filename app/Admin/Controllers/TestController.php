<?php

//namespace Dcat\Admin\Controllers;
namespace App\Admin\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Models\Repositories\Menu;
use Dcat\Admin\Tree;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Form as WidgetForm;
use App\Models\Menu as MenuModel;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Http\Request;
use EasyWeChat\Factory;

class TestController extends AdminController
{
    protected $app;

    protected $menuTypes =
    [
        'view' => '打开网址',
        'text' => '响应文本',
        'image' => '响应图片',
        'miniprogram' => '打开小程序'
    ];

    /**
     * 创建公众号实例
     */
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

    public function title()
    {
        return '公众号菜单';
    }

    public function index(Content $content)
    {
        return $content
            ->title($this->title())
            ->description('列表')
            ->body(function (Row $row) {
                // 最上面的警告框
                $alert = Alert::make('公众号菜单只分为一级和二级，一级最大个数是3个，二级最大个数是5个', '提示');
                $alert->info();
                $alert->icon('feather icon-info');
                $row->column(12, function(Column $column) use ($alert) {
                    $column->row($alert);
                });
                $row->column(6, $this->treeView()->render());

                $row->column(6, function (Column $column) {
                    # 创建表单实例
                    $form = new WidgetForm();
                    # 设置表单提交的action
                    $form->action(admin_url('insert'));

                    $model = new MenuModel();
                    $form->select('parent_id', '父级')->options($model::selectOptions())->required();
                    $form->text('name', '菜单标题')->required()->placeholder('输入 菜单标题');
                    $form->text('key', '菜单 Key')->placeholder('输入 菜单key');
                    $form->radio('type', '菜单类型')->options($this->menuTypes)
                        ->when('view', function() use ($form) {
                            $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url')->default('https://learnku.com/');
                        })
                        ->when('text', function() use ($form) {
                            $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url')->default('https://learnku.com/');
                        })
                        ->when('image', function() use ($form) {
                            $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url')->default('https://learnku.com/');
                        })
                        ->when('miniprogram', function() use ($form) {
                            $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url')->default('https://learnku.com/');
                        });
                    $form->confirm('您确定要提交表单吗');
                    # 设置了相应的中间件，所以不添加这一行也可以
                    # $form->hidden('_token')->default(csrf_token())
                    $form->width(9, 2);
                    $column->append(Box::make(trans('admin.new'), $form));
                });
            });
    }

    /**
     * @return \Dcat\Admin\Tree
     */
    protected function treeView()
    {
        # 先触发Tree类的构造方法
        return new Tree(new MenuModel(), function (Tree $tree) {
            # 禁用三个按钮
            $tree->disableCreateButton();
            $tree->disableQuickCreateButton();
            $tree->disableEditButton();
            # $branch是怎么来的呢？？
            # branch.blade.php文件里调用相应回调函数，并传递的
            $tree->branch(function ($branch) {
                $payload = '';
                if (! isset($branch['children'])) {
                    if (url()->isValidUrl($branch['url'])) {
                        $url = $branch['url'];
                    } else {
                        $url = admin_base_path($branch['url']);
                    }
                    $name = $branch['name'];
                    $payload .= "&nbsp;&nbsp;&nbsp;<a href=\"$url\" class=\"dd-nodrag\">$name</a>";
                } else {
                    if (url()->isValidUrl($branch['url'])) {
                        $url = $branch['url'];
                    } else {
                        $url = admin_base_path($branch['url']);
                    }
                    $name = $branch['name'];
                    $payload .= "&nbsp;&nbsp;&nbsp;<a href=\"$url\" class=\"dd-nodrag\">$name</a>";
                }
                return $payload;
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $menuModel = config('admin.database.menu_model');

        $relations = $menuModel::withPermission() ? ['permissions', 'roles'] : 'roles';

        return Form::make(new Menu($relations), function (Form $form) use ($menuModel) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });

            $form->display('id', 'ID');

            $form->select('parent_id', trans('admin.parent_id'))->options(function () use ($menuModel) {
                return $menuModel::selectOptions();
            })->saving(function ($v) {
                return (int) $v;
            });
            $form->text('name', '菜单标题')->required();
            //$form->icon('icon', trans('admin.icon'))->help($this->iconHelp());
            $form->text('uri', trans('admin.uri'));

            if ($menuModel::withRole()) {
                $form->multipleSelect('roles', trans('admin.roles'))
                    ->options(function () {
                        $roleModel = config('admin.database.roles_model');

                        return $roleModel::all()->pluck('name', 'id');
                    })
                    ->customFormat(function ($v) {
                        return array_column($v, 'id');
                    });
            }
            if ($menuModel::withPermission()) {
                $form->tree('permissions', trans('admin.permission'))
                    ->nodes(function () {
                        $permissionModel = config('admin.database.permissions_model');

                        return (new $permissionModel())->allNodes();
                    })
                    ->customFormat(function ($v) {
                        if (! $v) {
                            return [];
                        }

                        return array_column($v, 'id');
                    });
            }

            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));
        })->saved(function (Form $form, $result) {
            if ($result) {
                return $form->location('auth/menu', __('admin.save_succeeded'));
            }

            return $form->location('auth/menu', [
                'message' => __('admin.nothing_updated'),
                'status'  => false,
            ]);
        });
    }

    public function insertToDatabase(Request $request, Form $form)
    {
        $input = [];
        $input['parent_id'] = $request->parent_id ?? null;
        $input['name'] = $request->name ?? null;
        $input['key'] = $request->key ?? null;
        $input['type'] = $request->type ?? null;
        $input['url'] = $request->url ?? null;
        $res = MenuModel::create($input);
        if ($res) {
            return $form->success('Processed successfully.', '/test');
        }
    }

    public function createWechatMenu()
    {
        // 遍历菜单数据表中的所有数据
        // 记录一下处理过的数据的id
        // 生成规定格式的数组
        $ids = [];
        $res = [];
        $res['menu'] = [];
        $res['menu']['button'] = [];
        foreach(MenuModel::all() as $menu) {
            if (!in_array($menu->id, $ids)) {
                $res['menu']['button'][] = $menu->getAttributes();
                $id = count($res['menu']['button']) - 1;
                $ids[] = $menu->id;
                foreach($menu->menus as $subMenu) {
                    $res['menu']['button'][$id]['sub_button'][] = $subMenu->getAttributes();
                    $ids[] = $subMenu->id;
                }
            }
        }
        $this->app->menu->create($res['menu']['button']);
        //dd($res['menu']['button']);
    }
}
