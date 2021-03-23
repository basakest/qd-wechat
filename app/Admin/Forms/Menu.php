<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use Symfony\Component\HttpFoundation\Response;
use EasyWeChat\Factory;
use Exception;
use App\Models\Menu as MenuModel;
use Dcat\Admin\Widgets\Tab;

class Menu extends Form
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
    public function getApp()
    {
        if (!($this->app instanceof \EasyWeChat\OfficialAccount\Application)) {
            $this->app = Factory::officialAccount([
                'app_id' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'response_type' => env('WECHAT_RESPONSE_TYPE', 'array')
            ]);
        }
    }

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
        if ($input['type']) {
            $new['type'] = $input['type'];
        }
        if ($input['name']) {
            $new['name'] = $input['name'];
        }
        if ($input['key']) {
            $new['key'] = $input['key'];
        }
        if ($input['url']) {
            $new['url'] = $input['url'];
        }
        if (!isset($new['type'])) {
            $new['sub_button'] = [];
        }
        $res = $this->createMenu($parentName, $new);
        if ($res) {
            return $this->success('Processed successfully.', '/');
        }
        // return $this->error('Your error message.');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->select('parent', '父级')->options($this->getNameOfParentMenus(true))->required();
        $this->text('name', '菜单标题')->placeholder('输入 菜单标题')->required();
        $this->text('key', '菜单 Key')->placeholder('输入 菜单 Key');
        $this->radio('type', '菜单类型')->options($this->menuTypes)
            ->when('view', function() {
                $this->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->when('text', function() {
                $this->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->when('image', function() {
                $this->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            })
            ->when('miniprogram', function() {
                $this->url('url', '需要跳转的 url')->placeholder('输入 需要跳转的 url');
            });
        $this->confirm('您确定要提交表单吗');
    }

        /**
     * 获取所有的父级标题的名字
     *
     * @return array
     */
    public function getNameOfParentMenus($flag = null) : array
    {
        $this->getApp();
        $menus = $this->app->menu->list();
        if (!isset($menus['menu'])) {
            if ($flag) {
                return ['root' => 'root'];
            } else {
                return ['root' => '-1'];
            }
        } else {
            $result = [];
            foreach($menus['menu']['button'] as $k => $v) {
                if ($flag) {
                    $result[$v['name']] = $v['name'];
                } else {
                    $result[$k] = $v['name'];
                }
                // 要反过来才可以
                // $result[$v['name']] = $k;
            }
            //$result[-1] = 'root';
            //$result['root'] = '-1';
            if ($flag) {
                $result['root'] = 'root';
            } else {
                $result['root'] = -1;
            }
            return $result;
        }
    }

    /**
     * 判断指定的一级标题是否存在
     *
     * @param string $title
     * @param array $h1Titles
     * @return void
     */
    public function determineWhetherTiTleExists(string $title, array $h1Titles)
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

    /**
     * 查看指定一级标题下的二级标题数量是否超过上限
     * 或查看该公众号下的一级标题数量是否超过上限
     *
     * @param integer $index
     * @return void
     */
    public function checkNum(int $index = -1) : bool
    {
        $this->getApp();
        if ($index == -1) {
            if (isset($this->app->menu->list()['menu'])) {
                $num = count($this->app->menu->list()['menu']['button']);
                if ($num >= 3) {
                    echo '该公众号下的一级标题数量超过上限，无法创建';
                    return false;
                }
            }
            return true;
        } else {
            $num = count($this->app->menu->list()['menu']['button'][$index]['sub_button']);
            if ($num >= 5) {
                echo '该一级标题下的二级标题超过数量上限，无法创建';
                return false;
            }
            return true;
        }
    }

    /**
     * 将指定二级标题的信息插入到标题数组中，并执行创建操作
     *
     * @param integer $index
     * @param array $new
     * @return boolean
     */
    public function insert(int $index, array $new) : bool
    {
        $this->getApp();
        $list = $this->app->menu->list();
        $temp = $list['menu']['button'][$index]['sub_button'];
        $temp[] = $new;
        $list['menu']['button'][$index]['sub_button'] = $temp;
        $this->app->menu->create($list['menu']['button']);
        return true;
    }

    public function createParentMenu(array $new): bool
    {
        $this->getApp();
        $list = $this->app->menu->list();
        if (isset($list['menu'])) {
            $temp = $list['menu']['button'];
            $temp[] = $new;
            $list['menu']['button'] = $temp;
        } else {
            $list['menu']['button'] = [];
            $list['menu']['button'][] = $new;
        }
        try {
            $this->app->menu->create($list['menu']['button']);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return true;
    }

    public function createMenu($parent, $new)
    {
        $parentTitles = $this->getNameOfParentMenus();
        if ($parent == 'root') {
            // 这种情况下创建一级标题
            // 先判断该一级标题是否存在
            $index = $this->determineWhetherTiTleExists($new['name'], $parentTitles);
            if ($index === null) {
                // 该一级标题不存在，查看该公众号下的一级标题数量是否超过上限
                if ($this->checkNum()) {
                    // 满足条件的话创建一级标题
                    $this->createParentMenu($new);
                    MenuModel::create($new);
                }
            }
        } else {
            $index = $this->determineWhetherTiTleExists($parent, $parentTitles);
            if ($index !== null) {
                // 执行到这里说明该父级标题存在
                // 接下来判断该父级标题下的二级标题数量是否超过上限
                if ($this->checkNum($index)) {
                    // 执行到这里说明该父级标题下的二级标题数量没有超过上限，可以执行创建操作
                    $this->insert($index, $new);
                    $new['parent_id'] = $index;
                    MenuModel::create($new);
                }
            }
        }
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'type' => 'view',
            'url' => 'https://learnku.com/',
        ];
    }
}
