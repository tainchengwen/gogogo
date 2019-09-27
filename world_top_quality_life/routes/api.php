<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::any('longpic','Api\LongPicController@makeLongPic');


Route::post('makeLongPost','Api\LongPicController@makeLongPost');
Route::post('makeLongPostEdit','Api\LongPicController@makeLongPostEdit');
Route::post('makeLongPostDel','Api\LongPicController@makeLongPostDel');
Route::post('makeLongPostList','Api\LongPicController@makeLongPostList');

// 给营销工具临时用的
Route::get('checkWeChatSubscribe','Api\WechatController@checkWeChatSubscribe');

Route::any('apitest','ApiController@test');
Route::any('makeOrder','ApiController@makeOrder');
Route::any('makeOrderData','ApiController@makeOrderData');
Route::any('getOrderInfo','ApiController@getOrderInfo');
Route::any('payOrder','ApiController@payOrder');
Route::any('makePostNumber','ApiController@makePostNumber');
Route::any('cancelPostNumber','ApiController@cancelPostNumber');
Route::any('makePdfFile','ApiController@makePdfFile');
Route::any('getLastPrice','ApiController@getLastPrice');
Route::any('deletePackage','ApiController@deletePackage');
Route::any('deleteOrder','ApiController@deleteOrder');
Route::any('makePackagePdfFile','ApiController@makePackagePdfFile');
Route::any('updatePackageWeight','ApiController@updatePackageWeight');

Route::any('getPrintFiles','ApiController@getPrintFiles');
Route::any('printEnd','ApiController@printEnd');
//tracking more 推送
    Route::any('trackingMoreHook','ApiController@trackingMoreHook');

//不需要token
Route::group([
    'prefix' => 'auth'
], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    //Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

    Route::post('emailBack', 'AuthController@checkEmail');

    //提交验证
    Route::post('formCode', 'AuthController@formCode');

    //生成成为分销商
    Route::post('business/register','Api\BusinessController@register');
});

Route::middleware('jwt.api.refresh')->group(function($router) {
    $router->post('auth/refresh','AuthController@refresh');
});

//需要token
Route::middleware('jwt.api.auth')->group(function($router) {
    //修改密码
    Route::post('reset', 'AuthController@reset');
    //初次设置密码
    Route::post('firstSet', 'AuthController@firstSetPassword');

    Route::post('getLoginInfo', 'Api\UsersController@getLoginInfo');

    //新增事业部 事业部维护 全局维护
    Route::group(['middleware' => ['permission:1']], function ($router) {
        $router->post('business/add','Api\BusinessController@add');
    });


    //编辑事业部 事业部维护 全局维护
    Route::group(['middleware' => ['permission:2']], function ($router) {
        //
        $router->post('business/edit','Api\BusinessController@edit');
    });
    //master_id查找wxuser nickname
    $router->get('nickname/{master_id}','Api\WxUserController@nickname');

    // 事业部列表 事业部维护 全局维护
    Route::group(['middleware' => ['permission:3']], function ($router) {
        //
        $router->post('business/theList','Api\BusinessController@theList');
    });
    //删除事业部 事业部维护 全局维护
    Route::group(['middleware' => ['permission:5']], function ($router) {
        //
        $router->post('business/delete','Api\BusinessController@delete');
    });

    //全局维护 发货地址
    Route::group(['middleware' => ['permission:102']], function ($router) {
        //
        $router->post('config/editSendAddress','Api\ConfigureController@editSendAddress');

    });
    Route::group(['middleware' => ['permission:9']], function ($router) {
        $router->post('permission/addRole','Api\PermissionController@addRole');
    });

       //增加-角色维护-系统维护
    Route::group(['middleware' => ['permission:10']], function ($router) {
        $router->post('permission/roleInfo','Api\PermissionController@roleInfo');
        $router->post('permission/editRole','Api\PermissionController@editRole');
    });   //名称编辑-角色维护-系统维护
    Route::group(['middleware' => ['permission:11']], function ($router) {
        $router->post('permission/givePermissionToRole','Api\PermissionController@givePermissionToRole');
    });   //权限编辑-角色维护-系统维护
    Route::group(['middleware' => ['permission:12']], function ($router) {
        $router->post('permission/roleList','Api\PermissionController@roleList');
    });   //列表查询-角色维护-系统维护
    Route::group(['middleware' => ['permission:13']], function ($router) {
        Route::post('register', 'Api\UsersController@register');
    });   //增加-用户维护-系统维护
    Route::group(['middleware' => ['permission:14']], function ($router) {
        $router->post('users/giveRoleToUser','Api\UsersController@giveRoleToUser');
    });   //编辑-用户维护-系统维护
    Route::group(['middleware' => ['permission:17']], function ($router) {
        $router->post('users/giveBusinessTo','Api\UsersController@login');
        //用户拥有的事业部
        $router->post('users/businessList','Api\UsersController@businessList');
    });   //所属事业部-用户维护-系统维护
    Route::group(['middleware' => ['permission:19']], function ($router) {
        // 修改vip等级
        $router->put('wxuser/alterVipLevel', 'Api\WxUserController@alterVipLevel');
        // 添加报表黑名单
        $router->post('wxuser/addBlackList', 'Api\WxUserController@addBlackList');
        // 搜索黑名单用户
        $router->get('wxuser/searchBlackListUsers', 'Api\WxUserController@searchBlackListUsers');
        // 将用户移除黑名单
        $router->post('wxuser/removeBlackList', 'Api\WxUserController@removeBlackList');
    });
    $router->post('wxuser/addWhiteList', 'Api\WxUserController@addWhiteList');
    // 搜索黑名单用户
    $router->get('wxuser/searchWhiteListUsers', 'Api\WxUserController@searchWhiteListUsers');
    // 将用户移除黑名单
    $router->post('wxuser/removeWhiteList', 'Api\WxUserController@removeWhiteList');

    //列表查询-客户维护-系统维护
    Route::group(['middleware' => ['permission:20']], function ($router) {
        $router->post('expressNumber/add','Api\ExpressNumberController@add');

    });   //增加-快递鸟维护-系统维护
    Route::group(['middleware' => ['permission:21']], function ($router) {

        $router->post('expressNumber/edit','Api\ExpressNumberController@edit');
    });   //编辑-快递鸟维护-系统维护
    Route::group(['middleware' => ['permission:22']], function ($router) {
        $router->post('expressNumber/info','Api\ExpressNumberController@info');
    });   //详情-快递鸟维护-系统维护
    Route::group(['middleware' => ['permission:23']], function ($router) {
        //快递鸟账号密码维护
        $router->post('expressNumber/theList','Api\ExpressNumberController@theList');
    });   //列表查询-快递鸟维护-系统维护
    Route::group(['middleware' => ['permission:24']], function ($router) {
        $router->post('account/add','Api\AccountController@add');
    });   //增加-财务账户维护-系统维护
    Route::group(['middleware' => ['permission:25']], function ($router) {
        $router->post('account/edit','Api\AccountController@edit');
    });   //编辑-财务账户维护-系统维护
    Route::group(['middleware' => ['permission:26']], function ($router) {
        $router->post('account/delete','Api\AccountController@delete');
    });   //删除-财务账户维护-系统维护
    Route::group(['middleware' => ['permission:28']], function ($router) {
        $router->post('supplier/add','Api\ConfigureController@addSupplier');
    });   //增加-供应商维护-系统维护
    Route::group(['middleware' => ['permission:29']], function ($router) {
        $router->post('supplier/edit','Api\ConfigureController@editSupplier');
    });   //修改-供应商维护-系统维护
    Route::group(['middleware' => ['permission:32']], function ($router) {
        $router->post('supplier/delete','Api\ConfigureController@deleteSupplier');
    });   //删除-供应商维护-系统维护
    Route::group(['middleware' => ['permission:33']], function ($router) {
        $router->post('port/add','Api\ConfigureController@addPort');
    });   //增加-港口维护-系统维护
    Route::group(['middleware' => ['permission:34']], function ($router) {
        $router->post('port/edit','Api\ConfigureController@editPort');
    });   //修改-港口维护-系统维护
    Route::group(['middleware' => ['permission:37']], function ($router) {
        $router->post('port/delete','Api\ConfigureController@deletePort');
    });   //删除-港口维护-系统维护

    Route::group(['middleware' => ['permission:192']], function ($router) {
        $router->post('warehouse/delete','Api\ConfigureController@deleteWareHouse');
    });   //删除-仓库维护-系统维护
    Route::group(['middleware' => ['permission:190']], function ($router) {
        $router->post('warehouse/add','Api\ConfigureController@addWareHouse');
    });   //增加-库位维护-系统维护
    Route::group(['middleware' => ['permission:191']], function ($router) {
        $router->post('warehouse/edit','Api\ConfigureController@editWareHouse');
    });   //修改-库位维护-系统维护
    Route::group(['middleware' => ['permission:43']], function ($router) {
        $router->post('storehouse/add', 'Api\ConfigureController@addStorehouse');
    });
    Route::group(['middleware' => ['permission:44']], function ($router) {
        //销售单退货
        $router->post('storehouse/edit', 'Api\ConfigureController@editStorehouse');
    });
    Route::group(['middleware' => ['permission:46']], function ($router) {
        $router->post('storehouse/info','Api\ConfigureController@storehouseInfo');
    });   //详情查询-库位维护-系统维护
    Route::group(['middleware' => ['permission:47']], function ($router) {
        $router->post('storehouse/delete','Api\ConfigureController@deleteStorehouse');
    });   //删除-库位维护-系统维护
    Route::group(['middleware' => ['permission:48']], function ($router) {
        $router->post('purchaseOrder/addOrder','Api\PurchaseOrderListController@addOrder');
        //采购订单新增明细
        $router->post('purchaseOrder/addOrderGoodsList','Api\PurchaseOrderListController@addOrderGoodsList');
    });   //增加-采购订单维护-采购订单
    Route::group(['middleware' => ['permission:50']], function ($router) {
        //删除采购订单
        $router->post('purchaseOrder/deletePurchaseOrder','Api\PurchaseOrderListController@deletePurchaseOrder');
    });   //删除-采购订单维护-采购订单

    Route::group(['middleware' => ['permission:52']], function ($router) {
        //导入采购订单
        $router->post('purchaseOrder/exportPurchaseOrder','Api\PurchaseOrderListController@exportPurchaseOrder');
    });   //商品导入-采购订单维护-采购订单
    Route::group(['middleware' => ['permission:53']], function ($router) {
        $router->post('purchaseOrder/theList','Api\PurchaseOrderListController@theList');
    });   //列表查询-采购订单维护-采购订单
    Route::group(['middleware' => ['permission:54']], function ($router) {
        //新增物流单号页面
        $router->post('delivery/addLogisticsPage','Api\DeliveryController@addLogisticsPage');
        //新增物流单号
        $router->post('delivery/addLogistics','Api\DeliveryController@addLogistics');
        //运费测算
        $router->post('delivery/calculateFreight','Api\DeliveryController@calculateFreight');
    });   //新增物流单-港口发货-物流管理
    Route::group(['middleware' => ['permission:55']], function ($router) {
        $router->post('delivery/theList','Api\DeliveryController@theList');
    });   //列表查询-港口发货-物流管理
    Route::group(['middleware' => ['permission:56']], function ($router) {
        //港口收货 收货操作
        $router->post('delivery/receiveLogistics','Api\DeliveryController@receiveLogistics');
    });   //收货-港口收货-物流管理
    Route::group(['middleware' => ['permission:57']], function ($router) {
        $router->post('delivery/receivingList','Api\DeliveryController@receivingList');
        //物流单号详情
        $router->post('delivery/logisticsInfo','Api\DeliveryController@logisticsInfo');
    });   //列表查询-港口收货-物流管理
    Route::group(['middleware' => ['permission:58']], function ($router) {
        //新增转运订单页面
        $router->post('delivery/addPortTransport','Api\DeliveryController@addPortTransport');
        //新增转运订单处理
        $router->post('delivery/addPortTransportRes','Api\DeliveryController@addPortTransportRes');
    });   //新增物流单-订单转运-物流管理
    Route::group(['middleware' => ['permission:59']], function ($router) {
        //运单转运列表
        $router->post('delivery/portTransport','Api\DeliveryController@portTransport');
    });   //列表查询-订单转运-物流管理

    Route::group(['middleware' => ['permission:88']], function ($router) {
        //运单转运删除
        $router->post('delivery/deleteTransport','Api\DeliveryController@deleteTransport');
    });
    Route::group(['middleware' => ['permission:60']], function ($router) {
        //采购订单新增付款记录
        $router->post('payPurchaseOrder/addPayRecord','Api\PurchaseOrderListController@addPayRecord');
        //财务付款新增付款详情
        $router->post('payPurchaseOrder/addPayInfo','Api\PurchaseOrderListController@addPayInfo');
    });
      //付款-采购单付款-财务管理
    Route::group(['middleware' => ['permission:61']], function ($router) {
        $router->post('payPurchaseOrder/theList','Api\PurchaseOrderListController@payOrderList');


    });   //列表查询-采购单付款-财务管理

    Route::group(['middleware' => ['permission:62']], function ($router) {
        //运费订单付款详情
        $router->post('payLogistics/payLogisticsInfo','Api\DeliveryController@payLogisticsInfo');
        // 1、通过币种 取财务账户。2、通过账户id取充值记录 用采购订单付款的
        //运费订单 新增付款记录
        $router->post('payLogistics/addPayRecord','Api\DeliveryController@addPayRecord');
    });   //付款-运费付款-财务管理
    Route::group(['middleware' => ['permission:63']], function ($router) {
        //列表
        $router->post('payLogistics/payLogisticsList','Api\DeliveryController@payLogisticsList');
    });   //列表查询-运费付款-财务管理
    //运费付款撤销

    Route::group(['middleware' => ['permission:64']], function ($router) {
        //充值
        $router->post('account/recharge','Api\AccountController@recharge');
        //充值记录
        $router->post('account/rechargeRecord','Api\AccountController@rechargeRecord');
        //撤销充值
        $router->post('account/revokeRecharge','Api\AccountController@revokeRecharge');
    });   //充值-账户充值-财务管理
    Route::group(['middleware' => ['permission:66']], function ($router) {
        //销售订单付款详情
        $router->post('payOrder/orderInfo','Api\PayOrderController@orderInfo');
        //销售订单付款处理
        $router->post('payOrder/payOrder','Api\PayOrderController@payOrder');
        //批量收款弹出框信息
        $router->post('payOrder/batchPayInfo','Api\PayOrderController@batchPayInfo');
        //批量收款
        $router->post('payOrder/batchPayOrder','Api\PayOrderController@batchPayOrder');
        //销售订单付款历史
        $router->post('payOrder/payHistory','Api\PayOrderController@payHistory');
        //撤销销售订单付款
        $router->post('payOrder/cancelPayOrder','Api\PayOrderController@cancelPayOrder');
    });   //收款-订单收款-财务管理
    Route::group(['middleware' => ['permission:67']], function ($router) {
        //销售订单付款
        $router->post('payOrder/theList','Api\PayOrderController@theList');
    });   //列表查询-订单收款-财务管理
    Route::group(['middleware' => ['permission:69']], function ($router) {
        $router->post('accountLog/theList','Api\AccountLogController@theList');
        //交易类型
        $router->post('accountLog/payType','Api\AccountLogController@payType');
        //交易明细
        $router->post('accountLog/payDetail','Api\AccountLogController@payDetail');
    });   //列表查询-账户明细-财务管理
    Route::group(['middleware' => ['permission:70']], function ($router) {
        $router->post('stockOrder/addOrder','Api\StockOrderController@addOrder');
    });   //新增-采购订单维护-库存销售
    Route::group(['middleware' => ['permission:71']], function ($router) {
        //选择某仓库下的库存
        $router->post('stockOrder/getStockByWarehouse','Api\StockOrderController@getStockByWarehouse');
        //选择某馆区下的商品
        $router->post('stockOrder/getSkusByMp','Api\StockOrderController@getSkusByMp');
        //单独编辑 销售订单的 发货备注
        $router->post('stockOrder/editOrderRemark','Api\StockOrderController@editOrderRemark');

        //库存销售保存发货地址/收货地址 客户常用收件地址保存
        $router->post('stockOrder/saveAddress','Api\StockOrderController@saveAddress');
        //客户常用收件地址-发货地址
        $router->post('stockOrder/getUserAddress','Api\StockOrderController@getUserAddress');


    });   //编辑-采购订单维护-库存销售
    Route::group(['middleware' => ['permission:72']], function ($router) {
        //库存销售 删除
        $router->post('stockOrder/deleteStockOrder','Api\StockOrderController@deleteOrder');
    });   //删除-采购订单维护-库存销售payOrder/payOrder
    Route::group(['middleware' => ['permission:73']], function ($router) {
        //库存销售
        $router->post('stockOrder/fahuoList','Api\StockOrderController@fahuoList');
        //导出EXcel
        $router->post('stockOrder/orderexcelinfo','Api\StockOrderController@orderExcelInfo');
    });   //列表查询-采购订单维护-库存销售
    Route::group(['middleware' => ['permission:74']], function ($router) {
        $router->post('delivery/receiveWareHouse','Api\DeliveryController@receiveWareHouse');
    });   //商品入库-仓库收货-仓库管理
    Route::group(['middleware' => ['permission:75']], function ($router) {
        $router->post('delivery/receiveWareHouseList','Api\DeliveryController@receiveWareHouseList');
        //仓库收货 详情
        $router->post('delivery/receiveWareHouseInfo','Api\DeliveryController@receiveWareHouseInfo');
    });   //列表查询-仓库收货-仓库管理

    Route::group(['middleware' => ['permission:134']], function ($router) {
        //发货单报表
        $router->post('warehouseOrder/sendOrderRecordSheet','Api\WareHouseOrderController@sendOrderRecordSheet');

    });

    Route::group(['middleware' => ['permission:135']], function ($router) {
        //手动打印快递面单
        $router->post('warehouseOrder/printExpressPage','Api\WareHouseOrderController@printExpressPage');
    });


    Route::group(['middleware' => ['permission:78']], function ($router) {
        //库存调整 报废 调账
        $router->post('StockAdjust/changeStockNum','Api\StockAdjustController@changeStockNum');
    });   //数量调整-库存调整-仓库管理
    Route::group(['middleware' => ['permission:79']], function ($router) {
        //库存调整提交处理
        $router->post('StockAdjust/stockAdjustRes','Api\StockAdjustController@stockAdjustRes');
    });   //库位转移-库存调整-仓库管理
    Route::group(['middleware' => ['permission:80']], function ($router) {
        //库存调整
        $router->post('StockAdjust/theList','Api\StockAdjustController@theList');
        //库存调整详情
        $router->post('StockAdjust/info','Api\StockAdjustController@stockAdjustInfo');
    });   //列表查询-库存调整-仓库管理
    Route::group(['middleware' => ['permission:81']], function ($router) {

        $router->post('stock/theList','Api\StockController@theList'); //给杨晓杰用
        //通过商品编号 获取入库明细
        //仓库待发货明细
        $router->post('stock/deliverRecord','Api\StockController@deliverRecord');
    });   //列表查询-仓库明细-仓库管理
    Route::group(['middleware' => ['permission:82']], function ($router) {
        //仓库收货记录
        $router->post('stock/receiveList','Api\ReceiveGoodsController@receiveList');
        //仓库收货记录详情
        $router->post('stock/receiveInfo','Api\ReceiveGoodsController@receiveInfo');


    });   //列表查询-收货记录-仓库管理
    Route::group(['middleware' => ['permission:100']], function ($router) {
        //仓库收货记录删除
        $router->post('stock/deleteReceiveRecord','Api\ReceiveGoodsController@deleteReceiveRecord');
    });
    Route::group(['middleware' => ['permission:83']], function ($router) {
        //发货记录
        $router->post('warehouseOrder/sendOrderRecord','Api\WareHouseOrderController@sendOrderRecord');
        //发货记录明细
        $router->post('warehouseOrder/sendOrderRecordInfo','Api\WareHouseOrderController@sendOrderRecordInfo');
        //仓库发货记录编辑
        $router->post('warehouseOrder/editSendOrderRecord','Api\WareHouseOrderController@editSendOrderRecord');

    });   //列表查询-发货记录-仓库管理

    Route::group(['middleware' => ['permission:101']], function ($router) {
        //删除发货记录
        $router->post('warehouseOrder/deleteSendOrderRecord','Api\WareHouseOrderController@deleteSendOrderRecord');
    });


    Route::group(['middleware' => ['permission:84']], function ($router) {
        //仓库明细 带 成本
        $router->post('stock/stockPriceInfo','Api\ReceiveGoodsController@stockPriceInfo');
        //仓库明细 带成本 详情
        $router->post('stock/stockPriceDetail','Api\ReceiveGoodsController@stockPriceDetail');
    });   //列表查询-仓库明细带成本-仓库管理


    //仓库报表
    Route::group(['middleware' => ['permission:130']], function ($router) {
        //仓库交易明细
        $router->post('dealDetail/theList','Api\WareHouseOrderController@transactionDetails');
    });

    Route::group(['middleware' => ['permission:131']], function ($router) {
        //仓库交易明细，如果有销售订单的话 则 显示详情
        $router->post('dealDetail/getOneDetail','Api\WareHouseOrderController@oneTransactionDetail');
    });


    //销售列表 - 待付款列表
    Route::group(['middleware' => ['permission:132']], function ($router) {
        $router->post('waitPayReport/theList','Api\StockOrderController@waitPayOrderList');

    });

    //销售明细
    Route::group(['middleware' => ['permission:133']], function ($router) {
        $router->post('saleReport/saleDetail','Api\StockOrderController@saleDetails');

    });



    //销售报表 月报
    // Route::group(['middleware' => ['permission:137']], function ($router) {
        $router->post('stockOrder/saleSheetMonthCost','Api\StockOrderController@saleSheetMonthCost');
    // });


    // Route::group(['middleware' => ['permission:138']], function ($router) {
        //销售报表 周报
        $router->post('stockOrder/saleSheetWeekCost', 'Api\StockOrderController@saleSheetWeekCost');
        $router->get('stockOrder/getWeekDetail', 'Api\StockOrderController@getWeekDetail');
    // });


    $router->post('reviewPass', 'Api\SkuReviewController@reviewPass');
    $router->post('reviewRefuse', 'Api\SkuReviewController@reviewRefuse');
    $router->post('reviewList', 'Api\SkuReviewController@reviewList');
    $router->post('reviewSkuNumChange', 'Api\SkuReviewController@skuNumChange');
    $router->post('reviewAdd', 'Api\SkuReviewController@reviewAdd');
    $router->post('reviewEdit', 'Api\SkuReviewController@reviewEdit');
    $router->post('reviewInfo', 'Api\SkuReviewController@reviewInfo');
    $router->post('reviewRepeat', 'Api\SkuReviewController@reviewRepeat');


    /**
     *
     * 商城相关
     */
    //代理sku添加
    $router->post('privateProduct/addProduct','Api\PrivateProductListController@addProduct');
    $router->post('privateProduct/editProduct','Api\PrivateProductListController@editProduct');
    $router->post('privateProduct/productInfo','Api\PrivateProductListController@productInfo');
    $router->post('privateProduct/uploadExcel','Api\PrivateProductListController@uploadExcel');
    $router->post('privateProduct/theList','Api\PrivateProductListController@theList');
    $router->post('privateProduct/importExcel','Api\PrivateProductListController@importExcel');


    //商品
    Route::group(['middleware' => ['permission:166']], function ($router) {
        $router->post('productList/addProduct','Api\ProductListController@addProduct');
    });
    //商品维护 导入商品
    Route::group(['middleware' => ['permission:168']], function ($router) {
        $router->post('productList/editProduct','Api\ProductListController@editProduct');
    });
    Route::group(['middleware' => ['permission:169']], function ($router) {
        $router->post('productList/productInfo','Api\ProductListController@productInfo');
    });

    Route::group(['middleware' => ['permission:171']], function ($router) {
        $router->post('product/uploadExcel','Api\ProductListController@uploadExcel');
        $router -> post('productList/importExcel','Api\ProductListController@importExcel');
    });
    Route::group(['middleware' => ['permission:85']], function ($router) {
        $router->post('freighttpls/theList','Api\FreighttplsController@theList');
        $router->post('freighttpls/get','Api\FreighttplsController@get');
    });   //列表查询-运费模板-商城管理
    Route::group(['middleware' => ['permission:86']], function ($router) {
        $router->post('freighttpls/add','Api\FreighttplsController@add');
        $router->post('freighttpls/addTpl','Api\FreighttplsController@addTpl');
    });   //新增模板-运费模板-全局维护
    Route::group(['middleware' => ['permission:87']], function ($router) {
        $router->post('freighttpls/update','Api\FreighttplsController@update');
        $router->post('/freighttpls/theManageList','Api\FreighttplsController@theManageList');
        $router->post('/freighttpls/delTpl','Api\FreighttplsController@delTpl');
        $router->post('/freighttpls/editTpl','Api\FreighttplsController@editTpl');
    });   //修改模板-运费模板-全局维护



    // Tags
    Route::group(['middleware' => ['permission:181']], function ($router) {
        $router->post('/tags/add','Api\TagsController@add');
    });
    Route::group(['middleware' => ['permission:182']], function ($router) {
        $router->post('/tags/edit','Api\TagsController@edit');
    });
    Route::group(['middleware' => ['permission:183']], function ($router) {
        $router->post('/tags/del','Api\TagsController@del');
    });
    // SPU-SKU-link
    //sku管理
    Route::group(['middleware' => ['permission:203']], function ($router) {
        $router->post('/spulinksku/add','Api\SPUController@addLinkSKUs');
        $router->post('/spulinksku/remove','Api\SPUController@removeLinkSKUs');
    });
    // 分类相关接口
    Route::group(['middleware' => ['permission:176']], function ($router) {
        $router->post('/category/add','Api\SPUCategoryController@addCategory');
    });
    Route::group(['middleware' => ['permission:177']], function ($router) {
        $router->post('/category/edit','Api\SPUCategoryController@editCategory');
    });
    Route::group(['middleware' => ['permission:178']], function ($router) {
        $router->post('/category/del','Api\SPUCategoryController@delCategory');
    });
    Route::group(['middleware' => ['permission:210']], function ($router) {

        // 零库存报表
        $router->post('stock/zeroList','Api\StockController@zeroList');
    });
    // banners配置
    Route::group(['middleware' => ['permission:90']], function ($router) {
        $router->post('/mall/bannerslist', 'Api\MallController@bannersList');
        $router->post('/mall/bannersadd', 'Api\MallController@bannersAdd');
        $router->post('/mall/bannersedit', 'Api\MallController@bannersUpdate');
        $router->post('/mall/bannersdel', 'Api\MallController@bannersDel');
        $router->post('/mall/bannersinfo', 'Api\MallController@bannersInfo');
    });


    Route::group(['middleware' => ['permission:149']], function ($router) {
        $router->post('/mall/globalmallsettinglist', 'Api\MallController@theGlobalMallSettingList');

    });
    Route::group(['middleware' => ['permission:148']], function ($router) {
        $router->post('/mall/addlinkmpname', 'Api\MallController@addLinkMpName');
    });
    Route::group(['middleware' => ['permission:150']], function ($router) {
        $router->post('/mall/updatempnameon', 'Api\MallController@updateMpNameOn');
        $router->post('/mall/updatempnameoff', 'Api\MallController@updateMpNameOff');
    });
    //馆区配置
    Route::group(['middleware' => ['permission:159']], function ($router) {
        $router->post('/mall/addlinkspu', 'Api\MallController@addLinkSPUs');
    });
    Route::group(['middleware' => ['permission:201']], function ($router) {
        $router->post('/mall/removelinkspu', 'Api\MallController@removeLinkSPUs');
    });


    Route::group(['middleware' => ['permission:161']], function ($router) {
        $router->post('/mall/updateskuprice', 'Api\MallController@updateSkuPrice');
    });
    Route::group(['middleware' => ['permission:156']], function ($router) {
        $router->post('/mall/getlinkedskuList', 'Api\MallController@getMpNameLinkedSkuList');
        // 批量下载海报
        $router->post('downloadPoster', 'ApiShopMp\SPUController@shareGoodsImage');
    });
    Route::group(['middleware' => ['permission:158']], function ($router) {
        $router->post('/mall/getlinkedspulist', 'Api\MallController@getMpNameLinkedSpuList');
        $router->post('/mall/getunlinkedspuList', 'Api\MallController@getMpNameUnLinkedSpuList');
        $router->post('/mall/setSortIndex', 'Api\MallController@setSortIndex');
    });

    Route::group(['middleware' => ['permission:160']], function ($router) {
        $router->post('/mall/skushowon', 'Api\MallController@skuShowPutOn');
        $router->post('/mall/skushowoff', 'Api\MallController@skuShowPutOff');
    });
    //代理商配置
    Route::group(['middleware' => ['permission:140']], function ($router) {
        $router->post('/mall/getagentlinkedspuList', 'Api\MallController@getAgentLinkedSpuList');
        $router->post('/mall/getagentunlinkedspuList', 'Api\MallController@getAgentUnLinkedSpuList');
    });
    Route::group(['middleware' => ['permission:196']], function ($router) {
        $router->post('/mall/addagentlinkspu', 'Api\MallController@addAgentLinkSPUs');
    });
    Route::group(['middleware' => ['permission:208']], function ($router) {
        $router->post('/mall/removeagentlinkspu', 'Api\MallController@removeAgentLinkSPUs');
    });
    Route::group(['middleware' => ['permission:197']], function ($router) {
        $router->post('/mall/updateagentskuputon', 'Api\MallController@updateAgentSkuPutOn');
        $router->post('/mall/updateagentskuputoff', 'Api\MallController@updateAgentSkuPutOff');
    });
    Route::group(['middleware' => ['permission:198']], function ($router) {
        $router->post('/mall/updateagentskuprice', 'Api\MallController@updateAgentSkuPrice');
    });


    Route::group(['middleware' => ['permission:89']], function ($router) {
        $router->post('/mall/getbasicsetting', 'Api\MallController@getAgentBasicSetting');
        $router->post('/mall/updatebasicsetting', 'Api\MallController@updateAgentBasicSetting');
    });

    Route::group(['middleware' => ['permission:141']], function ($router) {
        $router->post('/category/agentadd', 'Api\MallController@addAgentCategory');
    });
    Route::group(['middleware' => ['permission:142']], function ($router) {
        $router->post('/category/agentcategoryinfo', 'Api\MallController@agentCategoryInfo');
        $router->post('/category/agentedit', 'Api\MallController@editAgentCategory');
    });
    Route::group(['middleware' => ['permission:143']], function ($router) {
        $router->post('/category/agentdel', 'Api\MallController@delAgentCategory');
    });
    // 代理分类相关接口
    Route::group(['middleware' => ['permission:151']], function ($router) {
        $router->post('/warehouse/mpnameadd','Api\ConfigureController@addMpName');
    });
    Route::group(['middleware' => ['permission:152']], function ($router) {
        $router->post('/warehouse/mpnamedel','Api\ConfigureController@deleteMpName');
    });
    Route::group(['middleware' => ['permission:153']], function ($router) {
        $router->post('/warehouse/mpnameedit','Api\ConfigureController@editMpName');
    });
    Route::group(['middleware' => ['permission:154']], function ($router) {
        $router->post('/warehouse/mpnamelist','Api\ConfigureController@mpNameList');
    });
    Route::group(['middleware' => ['permission:155']], function ($router) {
        $router->post('/warehouse/mpnameinfo','Api\ConfigureController@mpNameInfo');
    });


    // 生成小程序码(测试)
    Route::group(['middleware' => ['permission:146']], function ($router) {
        $router->post('mpQRCode', 'Api\BusinessController@mpQRCode');
    });

    Route::group(['middleware' => ['permission:187']], function ($router) {
        //仓库发货详情
        $router->post('warehouseOrder/sendOrderInfo','Api\WareHouseOrderController@sendOrderInfo');
        //仓库发货 处理
        $router->post('warehouseOrder/sendOrderInfoRes','Api\WareHouseOrderController@sendOrderInfoRes');
        //仓库发货 - 配货单
        $router->post('warehouseOrder/peihuo','Api\WareHouseOrderController@peihuo');
        //仓库发货 - 打印快递单
        $router->any('warehouseOrder/printPage','Api\WareHouseOrderController@printPage');
    });

    Route::group(['middleware' => ['permission:186']], function ($router) {
        $router->post('warehouseOrder/theList', 'Api\WareHouseOrderController@theList');
    });
    $router->post('port/info','Api\ConfigureController@portInfo');
    $router->post('business/info','Api\BusinessController@info');
    $router->post('port/theList','Api\ConfigureController@portList');
    $router->post('wxuser/theList','Api\WxUserController@theList');
    Route::group(['middleware' => ['permission:231']], function ($router) {
        $router->post('wxuser/adminUserData', 'Api\WxUserController@adminUserData');
        $router->post('wxuser/adminUserWeekData', 'Api\WxUserController@adminUserWeekData');
    });
    $router->post('supplier/theList','Api\ConfigureController@supplierList');
    //通过事业部id 获取账户
    $router->post('account/getAccount','Api\AccountController@getAccount');
    //删除物流单号
    $router->post('delivery/deleteLogistics','Api\DeliveryController@deleteLogistics');
    $router->post('productList/theList','Api\ProductListController@theList');
    $router->post('users/theList','Api\UsersController@theList');
    Route::group(['middleware' => ['permission:235']], function ($router) {
        $router->post('users/banUser','Api\UsersController@banUser');
        $router->post('users/unbanUser','Api\UsersController@unbanUser');
    });
    $router->post('config/sendAddress','Api\ConfigureController@sendAddress');
    $router->post('config/editSendAddress','Api\ConfigureController@editSendAddress');

    //营销相关
    $router->post('market/sku/tags','Api\MarketController@skutags');
    $router->post('market/sku/cats','Api\MarketController@skucats');
    $router->post('market/sku/mpnames','Api\MarketController@skumpnames');
    $router->post('market/sku/mpskus','Api\MarketController@mpskus');
    $router->post('market/spu/unions','Api\MarketController@unions');
    //----运费查增改删
    Route::group(['middleware' => ['permission:222']], function ($router) {
        $router->post('market/freight/list','Api\MarketController@freightList');
        $router->post('market/freight/skulist','Api\MarketController@freightSkuList');
    });
    Route::group(['middleware' => ['permission:223']], function ($router) {
        $router->post('market/freight/add','Api\MarketController@freightAdd');
    });
    Route::group(['middleware' => ['permission:224']], function ($router) {
        $router->post('market/freight/edit','Api\MarketController@freightEdit');
    });
    Route::group(['middleware' => ['permission:225']], function ($router) {
        $router->post('market/freight/delete','Api\MarketController@freightDelete');
    });
    //----优惠券查增改删
    Route::group(['middleware' => ['permission:239']], function ($router) {
        $router->post('market/coupon/list','Api\MarketController@couponList');
        $router->post('market/coupon/skulist','Api\MarketController@couponSkuList');
        $router->post('market/coupon/useAndReceiveDetail','Api\MarketController@couponUseAndReceiveDetail');
    });
    Route::group(['middleware' => ['permission:236']], function ($router) {
        $router->post('market/coupon/add','Api\MarketController@couponAdd');
    });
    Route::group(['middleware' => ['permission:238']], function ($router) {
        $router->post('market/coupon/edit','Api\MarketController@couponEdit');
    });
    Route::group(['middleware' => ['permission:237']], function ($router) {
        $router->post('market/coupon/delete','Api\MarketController@couponDelete');
    });
    //----团购查增改删
    Route::group(['middleware' => []], function ($router) {
        $router->post('market/groupBuy/normalList','Api\MarketController@groupBuyNormalList');
        $router->post('market/groupBuy/unionList','Api\MarketController@groupBuyUnionList');
        $router->post('market/groupBuy/detail','Api\MarketController@groupBuyDetail');
    });
    Route::group(['middleware' => []], function ($router) {
        $router->post('market/groupBuy/add','Api\MarketController@groupBuyAdd');
        $router->post('market/groupBuy/spuList','Api\MarketController@groupBuyCanAddSpuList');
        $router->post('market/groupBuy/getSkus','Api\MarketController@groupGetSkusByLinkIds');
    });
    Route::group(['middleware' => []], function ($router) {
        $router->post('market/groupBuy/edit','Api\MarketController@groupBuyEdit');
    });
    Route::group(['middleware' => []], function ($router) {
        $router->post('market/groupBuy/delete','Api\MarketController@groupBuyDelete');
    });
    //营销相关结束

    //退款申请相关(权限待配)
    //列表
    Route::group([], function ($router) {
        $router->post('refund','Api\StockOrderController@refund');
    });
    //审核操作
    Route::group([], function ($router) {
        $router->post('refund/edit','Api\StockOrderController@refundEdit');
    });
    //退款申请相关结束

    Route::group(['middleware' => ['permission:205']], function ($router) {
        //限时特价列表
        $router->get('special','Api\SpecialPriceController@index');
    });
    //限时特价生成长图
    Route::any('makeSpecialLongPic','Api\LongPicController@makeSpecialLongPic');
    Route::group(['middleware' => ['permission:206']], function ($router) {
        //可添加的限时特价列表
        $router->get('specialAddList', 'Api\SpecialPriceController@addList');
        //可添加的套餐限时特价列表
        $router->get('specialAddUnionList','Api\SpecialPriceController@addUnionList');
        $router->post('specialByLinkId','Api\SpecialPriceController@specialByLinkId');
        //限时特价添加
        $router->post('special','Api\SpecialPriceController@store');
        $router->post('specialUnion','Api\SpecialPriceController@storeUnion');
        //关联营销运费
        $router->post('special/freight/link','Api\SpecialPriceController@freightLink');
    });
    Route::group(['middleware' => ['permission:207']], function ($router) {
        //限时特价删除
        $router->post('special/delete', 'Api\SpecialPriceController@destroy');
    });
    Route::group(['middleware' => ['permission:179']], function ($router) {
        //增加类别、品牌 系列
        $router->post('configure/addProductClass','Api\ConfigureController@addProductClass');
    });
    Route::group(['middleware' => ['permission:180']], function ($router) {
        $router->post('configure/editProductClass','Api\ConfigureController@editProductClass');
    });

    //客户充值
    Route::group(['middleware' => ['permission:215']], function ($router) {
        $router->post('recharge','Api\WxUserController@recharge');
    });
    Route::group(['middleware' => ['permission:216']], function ($router) {
        $router->post('consume','Api\WxUserController@consume');
    });
    Route::group(['middleware' => ['permission:217']], function ($router) {
        $router->post('priceLog','Api\WxUserController@priceLog');
    });

    // *********运营报表**********
    // 概览接口
    Route::group(['middleware' => ['permission:230']], function ($router) {
        // 日常数据
        $router->get('eventTracking/dailyDetail', 'Api\EventTrackingController@dailyDetail');
        // 近八周的数据
        $router->get('eventTracking/weeksData', 'Api\EventTrackingController@weeksData');
        // 年度数据
        $router->get('eventTracking/yearData', 'Api\EventTrackingController@yearData');
        // 埋点报表销售详情
        $router->get('eventTracking/detail', 'Api\EventTrackingController@saleDetail');
    });
    // 待付款报表
    Route::group(['middleware' => ['permission:240']], function ($router) {
        // 未付款列表
        $router->get('arrearageList', 'Api\OperationsController@arrearage');
        // 待付款报表的详情
        $router->get('arrearageDetail', 'Api\OperationsController@arrearageDetail');
    });
    // 用户消费报表
    Route::group(['middleware' => ['permission:241']], function ($router) {
        // 用户消费列表
        $router->get('usersConsumption', 'Api\OperationsController@consumption');
        // 消费详情
        $router->get('consumptionDetail', 'Api\OperationsController@consumptionDetail');
    });
    // 商品报表
    Route::group(['middleware' => ['permission:242']], function ($router) {
        // 商品列表
        $router->get('goodsReport', 'Api\OperationsController@goodsReport');
    });
    // 访客报表
    Route::group(['middleware' => ['permission:243']], function ($router) {
        // 客户访问记录统计
        $router->get('visitRecord', 'Api\OperationsController@visitorReoprt');
        // 客户访问详情
        $router->get('visitRecordDetail', 'Api\OperationsController@visitRecordDetail');
    });
    // 特价商品报表
    Route::group(['middleware' => ['permission:245']], function ($router) {
        // 特价商品七天的数据
        $router->get('specialDailyData', 'Api\OperationsController@specialGoodsReport');
        // 特价商品近八周的数据
        $router->get('specialWeeksData', 'Api\OperationsController@specialWeeksData');
        // 特价商品年度数据
        $router->get('specialYearData', 'Api\OperationsController@specialYearData');
        // 特价商品销售详情
        $router->get('specialSaleDetail', 'Api\OperationsController@specialSaleDetail');
    });
    // 用户留存报表
    Route::group(['middleware' => ['permission:244']], function ($router) {
        // 用户留存列表
        $router->get('userRetentionReport', 'Api\OperationsController@userRetentionReport');
        // 用户留存表中的新用户
        $router->get('userRetentionReport/newUsers', 'Api\OperationsController@newUsers');
        // 剩余留存的用户
        $router->post('userRetentionReport/retentionUsers', 'Api\OperationsController@retentionDetail');
    });
    // 关键字报表
    Route::group(['middleware' => ['permission:247']], function ($router) {
        $router->get('/keywords', 'Api\OperationsController@keywordsReport');
    });

    //商城海报
    Route::group(['middleware' => ['permission:227']], function ($router) {
        $router->post('/backAdd', 'Api\ShareBackController@backAdd');
        $router->post('/backDel', 'Api\ShareBackController@backDel');
        $router->post('/backList', 'Api\ShareBackController@backList');
        $router->post('/backShow', 'Api\ShareBackController@backShow');
    });
    //代上架清单
    Route::group(['middleware' => ['permission:229']], function ($router) {
        Route::post('stock/GoodsDownRecord', 'Api\StockController@GoodsDownRecord');
    });
    /*                                                  全局商城配置                                                   */
    //支付方式
    $router->post('payMethod','Api\ConfigureController@payMethod');
    //用户充值方式配置
    $router->post('priceLogType','Api\ConfigureController@priceLogType');
    $router->post('config/sendAddress','Api\ConfigureController@sendAddress');
    $router->post('users/editUser','Api\UsersController@editUser');
    $router->post('purchaseOrder/orderInfo','Api\PurchaseOrderListController@orderInfo');
    //编辑采购订单
    $router->post('purchaseOrder/editPurchaseOrder','Api\PurchaseOrderListController@editPurchaseOrder');
     //维护明细-采购订单维护-采购订单
    //采购订单付款详情
    $router->post('payPurchaseOrder/payOrderInfo','Api\PurchaseOrderListController@payOrderInfo');
    //撤销采购订单付款
    $router->post('payPurchaseOrder/cancelPayRecord','Api\PurchaseOrderListController@cancelPayRecord');
    //撤销付款
    $router->post('payLogistics/cancelPayRecord','Api\DeliveryController@cancelPayRecord');
    //员工销售月报
    $router->post('stockOrder/saleSheetMonth','Api\StockOrderController@saleSheetMonth');
    //员工销售周报
    $router->post('stockOrder/saleSheetWeek','Api\StockOrderController@saleSheetWeek');
    // SPU
    $router->post('/spu/findwhid','Api\SPUController@findWarehouseId');
    $router->post('/spu/add','Api\SPUController@addSPU');
    $router->post('/spu/edit','Api\SPUController@editSPU');
    $router->post('/spu/batchLimitBuyEdit','Api\SPUController@batchLimitBuyEdit');
    $router->post('/spu/delete','Api\SPUController@deleteSPU');
    $router->post('/sku/maintainPrice','Api\SKUController@maintainPrice');
    // 批量上架接口
    $router->post('/skus/putOn','Api\SKUController@putOnSKUs');
    // 批量下架接口
    $router->post('/skus/putOff','Api\SKUController@putOffSKUs');
    $router->post('stock/receiveGoodsList','Api\StockController@receiveGoodsList');
    $router->post('/mall/putonskus','Api\MallController@putOnSKUs');
    $router->post('/mall/putoffskus','Api\MallController@putOffSKUs');
    $router->post('/mall/getmpnamelist', 'Api\MallController@getMpNameList');
    $router->post('/spu/get','Api\SPUController@getSPU');
    $router->post('/mall/getagentlinkedskuList', 'Api\MallController@getAgentLinkedSkuList');
    $router->post('stock/getUnLinkSKUList','Api\StockController@getUnLinkSKUList'); //未关联用
    $router->post('/mall/getbusinessnamelist', 'Api\MallController@getBusinessNameList');
    //仓库退货
    $router->any('warehouseOrder/returnGoods','Api\WareHouseOrderController@returnGoods');
    Route::post('menu', 'Api\UsersController@menu');
    $router->post('/spu/list', 'Api\SPUController@theSPUList');
    $router->post('/tags/list','Api\TagsController@theTagsList');
    $router->post('/category/categoryInfo','Api\SPUCategoryController@categoryInfo');
    //权限列表
    $router->post('permission/permissionList','Api\PermissionController@PermissionList');
    //全局配置
    //币种
    $router->post('configure/currency','Api\ConfigureController@currency');
    //快递类型
    $router->post('configure/getExpressType','Api\ConfigureController@getExpressType');
    //属性
    $router->post('configure/attribute','Api\ConfigureController@attribute');
    //采购类型
    $router->post('configure/purchaseType','Api\ConfigureController@purchaseType');
    //运费类型
    $router->post('configure/freightType','Api\ConfigureController@freightType');
    //物流单号 订单状态
    $router->post('configure/getLogisticsStatus','Api\ConfigureController@getLogisticsStatus');
    //智能拆分地址
    $router->post('address/getExtAddress','Api\DeliveryController@getExtAddress');
    //商城管理
    //价格维护
    $router->post('mall/priceManage','Api\MallController@priceManage');
    //分类
    $router->post('configure/productClass','Api\ConfigureController@productClass');
    //账户详情
    $router->post('account/info','Api\AccountController@info');
    $router->post('warehouse/theList','Api\ConfigureController@wareHouseList');
    $router->post('configure/simpleMpList','Api\ConfigureController@simpleMpList');
    $router->post('storehouse/theList','Api\ConfigureController@storehouseList');
    //以树的方式获取库位
    $router->post('storehouse/treeList','Api\ConfigureController@storehouseTreeList');

    $router->post('account/theList','Api\AccountController@theList');
    $router->post('stockOrder/theList','Api\StockOrderController@theList');
    $router->post('/category/list','Api\SPUCategoryController@getCategoryTree');
    //库存销售详情
    $router->post('stockOrder/stockOrderInfo','Api\StockOrderController@stockOrderInfo');
    $router->post('business/info','Api\BusinessController@info');

    $router->post('stock/getSKUList','Api\StockController@getSKUList');
    // 获取oss直传签名sign
    $router->get('ossSign','OssController@getSign');

    //仓库发货详情
    $router->post('warehouseOrder/sendOrderInfo','Api\WareHouseOrderController@sendOrderInfo');

    // 事业部代理商品
    $router->post('/businesslinkspu/list','Api\BusinessSPUController@theList');
    $router->post('/businesslinkspu/add','Api\BusinessSPUController@addBusinessLinkSPUs');
    $router->post('/businesslinkspu/del','Api\BusinessSPUController@delBusinessLinkSPUs');
    // 商城配置
    $router->post('/mall/info','Api\MallController@mallsettingInfo');
    $router->post('/mall/add','Api\MallController@mallsettingAdd');
    $router->post('/mall/edit','Api\MallController@mallsettingUpdate');
    $router->post('/mall/del','Api\MallController@mallsettingDelete');
    // 代理配置
    $router->post('/mall/agenthas','Api\MallController@agentAlreadyList');
    $router->post('/mall/agentno','Api\MallController@agentNoList');
    $router->post('/mall/agentadd','Api\MallController@agentBatchAdd');
    $router->post('/mall/agentdel','Api\MallController@agentBatchDel');
    $router->post('/mall/agentisall','Api\MallController@agentAllUpdate');

    $router->post('port/info','Api\ConfigureController@portInfo');

    $router->post('port/theList','Api\ConfigureController@portList');
    Route::group(['middleware' => ['permission:232']], function ($router) {
        $router->post('wxuser/bindAdminUser','Api\WxUserController@bindAdminUser');
    });
    Route::group(['middleware' => ['permission:233']], function ($router) {
        $router->post('wxuser/unbindAdminUser','Api\WxUserController@unbindAdminUser');
    });
    Route::group(['middleware' => ['permission:234']], function ($router) {
        $router->post('wxuser/removeAdminUser','Api\WxUserController@removeAdminUser');
    });
    $router->post('supplier/theList','Api\ConfigureController@supplierList');
    //通过事业部id 获取账户
    $router->post('account/getAccount','Api\AccountController@getAccount');
    //删除物流单号
    $router->post('delivery/deleteLogistics','Api\DeliveryController@deleteLogistics');

    $router->post('/mall/skusUnion','Api\MallController@skusUnion');//套餐管理页面
    $router->post('/mall/skusUnionStore','Api\MallController@skusUnionStore');//套餐管理结果保存
    $router->post('/mall/skusUnionFlag','Api\MallController@skusUnionFlag');//馆区套餐状态设置


    $router->post('/mall/sputagslist','Api\MallController@theSpuOfTagsList');
    $router->post('/warehouse/freighttagslist','Api\ConfigureController@theFreightOfTagsList');
    $router->post('/warehouse/isprovincesall','Api\ConfigureController@isProvincesAll');
    $router->post('/mall/changeBannerSort', 'Api\MallController@changeBannerSort');
    $router->get('/mall/searchProduct', 'Api\MallController@searchProduct');
    //销售单退货
    $router->post('stockOrder/backOrderList','Api\StockOrderController@backOrderList');
    $router->post('supplier/info','Api\ConfigureController@supplierInfo');
    $router->post('warehouse/info','Api\ConfigureController@wareHouseInfo');
    $router->post('users/getUserInfo','Api\UsersController@getUserInfo');
    $router->post('warehouseOrder/theList','Api\WareHouseOrderController@theList');
    //库存销售 编辑
    $router->post('stockOrder/editStockOrder','Api\StockOrderController@editStockOrder');
    //仓库发货 - 打印快递单
    $router->any('warehouseOrder/printPage','Api\WareHouseOrderController@printPage');
    //仓库发货 处理
    $router->post('warehouseOrder/sendOrderInfoRes','Api\WareHouseOrderController@sendOrderInfoRes');
    //仓库发货 - 配货单
    $router->post('warehouseOrder/peihuo','Api\WareHouseOrderController@peihuo');
    // 代理分类相关接口
    $router->post('/category/agentlist','Api\MallController@getAgentCategoryList');
});





/**
 * 商城小程序
 */
Route::group([
    'prefix' => 'shop_mp'
], function ($router) {

    // 代理版接口
    Route::group([
        'prefix' => 'agent'
    ], function ($router) {
        // 无需验证
        Route::middleware('ShopMpAgentRender')->group(function($router) {
            // 用户校验
            Route::get('check','ApiShopMpAgent\AuthController@check');
            // 微信登录
            Route::post('login','ApiShopMpAgent\AuthController@login');
            // 微信获取参数调试
            Route::post('debug','ApiShopMpAgent\TestController@debug');

            Route::get('test','ApiShopMpAgent\TestController@test');

            //跨境支付异步回调地址
            Route::any('pay/notify','ApiShopMpAgent\PayCenterController@asynNotify')->name('pay.asyn');
        });

        // 需要验证
        Route::middleware('jwt.api.wx', 'ShopMpAgentRender')->group(function($router) {
            // 首页
            Route::get('home','ApiShopMpAgent\TabController@home');
            // 分类
            Route::get('categories','ApiShopMpAgent\TabController@categories');

            // ️️商品列表
            Route::get('spus','ApiShopMpAgent\SPUController@spus');
            //商品搜索
            Route::get('spus/search','ApiShopMpAgent\SPUController@spusSearch');
            // ️️商品详情
            Route::get('spus/{id}','ApiShopMpAgent\SPUController@spu');

            // ️️购物车-列表
            Route::get('cart','ApiShopMpAgent\CartController@list');
            // 购物车-加入
            Route::post('cart','ApiShopMpAgent\CartController@add');
            // 购物车-删除
            Route::delete('cart/{id}','ApiShopMpAgent\CartController@delete');
            // 购物车-修改-购买数量
            Route::put('cart/{id}','ApiShopMpAgent\CartController@update');
            // ️️️️购物车-批量删除
            Route::post('cart/delete','ApiShopMpAgent\CartController@deleteAll');

            // ️️新增身份证
            Route::post('identitycard','ApiShopMp\
            @create');
            // 删除身份证
            Route::delete('identitycard/{id}','ApiShopMp\IdentityCardController@del');
            // 编辑身份证
            Route::put('identitycard/{id}','ApiShopMp\IdentityCardController@edit');
            // 身份证列表
            Route::get('identitycard','ApiShopMp\IdentityCardController@list');

            // ️️新增地址
            Route::post('address','ApiShopMp\AddressController@create');
            // 删除地址
            Route::delete('address/{id}','ApiShopMp\AddressController@del');
            // 编辑地址
            Route::put('address/{id}','ApiShopMp\AddressController@edit');
            // 地址列表
            Route::get('address','ApiShopMp\AddressController@list');
            // 智能解析
            Route::post('address/smart','ApiShopMp\AddressController@smart');

            // 上传接口
            Route::post('file/upload','ApiShopMp\UploadController@upload');

            // 填写订单信息
            Route::post('order/fill','ApiShopMpAgent\OrderController@fill');
            // 直接下单-到填写订单信息
            Route::post('order/direct','ApiShopMpAgent\OrderController@direct');
            // ️️提交订单
            Route::post('order','ApiShopMpAgent\OrderController@order');
            // 取消订单
            Route::post('order/cancel','ApiShopMpAgent\OrderController@cancelOrder');
            // ️️订单详情
            Route::get('order/{id}','ApiShopMpAgent\OrderController@detail');
            // 订单列表
            Route::get('orders','ApiShopMpAgent\OrderController@list');

            // 支付页面
            Route::get('pay/show/{orders}','ApiShopMpAgent\PayCenterController@showPay');
            // 支付（发起汇付跨境支付）
            Route::get('pay/{orders}','ApiShopMpAgent\PayCenterController@pay');
            // 订单支付结果查询
            Route::get('pay/result/{orders}','ApiShopMpAgent\PayCenterController@payResultQuery');

            // 获取oss直传签名sign
            Route::get('ossSign','OssController@getSign');

            // 用户中心
            // Route::get('user/center','ApiShopMp\UserCenterController@get');
        });
    });
    // 自营接口

    /**
     * 不需验证
     */
    Route::middleware('shopMpRender')->group(function($router) {
        // 用户校验
        Route::get('check','ApiShopMp\AuthController@check');
        // 微信登录
        Route::post('login','ApiShopMp\AuthController@login');
        // 小程序配置
        Route::get('config','ApiShopMp\SettingsController@config');

        //测试使用
        Route::any('test','ApiShopMp\TestController@test');
        Route::any('test1','ApiShopMp\TestController@test1');

        //跨境支付异步回调地址
        Route::any('pay/notify','ApiShopMp\PayCenterController@payNotify')->name('shopPay.asyn');
        //跨境支付充值异步回调地址
        Route::any('pay/rechargeNotify','ApiShopMp\PayCenterController@rechargeNotify')->name('shopRecharge.asyn');

        //登录(新)
        Route::post('signIn','ApiShopMp\AuthController@signIn');
    });

    /**
     * 需验证是否有token(即验证是否登录,包括游客)
     */
    Route::middleware('jwt.api.wx', 'shopMpRender')->group(function($router) {
        //授权(新)
        Route::post('auth','ApiShopMp\AuthController@auth');
        // 首页
        Route::get('home','ApiShopMp\TabController@home');
        // 分类
        Route::get('categories','ApiShopMp\TabController@categories');
        // ️会员
        Route::get('vip','ApiShopMp\TabController@vip');
        // ️️商品列表
        Route::get('spus','ApiShopMp\SPUController@spus');
        //商品搜索
        Route::get('spus/search','ApiShopMp\SPUController@spusSearch');
        // ️️商品详情
        Route::get('spus/{id}','ApiShopMp\SPUController@spu');
        //限时特价列表
        Route::get('special','ApiShopMp\SpecialPriceController@index');
        //限时特价详情
        Route::get('special/{id}','ApiShopMp\SpecialPriceController@specialSpu');
        // 用户中心
        Route::get('user/center','ApiShopMp\UserCenterController@get');
        // 购物车-获取当前数量
        Route::get('cert/num','ApiShopMp\CartController@num');
        // 商品详情分享的图片
        Route::get('shareMessageImage', 'ApiShopMp\SPUController@shareMessageImage');
        // 商品详情水印图片
        Route::post('shareGoodsImage', 'ApiShopMp\SPUController@shareGoodsImage');
        //团购列表
        Route::post('groups','ApiShopMp\GroupController@index');
        //团购商品详情
        Route::get('group/{id}','ApiShopMp\GroupController@show');

        //验证是否游客(即验证是否授权)
        Route::group([
            'middleware' => 'checkIsVisitor'
        ], function ($router) {
            //余额消费记录
            Route::get('userPayLog','ApiShopMp\UserCenterController@userPayLog');
            // ️️购物车-列表
            Route::get('cert/list','ApiShopMp\CartController@list');
            // 购物车-加入
            Route::post('cert/add','ApiShopMp\CartController@add');
            // ️️️️购物车-批量删除
            Route::post('cert/delete','ApiShopMp\CartController@deleteAll');
            // 购物车-删除
            Route::delete('cert/{id}','ApiShopMp\CartController@delete');
            // 购物车-修改-购买数量
            Route::put('cert/update','ApiShopMp\CartController@update');
            // ️️新增身份证
            Route::post('identitycard','ApiShopMp\IdentityCardController@create');
            // 删除身份证
            Route::delete('identitycard/{id}','ApiShopMp\IdentityCardController@del');
            // 编辑身份证
            Route::put('identitycard/{id}','ApiShopMp\IdentityCardController@edit');
            // 身份证列表
            Route::get('identitycard/list','ApiShopMp\IdentityCardController@list');
            // ️️新增地址
            Route::post('address','ApiShopMp\AddressController@create');
            // 删除地址
            Route::delete('address/{id}','ApiShopMp\AddressController@del');
            // 编辑地址
            Route::put('address/{id}','ApiShopMp\AddressController@edit');
            // 地址列表
            Route::get('address/list','ApiShopMp\AddressController@list');
            // ️️新增发货地址
            Route::post('sendAddress','ApiShopMp\AddressController@createSend');
            // 删除发货地址
            Route::delete('sendAddress/{id}','ApiShopMp\AddressController@delSend');
            // 编辑发货地址
            Route::put('sendAddress/{id}','ApiShopMp\AddressController@editSend');
            // 发货地址列表
            Route::get('sendAddress/list','ApiShopMp\AddressController@listSend');
            // 智能解析
            Route::post('address/smart','ApiShopMp\AddressController@smart');
            // 上传接口
            Route::post('file/upload','ApiShopMp\UploadController@upload');
            // 填写订单信息
            Route::post('order/fill','ApiShopMp\OrderController@fill');
            // 更新运费信息
            Route::post('order/expressPrice','ApiShopMp\OrderController@expressPrice');
            // 直接下单-到填写订单信息
            Route::post('order/direct','ApiShopMp\OrderController@direct');
            // ️️提交订单
            Route::post('order','ApiShopMp\OrderController@order');
            // 取消订单
            Route::post('order/cancel','ApiShopMp\OrderController@cancelOrder');
            // ️️订单详情
            Route::get('order/{id}','ApiShopMp\OrderController@detail');
            // 订单列表
            Route::get('orders','ApiShopMp\OrderController@list');
            // 支付
            Route::post('pay','ApiShopMp\PayCenterController@pay');
            //账号充值
            Route::post('rechargePay','ApiShopMp\PayCenterController@rechargePay');
            // 支付页面
            Route::post('pay/show','ApiShopMp\PayCenterController@showPay');
            // 订单支付结果查询
            Route::post('pay/result','ApiShopMp\PayCenterController@payResultQuery');
            //申请退款
            Route::post('refund','ApiShopMp\OrderController@refund');
            //优惠券列表
            Route::post('coupons','ApiShopMp\CouponController@index');
            //优惠券领取列表
            Route::post('coupons/receive/list','ApiShopMp\CouponController@receiveList');
            //优惠券领取
            Route::post('coupons/receive','ApiShopMp\CouponController@receive');
            //我的团购列表
            Route::post('my/groups','ApiShopMp\GroupController@myGroups');
            //开团详情
            Route::get('group/open/{id}','ApiShopMp\GroupController@openGroup');
            //团购邀请好友加入
            Route::post('inviteGroup', 'ApiShopMp\GroupController@inviteGroup');

            //小程序分享信息通用接口
            Route::get('share/{type}/message', 'ApiShopMp\ShareMessageController@message');

            //物流详情
            Route::get('express/{order_id}','ApiShopMp\ExpressController@show');

            // 获取oss直传签名sign
            Route::get('ossSign','OssController@getSign');

            // Route::any('productList','ApiShopMp\ProductListController@theList');
            // Route::any('productDetail','ApiShopMp\ProductListController@detail');

            Route::any('makeLongShareSpecial','ApiShopMp\SharePicController@makeLongShareSpecial');
            Route::any('makeLongShareNormal','ApiShopMp\SharePicController@makeLongShareNormal');

        });

    });

});




//物流小程序路由 v1版本
Route::group([
    'prefix' => 'mpv1'
], function ($router) {
    Route::middleware('mpRender')->group(function($router) {
//wx.login回调函数
        Route::any('mpLogin','ApiMpV1\AuthController@mpLogin');
        Route::any('code2Session','ApiMpV1\AuthController@code2Session');
//扫码 输入 包裹编号 返回扫码结果
        Route::any('scanCode','ApiMpV1\PackageController@scanCode');
        Route::any('selectMode','ApiMpV1\PackageController@selectMode');
        Route::any('updateMode','ApiMpV1\PackageController@updateMode');
        Route::any('addPackageInfo','ApiMpV1\PackageController@addPackageInfo');




        //新扫描流程
        //发货方式选择页面
        Route::any('selectModeV2','ApiMpV1\PackageV2Controller@selectMode');
        //选择包裹 更新发货方式到包裹
        Route::any('updateModeV2','ApiMpV1\PackageV2Controller@updateMode');
        //物品申报页面 需要绑定
        Route::any('declareGoodsV2','ApiMpV1\PackageV2Controller@declareGoods');
        //绑定包裹
        Route::any('bindTempPackageV2','ApiMpV1\PackageV2Controller@bindTempPackage');

//获取openid
        Route::any('getOpenid','ApiMpV1\AuthController@getOpenid');
        Route::any('reg','ApiMpV1\AuthController@reg');

//地址维护
//地址列表
        Route::any('addressList','ApiMpV1\AddressController@theList');
//智能拆分地址
        Route::any('getExtAddress','ApiMpV1\AddressController@getExtAddress');
//新增地址
        Route::any('addAddress','ApiMpV1\AddressController@addAddress');
//编辑地址详情！
        Route::any('addressInfo','ApiMpV1\AddressController@addressInfo');
//编辑地址提交
        Route::any('editAddress','ApiMpV1\AddressController@editAddress');
//删除地址
        Route::any('deleteAddress','ApiMpV1\AddressController@deleteAddress');



        //invoice地址库
        //地址列表
        Route::any('invoice/addressList','ApiMpV1\InvoiceAddressController@theList');
//新增地址
        Route::any('invoice/addAddress','ApiMpV1\InvoiceAddressController@addAddress');
//编辑地址详情！
        Route::any('invoice/addressInfo','ApiMpV1\InvoiceAddressController@addressInfo');
//编辑地址提交
        Route::any('invoice/editAddress','ApiMpV1\InvoiceAddressController@editAddress');
//删除地址
        Route::any('invoice/deleteAddress','ApiMpV1\InvoiceAddressController@deleteAddress');


//地址id绑定箱子编号
        Route::any('bindAddressId','ApiMpV1\PackageController@bindAddressId');
//货物清单扫描
        Route::any('scanGoods','ApiMpV1\PackageController@scanGoods');
//货物清单扫描 -> 编辑详情
        Route::any('getGoodsInfo','ApiMpV1\PackageController@getGoodsInfo');
//货物清单扫描 -> 编辑结果
        Route::any('editGoodsInfo','ApiMpV1\PackageController@editGoodsInfo');
//货物清单 删除
        Route::any('deleteGoods','ApiMpV1\PackageController@deleteGoods');
//货物清单list
        Route::any('scanGoodsList','ApiMpV1\PackageController@scanGoodsList');

        Route::any('editGoodsNumber','ApiMpV1\PackageController@editGoodsNumber');
//货物清单扫描结束
        Route::any('endGoodsList','ApiMpV1\PackageController@endGoodsList');
//上传图片
        Route::any('savePhotos','ApiMpV1\PackageController@savePhotos');
//图片列表
        Route::any('photoLists','ApiMpV1\PackageController@photoLists');
//删除图片
        Route::any('deletePhoto','ApiMpV1\PackageController@deletePhoto');
//设置未装箱图片
        Route::any('setPhoto','ApiMpV1\PackageController@setPhoto');
//提交照片
        Route::any('submitPhotos','ApiMpV1\PackageController@submitPhotos');
//列表
        Route::any('orderList','ApiMpV1\PackageController@orderList');

        Route::any('orderListV0516','ApiMpV1\PackageController@orderListV0516');

        Route::any('payTax','ApiMpV1\PackageController@payTax');
        Route::any('changePackageAddress','ApiMpV1\PackageController@changePackageAddress');

        Route::any('getOrderListNum','ApiMpV1\PackageController@getOrderListNum');
//支付
        Route::any('payOrder','ApiMpV1\PackageController@payOrder');

//2019-02-14 新增
        Route::any('payOrderNew','ApiMpV1\PackageController@payOrderNew');
//选择支付方式
        Route::any('selectPayMethod','ApiMpV1\PackageController@selectPayMethod');
//余额支付
        Route::any('payBalance','ApiMpV1\PackageController@payBalance');

        //批量付款
        Route::any('payOrderBatch','ApiMpV1\PackageController@payOrderBatch');






        Route::any('priceRecord','ApiMpV1\UserController@priceRecord');




//国际物流- 我已安排物流
        Route::any('uploadRepertoryPhoto','ApiMpV1\InputController@uploadRepertoryPhoto');
//送货上门提交
        Route::any('submitDoor','ApiMpV1\InputController@submitDoor');
        //预约上门打包
        Route::any('subscribeDoor','ApiMpV1\InputController@subscribeDoor');
//安排提货
        Route::any('takeGoods','ApiMpV1\InputController@takeGoods');
        //预约国际物流
        Route::any('intLogistics','ApiMpV1\InputController@intLogistics');

        Route::any('getCurrencySetting','ApiMpV1\InputController@getCurrencySetting');


//生成二维码
        Route::any('makeQrCode','ApiMpV1\UserController@makeQrCode');
        Route::any('getUnionid','ApiMpV1\AuthController@getUnionid');
//邀请的人
        Route::any('invitePersons','ApiMpV1\UserController@invitePersons');



//返点
        Route::any('returnPointSetting','ApiMpV1\ReturnPointController@returnPointSetting');
        Route::any('subReturnPoint','ApiMpV1\ReturnPointController@subReturnPoint');
        Route::any('myReturnPoint','ApiMpV1\ReturnPointController@myReturnPoint');
        Route::any('returnPointDetail','ApiMpV1\ReturnPointController@returnPointDetail');

        //我的预报
        Route::any('myInput','ApiMpV1\InputController@myInput');
        //我的预约
        Route::any('mySubscribe','ApiMpV1\InputController@mySubscribe');
        //我的预报详情
        Route::any('myInputDetail','ApiMpV1\InputController@myInputDetail');



        //开始申报
        Route::any('declareGoods ','ApiMpV1\PackageController@declareGoods');

        //随机取申报内容
        Route::any('getDeclareTemp ','ApiMpV1\PackageController@getDeclareTemp');


        //通过税号 获取税率
        Route::any('getTaxRate ','ApiMpV1\PackageController@getTaxRate');


        //保存物品申报
        Route::any('saveDeclare ','ApiMpV1\PackageController@saveDeclare');

        //扫描开始打印
        Route::any('scanPrintLabel ','ApiMpV1\LabelPrintController@scanPrintLabel');
        Route::any('printLabel ','ApiMpV1\LabelPrintController@printLabel');
        Route::any('printLabelByMail ','ApiMpV1\LabelPrintController@printLabelByMail');
        Route::any('scanMailPrintLabel ','ApiMpV1\LabelPrintController@scanMailPrintLabel');

        //从我的预约单打印
        Route::any('printFromMySubscribe','ApiMpV1\LabelPrintController@printFromMySubscribe');


        // 获取oss直传签名sign
        Route::get('ossSign','OssController@getSign');


    });

});






// 小程序商城后台
Route::prefix('applet')->group(function () {
    # 登录
    Route::post('/login', 'ApiWechatApplet\IndexController@login');
    # 注册
    Route::post('/register', 'ApiWechatApplet\IndexController@register');
    # 获取注册验证码
    Route::post('/getCode', 'ApiWechatApplet\IndexController@getCode');
    # 忘记密码
    Route::post('/resetPassword', 'ApiWechatApplet\IndexController@resetPassword');

    //二维码
    Route::post('createQrCode','ApiWechatApplet\IndexController@createQrCode');

    //需要token
    Route::middleware('jwt.api.auth')->group(function ($router) {
        //选择类型
        $router->post('/registerStore', 'ApiWechatApplet\IndexController@registerStore');
        $router->post('/businessList', 'ApiWechatApplet\IndexController@businessList');
        //修改个人信息
        $router->post('modification_personage_message','ApiWechatApplet\IndexController@modification_personage_message');
        //代理spu添加
        $router->post('privateSpuAdd', 'ApiWechatApplet\PublicSkuController@privateSpuAdd');
        $router->post('privateSpuEdit', 'ApiWechatApplet\PublicSkuController@privateSpuEdit');
        $router->post('privateSpuInfo', 'ApiWechatApplet\PublicSkuController@privateSpuInfo');
        $router->post('privateSpuList', 'ApiWechatApplet\PublicSkuController@privateSpuList');
        $router->post('privateSpuPutOnOff', 'ApiWechatApplet\PublicSkuController@privateSpuPutOnOff');
        //会员卡
        $router->post('vipcards/index', 'ApiWechatApplet\VipCardController@index');
        $router->post('vipcards/store', 'ApiWechatApplet\VipCardController@store');
        $router->post('vipcards/update', 'ApiWechatApplet\VipCardController@update');
        $router->post('vipcards/level', 'ApiWechatApplet\VipCardController@setLevel');//设置等级
        $router->post('vipcards/forbid', 'ApiWechatApplet\VipCardController@forbid');//禁用

        //供货中心
        $router->post('supplyList', 'ApiWechatApplet\SupplyCenterController@theList'); //商品库
        $router->post('pickSupplyGoods', 'ApiWechatApplet\SupplyCenterController@pickSupplyGoods'); //选择分销商品
        $router->post('syncSpuSkuInfo', 'ApiWechatApplet\SupplyCenterController@syncSpuSkuInfo');

        // 储值卡
        $router->post('/storecards/store', 'ApiWechatApplet\StoreCardController@store');
        $router->post('/storecards/index', 'ApiWechatApplet\StoreCardController@index');
        $router->post('/storecards/info', 'ApiWechatApplet\StoreCardController@info');
        $router->post('/storecards/changeStatus', 'ApiWechatApplet\StoreCardController@changeStatus');

        // 积分设置
        $router->post('/integralsetting/store', 'ApiWechatApplet\IntegralSettingController@store');
        $router->post('/integralsetting/info', 'ApiWechatApplet\IntegralSettingController@info');

        // 成长值设置
        $router->post('/growthvalue/store', 'ApiWechatApplet\GrowthValueController@store');
        $router->post('/growthvalue/info', 'ApiWechatApplet\GrowthValueController@info');
    });

});
