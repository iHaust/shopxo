<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2018 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\service;

use think\Db;
use app\service\ResourcesService;

/**
 * 应用管理服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PluginsAdminService
{
    /**
     * 列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function PluginsList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::name('Plugins')->where($where)->limit($m, $n)->order($order_by)->select();
        
        return DataReturn('处理成功', 0, self::PluginsDataHandle($data));
    }

    /**
     * 数据处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $data [数据]
     */
    private static function PluginsDataHandle($data)
    {
        $result = [];
        if(!empty($data))
        {
            foreach($data as $v)
            {
                $config = self::GetPluginsConfig($v['plugins']);
                if($config !== false)
                {
                    $base = $config['base'];
                    $result[] = [
                        'id'            => $v['id'],
                        'plugins'       => $v['plugins'],
                        'is_enable'     => $v['is_enable'],
                        'logo_old'      => $base['logo'],
                        'logo'          => ResourcesService::AttachmentPathViewHandle($base['logo']),
                        'is_home'       => isset($base['is_home']) ? $base['is_home'] : false,
                        'name'          => isset($base['name']) ? $base['name'] : '',
                        'author'        => isset($base['author']) ? $base['author'] : '',
                        'author_url'    => isset($base['author_url']) ? $base['author_url'] : '',
                        'version'       => isset($base['version']) ? $base['version'] : '',
                        'desc'          => isset($base['desc']) ? $base['desc'] : '',
                        'apply_version' => isset($base['apply_version']) ? $base['apply_version'] : [],
                        'apply_terminal'=> isset($base['apply_terminal']) ? $base['apply_terminal'] : [],
                        'add_time_time' => date('Y-m-d H:i:s', $v['add_time']),
                        'add_time_date' => date('Y-m-d', $v['add_time']),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * 总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function PluginsTotal($where = [])
    {
        return (int) Db::name('Plugins')->where($where)->count();
    }

    /**
     * 列表条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function PluginsListWhere($params = [])
    {
        $where = [];
        return $where;
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function PluginsStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => '状态有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据更新
        if(Db::name('Plugins')->where(['id'=>$params['id']])->update(['is_enable'=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('操作成功');
        }
        return DataReturn('操作失败', -100);
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function PluginsDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        
        // 获取应用标记
        $where = ['id'=>intval($params['id'])];
        $plugins = Db::name('Plugins')->where($where)->value('plugins');
        if(empty($plugins))
        {
           return DataReturn('应用不存在', -10); 
        }

        // 删除操作
        if(Db::name('Plugins')->where($where)->delete())
        {
            // 删除应用文件
            self::PluginsResourcesDelete($plugins);

            return DataReturn('删除成功');
        }

        return DataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 应用资源删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-02-13
     * @desc    description
     * @param   [string]          $plugins [唯一标记]
     */
    private static function PluginsResourcesDelete($plugins)
    {
        \base\FileUtil::UnlinkDir(APP_PATH.'plugins'.DS.$plugins);
        \base\FileUtil::UnlinkDir(APP_PATH.'plugins'.DS.'view'.DS.$plugins);
        \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'static'.DS.'plugins'.DS.'css'.DS.$plugins);
        \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'static'.DS.'plugins'.DS.'js'.DS.$plugins);
        \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'static'.DS.'plugins'.DS.'images'.DS.$plugins);
        \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'static'.DS.'upload'.DS.'images'.DS.'plugins_'.$plugins);
        \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'static'.DS.'upload'.DS.'video'.DS.'plugins_'.$plugins);
        \base\FileUtil::UnlinkDir(ROOT.'public'.DS.'static'.DS.'upload'.DS.'file'.DS.'plugins_'.$plugins);
    }

    /**
     * 获取应用配置信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-17
     * @desc    description
     * @param   [string]          $plugins [应用名称]
     */
    private static function GetPluginsConfig($plugins)
    {
        $config_file = APP_PATH.'plugins'.DS.$plugins.DS.'config.json';
        if(file_exists($config_file))
        {
            return json_decode(file_get_contents($config_file), true);
        }
        return false;
    }

    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function PluginsSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'plugins',
                'error_msg'         => '应用唯一标记不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'logo',
                'error_msg'         => '请上传LOGO',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'name',
                'error_msg'         => '应用名称不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'author',
                'error_msg'         => '作者不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'author_url',
                'error_msg'         => '作者主页不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'version',
                'error_msg'         => '版本号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'desc',
                'error_msg'         => '描述不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'apply_terminal',
                'error_msg'         => '请至少选择一个适用终端',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'apply_version',
                'error_msg'         => '请至少选择一个适用系统版本',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 应用唯一标记
        $plugins = trim($params['plugins']);

        // 权限校验
        $ret = self::PowerCheck($plugins);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 应用不存在则添加
        $ret = self::PluginsExistInsert($params, $plugins);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 应用目录不存在则创建
        $app_dir = APP_PATH.'plugins'.DS.$plugins;
        if(\base\FileUtil::CreateDir($app_dir) !== true)
        {
            return DataReturn('应用主目录创建失败', -10);
        }

        // 生成配置文件
        $ret = self::PluginsConfigCreated($params, $app_dir);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 应用主文件生成
        $ret = self::PluginsApplicationCreated($params, $app_dir);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        return DataReturn(empty($params['id']) ? '创建成功' : '更新成功', 0);
    }

    /**
     * 应用文件生成
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params    [输入参数]
     * @param   [string]         $app_dir   [主目录地址]
     */
    private static function PluginsApplicationCreated($params, $app_dir)
    {
        $plugins = trim($params['plugins']);
$admin=<<<php
<?php
namespace app\plugins\\$plugins;

/**
 * {$params['name']} - 后台管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin
{
    // 后台管理入口
    public function index(\$params = [])
    {
        // 数组组装
        \$data = [
            'data'  => ['hello', 'world!'],
            'msg'   => 'hello world! admin',
        ];
        return DataReturn('处理成功', 0, \$data);
    }
}
?>
php;

$hook=<<<php
<?php
namespace app\plugins\\$plugins;

/**
 * {$params['name']} - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 应用响应入口
    public function run(\$params = [])
    {
        // 是否控制器钩子
        if(isset(\$params['is_control']) && \$params['is_control'] === true)
        {
            return [];

        // 默认返回视图
        } else {
            return 'hello world!';
        }
    }
}
?>
php;

$index=<<<php
<?php
namespace app\plugins\\$plugins;

/**
 * {$params['name']} - 前端独立页面入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index
{
    // 前端页面入口
    public function index(\$params = [])
    {
        // 数组组装
        \$data = [
            'data'  => ['hello', 'world!'],
            'msg'   => 'hello world! index',
        ];
        return DataReturn('处理成功', 0, \$data);
    }
}
?>
php;

$admin_view=<<<php
{{include file="public/header" /}}

<!-- right content start  -->
<div class="content-right">
    <div class="content">
        <h1>后台管理页面</h1>
        {{:print_r(\$data)}}
        <p class="msg">{{\$msg}}</p>
    </div>
</div>
<!-- right content end  -->
        
<!-- footer start -->
{{include file="public/footer" /}}
<!-- footer end -->
php;

$index_view=<<<php
{{include file="public/header" /}}

<!-- nav start -->
{{include file="public/nav" /}}
<!-- nav end -->

<!-- header top nav -->
{{include file="public/header_top_nav" /}}

<!-- search -->
{{include file="public/nav_search" /}}

<!-- header nav -->
{{include file="public/header_nav" /}}

<!-- goods category -->
{{include file="public/goods_category" /}}

<!-- content start -->
<div class="am-g my-content">
    <div class="am-u-md-6 am-u-sm-centered">
        <h1>前端页面</h1>
        {{:print_r(\$data)}}
        <p class="msg">{{\$msg}}</p>
    </div>
</div>
<!-- content end -->

<!-- footer start -->
{{include file="public/footer" /}}
<!-- footer end -->
php;

$admin_css=<<<php
h1 {
    font-size: 60px;
}
.msg {
    font-size: 38px;
    color: #F00;
}
php;

$index_css=<<<php
h1 {
    font-size: 60px;
}
.msg {
    font-size: 68px;
    color: #4CAF50;
}
php;
        // 创建文件
        if(@file_put_contents($app_dir.DS.'Admin.php', $admin) === false)
        {
            return DataReturn('应用文件创建失败[admin]', -11);
        }
        if(@file_put_contents($app_dir.DS.'Hook.php', $hook) === false)
        {
            return DataReturn('应用文件创建失败[hook]', -11);
        }
        if(@file_put_contents($app_dir.DS.'Hook.php', $hook) === false)
        {
            return DataReturn('应用文件创建失败[admin-view]', -11);
        }

        // 应用后台视图目录不存在则创建
        $app_view_admin_dir = APP_PATH.'plugins'.DS.'view'.DS.trim($params['plugins']).DS.'admin';
        if(\base\FileUtil::CreateDir($app_view_admin_dir) !== true)
        {
            return DataReturn('应用视图目录创建失败[admin]', -10);
        }
        if(@file_put_contents($app_view_admin_dir.DS.'index.html', $admin_view) === false)
        {
            return DataReturn('应用视图文件创建失败[admin-view]', -11);
        }

        // css创建
        $app_static_css_dir = ROOT.'public'.DS.'static'.DS.'plugins'.DS.'css'.DS.trim($params['plugins']);
        if(\base\FileUtil::CreateDir($app_static_css_dir) !== true)
        {
            return DataReturn('应用静态目录创建失败[css]', -10);
        }
        if(@file_put_contents($app_static_css_dir.DS.'admin.css', $admin_css) === false)
        {
            return DataReturn('应用静态文件创建失败[admin-css]', -11);
        }


        // 是否有前端页面
        if(isset($params['is_home']) && $params['is_home'] == 1)
        {
            // 创建文件
            if(@file_put_contents($app_dir.DS.'Index.php', $index) === false)
            {
                return DataReturn('应用文件创建失败[index]', -11);
            }

            // 应用前端视图目录不存在则创建
            $app_view_index_dir = APP_PATH.'plugins'.DS.'view'.DS.trim($params['plugins']).DS.'index';
            if(\base\FileUtil::CreateDir($app_view_index_dir) !== true)
            {
                return DataReturn('应用视图目录创建失败[index]', -10);
            }
            if(@file_put_contents($app_view_index_dir.DS.'index.html', $index_view) === false)
            {
                return DataReturn('应用视图文件创建失败[index-view]', -11);
            }

            // css创建
            if(@file_put_contents($app_static_css_dir.DS.'index.css', $index_css) === false)
            {
                return DataReturn('应用静态文件创建失败[index-css]', -11);
            }

        // 没有独立前端页面则删除文件
        } else {
            \base\FileUtil::UnlinkFile($app_dir.DS.'Index.php');
            \base\FileUtil::UnlinkDir(APP_PATH.'plugins'.DS.'view'.DS.trim($params['plugins']).DS.'index');
            \base\FileUtil::UnlinkFile($app_static_css_dir.DS.'index.css');
        }

        return DataReturn('创建成功', 0);
    }

    /**
     * 应用配置文件生成
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params    [输入参数]
     * @param   [string]         $app_dir   [主目录地址]
     */
    private static function PluginsConfigCreated($params, $app_dir)
    {
        $data = [
            // 基础信息
            'base'  => [
                'plugins'           => trim($params['plugins']),
                'name'              => $params['name'],
                'logo'              => $params['logo'],
                'author'            => $params['author'],
                'author_url'        => $params['author_url'],
                'version'           => $params['version'],
                'desc'              => $params['desc'],
                'apply_terminal'    => explode(',', $params['apply_terminal']),
                'apply_version'     => explode(',', $params['apply_version']),
                'is_home'           => (isset($params['is_home']) && $params['is_home'] == 1) ? true : false,
            ],

            // 钩子配置
            'hook'  => [],
        ];

        // 创建配置文件
        if(@file_put_contents($app_dir.DS.'config.json', JsonFormat($data)) === false)
        {
            return DataReturn('应用配置文件创建失败', -10);
        }

        return DataReturn('创建成功', 0);
    }

    /**
     * 应用添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]           $params    [输入参数]
     * @param   [string]          $plugins   [应用唯一标记]
     */
    private static function PluginsExistInsert($params, $plugins)
    {
        $temp_plugins = Db::name('Plugins')->where(['plugins'=>$plugins])->value('plugins');
        if(empty($temp_plugins))
        {
           if(Db::name('Plugins')->insertGetId(['plugins'=>$plugins, 'add_time'=>time()]) <= 0)
            {
                return DataReturn('应用添加失败', -1);
            } 
        } else {
            if(empty($params['id']) && $temp_plugins == $plugins)
            {
                return DataReturn('应用名称已存在['.$plugins.']', -1);
            }
        }
        return DataReturn('添加成功', 0);
    }

    /**
     * 权限校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2018-09-29T00:01:49+0800
     * @param   [string]          $plugins [应用唯一标记]
     */
    private static function PowerCheck($plugins)
    {
        // 应用目录
        $app_dir = APP_PATH.'plugins';
        if(!is_writable($app_dir))
        {
            return DataReturn('应用目录没有操作权限'.'['.$app_dir.']', -3);
        }

        // 应用视图目录
        $app_view_dir = APP_PATH.'plugins'.DS.'view';
        if(!is_writable($app_view_dir))
        {
            return DataReturn('应用视图目录没有操作权限'.'['.$app_view_dir.']', -3);
        }

        // 应用css目录
        $app_static_css_dir = ROOT.'public'.DS.'static'.DS.'plugins'.DS.'css';
        if(!is_writable($app_static_css_dir))
        {
            return DataReturn('应用css目录没有操作权限'.'['.$app_static_css_dir.']', -3);
        }

        // 应用js目录
        $app_static_js_dir = ROOT.'public'.DS.'static'.DS.'plugins'.DS.'js';
        if(!is_writable($app_static_js_dir))
        {
            return DataReturn('应用js目录没有操作权限'.'['.$app_static_js_dir.']', -3);
        }

        // 应用images目录
        $app_static_images_dir = ROOT.'public'.DS.'static'.DS.'plugins'.DS.'images';
        if(!is_writable($app_static_images_dir))
        {
            return DataReturn('应用images目录没有操作权限'.'['.$app_static_images_dir.']', -3);
        }
        return DataReturn('权限正常', 0);
    }
}
?>