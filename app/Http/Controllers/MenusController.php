<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EasyWeChat\Factory;

class MenusController extends Controller
{
    protected $config = [
        'app_id' => 'wx49f960539fb6da0c',
        'secret' => 'da7c7ae0a568e41e81322d0eef0b7bf4',
        'response_type' => 'array',
    ];

    protected $app;

    public function __construct()
    {
        $this->app = Factory::officialAccount($this->config);
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

    public function insert($index, array $new) : bool
    {
        $this->app = Factory::officialAccount($this->config);
        $list = $this->app->menu->list();
        $temp = $list['menu']['button'][$index]['sub_button'];
        $temp[] = $new;
        $list['menu']['button'][$index]['sub_button'] = $temp;
        //dd($list['menu']['button']);
        //dd($list['menu']['button'][$index]['sub_button']);
        $this->app->menu->create($list['menu']['button']);
        // $origin = $this->app->menu->list()['menu']['button'][$index]['sub_button'];
        // $origin[] = $new;
        //dd($new);
        //$this->app->menu->list()['menu']['button'][$index]['sub_button'][] = $new;
        // $this->app->menu->list()['menu']['button'][$index]['sub_button'] = array_merge($this->app->menu->list()['menu']['button'][$index]['sub_button'], $new);
        //$this->app->menu->list()['menu']['button'][$index]['sub_button'][] = $new;
        //dd($this->app->menu->list()['menu']['button'][$index]['sub_button']);
        //$this->app->menu->create($this->app->menu->list()['menu']['button']);
        return true;
    }

    public function createMenu($parent, $new)
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [];
        $input['parent'] = '今日歌曲';
        $input['type'] = 'view';
        $input['name'] = 'alice';
        $input['key'] = 'admin';
        $input['url'] = 'https://unbug.github.io/codelf/';
        $parentName = $input['parent'];
        $new = [];
        $new['type'] = $input['type'];
        $new['name'] = $input['name'];
        $new['key'] = $input['key'];
        $new['url'] = $input['url'];
        $res = $this->createMenu($parentName, $new);
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
