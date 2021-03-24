<?php

//namespace Dcat\Admin\Http\Controllers;
namespace App\Admin\Controllers;

use App\Admin\Forms\Menu as FormsMenu;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
//use Dcat\Admin\Models\Repositories\Menu;
use Dcat\Admin\Tree;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Form as WidgetForm;
use App\Models\Menu as MenuModel;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;
use Illuminate\Http\Request;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Redirect;
use Dcat\Admin\Http\Repositories\Menu;

class TestController extends AdminController
{
    protected $app;

    protected $menuTypes =
    [
        'view' => '打开网址',
        'click' => '响应文本',
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

    protected $description = [
        'index'  => 'Index',
        'show'   => 'Show',
        'edit'   => 'Edit',
        'create' => 'Create',
    ];

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
                    $form->allowAjaxSubmit(true);
                    $model = new MenuModel();
                    $form->select('parent_id', '父级')->options($model::selectOptions())->required();
                    $form->text('name', '菜单标题')->required()->placeholder('输入 菜单标题');
                    $form->text('key', '菜单 Key')->placeholder('输入 菜单key');
                    $form->radio('type', '菜单类型')->options($this->menuTypes)
                        ->when('view', function() use ($form) {
                            $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url')->default('https://learnku.com/');
                        })
                        ->when('click', function() use ($form) {
                        });
                    $form->confirm('您确定要提交表单吗');
                    # 设置了相应的中间件，所以不添加这一行也可以
                    $form->hidden('_token')->default(csrf_token());
                    $form->width(9, 2);
                    $column->append(Box::make(trans('admin.new'), $form));
                });
            });
    }

    public function editForm(MenuModel $menu, Content $content)
    {
        return $content
            ->title($menu->name)
            ->description('编辑')
            ->body(function (Row $row) use ($menu) {
            //->body(function (Column $column) use ($menu) {
                $row->column(12, function (Column $column) use ($menu) {
                //$column->row(function (Row $row) use ($menu) {
                    # 创建表单实例
                    $form = new WidgetForm();
                    # 设置表单提交的action
                    $form->action(admin_url("test/$menu->id/save"));
                    $form->allowAjaxSubmit(true);
                    $form->select('parent_id', '父级')->options($menu::selectOptions())->required()->default($menu->parent_id);
                    $form->text('name', '菜单标题')->required()->placeholder('输入 菜单标题')->value($menu->name);
                    $form->text('key', '菜单 Key')->placeholder('输入 菜单key')->value($menu->key);
                    $form->radio('type', '菜单类型')->options($this->menuTypes)
                        ->when('view', function () use ($form, $menu) {
                            $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url')->default($menu->url);
                        })
                        ->when('click', function () use ($form) {
                        })
                        ->default($menu->type);
                    $form->confirm('您确定要提交表单吗');
                    # 设置了相应的中间件，所以不添加这一行也可以
                    // $form->hidden('_token')->default(csrf_token());
                    $form->allowAjaxSubmit(true);
                    $form->width(9, 2);
                    $column->append(Box::make(trans('admin.new'), $form));
                });
            });
    }

    public function saveForm($menu, Request $request, Form $form)
    {
        $new = [];
        $new['parent_id'] = $request->parent_id ?? null;
        $new['name'] = $request->name ?? null;
        $new['key'] = $request->key ?? null;
        $new['type'] = $request->type ?? null;
        $new['url'] = $request->url ?? null;
        $res = MenuModel::find($menu)->update($new);
        if ($res) {
            return $form->response()->success('修改成功', 'http://wechat.test/admin/test/');
        } else {
            return $form->response()->error('修改失败', 'http://wechat.test/admin/test/');
        }
    }

    public function deleteMenu($menu)
    {
        MenuModel::destroy($menu);
        redirect(admin_url('/test'));
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
        $menuModel = MenuModel::class;
        return Form::make(new MenuModel(), function (Form $form) use ($menuModel) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });

            $form->select('parent_id', trans('admin.parent_id'))->options(function () use ($menuModel) {
                return $menuModel::selectOptions();
            })->saving(function ($v) {
                return (int) $v;
            });
            $form->text('name', '菜单标题')->required();
            $form->radio('type', '菜单类型')->options($this->menuTypes)
                 ->when('view', function () use ($form) {
                     $form->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
                });
            $form->allowAjaxSubmit(true);
        });
    }

    public function insertToDatabase(Request $request, Form $form)
    {
        $input = [];
        $input['parent_id'] = $request->parent_id ?? null;
        if (is_numeric($input['parent_id']) && $input['parent_id'] == 0) {
            $num = MenuModel::where('parent_id', 0)->get()->count();
            if ($num >= 3) {
                return $form->response()->error('微信最多支持 3 个一级菜单！');
            }
        } else {
            $num = MenuModel::where('parent_id', $input['parent_id'])->get()->count();
            if ($num >= 5) {
                return $form->response()->error('每个一级菜单下最多包含 5 个二级菜单！');
            }
        }
        // 微信公众号只支持一级标题和二级标题，所以收到的parent_id只能为0
        // 或者一级标题(parent_id==0)的id，如果不匹配，就提示相应的信息
        $idsOfH1 = MenuModel::where('parent_id', 0)->get()->pluck('id')->toArray();
        array_unshift($idsOfH1, 0);
        if (!in_array($input['parent_id'], $idsOfH1)) {
            return $form->error('微信公众号暂时只支持一级菜单和二级菜单');
        }
        $input['name'] = $request->name ?? null;
        $input['key'] = $request->key ?? null;
        $input['type'] = $request->type ?? null;
        $input['url'] = $request->url ?? null;
        $res = MenuModel::create($input);
        if ($res) {
            return $form->response()->success('Processed successfully.', '/test');
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
        if (empty($res['menu']['button'])) {
            $this->app->menu->delete();
        } else {
            $this->app->menu->create($res['menu']['button']);
        }
    }
}
