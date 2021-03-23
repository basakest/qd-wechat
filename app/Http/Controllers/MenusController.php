<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EasyWeChat\Factory;
use Exception;
use App\Models\Menu;

class MenusController extends Controller
{
    protected $app;

    /**
     * 公众号菜单的对应类型
     *
     * @var array
     */
    protected $menuTypes = [
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

    /**
     * 获取所有的父级标题的名字
     *
     * @return array
     */
    public function getNameOfParentMenus() : array
    {
        //dd($this->app->menu->list());
        $menus = $this->app->menu->list();
        if (!isset($menus['menu'])) {
            return [];
        } else {
            $result = [];
            foreach($menus['menu']['button'] as $k => $v) {
                $result[$k] = $v['name'];
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
     * 查看指定一级标题下的二级标题数量是否超过上限或查看该公众号下的一级标题数量是否超过上限
     *
     * @param integer $index
     * @return void
     */
    public function checkNum(int $index = -1) : bool
    {
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
        $list = $this->app->menu->list();
        $temp = $list['menu']['button'][$index]['sub_button'];
        $temp[] = $new;
        $list['menu']['button'][$index]['sub_button'] = $temp;
        $this->app->menu->create($list['menu']['button']);
        return true;
    }

    public function createParentMenu(array $new): bool
    {
        //dd($new);
        $list = $this->app->menu->list();
        if (isset($list['menu'])) {
            $temp = $list['menu']['button'];
            $temp[] = $new;
            $list['menu']['button'] = $temp;
        } else {
            $list['menu']['button'] = [];
            $list['menu']['button'][] = $new;
        }
        //dd($list);
        try {
            $this->app->menu->create($list['menu']['button']);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return true;
    }

    public function createMenu($parent, $new)
    {
        //dd($this->getNameOfParentMenus());
        $parentTitles = $this->getNameOfParentMenus();
        if ($parent == 'root') {
            // 这种情况下创建一级标题
            // 先判断该一级标题是否存在
            $index = $this->determineWhetherTiTleExists($new['name'], $parentTitles);
            //dd($index);
            if ($index === null) {
                // 该一级标题不存在，查看该公众号下的一级标题数量是否超过上限
                if ($this->checkNum()) {
                    // 满足条件的话创建一级标题
                    //dd($new);
                    $this->createParentMenu($new);
                }
            }
        } else {
            $index = $this->determineWhetherTiTleExists($parent, $parentTitles);
            //dd($index);
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
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //dd(Menu::whereNull('parent_id')->get());
        dd($this->app->menu->list());
        $input = [];
        $input['parent'] = 'nrd';
        $input['type'] = 'view';
        $input['name'] = 'pan';
        $input['key'] = '';
        $input['url'] = 'https://learnku.com/';
        //$input['url'] = 'https://learnku.com/';
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
        //dd($new);
        /*
        $input['type'] ?? $new['type'] = $input['type'];
        $input['name'] ?? $new['name'] = $input['name'];
        $input['key'] ?? $new['key'] = $input['key'];
        $input['url'] ?? $new['url'] = $input['url'];
        */
        //dd($input, $new);
        $res = $this->createMenu($parentName, $new);
        return $res;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $this->app->menu->delete();
        Menu::query()->delete();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }
}
