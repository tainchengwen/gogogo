<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('pay.syn');
Route::get('redirect','Home\HomeController@redirect');
Route::get('home','Home\HomeController@home');

//是否登陆过 中间件
Route::group(['middleware' => ['CheckLogin']], function () {



});

Route::get('printPdf','Home\HomeController@printPdf');


Route::get('agreeSign','Home\HomeController@agreeSign');
//订单列表
//Route::get('order','Home\OrderController@index');
Route::get('order', [
    'as' => 'order', 'uses' => 'Home\OrderController@index'
]);

//取消订单
Route::post('cancelOrder','Home\OrderController@cancelOrder');
//填写订单地址
Route::get('writeAdd/{id}','Home\OrderController@writeAdd');
//订单支付
Route::get('payOrder/{id}','Home\OrderController@payOrder');
//订单支付结束
Route::get('payEnd/{id}','Home\OrderController@payEnd');

Route::get('payEndType/{id}/{type}','Home\OrderController@payEndType');
//支付成功
Route::get('paySuccess/{id}','Home\OrderController@paySuccess');
//提交地址处理
Route::post('subOrderAddress','Home\OrderController@subOrderAddress');
//订单详情
Route::get('orderInfo/{id}','Home\OrderController@orderInfo');

Route::get('ifNotUserid','Home\OrderController@ifNotUserid');

//商城首页
Route::get('market','Home\MallController@index')->name('market');



Route::get('stockPrice/{level}','Home\MallController@stockPrice')->name('stockPrice');
Route::any('mall/ajaxGetStockVIPInfo/{level}','Home\MallController@ajaxGetStockVIPInfo');



////////////////临时
Route::get('index2','Home\MallController@index2')->name('market');
Route::any('mall/ajaxGetInfo2','Home\MallController@ajaxGetInfo2');
////////////////临时



Route::any('mall/ajaxGetInfo','Home\MallController@ajaxGetInfo');
Route::any('mall/ajaxGetMergeGoods','Home\MallController@ajaxGetMergeGoods');

Route::any('mall/car','Home\MallController@car')->name('car');



Route::any('mall/myorder','Home\MallController@myorder');
Route::any('mall/center','Home\MallController@center')->name('center');
//测试
Route::any('mall/makeOrder','Home\MallController@makeOrder');
//确认订单
Route::any('mall/submitbuy','Home\MallController@submitbuy');
//订单支付
Route::any('mall/payOrder','Home\MallController@payOrder');
//提交订单按钮请求
Route::any('mall/sendOrderRes','Home\MallController@sendOrderRes');
//支付方式
Route::any('mall/payMethod','Home\MallController@payMethod');
//余额支付接口
Route::any('mall/payApi','Home\MallController@payApi');
//删除购物车
Route::any('mall/delCar','Home\MallController@delCar');
//订单详情
Route::any('mall/orderdetail','Home\MallController@orderdetail');
//通过 收货地址、总价 计算运费
Route::post('mall/getKuaiDiPrice','Home\MallController@getKuaiDiPrice');
//选择运费
Route::get('mall/checkAddress','Home\MallController@checkAddress');
Route::get('mall/addAddress','Home\MallController@addAddress');
Route::get('mall/editAddress','Home\MallController@editAddress');
Route::post('mall/editAddressRes','Home\MallController@editAddressRes');


Route::post('mall/addAddressRes','Home\MallController@addAddressRes');
Route::get('mall/delAddress','Home\MallController@delAddress');


Route::get('mall/howtouser','Home\MallController@howtouser');


//只能地址
Route::post('mall/getExtAddress','Home\MallController@getExtAddress');


Route::any('sameGoodsData/{type}','Home\SameDataController@sameGoodsData');


//地址选择
Route::any('checkAddress','Home\OrderController@checkAddress');
//新增地址
Route::any('addAddress','Home\OrderController@addAddress');
Route::any('delAddress','Home\OrderController@delAddress');
Route::any('editAddress','Home\OrderController@editAddress');







//好友
Route::get('myfriend', [
    'as' => 'myFriend', 'uses' => 'Home\FriendController@index'
]);



//我的账户
Route::get('myData', [
    'as' => 'mydata', 'uses' => 'Home\DataController@index'
]);

//余额明细
Route::get('priceTable','Home\DataController@table');

//订单管理下载模板
Route::get('download',function(){
    return response()->download(realpath(base_path('public')).'/img/exportFile.xlsx', 'exportFile.xlsx');
});

//过机重量模版
Route::get('download_passweight',function(){
    return response()->download(realpath(base_path('public')).'/img/importWeight.xlsx', '过机重量导入.xlsx');
});

//某用户下单 下载模板
Route::get('download_user',function(){
    return response()->download(realpath(base_path('public')).'/img/userOrderExportFile.xlsx', '用户下单.xlsx');
});

Route::get('download_user_address',function(){
    return response()->download(realpath(base_path('public')).'/img/userAddress.xlsx', '用户导入收货地址.xlsx');
});

Route::get('download_repertory_address',function(){
    return response()->download(realpath(base_path('public')).'/img/userAddress.xlsx', '物流导入收货地址.xlsx');
});

//某个用户 导入到货库存
Route::get('download_user_kucun',function(){
    return response()->download(realpath(base_path('public')).'/img/download_user_kucun.xlsx', '到货库存.xlsx');
});


//商品列表模板
Route::get('download_product_list',function(){
    return response()->download(realpath(base_path('public')).'/img/product_list.xls', '商品导入.xls');
});

//更新价格模板
Route::get('download_update_price',function(){
    return response()->download(realpath(base_path('public')).'/img/download_update_price.xls', '导入更新价格.xls');
});

//下载小程序订单上传 模版
Route::get('download_mp_order',function(){
    return response()->download(realpath(base_path('public')).'/img/download_upload_mporder.xlsx', '小程序订单模版.xls');
});


//生成条形码图片
Route::get('getCodeImg/{ids}','Home\HomeController@getCodeImg');
Route::get('exportPdfPage/{id}','Home\HomeController@exportPdfPage');

Route::get('exportPdf/{id}','Home\HomeController@exportPdf');


//拆分条形码图片
Route::get('exportSplitPdfPage','Home\HomeController@exportSplitPdfPage');
//测试 大标签-单号
Route::get('testPdfPage','Home\HomeController@testPdfPage');
//识别码 打印标签（大标签）
Route::get('markPdfPage','Home\HomeController@markPdfPage');




Route::get('importSplitPackage','Home\HomeController@importSplitPackage');
Route::any('importSplitPackageRes','Home\HomeController@importSplitPackageRes');





Route::get('clearCache123456','Home\HomeController@clearCache');
Route::get('signPage','Home\HomeController@signPage');
Route::any('service','Home\ServerController@index');


Route::any('testWeight/{weight}','Home\HomeController@testWeight');

Route::any('test2','Home\HomeController@test2');
Route::any('eLoginRes','Home\HomeController@eLoginRes');
Route::any('saveCookieFile','Home\HomeController@saveCookieFile');
Route::any('eLogin','Home\HomeController@eLogin');
Route::any('createMenu','Home\HomeController@createMenu');

//打印送货单
Route::any('repertoryPrint','Home\HomeController@repertoryPrint');

//初始化 erp用户
Route::any('initUser','Home\HomeController@initUser');

//初始化批次
Route::any('initBatchPackage','Home\HomeController@initBatchPackage');

Route::any('monthCount','Home\CountListController@monthCount');
Route::any('dayCount','Home\CountListController@dayCount');
Route::any('clearCache','Home\CountListController@clearCache');
Route::any('Reptile','Home\ReptileController@test');


//Api部分



//扫码枪用
Route::any('barCode','Home\HomeController@barCode');
Route::any('barCodeAjax','Home\HomeController@barCodeAjax');
Route::any('saveBarCode','Home\HomeController@saveBarCode');

//新扫码
Route::any('scanGoods','Home\HomeController@scanGoods');
Route::any('subScanGoodsRes','Home\HomeController@subScanGoodsRes');
Route::any('deleteScanGoods','Home\HomeController@deleteScanGoods');
Route::any('createNewBatch','Home\HomeController@createNewBatch');
Route::any('saveBatchPackages','Home\HomeController@saveBatchPackages');
Route::any('createNewBatchAjax','Home\HomeController@createNewBatchAjax');
Route::any('deleteNewBatch','Home\HomeController@deleteNewBatch');
Route::any('splitPackages','Home\HomeController@splitPackages');
Route::any('splitPackagesRes','Home\HomeController@splitPackagesRes');
//扫码打印
Route::any('printScanPage','Home\HomeController@printScanPage');
Route::any('printScanRes','Home\HomeController@printScanRes');

//扫描查找商品
Route::any('scanFindGoods','Home\HomeController@scanFindGoods');
Route::any('scanFindGoodsRes','Home\HomeController@scanFindGoodsRes');
//匹配包裹
Route::any('matchPackage','Home\HomeController@matchPackage');
Route::any('matchPackageAjax','Home\HomeController@matchPackageAjax');
Route::any('deleteMatchData','Home\HomeController@deleteMatchData');
Route::any('deleteAllMatchData','Home\HomeController@deleteAllMatchData');

//补打印
Route::any('exPrint','Home\HomeController@exPrint');
Route::any('exPrintAjax','Home\HomeController@exPrintAjax');

//添加临时扫描数据
Route::any('addTempData','Home\HomeController@addTempData');
Route::any('addTempDataRes','Home\HomeController@addTempDataRes');

//前台盘点
Route::any('checkGoods','Home\HomeController@checkGoods');
Route::any('addCheckGoods','Home\HomeController@addCheckGoods');
Route::any('addCheckGoodsRes','Home\HomeController@addCheckGoodsRes');
Route::any('addCheckGoodsDetail','Home\HomeController@addCheckGoodsDetail');
Route::any('addCheckGoodsDetailRes','Home\HomeController@addCheckGoodsDetailRes');
Route::any('deleteCheckGoods','Home\HomeController@deleteCheckGoods');


//扫描商品编码
Route::any('scanCommodityCode','Home\HomeController@scanCommodityCode');
//扫描商品编码-包裹部分
Route::any('scanCommodityCodePackage','Home\HomeController@scanCommodityCodePackage');
//扫描商品编码-商品部分
Route::any('scanCommodityCodeGoods','Home\HomeController@scanCommodityCodeGoods');
Route::any('scanCommodityCodeGoodsRes','Home\HomeController@scanCommodityCodeGoodsRes');
Route::any('deleteScanCommodityCode','Home\HomeController@deleteScanCommodityCode');


Route::any('splitXXPackages','Home\HomeController@splitXXPackages');
Route::any('splitXXPackagesRes','Home\HomeController@splitXXPackagesRes');


//到货清单
Route::any('repertoryList','Home\HomeController@repertoryList');
//到货清单 打印功能
Route::any('printRepertoryList','Home\HomeController@printRepertoryList');

Route::any('updateRepertoryData','Home\HomeController@updateRepertoryData');
Route::any('saveUpdateRepertoryData','Home\HomeController@saveUpdateRepertoryData');
Route::any('makePackageLabel','Home\HomeController@makePackageLabel');
Route::any('makePackageLabelRes','Home\HomeController@makePackageLabelRes');



//到货扫描
Route::any('scanStart','Home\HomeController@scanStart');
//异常件到货扫描
Route::any('scanWarningStart','Home\HomeController@scanWarningStart');
Route::any('scanWarningStartRes','Home\HomeController@scanWarningStartRes');
Route::any('scanNumbersAjax','Home\HomeController@scanNumbersAjax');

//群发消息
Route::any('xxxxxxxxxxxxsendMessageAll','Home\HomeController@sendMessageAll');
//获取素材列表
Route::any('xxxxxxxxxxxxgetNewsList','Home\HomeController@getNewsList');


//自动程序

Route::get('auto/updateTrackingList','Auto\UpdateFtpDataController@updateTrackingList');
Route::get('auto/updateClearStatus','Auto\UpdateFtpDataController@updateClearStatus');
Route::get('auto/updateWxInfo','Auto\UpdateWxInfoController@updateWxInfo');
//更新trackingList
Route::get('auto/updateTrackingMore','Auto\UpdateTrackingMoreController@index');


//异步程序
Route::any('asy/trackingMore','Client\TrackingMoreController@index');



//发货列表生成二维码 sendOrderQrCode

Route::any('etk/index','Home\EtkController@index');
Route::any('etk/sendOrderQrCode','Home\EtkController@sendOrderQrCode');
Route::any('etk/qrCodeManage','Home\EtkController@qrCodeManage');
Route::any('etk/subOrderRes','Home\EtkController@subOrderRes');
Route::any('etk/managePrintPage','Home\EtkController@managePrintPage');
Route::any('etk/startPrint','Home\EtkController@startPrint');


//返点
Route::any('return/index','Home\ReturnPointController@index')->name('return_index');
Route::any('return/writePage','Home\ReturnPointController@writePage');
Route::any('return/subData','Home\ReturnPointController@subData');
Route::any('return/look','Home\ReturnPointController@look');
Route::any('return/returnList','Home\ReturnPointController@returnList')->name('return_list');
Route::any('saveImg','Home\HomeController@saveImg');


Route::any('tttttt','Home\HomeController@testUser');
Route::any('moniSplit','Home\HomeController@moniSplit');
Route::any('getVipByOpenid','Home\HomeController@getVipByOpenid');


//预约送货
Route::any('repertory/home','Home\OrderRepertoryController@home');
Route::any('repertory/info','Home\OrderRepertoryController@info');
//国际快递
Route::any('repertory/home_express','Home\OrderRepertoryController@home_express');
Route::any('repertory/express_info','Home\OrderRepertoryController@express_info');
//提交上传物流照片
Route::any('repertory/subRepertoryData','Home\OrderRepertoryController@subRepertoryData');

Route::any('repertory/orderRepertory','Home\OrderRepertoryController@orderRepertory');
Route::any('repertory/orderRepertoryRes','Home\OrderRepertoryController@orderRepertoryRes');


//pdfpage
Route::any('pdfpage/deliverGoods','Home\PdfPageController@deliverGoods');
Route::any('saveCangwei','Home\HomeController@saveCangwei');
Route::any('makeMarkPdf','Home\HomeController@makeMarkPdf');



Route::any('mpPage/page1','Home\MpPageController@page1');
Route::any('mpPage/page2','Home\MpPageController@page2');
Route::any('mpPage/page3','Home\MpPageController@page3');
Route::any('mpPage/page4','Home\MpPageController@page4');
Route::any('mpPage/page5','Home\MpPageController@page5');
Route::any('mpPage/page6','Home\MpPageController@page6');



Route::any('Translate','TranslateController@test');
Route::any('stopPrinter','Home\HomeController@stopPrinter');
Route::any('scanPrintPdf','Home\HomeController@scanPrintPdf');
Route::any('scanPrintPdfRes','Home\HomeController@scanPrintPdfRes');

//物流小程序包裹编号a4纸打印
Route::any('mpLabelPage','Home\HomeController@mpLabelPage');
//物流小程序单张打印
Route::any('mpLabelOnePage','Home\HomeController@mpLabelOnePage');

Route::any('test1','Home\TestController@test1');
Route::any('testGetCard','Home\GetCardController@main');
Route::any('testUpdateAllPackagesStatus','Home\TestController@testUpdateAllPackagesStatus');