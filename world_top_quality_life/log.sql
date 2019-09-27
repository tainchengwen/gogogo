INSERT INTO `erp_f_permissions` (`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (NULL, '商城管理', '0', '0', '1551542685', '1551542685', '8');

INSERT INTO `erp_f_permissions` (`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (NULL, '运费模板', '36', '0', '1551542685', '1551542685', '8.1');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (NULL, '列表查询', 'web', '2019-03-03 00:00:00', '2019-03-03 00:00:00', '37', '8.1.1');

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (NULL, '增加', 'web', '2019-03-03 00:00:00', '2019-03-03 00:00:00', '37', '8.1.2');
-- 运费模板和

-- 修改运费模板为全局维护
UPDATE `erp_f_permissions` SET `fid` = '1', `sort_str` = '1.3' WHERE `erp_f_permissions`.`id` = 37;
UPDATE `permissions` SET `sort_str` = '1.3.1' WHERE `permissions`.`id` = 85;
UPDATE `permissions` SET `sort_str` = '1.3.2' WHERE `permissions`.`id` = 86;

-- 修改权限
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (NULL, '修改', 'web', '2019-03-03 00:00:00', '2019-03-03 00:00:00', '37', '1.3.3');

SET sql_mode = 'NO_ZERO_DATE';

-- 商品表
CREATE TABLE `erp_spu_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '商品标题',
  `sub_name` varchar(128) NOT NULL DEFAULT '' COMMENT '商品副标题',
  `details` text COMMENT '商品详情',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '商品状态0待上架1已上架2下架',
  `class_id` int(11) unsigned NOT NULL COMMENT '分类Id',
  `warehouse_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `flag` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `class_id` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='商品（SPU）表';

-- 商品 库存关联表
CREATE TABLE `erp_spu_sku_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `spu_id` int(10) unsigned NOT NULL,
  `sku_id` int(10) unsigned NOT NULL,
  `sort_index` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序索引',
  `flag` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='商品单品关联表';

-- 商品 标签关联表
CREATE TABLE `erp_spu_tag_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
  `spu_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'SPUID',
  PRIMARY KEY (`id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='spu-标签关联表';

-- 标签表
CREATE TABLE `erp_tags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '标签名',
  `num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '引用数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='标签表';

-- 商品价格维护表
CREATE TABLE `erp_product_price`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'skuId',
  `price_a` float(10, 2) NOT NULL DEFAULT 0.00,
  `price_b` float(10, 2) NOT NULL DEFAULT 0.00,
  `price_c` float(10, 2) NOT NULL DEFAULT 0.00,
  `price_d` float(10, 2) NOT NULL DEFAULT 0.00,
  `price_s` float(10, 2) NOT NULL DEFAULT 0.00,
  `flag` tinyint(1) NOT NULL COMMENT '0正常1删除',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = 'sku价格表';

ALTER TABLE `erp_product_price`
ADD UNIQUE INDEX `product_id`(`product_id`) COMMENT '商品Id索引';

ALTER TABLE `erp_product_price`
MODIFY COLUMN `flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0正常1删除' AFTER `price_s`,
MODIFY COLUMN `created_at` int(11) NOT NULL DEFAULT 0 AFTER `flag`,
MODIFY COLUMN `updated_at` int(11) NOT NULL DEFAULT 0 AFTER `created_at`;

ALTER TABLE `erp_stock`
ADD COLUMN `status` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上架状态0未上架1已上架2已下架' AFTER `flag`;

CREATE TABLE `erp_spu_category`  (
  `id` int(0) NOT NULL,
  `name` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `path` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci;

ALTER TABLE `erp_spu_category`
MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT FIRST,
ADD UNIQUE INDEX `code`(`code`);

ALTER TABLE `erp_spu_list`
MODIFY COLUMN `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '商品状态0不显示1显示' AFTER `details`;


CREATE TABLE `erp_business_spu_link`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `spu_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `create_at` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `flag` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0正常1删除',
  PRIMARY KEY (`id`),
  INDEX `business_id`(`business_id`)
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '事业部-SPU关联表';


CREATE TABLE `erp_shop_cart` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'mp_shop_users表',
  `spuId` int(11) unsigned NOT NULL DEFAULT '0',
  `skuId` int(11) unsigned NOT NULL DEFAULT '0',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '购买数量',
  `businessId` int(11) NOT NULL DEFAULT '0' COMMENT '代理商Id',
  `createdTime` int(11) NOT NULL DEFAULT '0' COMMENT '添加购物车时间',
  `isDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除 0正常1删除',
  `delTime` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `isOrder` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否下单 0未下单1下单',
  `orderId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `orderTime` int(11) NOT NULL DEFAULT '0' COMMENT '下单时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='购物车表';

ALTER TABLE `erp_shop_cart`
ADD COLUMN `updateTime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间-修改num' AFTER `createdTime`;


CREATE TABLE `erp_mp_shop_address` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城小程序用户id',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '收货人',
  `phone` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '手机号',
  `province` varchar(64) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(64) NOT NULL DEFAULT '' COMMENT '市',
  `area` varchar(64) NOT NULL DEFAULT '' COMMENT '区',
  `detail` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `idNumber` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号',
  `imageFront` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证正面',
  `imageBack` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证反面',
  `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
  `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
  `isDel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0正常 1删除',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商城小程序地址';

ALTER TABLE `erp_mp_shop_address`
ADD COLUMN `isDefault` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认 0否1是' AFTER `isDel`;

ALTER TABLE `erp_mp_shop_address`
MODIFY COLUMN `phone` varchar(24) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号' AFTER `name`;


ALTER TABLE `erp_shop_cart`
ADD COLUMN `isDirect` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否是直接下单' AFTER `updateTime`;

-- 商城基础配置表
CREATE TABLE `erp_setting`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT 0 COMMENT '事业部id',
  `color` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `is_all` int(11) NOT NULL DEFAULT 0,
  `created_at` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `updated_at` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);

-- 商城轮播图配置表
CREATE TABLE `erp_setting_banners`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL DEFAULT 0 COMMENT '事业部id',
  `spu_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '图片',
  `is_show` int(11) NOT NULL DEFAULT 0,
  `is_del` int(11) NOT NULL DEFAULT 0,
  `created_at` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `updated_at` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);

-- 增加首重成本和续重成本
ALTER TABLE `freight_temp`
ADD COLUMN `firstWeight_cost` float NULL COMMENT '首重成本' AFTER `address`,
ADD COLUMN `secondWeight_cost` float NULL COMMENT '续重成本' AFTER `firstWeight_cost`;

-- 增加
ALTER TABLE `freight_temp_name`
ADD COLUMN `is_incountry` int(11) NULL COMMENT '0国内1国外' AFTER `updated_at`,
ADD COLUMN `is_weight` int(11) NULL COMMENT '0没要求1有要求' AFTER `is_incountry`,
ADD COLUMN `weight_info` decimal(11, 2) NULL COMMENT '重量具体要求' AFTER `is_weight`,
ADD COLUMN `package_limit` decimal(11, 2) NULL COMMENT '包裹价值上限' AFTER `weight_info`;

ALTER TABLE `erp_warehouse`
ADD COLUMN `freight_temp_name_id` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `image`;

ALTER TABLE `erp_product_list`
ADD COLUMN `declared_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '申报价格' AFTER `volume_weight`;

ALTER TABLE `freight_temp_name`
CHANGE COLUMN `is_incountry` `country` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0国内1国外' AFTER `updated_at`,
MODIFY COLUMN `is_weight` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0没要求1有要求' AFTER `country`;
ALTER TABLE `freight_temp_name`
MODIFY COLUMN `weight_info` decimal(11, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '重量具体要求' AFTER `is_weight`;
ALTER TABLE `freight_temp_name`
MODIFY COLUMN `package_limit` decimal(11, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '包裹价值上限' AFTER `weight_info`;
ALTER TABLE `freight_temp_name`
ADD COLUMN `package_limit` decimal(11, 2) NULL COMMENT '包裹价值上限' AFTER `weight_info`;



ALTER TABLE `erp_stock_order` ADD `print_express` SMALLINT(1) NULL DEFAULT '0' COMMENT '是否打印快递面单' , ADD `print_distribution` SMALLINT(1) NULL DEFAULT '0' COMMENT '是否打印配货单' ;

ALTER TABLE `erp_stock_order` ADD `operator_user_id` INT(11) NULL COMMENT '操作人userid' AFTER `print_distribution`;


CREATE TABLE `erp_send_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `tel` varchar(128) DEFAULT NULL,
  `phone` varchar(128) DEFAULT NULL,
  `province` varchar(128) DEFAULT NULL,
  `city` varchar(128) DEFAULT NULL,
  `area` varchar(128) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `zip_code` varchar(200) DEFAULT NULL,
  `address_alias` varchar(200) DEFAULT NULL,
  `created_at` varchar(12) DEFAULT NULL,
  `updated_at` varchar(12) DEFAULT NULL,
  `flag` smallint(1) DEFAULT '0',
  `business_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='发货地址';


ALTER TABLE `erp_product_list` ADD `price` FLOAT(11) NULL DEFAULT '0';
ALTER TABLE `erp_product_list` ADD `japan_name` VARCHAR(500) NULL DEFAULT '';
ALTER TABLE `erp_product_list` ADD `element_zh` VARCHAR(500) NULL DEFAULT '';
ALTER TABLE `erp_product_list` ADD `element_ja` VARCHAR(500) NULL DEFAULT '';
ALTER TABLE `erp_product_list` ADD `product_country` VARCHAR(500) NULL DEFAULT '';


ALTER TABLE `erp_logistics_info` ADD `deliver_number` INT(11) NULL DEFAULT '0' COMMENT '已发货数量' ;


ALTER TABLE `erp_stock_order_info_receive` ADD `send_num` INT(11) NULL DEFAULT '0' COMMENT '已发数量';

ALTER TABLE `erp_stock_order` ADD `pay_method` INT(11) NULL DEFAULT '0' COMMENT '支付方式 数据字典在admin.php';


-- 增加日本库，澳洲库，德国库的权限
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (42, '日本库', 0, 0, '1551542685', '1551542685', '9');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (43, '澳洲库', 0, 0, '1551542685', '1551542685', '10');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (44, '德国库', 0, 0, '1551542685', '1551542685', '11');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (45, 'SPU', 42, 0, '1551542685', '1551542685', '9.1');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (46, 'SKU', 42, 0, '1551542685', '1551542685', '9.2');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (47, 'SPU', 43, 0, '1551542685', '1551542685', '10.1');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (48, 'SKU', 43, 0, '1551542685', '1551542685', '10.2');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (49, 'SPU', 44, 0, '1551542685', '1551542685', '11.1');
INSERT INTO `erp_f_permissions`(`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (50, 'SKU', 44, 0, '1551542685', '1551542685', '11.2');

INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (104, '编辑', 'web', '2019-04-18 10:13:36', '2019-04-18 10:13:39', 45, '9.1.2');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (105, 'SKU管理', 'web', '2019-04-18 10:14:30', '2019-04-18 10:14:33', 45, '9.1.3');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (106, '批量上架', 'web', '2019-04-18 10:15:29', '2019-04-18 10:15:32', 46, '9.2.1');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (107, '批量下架', 'web', '2019-04-18 10:15:55', '2019-04-18 10:15:57', 46, '9.2.2');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (108, '上架', 'web', '2019-04-18 10:16:16', '2019-04-18 10:16:20', 46, '9.2.3');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (109, '下架', 'web', '2019-04-18 10:16:38', '2019-04-18 10:16:40', 46, '9.2.4');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (110, '编辑', 'web', '2019-04-18 10:17:00', '2019-04-18 10:17:03', 46, '9.2.5');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (111, '价格维护', 'web', '2019-04-18 10:17:31', '2019-04-18 10:17:34', 46, '9.2.6');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (112, '增加', 'web', '2019-04-18 10:17:31', '2019-04-18 10:17:34', 47, '10.1.1');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (113, '编辑', 'web', '2019-04-18 10:20:16', '2019-04-18 10:20:18', 47, '10.1.2');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (114, 'SKU管理', 'web', '2019-04-18 10:14:30', '2019-04-18 10:14:33', 47, '10.1.3');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (115, '批量上架', 'web', '2019-04-18 10:15:29', '2019-04-18 10:15:32', 48, '10.2.1');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (116, '批量下架', 'web', '2019-04-18 10:15:55', '2019-04-18 10:15:57', 48, '10.2.2');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (117, '上架', 'web', '2019-04-18 10:16:16', '2019-04-18 10:16:20', 48, '10.2.3');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (118, '下架', 'web', '2019-04-18 10:16:38', '2019-04-18 10:16:40', 48, '10.2.4');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (119, '编辑', 'web', '2019-04-18 10:17:00', '2019-04-18 10:17:03', 48, '10.2.5');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (120, '价格维护', 'web', '2019-04-18 10:17:31', '2019-04-18 10:17:34', 48, '10.2.6');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (121, '增加', 'web', '2019-04-18 10:17:31', '2019-04-18 10:17:34', 49, '11.1.1');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (122, '编辑', 'web', '2019-04-18 10:20:16', '2019-04-18 10:20:18', 49, '11.1.2');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (123, 'SKU管理', 'web', '2019-04-18 10:14:30', '2019-04-18 10:14:33', 49, '11.1.3');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (124, '批量上架', 'web', '2019-04-18 10:15:29', '2019-04-18 10:15:32', 50, '11.2.1');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (125, '批量下架', 'web', '2019-04-18 10:15:55', '2019-04-18 10:15:57', 50, '11.2.2');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (126, '上架', 'web', '2019-04-18 10:16:16', '2019-04-18 10:16:20', 50, '11.2.3');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (127, '下架', 'web', '2019-04-18 10:16:38', '2019-04-18 10:16:40', 50, '11.2.4');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (128, '编辑', 'web', '2019-04-18 10:17:00', '2019-04-18 10:17:03', 50, '11.2.5');
INSERT INTO `permissions`(`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (129, '价格维护', 'web', '2019-04-18 10:17:31', '2019-04-18 10:17:34', 50, '11.2.6');


ALTER TABLE `wxuser` ADD `hope_market_class` INT(11) NULL COMMENT '希望指定日期变回哪个等级' , ADD `hope_date` VARCHAR(128) NULL COMMENT '指定日期';

ALTER TABLE `erp_stock_order` ADD `insert_type` SMALLINT(1) NULL DEFAULT '0' COMMENT '0erp后台添加、1小程序商城添加' AFTER `pay_method`;

CREATE TABLE `deliver_goods_record` ( `id` INT NOT NULL AUTO_INCREMENT , `stock_order_id` INT(11) NULL COMMENT '库存销售单id' , `flag` SMALLINT(1) NULL DEFAULT '0' , `wuliu_num` VARCHAR(128) NULL COMMENT '物流单号' , `created_at` VARCHAR(12) NULL , `updated_at` VARCHAR(12) NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB COMMENT = '一个销售订单 多次发货记录';

ALTER TABLE `erp_deliver_goods_record` ADD `stock_order_no` VARCHAR(128) NULL DEFAULT '' COMMENT '请求物流单号用的销售单号' AFTER `business_id`;

ALTER TABLE `erp_deliver_goods_record` ADD `express_html` TEXT NULL COMMENT '快递单html结构' AFTER `stock_order_no`;

ALTER TABLE `erp_warehouse`
ADD COLUMN `miniprogram_name` varchar(128) NOT NULL DEFAULT '' COMMENT '小程序名称' AFTER `freight_temp_name_id`,
ADD COLUMN `miniprogram_flag` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序是否显示 默认0显示' AFTER `miniprogram_name`;

ALTER TABLE `erp_warehouse`
CHANGE COLUMN `miniprogram_name` `mp_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小程序名称' AFTER `freight_temp_name_id`,
CHANGE COLUMN `miniprogram_flag` `mp_flag` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序是否显示 默认1显示' AFTER `mp_name`;

ALTER TABLE `erp_spu_category`
ADD COLUMN `mp_flag` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '小程序是否显示 默认1显示' AFTER `image`;

ALTER TABLE `erp_spu_category`
ADD COLUMN `sort_index` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类排序序号' AFTER `mp_flag`;

ALTER TABLE `erp_spu_sku_link`
ADD COLUMN `status` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0下架1上架 default 0' AFTER `flag`;

ALTER TABLE `wxuser` ADD `source` INT(11) NULL DEFAULT '0' COMMENT '来源1、vip二维码' AFTER `hope_date`;


ALTER TABLE `erp_spu_sku_link`
ADD COLUMN `warehouse_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '仓库id' AFTER `status`,
ADD COLUMN `business_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '事业部id' AFTER `warehouse_id`;

ALTER TABLE `erp_spu_list`
ADD COLUMN `business_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '事业部id' AFTER `flag`;

ALTER TABLE `erp_product_price`
MODIFY COLUMN `price_a` decimal(10, 2) NOT NULL DEFAULT 0.00 AFTER `product_id`,
MODIFY COLUMN `price_b` decimal(10, 2) NOT NULL DEFAULT 0.00 AFTER `price_a`,
MODIFY COLUMN `price_c` decimal(10, 2) NOT NULL DEFAULT 0.00 AFTER `price_b`,
MODIFY COLUMN `price_d` decimal(10, 2) NOT NULL DEFAULT 0.00 AFTER `price_c`,
MODIFY COLUMN `price_s` decimal(10, 2) NOT NULL DEFAULT 0.00 AFTER `price_d`;

ALTER TABLE `erp_product_list`
ADD UNIQUE INDEX `product_no`(`product_no`);
ALTER TABLE `erp_stock_order` ADD `send_phone` VARCHAR(128) NULL COMMENT '发货人电话' AFTER `insert_type`;

CREATE TABLE `erp_user_address`(
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL DEFAULT '0',
    `business_id` INT(11) NULL DEFAULT '0',
    `province` VARCHAR(128) NULL DEFAULT '',
    `city` VARCHAR(128) NULL DEFAULT '',
    `area` VARCHAR(128) NULL DEFAULT '',
    `name` VARCHAR(128) NULL DEFAULT '',
    `tel` VARCHAR(128) NULL DEFAULT '',
    `phone` VARCHAR(128) NULL DEFAULT '',
    `address` VARCHAR(500) NULL DEFAULT '',
    `flag` SMALLINT(1) NULL DEFAULT '0',
    `created_at` VARCHAR(12) NULL DEFAULT '',
    `updated_at` VARCHAR(12) NULL DEFAULT '',
    `address_type` SMALLINT(1) NULL DEFAULT '0' COMMENT '0发货地址1收货地址',
    PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 COMMENT = 'erp库存销售订单的地址';


ALTER TABLE `erp_business` ADD `user_id` INT(11) NULL DEFAULT '0' COMMENT '事业部绑定wxuser id';

ALTER TABLE
    `erp_stock_order` ADD `idNumber` VARCHAR(128) NULL DEFAULT '' COMMENT '身份证号码' ,
                      ADD `imageFront` VARCHAR(500) NULL DEFAULT '' COMMENT '身份证正面照片',
                      ADD `imageBack` VARCHAR(500) NULL DEFAULT '' COMMENT '身份证反面照片';



--
-- 表的结构 `erp_express_log`
--

CREATE TABLE `erp_express_log` (
  `id` int(11) NOT NULL,
  `order_num` varchar(128) DEFAULT NULL COMMENT '请求面单订单编号',
  `express_num` varchar(128) DEFAULT NULL COMMENT '快递单编号',
  `express_type` varchar(128) DEFAULT NULL COMMENT '快递单类型',
  `express_html` text DEFAULT NULL COMMENT '',
  `flag` smallint(1) DEFAULT '0',
  `created_at` varchar(12) DEFAULT NULL,
  `updated_at` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='快递面单打印记录';
ALTER TABLE `erp_express_log`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `erp_express_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;


ALTER TABLE `erp_receive_goods_record` ADD `true_num` INT(11) NULL DEFAULT '0' COMMENT '真实入库数量，不发生改变' AFTER `number`;

ALTER TABLE `erp_stock_adjust` ADD `to_receive_record_id` INT(11) NULL DEFAULT '0' COMMENT '转移到的仓库到货记录' AFTER `receive_goods_record_id`;


ALTER TABLE `erp_stock_adjust` ADD `flag` SMALLINT(1) NULL DEFAULT '0' ;

-- 重构商城底层开始

ALTER TABLE `erp_warehouse`
ADD COLUMN `agent_status` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0不代理 1代理' AFTER `mp_flag`;

CREATE TABLE `erp_mp_name`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mp_name` varchar(128) NOT NULL DEFAULT '' COMMENT '小程序名称',
  PRIMARY KEY (`id`)
);

CREATE TABLE `erp_mp_name_spu_link`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mp_name_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序id',
  `spu_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SPUid',
  PRIMARY KEY (`id`)
);

CREATE TABLE `erp_agent_price`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `spu_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sku_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '事业部id',
  `mp_name_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序名称id',
  `price` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '代理商自己售价',
  PRIMARY KEY (`id`)
);

ALTER TABLE `erp_product_price`
ADD COLUMN `mp_name_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序名称id' AFTER `updated_at`;

ALTER TABLE `erp_warehouse`
ADD COLUMN `mp_name_id` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '小程序名称id' AFTER `agent_status`;

ALTER TABLE `erp_mp_name_spu_link`
ADD COLUMN `status` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自营SPU上下架状态' AFTER `spu_id`;

ALTER TABLE `erp_product_price`
ADD COLUMN `status` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自营SKU上下架状态' AFTER `mp_name_id`;

ALTER TABLE `erp_business_spu_link`
ADD COLUMN `status` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理SPU上下架状态' AFTER `flag`;

ALTER TABLE `erp_agent_price`
ADD COLUMN `status` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理商SKU上下架状态' AFTER `price`;

ALTER TABLE `erp_mp_name_spu_link`
ADD COLUMN `flag` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `status`;

ALTER TABLE `erp_business_spu_link`
ADD COLUMN `mp_name_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '馆区对应id' AFTER `status`;

ALTER TABLE `erp_agent_price`
ADD COLUMN `flag` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除' AFTER `status`;

ALTER TABLE `erp_business`
MODIFY COLUMN `level` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'vip等级 0无等级' AFTER `flag`;

ALTER TABLE `erp_business`
ADD COLUMN `master_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主号' AFTER `flag`,
ADD COLUMN `self_status` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否自营' AFTER `master_id`;

ALTER TABLE `erp_product_price`
MODIFY COLUMN `flag` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0正常1删除' AFTER `price_s`,
DROP INDEX `product_id`;

ALTER TABLE `erp_product_price`
ADD UNIQUE INDEX `price`(`product_id`, `mp_name_id`) COMMENT '组合唯一索引 product_id+mp_name_id';

ALTER TABLE `erp_mp_name`
MODIFY COLUMN `mp_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '馆区名称' AFTER `id`,
ADD COLUMN `created_at` int(11) NOT NULL DEFAULT 0 AFTER `mp_name`,
ADD COLUMN `updated_at` int(11) NOT NULL DEFAULT 0 AFTER `created_at`,
ADD COLUMN `flag` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0正常1删除' AFTER `updated_at`,
ADD COLUMN `image` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `flag`,
ADD COLUMN `is_show` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否显示 默认1显示' AFTER `image`,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `erp_shop_cart`
MODIFY COLUMN `userId` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'wxuser表' AFTER `id`;

CREATE TABLE `erp_agent_spu_category`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `path` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `image` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '图片',
  `mp_flag` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '小程序是否显示 默认1显示',
  `sort_index` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类排序序号',
  PRIMARY KEY (`id`)
);

ALTER TABLE `erp_agent_spu_category`
ADD COLUMN `business_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '事业部id' AFTER `sort_index`;

ALTER TABLE `erp_business_spu_link`
ADD COLUMN `class_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类Id' AFTER `mp_name_id`;

ALTER TABLE `erp_product_price`
MODIFY COLUMN `status` tinyint(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否正常0正常1不正常' AFTER `mp_name_id`,
ADD COLUMN `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上下架显示0下架1上架' AFTER `status`;

ALTER TABLE `erp_product_price`
ADD COLUMN `has_stock` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '库存状态默认0没库存1有库存' AFTER `is_show`;

ALTER TABLE `erp_mp_name`
ADD COLUMN `freight_temp_name_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '运费模板id' AFTER `is_show`;

ALTER TABLE `erp_mp_name`
ADD COLUMN `icon_image` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `image`,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `erp_product_price`
MODIFY COLUMN `has_stock` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '库存状态默认0没库存1有库存' AFTER `is_show`;

-- 重构商城底层结束

ALTER TABLE `erp_business` ADD `business_type` SMALLINT(1) NULL DEFAULT '0' COMMENT '0普通版本1代理版' AFTER `user_id`;


CREATE TABLE `erp_sale_sheet` ( `id` INT NOT NULL , `date_str` VARCHAR(12) NULL DEFAULT '' COMMENT '日期字符' , `price` FLOAT(11) NULL COMMENT '销售金额' , `number` INT(11) NULL COMMENT '销售单数' , `guest_number` INT(11) NULL COMMENT '客单' , `created_at` VARCHAR(12) NULL , `updated_at` VARCHAR(12) NULL , `user_id` INT(11) NULL ) ENGINE = InnoDB COMMENT = '销售日报';

ALTER TABLE `erp_sale_sheet` ADD PRIMARY KEY(`id`);

ALTER TABLE `erp_sale_sheet` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;

-- 商城小程序身份证管理
CREATE TABLE `erp_shopmp_identity_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城小程序用户id',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '姓名',
  `idNumber` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号',
  `imageFront` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证正面',
  `imageBack` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证反面',
  `isDel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0正常 1删除',
  `isDefault` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认 0否1是',
  `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
  `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `select` (`userId`,`isDel`,`isDefault`) COMMENT '查询索引'
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='商城小程序身份证管理';

CREATE TABLE `erp_sale_cost_sheet` ( `id` INT NOT NULL AUTO_INCREMENT , `class_name` VARCHAR(128) NULL , `price` FLOAT(12) NULL COMMENT '销售金额' , `cost` FLOAT(12) NULL COMMENT '成本' , `date_str` VARCHAR(20) NULL , `created_at` VARCHAR(12) NULL , `updated_at` VARCHAR(12) NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB COMMENT = '销售报表带成本';

ALTER TABLE `erp_sale_cost_sheet` ADD `business_id` INT(11) NULL ;

ALTER TABLE `erp_stock_order_pay` ADD `pay_method` INT(11) NULL DEFAULT '0' COMMENT '支付方式' ;

ALTER TABLE `erp_agent_price`
DROP COLUMN `spu_id`;


ALTER TABLE `erp_setting`
DROP COLUMN `is_all`,
ADD COLUMN `name` varchar(128) NOT NULL DEFAULT '' COMMENT '店铺名称' AFTER `color`,
ADD COLUMN `notice` varchar(1024) NOT NULL DEFAULT '' COMMENT '店铺公告' AFTER `name`,
ADD COLUMN `logo` varchar(128) NOT NULL DEFAULT '' COMMENT '店铺Logo' AFTER `notice`,
ADD COLUMN `image` varchar(128) NOT NULL DEFAULT '' COMMENT '店铺背景图' AFTER `logo`;

-- 代理小程序
ALTER TABLE `erp_agent_price`
ADD INDEX `select`(`business_id`, `mp_name_id`, `price`, `status`, `flag`);

-- 代理购物车
CREATE TABLE `erp_agent_shop_cart` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'wxuser表',
  `spuId` int(11) unsigned NOT NULL DEFAULT '0',
  `skuId` int(11) unsigned NOT NULL DEFAULT '0',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '购买数量',
  `businessId` int(11) NOT NULL DEFAULT '0' COMMENT '代理商Id',
  `createdTime` int(11) NOT NULL DEFAULT '0' COMMENT '添加购物车时间',
  `updateTime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间-修改num',
  `isDirect` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是直接下单',
  `isDel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除 0正常1删除',
  `delTime` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `isOrder` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否下单 0未下单1下单',
  `orderId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `orderTime` int(11) NOT NULL DEFAULT '0' COMMENT '下单时间',
  `spuName` varchar(128) NOT NULL DEFAULT '',
  `skuName` varchar(128) NOT NULL DEFAULT '',
  `unitPrice` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '下单时单品价格',
  `product_no` varchar(128) NOT NULL DEFAULT '' COMMENT '商品编号',
  `image` varchar(128) NOT NULL DEFAULT '' COMMENT '图片',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='代理购物车表';


ALTER TABLE `repertory` ADD `come_date` VARCHAR(128) NULL DEFAULT '' AFTER `area_id`, ADD `come_time` VARCHAR(128) NULL DEFAULT '' AFTER `come_date`, ADD `come_number` VARCHAR(128) NULL DEFAULT '' AFTER `come_time`;

ALTER TABLE `mp_temp_package_number` ADD `is_temp` INT NULL DEFAULT '0' COMMENT '是否临时1临时0正常';

-- 2019.5.29改价格字段属性
alter table `erp_stock_order` modify column `price` decimal(10,2);
alter table `erp_stock_order` modify column `pay_price` decimal(10,2);
alter table `erp_stock_order` modify column `freight` decimal(10,2);

-- 2019.5.31增加mp_shop_users表的business_id字段
alter table `mp_shop_users` add `business_id` int unsigned NOT NUll DEFAULT '0' COMMENT '事业部id';
-- 2019.6.3增加online_order_num字段
alter table `erp_stock_order` add column `online_order_num` varchar(32) NOT NULL DEFAULT '' COMMENT '在线订单编号' after `order_num`;

-- 2019.6.3杨晓杰修改
ALTER TABLE `mp_temp_package_number` ADD `temp_repertory_id` INT(11) NULL DEFAULT '0' COMMENT '从预约单打印的话，会直接绑定好预约单号';

-- 2019.6.6增加erp_setting表的小程序码字段
alter table `erp_setting` add `qrcode_image` varchar(128) DEFAULT NULL COMMENT '小程序码';

-- 新增代理索引
ALTER TABLE `erp_mp_name_spu_link`
ADD INDEX `select`(`mp_name_id`, `spu_id`, `status`, `flag`);
ALTER TABLE `erp_business_spu_link`
DROP INDEX `business_id`,
ADD INDEX `select`(`business_id`, `spu_id`, `flag`, `status`, `mp_name_id`, `class_id`);

-- 2019.6.11增加erp_agent_price表的original_price字段
alter table `erp_agent_price` add `original_price` decimal(10,2)  NOT NUll DEFAULT '0' COMMENT '原价' after `price`;
--erp_spu_list商品表加首图字段
alter table `erp_spu_list` add `img` varchar(128)  NOT NUll DEFAULT '' COMMENT '商品首图' after `sub_name`;

--erp_product_price加'最小销售单元'字段
alter table `erp_product_price` add `min_unit` int(11) unsigned NOT NUll DEFAULT '1' COMMENT '最小销售单元';

-- 2019.6.18改价格字段属性
alter table `erp_stock_order` modify column `substitute` decimal(10,2);

-- 2019.6.19 erp账户余额保留两位小数
ALTER TABLE `erp_account`
MODIFY COLUMN `balance` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '余额' AFTER `flag`;

-- 2019.6.19 下单保留两位小数
ALTER TABLE `erp_purchase_order`
MODIFY COLUMN `price` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '采购金额' AFTER `purchase_type`,
MODIFY COLUMN `rate` decimal(16, 6) NOT NULL DEFAULT 0 COMMENT '汇率' AFTER `currency`,
MODIFY COLUMN `pay_price` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '已支付金额' AFTER `pay_status`,
MODIFY COLUMN `weight_rate` decimal(16, 6) NOT NULL DEFAULT 1 COMMENT '加权汇率' AFTER `update_user_id`;

ALTER TABLE `erp_account_log`
MODIFY COLUMN `price` decimal(11, 2) NOT NULL DEFAULT 0 AFTER `currency`;

ALTER TABLE `erp_receive_goods_record`
MODIFY COLUMN `cost` decimal(11, 2) NOT NULL DEFAULT 0 AFTER `warehouse_id`;

ALTER TABLE `erp_purchase_order_goods`
MODIFY COLUMN `price` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '采购明细商品价格' AFTER `product_id`;


ALTER TABLE `repertory` ADD `is_door` INT(1) NULL DEFAULT '0' COMMENT '是否上门 1 已上门 0 未上门';

-- 7.3限时特价表
CREATE TABLE `erp_special_price` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mp_spu_link_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'mp_spu_link表id',
  `sku_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'sku_id',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '限时数量',
  `sold_num` int(11) NOT NULL DEFAULT '0' COMMENT '已下单数量',
  `price` decimal(10,2)  NOT NUll DEFAULT '0' COMMENT '限时价格',
  `date` date NULL DEFAULT NULL COMMENT '限时日期',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态，0正常1删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='限时特价表';
--erp_shop_cart加'special_id'字段
alter table `erp_shop_cart` add `special_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '0:普通,其他:限时特价的id';
alter table `erp_stock_order_info` add `special_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '0:普通,其他:限时特价的id';


--2019.7.9 erp_setting_banners
alter table `erp_setting_banners` add `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1:详情,2:分类,3:搜索';
alter table `erp_setting_banners` add `keyword` varchar(128) NOT NULL DEFAULT '' COMMENT '参数';
alter table `erp_setting_banners` add `title` varchar(128) NOT NULL COMMENT '标题';
alter table `erp_setting_banners` DROP COLUMN `spu_id`;
alter table `erp_setting_banners` add `sort_index` tinyint(255) unsigned COMMENT '展示时的排列顺序';

-- 7.9套餐功能erp_spu_list加'type'字段
alter table `erp_spu_list` add `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通spu,1:套餐spu';
alter table `erp_product_price` add `union_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '套餐数量';
alter table `erp_product_price` add `union_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '套餐摆盘,0:下架,1:上架';
alter table `erp_mp_name_spu_link` add `union_flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '馆区套餐状态,0:正常显示,1:不显示';
alter table `erp_shop_cart` add `union_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '0:普通,其他:套餐spu的id';
alter table `erp_stock_order_info` add `union_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '0:普通,其他:套餐spu的id';
alter table `erp_special_price` add `sort_index` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '排序';
alter table `erp_shop_cart` modify column `special_id` varchar(255) NOT NUll DEFAULT '0' COMMENT '0:普通,其他:限时特价的id';
alter table `erp_stock_order_info` modify column `special_id` varchar(255) NOT NUll DEFAULT '0' COMMENT '0:普通,其他:限时特价的id';

--order表加身份证id
alter table `erp_stock_order` add `ident_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT 'erp_shopmp_identity_card的id';

--mp_operation_log加wx_user_id字段
alter table `mp_operation_log` add `wx_user_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '0:未登录用户,其他:wxuser表的id';

--临时存储表，用完即删
CREATE TABLE `erp_log_temp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `spu_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `spu_name` varchar(255)  NOT NULL DEFAULT '' COMMENT '商品名称',
  `total` int(11) NOT NULL DEFAULT '0' COMMENT '总浏览量',
  `link_id` int(11) NOT NULL DEFAULT '0' COMMENT 'link_id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='临时日志存储';

--备份表，用于记录订单生成的在线支付订单号
CREATE TABLE `erp_online_order_backup` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `online_order_num` varchar(255)  NOT NULL DEFAULT '' COMMENT 'online_order_num',
  `before_pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付前的状态',
  `before_pay_method` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付前的状态',
  `after_pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付后的状态',
  `after_pay_method` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付后的状态',
  `created_at` varchar(255) NOT NULL DEFAULT '' COMMENT '创建时间',
  `updated_at` varchar(255) NOT NULL DEFAULT '' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='在线订单号记录备份';

ALTER TABLE `wxuser` ADD COLUMN `in_black_list` tinyint(255) UNSIGNED NOT NULL DEFAULT 0 COMMENT '不计入统计报表中' AFTER `source`;

--在线支付post data记录
CREATE TABLE `erp_online_pay_post_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inputCharset` varchar(255)  NOT NULL DEFAULT '' COMMENT '编码方式',
  `pageUrl` varchar(255)  NOT NULL DEFAULT '' COMMENT '接收支付结果的页面地址',
  `bgUrl` varchar(255)  NOT NULL DEFAULT '' COMMENT '服务器接收支付结果的后台地址',
  `version` varchar(255)  NOT NULL DEFAULT '' COMMENT '网关版本',
  `language` varchar(255)  NOT NULL DEFAULT '' COMMENT '语言种类',
  `signType` varchar(255)  NOT NULL DEFAULT '' COMMENT '签名类型',
  `terminalId` varchar(255)  NOT NULL DEFAULT '' COMMENT '终端号',
  `payerName` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人姓名',
  `payerContactType` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人联系类型',
  `payerContact` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人联系方式',
  `payerIdentityCard` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人身份证号码',
  `mobileNumber` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人手机号',
  `cardNumber` varchar(255)  NOT NULL DEFAULT '' COMMENT '支持人所持卡号',
  `customerId` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人在商户系统的客户编号',
  `orderId` varchar(255)  NOT NULL DEFAULT '' COMMENT '商户订单号',
  `settlementCurrency` varchar(255)  NOT NULL DEFAULT '' COMMENT '结算币种',
  `orderCurrency` varchar(255)  NOT NULL DEFAULT '' COMMENT '订单币种',
  `orderAmount` varchar(255)  NOT NULL DEFAULT '' COMMENT '订单金额，分',
  `orderTime` varchar(255)  NOT NULL DEFAULT '' COMMENT '订单提交时间',
  `inquireTrxNo` varchar(255)  NOT NULL DEFAULT '' COMMENT '询盘流水号',
  `productName` varchar(255)  NOT NULL DEFAULT '' COMMENT '商品名称',
  `productNum` varchar(255)  NOT NULL DEFAULT '' COMMENT '商品数量',
  `productId` varchar(255)  NOT NULL DEFAULT '' COMMENT '商品代码',
  `productDesc` varchar(255)  NOT NULL DEFAULT '' COMMENT '商品描述',
  `ext1` varchar(255)  NOT NULL DEFAULT '' COMMENT '扩展字段1',
  `ext2` varchar(255)  NOT NULL DEFAULT '' COMMENT '扩展字段2',
  `openId` varchar(255)  NOT NULL DEFAULT '' COMMENT 'openID',
  `deviceType` varchar(255)  NOT NULL DEFAULT '' COMMENT '1：pc端支付2：移动端支付',
  `payType` varchar(255)  NOT NULL DEFAULT '' COMMENT '1：微信扫码（返回二维码）2：支付宝扫码 8:小程序',
  `bankId` varchar(255)  NOT NULL DEFAULT '' COMMENT '银行代码',
  `customerIp` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付人Ip',
  `redoFlag` varchar(255)  NOT NULL DEFAULT '' COMMENT '实物购物车填1，虚拟产品用0',
  `signMsg` varchar(255)  NOT NULL DEFAULT '' COMMENT 'RSA 签名',
  `stockOrderIds` varchar(255) NOT NULL DEFAULT '' COMMENT 'erp_stock_order的id集合，逗号分割',
  `payResult` varchar(255)  NOT NULL DEFAULT '' COMMENT '支付结果',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='在线支付post data记录';


alter table `erp_stock_order_info` add `union_number` int(11) unsigned NOT NUll DEFAULT '0' COMMENT '下单的套餐数量';

--营销相关表----------------------------start------------------------------------------
--运费
CREATE TABLE `erp_market_freight` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '活动名称',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '活动内容',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:满金额减,2:满金额包邮,3:满数量减,4:满数量包邮',
  `policy` varchar(255) NOT NULL DEFAULT '' COMMENT '对应type的具体策略',
  `vip` varchar(255) NOT NULL DEFAULT '' COMMENT '适用vip人群,空为全部',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `begin_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '参与该优惠总人数',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态，0正常1删除',
  PRIMARY KEY (`id`),
  KEY `begin_at` (`begin_at`,`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:运费表';

CREATE TABLE `erp_market_freight_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `market_freight_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'erp_market_freight表id',
  `link_type`  varchar(255) NOT NULL DEFAULT '' COMMENT '关联的模型名,有标签,馆区,分类,sku,..',
  `link_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联type类型表的id',
  PRIMARY KEY (`id`),
  KEY `market_freight_id` (`market_freight_id`),
  KEY `link_id` (`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:运费关联表';

CREATE TABLE `erp_sku_tag_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
  `sku_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'SkUID',
  PRIMARY KEY (`id`),
  KEY `tag_id` (`tag_id`),
  KEY `sku_id` (`sku_id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8 COMMENT='sku-标签关联表';

alter table `erp_stock_order` add `market_freight_id` int(11) unsigned NOT NUll DEFAULT '0' COMMENT 'erp_market_freight的id,用户选择的运费优惠';
alter table `erp_stock_order` add `origin_freight` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '优惠前的运费';

--增加权限
INSERT INTO `erp_f_permissions` (`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (82, '营销管理', '0', '0', '1551542685', '1551542685', '12');
INSERT INTO `erp_f_permissions` (`id`, `name`, `fid`, `sort`, `created_at`, `updated_at`, `sort_str`) VALUES (83, '运费优惠', '78', '0', '1551542685', '1551542685', '12.1');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (222, '列表', 'web', '2019-08-02 17:26:01', '2019-08-02 17:26:06', '79', '12.1.1');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (223, '添加', 'web', '2019-08-02 17:26:01', '2019-08-02 17:26:06', '79', '12.1.2');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (224, '编辑', 'web', '2019-08-02 17:26:01', '2019-08-02 17:26:06', '79', '12.1.3');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`, `fid`, `sort_str`) VALUES (225, '删除', 'web', '2019-08-02 17:26:01', '2019-08-02 17:26:06', '79', '12.1.4');

--优惠券
CREATE TABLE `erp_market_coupons` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '优惠券内容',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:运费优惠券,2:普通优惠券',
  `full` varchar(255) NOT NULL DEFAULT '0' COMMENT '满多少金额',
  `decr` varchar(255) NOT NULL DEFAULT '0' COMMENT '减多少金额',
  `is_plus` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否支持叠加使用,0不支持,1支持',
  `is_need_receive` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否需要领取,0不需要,1需要',
  `number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发放数量',
  `audience` varchar(255) NOT NULL DEFAULT '' COMMENT '适用vip人群(另加新人),空为全部,01234new',
  `show_position` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '展示位置',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `begin_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券领取开始时间',
  `end_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券领取结束时间',
  `use_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '使用方式,1领券后多长时间可以使用,2固定时间前可以使用',
  `use_term` varchar(255) NOT NULL DEFAULT '' COMMENT '对应使用方式的使用期限',
  `use_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已使用该优惠的数量',
  `receive_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已领取该优惠的数量',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态，0正常1关闭',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:优惠券表';

CREATE TABLE `erp_market_coupon_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `market_coupon_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'erp_market_coupons表id',
  `link_type`  varchar(255) NOT NULL DEFAULT '' COMMENT '关联的模型名,有标签,馆区,分类,sku,..',
  `link_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联type类型表的id',
  PRIMARY KEY (`id`),
  KEY `market_coupon_id` (`market_coupon_id`),
  KEY `link_id` (`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:优惠券关联表';

CREATE TABLE `erp_user_coupon_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `market_coupon_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'erp_market_coupons表id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'wxuser_id',
  `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '状态0正常,1已使用,2已失效',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '领取时间',
  `invalid_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '失效时间',
  PRIMARY KEY (`id`),
  KEY `market_coupon_id` (`market_coupon_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:用户优惠券关联表';

CREATE TABLE `erp_order_coupon_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `market_coupon_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'erp_market_coupons表id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'wxuser_id',
  PRIMARY KEY (`id`),
  KEY `market_coupon_id` (`market_coupon_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:订单使用的优惠券';

alter table `wxuser` add `is_new` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是不是新人(从没有下过单),0不是,1是';
alter table `wxuser` add `last_popup_at` varchar(255) NOT NULL DEFAULT '' COMMENT '上次弹窗时间';
--团购
CREATE TABLE `erp_market_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mp_spu_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'mp spu的link id',
  `spu_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'spu类型0普通1套餐',
  `group_people_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '成团人数',
  `support_bot` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否支持机器人成团1:支持,0:不支持',
  `begin_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '团购开始时间',
  `end_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '团购结束时间',
  `duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每个开的团持续时间(分钟)',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态，0正常1关闭',
  `union_group_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '套餐参加团购数量,以下几个字段冗余，在spu type是套餐时才用到',
  `union_origin_price` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '套餐原价',
  `union_group_price` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '套餐团购价',
  `union_buy_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '套餐已购买数量',
  PRIMARY KEY (`id`),
  KEY `mp_spu_id` (`mp_spu_id`),
  KEY `created_at` (`created_at`),
  KEY `begin_at`(`begin_at`,`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:团购主表';

CREATE TABLE `erp_market_group_details` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '团购主表id',
  `price_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'erp product price表id',
  `group_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '参加团购数量',
  `origin_price` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '原价',
  `group_price` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '团购价',
  `buy_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已购买数量',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `price_id` (`price_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:团购副表';

CREATE TABLE `erp_market_open_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '团购主表id',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开团时间',
  `invalid_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '该团结束时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态，0拼团中1拼团成功2拼团失败',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:团购开团记录表';

CREATE TABLE `erp_market_group_buyers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `open_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开团记录表id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '买家(参团者)id,即wxuser的id',
  `group_detail_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '团购副表id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order订单id',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '参团时间',
  `is_bot` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否机器人1是0不是',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态1已支付0未支付',
  PRIMARY KEY (`id`),
  KEY `open_id` (`open_id`),
  KEY `user_id` (`user_id`),
  KEY `group_detail_id` (`group_detail_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销功能:团购买家(参团者)详细表';

alter table `erp_stock_order` add `group_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT
'团购订单状态0不是团购单,1拼单中未付款,2拼单中待发货,3拼单成功未付款,4拼单成功待发货,5拼单失败退款中';
alter table `erp_stock_order` add `group_price` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '团购价或团购原价';

CREATE TABLE `wxuser_bot` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `head_img` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='机器人用户数据';
--限购
alter table `erp_spu_list` add `limit_buy_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '限购类型0:不限制,1:每人限制,2:会员限制';
alter table `erp_spu_list` add `limit_buy_number` varchar(255) NOT NULL DEFAULT '' COMMENT '每人限购数量';
alter table `erp_spu_list` add `vip0` varchar(255) NOT NULL DEFAULT '' COMMENT 'vip0限购数量';
alter table `erp_spu_list` add `vip1` varchar(255) NOT NULL DEFAULT '' COMMENT 'vip1限购数量';
alter table `erp_spu_list` add `vip2` varchar(255) NOT NULL DEFAULT '' COMMENT 'vip2限购数量';
alter table `erp_spu_list` add `vip3` varchar(255) NOT NULL DEFAULT '' COMMENT 'vip3限购数量';
alter table `erp_spu_list` add `vip4` varchar(255) NOT NULL DEFAULT '' COMMENT 'vip4限购数量';
alter table `erp_spu_list` add `cycle` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '限购周期';
--限购记录表
CREATE TABLE `erp_limit_buy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城小程序用户id',
  `spu_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'spuid',
  `sku_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'skuid',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `created_at` varchar(255) NOT NULL DEFAULT '0' COMMENT '创建时间Ymd',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `spu_id` (`spu_id`),
  KEY `sku_id` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='限购记录表';
--营销相关表----------------------------end------------------------------------------

--2019-08-12发货地址
CREATE TABLE `erp_mp_shop_send_address` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城小程序用户id',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '发货人',
  `phone` varchar(24) NOT NULL DEFAULT '' COMMENT '手机号',
  `province` varchar(64) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(64) NOT NULL DEFAULT '' COMMENT '市',
  `area` varchar(64) NOT NULL DEFAULT '' COMMENT '区',
  `detail` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `idNumber` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号',
  `imageFront` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证正面',
  `imageBack` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证反面',
  `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
  `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
  `isDel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0正常 1删除',
  `isDefault` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认 0否1是',
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=1165 DEFAULT CHARSET=utf8 COMMENT='商城小程序发货地址';
--申请退款记录表
CREATE TABLE `erp_refunds` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `request_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '申请退款原因',
  `result` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '操作结果0未操作，1已同意退款，2已拒绝退款',
  `reason` varchar(255) NOT NULL DEFAULT '' COMMENT '同意/拒绝原因',
  `operate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '申请时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1165 DEFAULT CHARSET=utf8 COMMENT='申请退款记录表';

alter table `erp_stock_order` add `mp_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '馆区id';

-- 换服务器之前先用着
CREATE TABLE `wx_access_token`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `access_token` varchar(255) NOT NULL DEFAULT '',
  `expires` varchar(255) NOT NULL DEFAULT '',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
);

--2019.9.6安全库存字段
alter table `erp_product_price` add `safe_stock` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '安全库存';
alter table `erp_mp_name_spu_link` add `sort_index` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序(优先级最高)';
alter table `erp_mp_name_spu_link` add `weight_index` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '权重排序';

alter table `erp_product_price` add `market_price` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '市场价格';

--采购订单手动填写的运费字段
alter table `erp_purchase_order` add `freight` decimal(10,2) NULL DEFAULT NUll COMMENT '手动填写的运费';
--2019.9.16采购订单手动填写的运费字段


--小程序商城后台注册/登录功能 2019年9月17日
-- 验证码表
CREATE TABLE `erp_verification_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` int(10) unsigned NOT NULL COMMENT '验证码',
  `type` tinyint(3) unsigned NOT NULL COMMENT '验证码类型 0 注册 1登录 2找回密码',
  `status` tinyint(3) unsigned DEFAULT '0' COMMENT '0未使用 1已用',
  `mobile` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号',
  `uid` int(10) unsigned DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信验证码表';

-- 用户表新增mobile字段
ALTER TABLE `erp`.`users` ADD COLUMN `mobile` varchar(15) NULL COMMENT '手机号 可空兼容旧数据' AFTER `name`;


-- 分销相关
alter table `erp_spu_list` add `is_public` int(1) NOT NULL DEFAULT '0' COMMENT '是否公有';
alter table `erp_product_list` add `is_public` int(1) NOT NULL DEFAULT '0' COMMENT '是否公有';
alter table `erp_product_list` add `business_id` int(11) NOT NULL DEFAULT '49' COMMENT '事业部id';
alter table `erp_product_list` add `public_no` varchar(128) NULL DEFAULT NULL COMMENT '公码';
alter table `erp_product_list` add `origin_price` decimal(10,2) DEFAULT NULL COMMENT '成本价,采购价';
alter table `erp_product_list` add  `sku_info` varchar(255) DEFAULT NULL COMMENT '规格详情。json';

alter table `erp_stock_order` add `relate_purchase_order` int(11) NOT NULL DEFAULT '0' COMMENT '关联采购订单';
alter table `erp_spu_list` add  `delivery_type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '配送方式。1快递发货2同城配送3到店自提 3者之和';
alter table `erp_spu_list` add   `logistic_pay` tinyint(2) NOT NULL DEFAULT '2' COMMENT '快递运费收费 1统一邮费 2 运费模版';
alter table `erp_spu_list` add   `logistic_info` varchar(30) DEFAULT NULL COMMENT '运费 或运费模版id';
alter table `erp_spu_list` add   `marked_price` decimal(10,2) DEFAULT NULL COMMENT '划线价格';

CREATE TABLE `erp_sku_public_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `new_num` int(11) NOT NULL,
  `old_num` int(11) NOT NULL,
  `type` enum('order','orderback','apply','change') NOT NULL DEFAULT 'order',
  `order_id` int(11) DEFAULT NULL COMMENT '关联订单id',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `com_index` (`sku_id`,`business_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `erp_sku_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL COMMENT '需要审核的skuid',
  `sku_num` int(11) DEFAULT '0' COMMENT '提交公有数量',
  `price` decimal(10,2) NOT NULL COMMENT '提交的售价',
  `business_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `act_user` int(11) DEFAULT NULL COMMENT '审核用户',
  `reviewd_at` int(11) DEFAULT NULL,
  `can_buy_num` int(11) DEFAULT '0',
  `pic` varchar(128) DEFAULT NULL,
  `to_store_house_id` int(11) DEFAULT NULL COMMENT '放在哪个库位',
  `private_no` varchar(50) NOT NULL COMMENT '私product_no',
  `public_no` varchar(50) DEFAULT NULL COMMENT '公public_no',
  `reason` varchar(255) DEFAULT NULL COMMENT '失败原因',
  PRIMARY KEY (`id`),
  KEY `business_Id` (`business_id`) USING BTREE,
  KEY `fix_index` (`business_id`,`sku_id`,`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Sku审核表';


CREATE TABLE `erp_public_stock_purchase_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `main_stock_id` int(11) NOT NULL COMMENT '主订单',
  `order_Id` int(11) DEFAULT NULL,
  `type` tinyint(1) DEFAULT '0' COMMENT '0stock_order 1purchase_order',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--会员卡
CREATE TABLE `erp_vip_cards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '会员卡名称',
  `back_color` varchar(255) NOT NULL DEFAULT '' COMMENT '十六进制颜色',
  `back_img` varchar(255) NOT NULL DEFAULT '' COMMENT '背景图片',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '会员卡等级',
  `validity_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '有效期1:永久有效2:领卡后多长时间失效3:时间段内有效',
  `begin_at` int(11) NOT NULL DEFAULT '0' COMMENT '开始有效期',
  `end_at` int(11) NOT NULL DEFAULT '0' COMMENT '结束有效期',
  `receive_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '领取类型1:直接领取2:付费或条件领取',
  `condition_data` text NOT NULL DEFAULT '' COMMENT '当receive_type为2时的领取条件,1:标准方式2:多种付费方式3:累计消费金额4:累计总经验值5:购买指定商品',
  `rights` text NOT NULL DEFAULT '' COMMENT '权益1:消费折扣2:包邮3:积分回馈4好友体验卡',
  `business_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事业部id',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否禁用',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '会员卡';

CREATE TABLE `erp_wxuser_vip_cards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `card_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员卡id',
  `wxuser_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户表wxuser的id',
  `gived_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '0:不是赠送卡(自己购买/系统自动发放),其他:赠送人id',
  `give_count` varchar(255) NOT NULL DEFAULT '' COMMENT '如果这张会员卡有赠送的权益,那么该字段记录已赠送了多少张,格式:{会员卡id:赠送次数}',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `invalid_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  KEY `wxuser_id` (`wxuser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '用户-会员卡关联表';

CREATE TABLE `erp_business_spu_link_new` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) unsigned NOT NULL DEFAULT '0',
  `spu_id` int(11) unsigned NOT NULL DEFAULT '0',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0正常1删除',
  `new_spu_id` int(11) NOT NULL COMMENT '关联分销商的spuid',
  PRIMARY KEY (`id`),
  KEY `select` (`business_id`,`spu_id`,`flag`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='事业部-SPU关联表';

--  储值卡表 2019年9月20日
CREATE TABLE `erp_store_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '事业部id',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `price` decimal(10,2) NOT NULL COMMENT '价格',
  `vip_card` int(11) DEFAULT NULL COMMENT '赠送会员卡id',
  `vip_time` int(11) DEFAULT NULL COMMENT '赠送会员时常秒',
  `integral` int(11) DEFAULT NULL COMMENT '赠送积分',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序后台：储值卡';

ALTER TABLE `erp`.`erp_store_card`
ADD COLUMN `flag` tinyint(4) UNSIGNED NULL DEFAULT 1 COMMENT '1可用 0禁用' AFTER `integral`;

--  成长值表 2019年9月23日
CREATE TABLE `erp_growth_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) unsigned NOT NULL COMMENT '事业部id',
  `setting` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '设置项',
  `created_at` int(11) DEFAULT NULL COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序后台：成长值设置';

--  积分设置表 2019年9月23日
CREATE TABLE `erp_integral_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) unsigned NOT NULL COMMENT '事业部id',
  `deduction_per` int(10) unsigned NOT NULL COMMENT '积分抵扣百分比 （例如100积分=1元）',
  `single_use_limit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '单次使用限制',
  `integral_deduction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '积分抵扣',
  `get_way` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '积分获取方式',
  `clear` tinyint(3) unsigned NOT NULL COMMENT '积分清零类别 0 一直有效， 1 自然年清零上一年的积分，2 自然年清零所有积分',
  `created_at` int(11) DEFAULT NULL COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序后台：积分设置';


-- 用户积分成长值表2019年9月24日
CREATE TABLE `erp_wxuser_extend` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wxuser_id` int(10) unsigned NOT NULL,
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `growth_value` int(11) NOT NULL DEFAULT '0' COMMENT '成长值',
  `updated_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wxuser_id` (`wxuser_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序后台：用户的成长值和积分表';
--  修改用户和会员卡绑定表字段 新增 获取会员卡获取类型
ALTER TABLE `erp`.`erp_wxuser_vip_cards`
ADD COLUMN `get_way` tinyint(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员卡获取方式：0系统赠送 1自己购买 2会员赠送 3储值卡赠送' AFTER `wxuser_id`,
MODIFY COLUMN `gived_by` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送人id' AFTER `wxuser_id`,
MODIFY COLUMN `give_count` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '如果这张会员卡有赠送的权益,那么该字段记录已赠送了多少张,格式:{会员卡id:赠送次数}' AFTER `gived_by`;
ALTER TABLE `erp`.`erp_wxuser_vip_cards`
MODIFY COLUMN `invalid_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间： 0永不过期' AFTER `created_at`;
-- 积分流水表
CREATE TABLE `erp_integral_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wsuser_id` int(11) NOT NULL COMMENT '用户id 关联wxuser',
  `number` int(11) NOT NULL DEFAULT '0' COMMENT '积分变动数量(进一位取整)',
  `get_type` tinyint(4) NOT NULL COMMENT '变动类型:1 消费订单获得 2 赠送好友会员卡获得 3 储值卡获得 4 订单金额抵扣 5 运费抵扣 6兑换商品抵扣 7 积分清理',
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单id',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `wxuser_id` (`wsuser_id`) USING BTREE,
  KEY `get_type` (`get_type`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分流水记录';
- 成长值流水记录
CREATE TABLE `erp_growth_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wsuser_id` int(11) NOT NULL COMMENT '用户id 关联wxuser',
  `number` int(11) NOT NULL DEFAULT '0' COMMENT '成长值变动数量(进一位取整)',
  `get_type` tinyint(4) NOT NULL COMMENT '变动类型: 1 自动增长 2 签到 3 单笔交易 4 单笔支付满多少元获得',
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单id',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `wxuser_id` (`wsuser_id`) USING BTREE,
  KEY `get_type` (`get_type`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='成长值流水记录';


--------- 店铺信息注册
ALTER TABLE `erp_business` ADD column shop_address_province varchar(20) NOT NULL DEFAULT '' COMMENT '店铺地址-省';
ALTER TABLE `erp_business` ADD column shop_address_city varchar(20) NOT NULL DEFAULT '' COMMENT '店铺地址-市';
ALTER TABLE `erp_business` ADD column shop_address_area varchar(20) NOT NULL DEFAULT '' COMMENT '店铺地址-区';
ALTER TABLE `erp_business` ADD column shop_address_detail  varchar(100) NOT NULL DEFAULT '' COMMENT '店铺详细地址';