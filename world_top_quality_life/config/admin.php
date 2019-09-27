<?php

return [

    /*
     * Laravel-admin name.
     */
    'name' => '寰球优品生活',

    /*
     * Logo in admin panel header.
     */
    'logo' => '<b>寰球优品生活</b>',

    /*
     * Mini-logo in admin panel header.
     */
    'logo-mini' => '<b>寰球</b>',

    /*
     * Route configuration.
     */
    'route' => [

        'prefix' => 'admin',

        'namespace' => 'App\\Admin\\Controllers',

        'middleware' => ['web', 'admin'],
    ],

    /*
     * Laravel-admin install directory.
     */
    'directory' => app_path('Admin'),

    /*
     * Laravel-admin html title.
     */
    'title' => '寰球优品生活',

    /*
     * Use `https`.
     */
    'secure' => false,

    /*
     * Laravel-admin auth setting.
     */
    'auth' => [
        'guards' => [
            'admin' => [
                'driver'   => 'session',
                'provider' => 'admin',
            ],
        ],

        'providers' => [
            'admin' => [
                'driver' => 'eloquent',
                'model'  => Encore\Admin\Auth\Database\Administrator::class,
            ],
        ],
    ],

    /*
     * Laravel-admin upload setting.
     */
    'upload' => [

        'disk' => 'admin',

        'directory' => [
            'image' => 'images',
            'file'  => 'files',
        ],
    ],

    /*
     * Laravel-admin database setting.
     */
    'database' => [

        // Database connection for following tables.
        'connection' => '',

        // User tables and model.
        'users_table' => 'admin_users',
        'users_model' => Encore\Admin\Auth\Database\Administrator::class,

        // Role table and model.
        'roles_table' => 'admin_roles',
        'roles_model' => Encore\Admin\Auth\Database\Role::class,

        // Permission table and model.
        'permissions_table' => 'admin_permissions',
        'permissions_model' => Encore\Admin\Auth\Database\Permission::class,

        // Menu table and model.
        'menu_table' => 'admin_menu',
        'menu_model' => Encore\Admin\Auth\Database\Menu::class,

        // Pivot table for table above.
        'operation_log_table'    => 'admin_operation_log',
        'user_permissions_table' => 'admin_user_permissions',
        'role_users_table'       => 'admin_role_users',
        'role_permissions_table' => 'admin_role_permissions',
        'role_menu_table'        => 'admin_role_menu',
    ],

    /*
     * By setting this option to open or close operation log in laravel-admin.
     */
    'operation_log' => [

        'enable' => true,
        'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'],
        /*
         * Routes that will not log to database.
         *
         * All method to path like: admin/auth/logs
         * or specific method to path like: get:admin/auth/logs
         */
        'except' => [
            'admin/auth/logs*',
        ],
    ],

    /*
     * @see https://adminlte.io/docs/2.4/layout
     */
    'skin' => 'skin-blue-light',

    /*
    |---------------------------------------------------------|
    |LAYOUT OPTIONS | fixed                                   |
    |               | layout-boxed                            |
    |               | layout-top-nav                          |
    |               | sidebar-collapse                        |
    |               | sidebar-mini                            |
    |---------------------------------------------------------|
     */
    'layout' => ['sidebar-mini'],

    /*
     * Version displayed in footer.
     */
    //'version' => '1.5.x-dev',

    /*
     * Settings for extensions.
     */
    'extensions' => [

    ],

    //订单状态
    'order_status' => [
        0 => '待付款',
        1 => '待填地址',
        2 => '待发货',
        3 => '已发货',
        4 => '已完成',
        5 => '部分发货',
        9 => '用户取消订单',
        10 => '全部'
    ],

    //订单状态（update by 2018-07-12）
    //a.支付状态分为：未支付和已支付
    //b.发货状态分为：待填地址、待发货、已发货
    'order_status_new' => [
        10 => '全部',
        0 => '待填地址',
        5 => '已填地址',
        1 => '待发货',
        2 => '部分发货',
        3 => '已发货',
        4 => '已完成',
        9 => '用户取消订单'
    ],

    'pay_status_new' => [
        10 => '全部',
        0 => '待付款',
        1 => '已支付',
    ],



    //会员等级
    'user_class' => [
        0 => '未开通',
        1 => 'Lv.1',
        2 => 'Lv.2',
        3 => 'Lv.3',
        4 => 'Lv.4',
        5 => 'Lv.5',
    ],
    //会员等级 ===> 优惠比例 --废弃20180922
    'class_price' => [
        0 => 1,
        1 => 0.95,
        2 => 0.9,
        3 => 0.85,
        4 => 0.8,
        5 => 0.75,
    ],

    //会员等级 ====> 返点
    'class_in_price'=>[
        0 => 3,
        1 => 5,
        2 => 5,
        3 => 7,
        4 => 7,
        5 => 10,
    ],

    //客户商城等级
    'market_class' => [
        0 => 'd',
        1 => 'c',
        2 => 'b',
        3 => 'a',
        4 => 's',
    ],

    'from_area' => [
        1 => '大阪',
        2 => '东京',
    ],

    //用户消费日志
    'price_log_type' => [
        0 => '客服充值',
        1 => '在线支付',
        2 => '客服扣款',
        3 => '返现',
        4 => '商城返现',
        5 => '返点',
        6 => '商城返点',
        7 => '小程序消费',
        8 => '商城小程序消费',
        9 => '撤销付款',
        10 => 'erp下单扣款',
    ],

    //商城返点比例
    'market_bili' => [
        '0' => 0.005, //d
        '1' => 0.01, //c
        '2' => 0.02, //b
        '3' => 0, //a
    ],

    //路线配置
    'route_setting' => [
        1 => 'G130',
        2 => 'LN01',
        3 => 'BT3',
        4 => 'HK(NN100)',
        5 => 'HK(XS001)',
        6 => 'MO(NN100)',
        7 => 'MO(XS001)',
        8 => 'B线路',
    ],


    //区域金额 变化配置
    // ！！！！！！！！！！一体的
    'area_price_setting' => [
        1 => '充值',
        2 => '预扣',
        3 => '返还',
        4 => '补扣',
        5 => '取消订单返还'
    ],
    //区域金额 正负
    // ！！！！！！！！！！一体的
    'area_price_setting_sign' => [
        1 => 1, //正
        2 => 0,
        3 => 1,
        4 => 0,
        5 => 1
    ],


    'url_type' => [
        '0' => 0,

        //国内现货 = 展鹏-苏州库
        '1' => [
            'name' => '国内现货',
            'Organization' => '展鹏',
            'WarehouseName' => '苏州库',
            'LocationName' => ''
        ],

        //欧洲轻奢= 展鹏-德国库
        '2' => [
            'name' => '欧洲轻奢',
            'Organization' => '展鹏',
            'WarehouseName' => '德国库',
            'LocationName' => ''
        ],

        //香港直邮 = 展鹏-香港库
        '3' => [
            'name' => '香港直邮',
            'Organization' => '展鹏',
            'WarehouseName' => '香港库',
            'LocationName' => ''
        ],

        //德国直邮 = 展鹏-德国直邮库
        '4' => [
            'name' => '德国直邮',
            'Organization' => '展鹏',
            'WarehouseName' => '德国直邮库',
            'LocationName' => ''
        ],

        //澳洲直邮 = 展鹏-澳洲直邮库
        '5' => [
            'name' => '澳洲直邮',
            'Organization' => '展鹏',
            'WarehouseName' => '澳洲直邮库',
            'LocationName' => ''
        ],

        '6' => [
            'name' => '日本直邮',
            'Organization' => '展鹏',
            'WarehouseName' => '日本直邮库',
            'LocationName' => ''
        ],



    ],

    //事业部配置
    'Organization' => [
        '1' => '展鹏',
        '2' => '苏州',
        '3' => '日本 东京'
    ],

    //仓库配置
    'WarehouseName' => [
        '1' => '苏州库',
        '2' => '德国库',
        '3' => '上海库',
        '4' => '香港库',
    ],

    //仓位配置
    'LocationName' => [
        '1' => '',
        '2' => '主库',
        '3' => '从库'
    ],

    //商品库状态
    'GoodsListStatus' => [
        '0' => '待上架',
        '1' => '上架',
        '2' => '下架'
    ],

    //省份数组
    'provinces' => [
        '安徽','北京','重庆','福建','甘肃','广东','广西','贵州','海南','河北','黑龙江','河南','香港','湖北','湖南','江苏','江西','吉林','辽宁','澳门','内蒙古','宁夏','青海','山东','上海','山西','陕西','四川','台湾','天津','新疆','西藏','云南','浙江'
    ],

    //到货库存物流公司配置
    'repertory_company' => [
        '1' => 'Harvey',
        '2' => 'SKYNET',
        '3' => 'TNT',
        '4' => 'UPS',
        '5' => '联邦',
        '6' => '联邦手写',
        '7' => '送货',
        '8' => 'DHL',
        '9' => '顺丰',
        '10' => '邮政',
        '999' => '其他',
    ],

    //到货库存状态
    'repertory_status' => [
        '1' => '出货中',  //上卡板时候更新
        '2' => '需通关信息',
        '3' => '已出货', //生成交货单 = 已出货
        '5' => '已入仓', //已做到货扫描
        '6' => '运输中', //生成默认为运输中
        '7' => '已取消',
    ],

    //币种
    'currency' => [
        '0' => '人民币',
        '1' => '港币',
        '2' => '日元',
        '3' => '美元',
        '4' => '欧元',
        '5' => '英镑',
        '999' => ''
    ],

    //到货库存 包裹状态
    'package_status' => [

        '0' => '未打包',
        '1' => '已打包',
        '2' => '已贴单',
        '3' => '部分贴单',
        '999' => '',
    ],


    //中通api账号密码配置
    'api_number' => [
        1 => [
            'loginID' => 'NN100-A',
            'pwd' => '123456',
            'token' => '6DA7B60CD0701FB0BA28C0AB631943E7'
        ],
        2 => [
            'loginID' => 'MX01',
            'pwd' => '123123',
            'token' => 'ACE8A64C5C887DBB3743FC2D8EF2D8AB'
        ],
        3=> [
            'loginID' => 'CT01',
            'pwd' => '123123',
            'token' => ''
        ],
    ],

    //合作人区域账号
    'partner_number' => [
        'xx'
    ],

    //清关状态 代码表示
    /*
    'tracking_status' => [
        'C' => '运单创建',
        'I' => '过机',
        'S' => '提交运输请求',
        'H' => '邮局退回',
        'W' => '处理中心退回',
        'D' => '运单取消',
        'B' => '离开香港',
        'E' => '预报',
        'R' => '香港海关放行',
        'Z' => '已交付客户',
        'U' => 'Return',
    ],
    */
    'tracking_status' => [
        'created' => '订单建立',
        'exported' => '已预报',
        'arrived at destination' => '到达广州交换局',
        'arrived at processing centre' => '到达广州海关',
        'arrived at warehouse' => '邮局已接收',
        'inscanned' => '邮局已过机',
        'clearance' => '香港海关放行',
        'submitted' => '提交运输请求',
        'accepted' => '接受运输请求',
        'despatched' => '离开香港',
        'completed' => '运输流程完成',
        'delivery' => '已派送',
        'handed' => '移交待发货区',
        'delivered' => '已派送',
        'held by customs' => '送交海关',
        'shipment rejected' => '拒绝出货',
        'shipment is ready to returned to sender' => '准备退回',
        'shipment returned' => '已退回',
        'inward office of exchange' => '海关放行',
    ],



    'money_type' => [
        '1' => '人民币',
        '2' => '日元',
        '3' => '韩币',
    ],
    'paratemer_package_id' => 32045,
    'deal_method' => [
        '1' => '拆箱，扫描，上卡板',
        '2' => '拆箱，分批出货(计划）',
        '3' => '拆箱，打包称重，贴标签，扫描，上卡板',
        '4' => '拆箱，称重，贴标签，扫描，上卡板',
        '5' => '拆箱，称重，分批出货(计划）',
        '6' => '拆箱，点数，打包称重，贴标签，扫描，上卡板',
        '7' => '拆箱，点数，打包称重，分批出货(计划）',
        '8' => '不拆箱，上货架储存',
        '9' => '不拆箱，上卡板储存'
    ],

    //物流单号的每个节点
    'repertory_point' => [
        '1' => '预约',
        '2' => '到货扫描',
        '3' => '拆单',
        '4' => '创建托盘',
        '5' => '扫描包裹',
        '6' => '生成交货单'
    ],

    //异常件 原因
    'warning_reason' => [
        '1' => '需办退运',
        '2' => '重量差异超标，邮政仓拒收',
        '3' => '海关查验',
        '4' => '规范申报',
        '5' => '补充申报',
    ],

    'warning_tracking_status' => [
        '1' => '退运申请已办',
        '2' => '办结退运手续',
        '3' => '退往处理中心',
        '4' => '退到处理中心',
        '5' => '装车发往香港',
        '6' => '退往香港途中',
        '7' => '已到香港仓库',
    ],

    //出入库管理 提交类型
    'repertory_sub_type' => [
        '0' => '到货扫描',
        '1' => '国际物流',
        '2' => '送货上门',
        '3' => '安排提货',
        '4' => '区域发货单',
        '5' => '预约上门打包',
    ],

    //关邮e通状态
    'ems_status' => [
        '1' => '未到达海关',
        '2' => '放行',
        '3' => '已退运',
        '4' => '查验',
        '5' => '待缴税',
        '6' => '退运申请已通过',
        '7' => '已缴税',
        '8' => '需办理退运',
        '9' => '退运申请已提交',
        '10' => '等待向海关申报',
        '11' => '0税金放行',
    ],

    'repertory_service_type' => [
        '1' => '打包发货',
        '2' => '直接发货',
        '999' => '其他',
    ],

    'repertory_id_prefix' => 'ZP',

    //熊谢打印机配置
    'xx_printer_set' => [
        '010','011','012','013','014'
    ],

    //我们自己的打印机配置
    'printer_set' => [
        '009','008','998'
    ],

    //打印机偏移量设置
    'printer_margin' => [
        '008' => [
            'marginLeft' => 0,
            'marginRight' => 0,
            'marginTop' => 0,
            'marginBottom' => 0,
        ],
        '009' => [
            'marginLeft' => 0,
            'marginRight' => 0,
            'marginTop' => 0,
            'marginBottom' => 0,
        ],
        '777' => [
            'marginLeft' => 10,
            'marginRight' => 0,
            'marginTop' => 15,
            'marginBottom' => 0,
        ],
    ],

    //小程序显示送货方式
    'mp_mode' => [
        '1' => '香港邮政小包',
        '2' => '个人物品快件',
        '3' => '跨境电商',
        '4' => '国内邮政小包',
        '5' => '香港邮政大包',
    ],

    'goods_tax' => array(
        array('code' => '01010400','tax' => '0.13'),
        array('code' => '01010700','tax' => '0.13'),
        array('code' => '01010800','tax' => '0.13'),
        array('code' => '01019900','tax' => '0.13'),
        array('code' => '01020100','tax' => '0.13'),
        array('code' => '01020200','tax' => '0.13'),
        array('code' => '01029900','tax' => '0.13'),
        array('code' => '01030100','tax' => '0.03'),
        array('code' => '01030200','tax' => '0.13'),
        array('code' => '01039900','tax' => '0.13'),
        array('code' => '02010100','tax' => '0.5'),
        array('code' => '02010200','tax' => '0.5'),
        array('code' => '02020100','tax' => '0.5'),
        array('code' => '02020200','tax' => '0.5'),
        array('code' => '02030100','tax' => '0.5'),
        array('code' => '02030200','tax' => '0.5'),
        array('code' => '02030300','tax' => '0.5'),
        array('code' => '02040000','tax' => '0.5'),
        array('code' => '02050000','tax' => '0.5'),
        array('code' => '02060000','tax' => '0.5'),
        array('code' => '02070000','tax' => '0.5'),
        array('code' => '02990000','tax' => '0.5'),
        array('code' => '03010000','tax' => '0.5'),
        array('code' => '03020000','tax' => '0.5'),
        array('code' => '03030000','tax' => '0.5'),
        array('code' => '03990000','tax' => '0.5'),
        array('code' => '04010100','tax' => '0.2'),
        array('code' => '04010200','tax' => '0.2'),
        array('code' => '04010300','tax' => '0.2'),
        array('code' => '04010400','tax' => '0.2'),
        array('code' => '04019900','tax' => '0.2'),
        array('code' => '04020100','tax' => '0.2'),
        array('code' => '04020200','tax' => '0.2'),
        array('code' => '04020300','tax' => '0.2'),
        array('code' => '04020400','tax' => '0.2'),
        array('code' => '04020500','tax' => '0.2'),
        array('code' => '04029900','tax' => '0.2'),
        array('code' => '04030100','tax' => '0.2'),
        array('code' => '04030200','tax' => '0.2'),
        array('code' => '04030300','tax' => '0.2'),
        array('code' => '04030400','tax' => '0.2'),
        array('code' => '04039900','tax' => '0.2'),
        array('code' => '04990000','tax' => '0.2'),
        array('code' => '05010100','tax' => '0.2'),
        array('code' => '05010200','tax' => '0.2'),
        array('code' => '05010300','tax' => '0.2'),
        array('code' => '05010400','tax' => '0.2'),
        array('code' => '05010500','tax' => '0.2'),
        array('code' => '05010600','tax' => '0.2'),
        array('code' => '05019900','tax' => '0.2'),
        array('code' => '05020100','tax' => '0.2'),
        array('code' => '05020200','tax' => '0.2'),
        array('code' => '05020300','tax' => '0.2'),
        array('code' => '05029900','tax' => '0.2'),
        array('code' => '05990000','tax' => '0.2'),
        array('code' => '06010100','tax' => '0.2'),
        array('code' => '06010200','tax' => '0.2'),
        array('code' => '06010300','tax' => '0.2'),
        array('code' => '06019900','tax' => '0.2'),
        array('code' => '06020100','tax' => '0.2'),
        array('code' => '06020200','tax' => '0.2'),
        array('code' => '06020300','tax' => '0.2'),
        array('code' => '06029900','tax' => '0.2'),
        array('code' => '07010100','tax' => '0.5'),
        array('code' => '07010210','tax' => '0.2'),
        array('code' => '07010220','tax' => '0.2'),
        array('code' => '07010290','tax' => '0.2'),
        array('code' => '07020100','tax' => '0.2'),
        array('code' => '07020200','tax' => '0.2'),
        array('code' => '07029900','tax' => '0.2'),
        array('code' => '07030000','tax' => '0.2'),
        array('code' => '08010000','tax' => '0.13'),
        array('code' => '08020100','tax' => '0.2'),
        array('code' => '08020200','tax' => '0.5'),
        array('code' => '09010111','tax' => '0.5'),
        array('code' => '09010112','tax' => '0.2'),
        array('code' => '09010211','tax' => '0.5'),
        array('code' => '09010212','tax' => '0.2'),
        array('code' => '09010221','tax' => '0.5'),
        array('code' => '09010222','tax' => '0.2'),
        array('code' => '09010291','tax' => '0.5'),
        array('code' => '09010299','tax' => '0.2'),
        array('code' => '09010311','tax' => '0.5'),
        array('code' => '09010312','tax' => '0.2'),
        array('code' => '09010321','tax' => '0.5'),
        array('code' => '09010322','tax' => '0.2'),
        array('code' => '09010331','tax' => '0.5'),
        array('code' => '09010332','tax' => '0.2'),
        array('code' => '09010341','tax' => '0.5'),
        array('code' => '09010342','tax' => '0.2'),
        array('code' => '09010391','tax' => '0.5'),
        array('code' => '09010392','tax' => '0.2'),
        array('code' => '09010411','tax' => '0.5'),
        array('code' => '09010412','tax' => '0.2'),
        array('code' => '09010421','tax' => '0.5'),
        array('code' => '09010422','tax' => '0.2'),
        array('code' => '09010491','tax' => '0.5'),
        array('code' => '09010492','tax' => '0.2'),
        array('code' => '09010511','tax' => '0.5'),
        array('code' => '09010512','tax' => '0.2'),
        array('code' => '09010521','tax' => '0.5'),
        array('code' => '09010522','tax' => '0.2'),
        array('code' => '09010531','tax' => '0.5'),
        array('code' => '09010532','tax' => '0.2'),
        array('code' => '09010541','tax' => '0.5'),
        array('code' => '09010542','tax' => '0.2'),
        array('code' => '09010591','tax' => '0.5'),
        array('code' => '09010592','tax' => '0.2'),
        array('code' => '09010610','tax' => '0.5'),
        array('code' => '09010620','tax' => '0.2'),
        array('code' => '09020110','tax' => '0.2'),
        array('code' => '09020120','tax' => '0.2'),
        array('code' => '09020190','tax' => '0.2'),
        array('code' => '09020211','tax' => '0.5'),
        array('code' => '09020212','tax' => '0.2'),
        array('code' => '09020221','tax' => '0.5'),
        array('code' => '09020222','tax' => '0.2'),
        array('code' => '09020231','tax' => '0.5'),
        array('code' => '09020232','tax' => '0.2'),
        array('code' => '09020241','tax' => '0.5'),
        array('code' => '09020242','tax' => '0.2'),
        array('code' => '09020251','tax' => '0.5'),
        array('code' => '09020252','tax' => '0.2'),
        array('code' => '09020261','tax' => '0.5'),
        array('code' => '09020262','tax' => '0.2'),
        array('code' => '09020271','tax' => '0.5'),
        array('code' => '09020272','tax' => '0.2'),
        array('code' => '09020281','tax' => '0.5'),
        array('code' => '09020282','tax' => '0.2'),
        array('code' => '09020291','tax' => '0.5'),
        array('code' => '09020292','tax' => '0.2'),
        array('code' => '09020310','tax' => '0.2'),
        array('code' => '09020390','tax' => '0.2'),
        array('code' => '09029900','tax' => '0.2'),
        array('code' => '10010100','tax' => '0.2'),
        array('code' => '10010200','tax' => '0.2'),
        array('code' => '10010300','tax' => '0.2'),
        array('code' => '10010400','tax' => '0.2'),
        array('code' => '10010500','tax' => '0.2'),
        array('code' => '10019900','tax' => '0.2'),
        array('code' => '10020100','tax' => '0.2'),
        array('code' => '10020200','tax' => '0.2'),
        array('code' => '10029900','tax' => '0.2'),
        array('code' => '10030100','tax' => '0.2'),
        array('code' => '10030200','tax' => '0.2'),
        array('code' => '10039900','tax' => '0.2'),
        array('code' => '11010100','tax' => '0.2'),
        array('code' => '11010200','tax' => '0.2'),
        array('code' => '11010300','tax' => '0.2'),
        array('code' => '11010400','tax' => '0.2'),
        array('code' => '11010500','tax' => '0.2'),
        array('code' => '11011100','tax' => '0.2'),
        array('code' => '11011200','tax' => '0.2'),
        array('code' => '11011300','tax' => '0.2'),
        array('code' => '11011400','tax' => '0.2'),
        array('code' => '11011500','tax' => '0.2'),
        array('code' => '11011600','tax' => '0.2'),
        array('code' => '11011700','tax' => '0.2'),
        array('code' => '11019900','tax' => '0.2'),
        array('code' => '11020100','tax' => '0.2'),
        array('code' => '11021120','tax' => '0.2'),
        array('code' => '11021130','tax' => '0.2'),
        array('code' => '11020400','tax' => '0.2'),
        array('code' => '11029900','tax' => '0.2'),
        array('code' => '11030110','tax' => '0.13'),
        array('code' => '11030121','tax' => '0.13'),
        array('code' => '11030122','tax' => '0.13'),
        array('code' => '11030130','tax' => '0.13'),
        array('code' => '11030140','tax' => '0.13'),
        array('code' => '11030150','tax' => '0.13'),
        array('code' => '11030190','tax' => '0.13'),
        array('code' => '11031200','tax' => '0.2'),
        array('code' => '11031300','tax' => '0.2'),
        array('code' => '11031400','tax' => '0.2'),
        array('code' => '11031500','tax' => '0.2'),
        array('code' => '11031600','tax' => '0.2'),
        array('code' => '11031700','tax' => '0.2'),
        array('code' => '11031800','tax' => '0.2'),
        array('code' => '11031900','tax' => '0.2'),
        array('code' => '11032000','tax' => '0.2'),
        array('code' => '11032100','tax' => '0.2'),
        array('code' => '11039910','tax' => '0.13'),
        array('code' => '11039990','tax' => '0.2'),
        array('code' => '12010000','tax' => '0.13'),
        array('code' => '12020000','tax' => '0.13'),
        array('code' => '12030000','tax' => '0.13'),
        array('code' => '12990000','tax' => '0.13'),
        array('code' => '13010100','tax' => '0.2'),
        array('code' => '13010200','tax' => '0.2'),
        array('code' => '13010300','tax' => '0.2'),
        array('code' => '13010400','tax' => '0.2'),
        array('code' => '13020000','tax' => '0.2'),
        array('code' => '13990000','tax' => '0.2'),
        array('code' => '14010100','tax' => '0.2'),
        array('code' => '14010200','tax' => '0.2'),
        array('code' => '14010300','tax' => '0.2'),
        array('code' => '14010400','tax' => '0.2'),
        array('code' => '14010500','tax' => '0.2'),
        array('code' => '14010600','tax' => '0.2'),
        array('code' => '14010700','tax' => '0.2'),
        array('code' => '14020100','tax' => '0.2'),
        array('code' => '14020200','tax' => '0.2'),
        array('code' => '14020300','tax' => '0.2'),
        array('code' => '14020400','tax' => '0.2'),
        array('code' => '14020500','tax' => '0.2'),
        array('code' => '14020600','tax' => '0.2'),
        array('code' => '14030000','tax' => '0.2'),
        array('code' => '14990000','tax' => '0.2'),
        array('code' => '15010100','tax' => '0.2'),
        array('code' => '15010200','tax' => '0.2'),
        array('code' => '15020000','tax' => '0.2'),
        array('code' => '15030000','tax' => '0.2'),
        array('code' => '15990000','tax' => '0.2'),
        array('code' => '16010100','tax' => '0.2'),
        array('code' => '16010200','tax' => '0.2'),
        array('code' => '16010300','tax' => '0.2'),
        array('code' => '16010400','tax' => '0.2'),
        array('code' => '16010500','tax' => '0.2'),
        array('code' => '16010600','tax' => '0.2'),
        array('code' => '16010700','tax' => '0.2'),
        array('code' => '16010800','tax' => '0.2'),
        array('code' => '16010900','tax' => '0.2'),
        array('code' => '16011000','tax' => '0.2'),
        array('code' => '16020000','tax' => '0.2'),
        array('code' => '16990000','tax' => '0.2'),
        array('code' => '17010110','tax' => '0.13'),
        array('code' => '17010121','tax' => '0.13'),
        array('code' => '17010122','tax' => '0.2'),
    ),

    //仓库员工编号
    'op_user_id' => [
        '1011101',
        '1011102',
    ],

    'shop_mp_provinces_map' => [
        '北京市'      => '北京',
        '天津市'      => '天津',
        '河北省'      => '河北',
        '山西省'      => '山西',
        '内蒙古自治区'   => '内蒙古',
        '辽宁省'      => '辽宁',
        '吉林省'      => '吉林',
        '黑龙江省'     => '黑龙江',
        '上海市'      => '上海',
        '江苏省'      => '江苏',
        '浙江省'      => '浙江',
        '安徽省'      => '安徽',
        '福建省'      => '福建',
        '江西省'      => '江西',
        '山东省'      => '山东',
        '河南省'      => '河南',
        '湖北省'      => '湖北',
        '湖南省'      => '湖南',
        '广东省'      => '广东',
        '广西壮族自治区'  => '广西',
        '海南省'      => '海南',
        '重庆市'      => '重庆',
        '四川省'      => '四川',
        '贵州省'      => '贵州',
        '云南省'      => '云南',
        '西藏自治区'    => '西藏',
        '陕西省'      => '陕西',
        '甘肃省'      => '甘肃',
        '青海省'      => '青海',
        '宁夏回族自治区'  => '宁夏',
        '新疆维吾尔自治区' => '新疆',
        '台湾省'      => '台湾',
        '香港特别行政区'  => '香港',
        '澳门特别行政区'  => '澳门'
    ],

    'stock_order_pay_method' => [
        '1' => '积分支付',
        '2' => 'erp使用余额支付',
        '3' => '微信支付'
    ],

    'stock_order_pay_method_img' => [
        '1' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/proprietary/balance.png',
        '2' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/proprietary/balance.png',
        '3' => 'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/shopmp/agent/pay/%E5%BE%AE%E4%BF%A1%403x.jpg'
    ],

    //超级发货员username
    'super_send_order' => [
        'chenbin2019',
        'mingzhen.chen',
        'gao'
    ],
    'super_send_order_business_id' => [
        '49',
        '50',
        '51',
    ],
    'zhanpeng_business_id' => 49,

    'SFMonthCode' => '5125872943',

    /*
    |--------------------------------------------------------------------------
    |游客配置
    |--------------------------------------------------------------------------
    */
    'visitor'=>[
        'nickname'=>'游客',
        'headimg'=>'https://fenithcdn.oss-cn-shanghai.aliyuncs.com/upload/visitor.png',
        'market_class'=>2,
        'price'=>0,
        'fandian'=>0,
        'is_new'=>1,
    ],

];
