<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();


Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
], function (Router $router) {
    //小程序订单生成pdf
    $router -> any('printMpPdf','MpPackageNumberController@printMpPdf');
    $router -> any('makePdf','MpPackageNumberController@makePdf');
    $router -> any('printMpPdfPage','MpPackageNumberController@printMpPdfPage');
});
Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'OrderController@index');
    $router->resource('users', WxUserController::class);
    $router->resource('vipuser', VipController::class);
    $router->resource('price_setting', MakePriceController::class);
    $router->resource('payImageSetting', PayImageController::class);
    $router->resource('areaName', AreaNameController::class);
    $router->resource('areaPriceLog', AreaPriceLogController::class);
    $router->resource('mallClass', MallClassController::class);
    $router->resource('mallImage', MallImageController::class);
    $router->resource('mallImageA', MallImageAController::class);
    $router->resource('freightTemp', FreightTempController::class);
    $router->resource('repertory', RepertoryController::class);
    $router->resource('pricelog', PriceLogController::class);
    $router->resource('returnPoint', ReturnPointController::class);
    $router->resource('returnShop', ReturnShopController::class);
    $router->resource('warning', WarningPackageController::class);
    $router->resource('splitPackage', SplitPackageController::class);
    $router->resource('CommodityCode', CommodityCodeController::class);
    $router->resource('FindGoods', FindGoodsController::class);
    $router->resource('AdminPort', AdminPortSettingController::class);
    $router->resource('AdminRoute', AdminRouteSettingController::class);
    $router->resource('MpPackageNumber', MpPackageNumberController::class);
    $router->resource('AreaScanOrder', AreaScanOrderController::class);
    $router->resource('CheckGoods', CheckGoodsController::class);
    $router->resource('OrderList', PackageController::class);


    //订单列表 批量申报
    $router -> any('declareAll','OrderController@declareAll');
    //导入小程序订单
    $router -> any('importMpNumber','MpPackageNumberController@importMpNumber');
    $router -> any('importMpNumberRes','MpPackageNumberController@importMpNumberRes');

    //区域交货单 扫描生成
    $router -> any('scanMakeAreaScanOrder','AreaScanOrderController@scanMakeAreaScanOrder');

    //模拟小程序扫描
    $router -> any('scanMpPackage','MpPackageNumberController@scanMpPackage');
    //小程序扫描内件
    $router -> any('scanMpPackageGoods','MpPackageNumberController@scanMpPackageGoods');


    //异常件下单
    $router -> any('MakeWarningPackageOrder','WarningPackageController@MakeWarningPackageOrder');
    $router -> any('submitWarningOrder','WarningPackageController@submitWarningOrder');



    $router -> any('makeTempNumberPdf','MpPackageNumberController@makeTempNumberPdf');
    //删除盘点单
    $router -> any('deleteCheckGoods','CheckGoodsController@deleteCheckGoods');

    //盘点包裹详情
    $router -> any('checkGoodsDetail','CheckGoodsController@checkGoodsDetail');


    $router -> any('areaScanSelect','AreaScanOrderController@areaScanSelect');
    $router -> any('areaScanSelectRes','AreaScanOrderController@areaScanSelectRes');

    //区域交货单 维护物流信息
    $router -> any('writeRepertory','AreaScanOrderController@writeRepertory');
    $router -> any('writeRepertoryPage','AreaScanOrderController@writeRepertoryPage');
    $router -> any('writeRepertoryRes','AreaScanOrderController@writeRepertoryRes');



    $router -> any('areaSendOrderDetail','AreaScanOrderController@areaSendOrderDetail');
    $router -> any('areaSendOrderId','AreaScanOrderController@areaSendOrderId');
    $router -> any('exportAreaOrderDetail','AreaScanOrderController@exportAreaOrderDetail');
    $router -> any('deleteAreaOrderDetail','AreaScanOrderController@deleteAreaOrderDetail');

    //设置vipuser
    $router -> any('setVipUser','WxUserController@setVipUser');
    //删除
    $router -> any('DeleteMpOrder','MpPackageNumberController@DeleteMpOrder');
    //小程序批量下单
    $router -> any('MakeMpPackageOrder','MpPackageNumberController@MakeMpPackageOrder');
    $router -> any('MakeMpPackageAreaOrder','MpPackageNumberController@MakeMpPackageAreaOrder');
    $router -> any('MakeMpPackageOrderPage','MpPackageNumberController@MakeMpPackageOrderPage');
    $router -> any('submitMpOrder','MpPackageNumberController@submitMpOrder');

    $router -> any('mpPakcageNumber','MpPackageNumberController@mpPakcageNumber');
    $router -> any('mpPackageNumberRes','MpPackageNumberController@mpPackageNumberRes');




    //拆分扫过的商品编码包裹
    $router -> any('splitCommodityCode','CommodityCodeController@splitCommodityCode');
    $router -> any('splitCommodityCodeRes','CommodityCodeController@splitCommodityCodeRes');
    //编辑地址
    $router -> any('editCommodityCodeAddress','CommodityCodeController@editCommodityCodeAddress');
    $router -> any('editCommodityCodeAddressRes','CommodityCodeController@editCommodityCodeAddressRes');

    //导入要查找的商品excel
    $router -> any('importFindGoodsExcel','FindGoodsController@importFindGoodsExcel');
    $router -> any('importFindGoodsExcelRes','FindGoodsController@importFindGoodsExcelRes');

    //拆单后下单
    $router -> any('splitPackageOrder','SplitPackageController@splitPackageOrder');
    //导入拆单后的重量
    $router -> any('importSplitPackage','SplitPackageController@importSplitPackage');
    $router -> any('importSplitPackageRes','SplitPackageController@importSplitPackageRes');
    //导入不允许拆分的包裹单号
    $router -> any('importNoSplitPackage','SplitPackageController@importNoSplitPackage');
    $router -> any('importNoSplitPackageRes','SplitPackageController@importNoSplitPackageRes');
    //拆单后下单
    $router -> any('splitOrder','SplitPackageController@splitOrder');

    //编辑区域
    $router -> any('editAreaPayImageRes','PayImageController@editAreaPayImageRes');


    $router -> any('editImage','PayImageController@editImage');

    //返点申请
    $router -> any('checkReturnPoint','ReturnPointController@checkReturnPoint');

    //到货库存（没有分配）
    $router -> any('repertoryNoUser','RepertoryController@repertoryNoUser');
    $router -> any('repertorySplit','RepertoryController@repertorySplit');
    $router -> any('repertorySplitRes','RepertoryController@repertorySplitRes');

    //到货库存 维护收获地址
    $router -> any('repertoryAddress','RepertoryController@repertoryAddress');
    $router -> any('repertoryAddressRes','RepertoryController@repertoryAddressRes');
    $router -> any('deleteRepertoryAddress','RepertoryController@deleteRepertoryAddress');
    //物流单号下单
    $router -> any('repertory_under','RepertoryController@repertory_under');
    $router -> any('repertoryUnderOrderRes','RepertoryController@repertoryUnderOrderRes');
    //出货汇总
    $router -> any('shipmentSummary','RepertoryController@shipmentSummary');
    $router -> any('shipmentSummaryDetail','RepertoryController@shipmentSummaryDetail');

    $router -> any('checkRepertory','RepertoryController@checkRepertory');
    $router -> any('repertoryCheckInfo','RepertoryController@repertoryCheckInfo');
    $router -> any('repertoryCheckInfoRes','RepertoryController@repertoryCheckInfoRes');


    $router -> any('repertoryPrint','RepertoryController@repertoryPrint');

    //批量通过
    $router -> any('PassRepertory','RepertoryController@PassRepertory');

    $router -> any('invoiceList','RepertoryController@invoiceList');


    $router -> any('updateMailPayLog','OrderController@updateMailPayLog');






    $router->get('goodsList/{type}','GoodsListController@index');
    $router->get('editGoodsList/{id}','GoodsListController@editGoodsList');
    $router->any('editGoodsListRes','GoodsListController@editGoodsListRes');
    $router->any('deleteProductImage','GoodsListController@deleteProductImage');
    //新增模板名称
    $router->any('addFreightTempName','FreightTempController@addFreightTempName');
    //运费模板下 增加配置
    $router->any('addTempTr/{id}','FreightTempController@addTempTr');
    //添加运费模板处理
    $router->any('addFreightTempRes','FreightTempController@addFreightTempRes');
    //删除运费模板
    $router->any('deleteFreightTemp/{freight_temp_id}','FreightTempController@deleteFreightTemp');
    //编辑运费模板
    $router->any('editFreightTemp','FreightTempController@editFreightTemp');


    //列表 上架
    $router->get('upGoodsList/{type}','GoodsListController@upGoodsList');
    //下架商品
    $router->get('downGoodsList/{type}','GoodsListController@downGoodsList');
    //待上架商品
    $router->get('loadGoodsList/{type}','GoodsListController@loadGoodsList');
    $router->get('sameGoodsData/{type}','GoodsListController@sameGoodsData');
    $router->get('importUpdatePrice/{type}','GoodsListController@importUpdatePrice');
    $router->any('importUpdatePriceRes','GoodsListController@importUpdatePriceRes');

    //价格维护页面
    $router->get('updatePriceGoodsList/{type}','GoodsListController@updatePriceGoodsList');
    //没有维护价格是 新增
    $router->get('addGoodsListPrice','GoodsListController@addGoodsListPrice');


    $router->get('mallNotShow','MallImageController@index2');
    $router->any('upGoods','GoodsListController@upGoods');
    $router->any('goodsPriceTempRes','GoodsListController@goodsPriceTempRes');

    $router->any('makeMpAreaOrder','MpPackageNumberController@makeMpAreaOrder');
    //商品列表价格设置
    $router->any('goodsListPrice','GoodsListController@goodsListPrice');
    //商品导入
    $router->any('importGoods','GoodsListController@importGoods');
    $router->any('importGoodsRes','GoodsListController@importGoodsRes');
    $router->any('updateMpScanInfo','GoodsListController@updateMpScanInfo');


    $router->get('order/{id}','OrderController@order');


    $router->any('orderEditRes','OrderController@orderEditRes');
    $router->get('order/{id}/edit','OrderController@edit');
    $router->any('addOrderRes','OrderController@addOrderRes');
    //订单列表
    $router->get('order', 'OrderController@index');
    //包裹查询
    $router->get('packageSearch', 'OrderController@packageSearch');
    //包裹维度-
    $router->get('packageEms', 'OrderController@packageEms');

    $router->get('emsEcharts', 'OrderController@emsEcharts');
    $router->any('emsEchartsAjax', 'OrderController@emsEchartsAjax');

    //订单  包裹维度显示
    $router->any('orderPacket', 'OrderController@orderPacket');
    $router->any('orderPacketExport/{type}', 'OrderController@orderPacketExport');
    $router->any('isBatchRepeat', 'OrderController@isBatchRepeat');
    $router->resource('batchList', BatchListController::class);

    $router->any('noPassPackage', 'BatchListController@noPassPackage');

    //超级删除
    $router->any('superDel', 'OrderController@superDel');


    //取消物流单号
    $router->any('cancelPackageWuliuNum', 'OrderController@cancelPackageWuliuNum');

    //获取轨迹
    //内网轨迹
    $router->any('getTrackList', 'OrderController@getTrackList');
    //外网轨迹
    $router->any('getTrackListWai', 'OrderController@getTrackListWai');
    $router->any('getMailStatus', 'OrderController@getMailStatus');

    $router->any('exportFile', 'OrderController@exportFile');
    $router->any('exportFileRes', 'OrderController@exportFileRes');
    $router->any('exportFile2', 'OrderController@exportFile2');
    $router->any('exportFileRes2', 'OrderController@exportFileRes2');


    $router->any('cancelOrder', 'OrderController@cancelOrder');
    //删除单个包裹
    $router->any('deletePackage', 'OrderController@deletePackage');
    //编辑单个包裹重量
    $router->any('editPacketPage/{id}', 'OrderController@editPacketPage');
    $router->any('editPacketRes', 'OrderController@editPacketRes');

    $router->any('editGoodsParatemer/{id}', 'OrderController@editGoodsParatemer');
    $router->any('editGoodsParatemerRes', 'OrderController@editGoodsParatemerRes');
    $router->any('editAllGoodsParatemerRes', 'OrderController@editAllGoodsParatemerRes');

    //通过税号 获取税率
    $router->any('getGoodsTax', 'OrderController@getGoodsTax');

    //编辑单个包裹的地址
    $router->any('editPacketAddressPage/{id}', 'OrderController@editPacketAddressPage');
    $router->any('editPacketAddressRes', 'OrderController@editPacketAddressRes');


    //确认支付页面
    $router->any('confirmPayPage/{id}','OrderController@confirmPayPage');
    $router->any('submitPayOrder','OrderController@submitPayOrder');

    $router->get('recharge/{id}', 'WxUserController@recharge');
    $router->get('subPrice/{id}', 'WxUserController@subPrice');
    $router->get('priceLog/{id}', 'WxUserController@priceLog');
    $router->any('rechargeRes', 'WxUserController@rechargeRes');
    $router->any('subPriceRes', 'WxUserController@subPriceRes');
    $router->any('underOrder/{id}', 'WxUserController@underOrder');
    $router->any('address/{id}', 'WxUserController@address');
    //后台用户上传文件下单处理
    $router->any('underOrderRes', 'WxUserController@underOrderRes');
    $router->any('addressRes', 'WxUserController@addressRes');
    $router->any('autoUnderOrderRes', 'WxUserController@autoUnderOrderRes');
    $router->any('submitUnderOrderRes', 'WxUserController@submitUnderOrderRes');
    $router->any('deleteUserAddress', 'WxUserController@deleteUserAddress');

    //导出重写
    $router->get('import/{type}','OrderController@importFile');
    $router->any('exportApi','OrderController@exportApi');
    $router->any('pdfApi','OrderController@pdfApi');
    $router->any('makePrintQueue','OrderController@makePrintQueue');

    //套餐商品
    $router->any('mergeGoods','MergeGoodsController@index');
    $router->any('mergeGoodsRes','MergeGoodsController@mergeGoodsRes');
    $router->any('mergeGoodsAjax','MergeGoodsController@mergeGoodsAjax');
    $router->any('mergeGoodsList','MergeGoodsController@mergeGoodsList');
    $router->any('deleteMerge','MergeGoodsController@deleteMerge');


    //限时特价
    $router->resource('superGoods', SuperGoodsController::class);
    $router->any('addSuperGoodsRes','SuperGoodsController@addSuperGoodsRes');


    //余额明细的导出
    $router->any('exportPriceLog','AreaPriceLogController@exportPriceLog');



    $router->any('test','OrderController@test');
    $router->any('apiAlertPage','OrderController@apiAlertPage');
    $router->any('cancelpackageAlert','OrderController@cancelpackageAlert');

    $router->any('deleteApiData/{code}','OrderController@deleteApiData');
    $router->any('refreshPackageStatus/{code}','OrderController@refreshPackageStatus');



    $router->get('exportPdf/{id}','OrderController@exportPdf');
    $router->get('exportOrder/{id}','OrderController@exportOrder');
    $router->get('exportPdfPage/{id}','OrderController@exportPdfPage');


    $router->get('autoUnderOrder/{id}','WxUserController@autoUnderOrder');
    //价格模板
    $router->get('routeSettingUser/{id}','RouteSettingController@routeSettingUser');
    //到货库存
    $router -> any('importKucun/{id}','WxUserController@importKucun');
    $router -> any('importKucunRes','WxUserController@importKucunRes');
    //
    $router->any('routeSettingRes','RouteSettingController@routeSettingRes');


    $router->any('getPriceByUserIdWeight','OrderController@getPriceByUserIdWeight');

    //区域余额充值
    $router->any('editAreaprice/{id}','AreaNameController@editAreaprice');
    $router->any('chargeAreaRes','AreaNameController@chargeAreaRes');

    //后台 价格配置模板
    $router->resource('routeSetting', RouteSettingController::class);
    $router->resource('zips', ZipsController::class);

    //统计
    $router -> get('countList','CountListController@index');

    $router -> get('exportPici/{id}','BatchListController@exportPici');



    //$router -> get('sameData','MallImageController@sameData');
    //$router -> get('sameData_a','MallImageAController@sameData');
    //托盘生成发货单
    $router -> get('makeSendOrder','BatchListController@makeSendOrder');
    $router -> any('makeSendOrderRes','BatchListController@makeSendOrderRes');
    //发货单列表
    $router -> any('sendOrderList','BatchListController@sendOrderList');
    $router -> any('exportOrderDetail/{id}','BatchListController@exportOrderDetail');
    $router -> any('sendOrderDetail/{id}','BatchListController@sendOrderDetail');
    $router -> any('trackingMoreData','BatchListController@trackingMoreData');


    //导入过机重量
    $router -> any('importPassWeight','OrderController@importPassWeight');
    $router -> any('importPassWeightRes','OrderController@importPassWeightRes');
    $router -> any('importPassWeightResPage','OrderController@importPassWeightResPage');


    //退件下单
    $router -> any('returnOrder','OrderController@returnOrder');
    $router -> any('returnUnderOrderRes','OrderController@returnUnderOrderRes');




});
