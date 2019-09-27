-- phpMyAdmin SQL Dump
-- version 2.10.3
-- http://www.phpmyadmin.net
-- 
-- 主机: localhost
-- 生成日期: 2019 年 02 月 28 日 03:48
-- 服务器版本: 5.0.51
-- PHP 版本: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- 数据库: `lot888`
-- 

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_admin`
-- 

CREATE TABLE `xz_admin` (
  `id` int(10) NOT NULL auto_increment,
  `admin_name` varchar(20) NOT NULL,
  `admin_pass` varchar(50) NOT NULL,
  `lastLoginTime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `nType` int(3) NOT NULL default '0',
  `nDel` int(3) NOT NULL default '0',
  `ip` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- 
-- 导出表中的数据 `xz_admin`
-- 

INSERT INTO `xz_admin` VALUES (4, 'admin', 'e10adc3949ba59abbe56e057f20f883e', '2019-02-28 10:37:45', 0, 0, '127.0.0.1');

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_exchange`
-- 

CREATE TABLE `xz_exchange` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(100) NOT NULL,
  `nProductId` int(10) NOT NULL default '0',
  `nUserId` int(10) NOT NULL,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `username` varchar(20) NOT NULL,
  `money` int(11) NOT NULL,
  `sname` varchar(50) NOT NULL,
  `address` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- 导出表中的数据 `xz_exchange`
-- 

INSERT INTO `xz_exchange` VALUES (1, 'iPad', 1, 1, '2018-10-28 17:10:54', '', 100, '1111', '1111111111111111', '13800138000');

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_form`
-- 

CREATE TABLE `xz_form` (
  `id` int(10) NOT NULL,
  `title` varchar(50) NOT NULL,
  `nType` int(2) NOT NULL default '0',
  `nOrder` int(5) NOT NULL default '0',
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- 导出表中的数据 `xz_form`
-- 


-- --------------------------------------------------------

-- 
-- 表的结构 `xz_lottery`
-- 

CREATE TABLE `xz_lottery` (
  `id` int(10) NOT NULL auto_increment,
  `mobile` varchar(30) NOT NULL,
  `nStatus` int(11) NOT NULL,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ip` varchar(30) NOT NULL,
  `remarks` varchar(200) NOT NULL,
  `jiangPin` varchar(50) NOT NULL default '0',
  `company` varchar(100) NOT NULL,
  `nUserId` int(10) NOT NULL default '0',
  `username` varchar(50) NOT NULL,
  `nRecId` int(11) NOT NULL default '0',
  `jpTitle` varchar(50) NOT NULL,
  `nPrizeId` int(10) NOT NULL default '0',
  `cjm` varchar(50) NOT NULL,
  `nJPTag` varchar(2) NOT NULL default '0',
  `nPayed` int(2) NOT NULL default '0',
  `paytime` datetime NOT NULL,
  `orderId` varchar(50) NOT NULL,
  `nMoney` decimal(10,2) NOT NULL default '0.00',
  `cash_info` varchar(200) NOT NULL,
  `cash_time` datetime NOT NULL,
  `sname` varchar(50) NOT NULL,
  `nThemeId` int(10) NOT NULL default '0',
  `address` varchar(200) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=134 ;

-- 
-- 导出表中的数据 `xz_lottery`
-- 

INSERT INTO `xz_lottery` VALUES (127, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345675', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (126, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345674', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (125, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345673', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (124, '13800138000', 1, '2019-02-22 21:46:25', '', '测试', '0', '1', 0, 'test', 0, '', 0, 'c12345672', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (123, '13800138000', 1, '2019-02-22 21:20:34', '', '测试', '0', '1', 0, '', 0, '', 0, 'c12345671', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', 'test', 0, '');
INSERT INTO `xz_lottery` VALUES (128, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345676', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (129, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345677', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (130, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345678', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (131, '', 0, '2019-02-22 21:20:34', '', '测试', '0', '', 0, '', 0, '', 0, 'c12345679', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (132, '', 0, '2019-02-26 09:51:37', '127.0.0.1', '', 'iPhones XS max', '', 27, '', 0, '', 1, '', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');
INSERT INTO `xz_lottery` VALUES (133, '', 0, '2019-02-27 18:23:35', '', '', '0', '', 0, '', 0, '', 0, '1111', '0', 0, '0000-00-00 00:00:00', '', 0.00, '', '0000-00-00 00:00:00', '', 0, '');

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_lottery_prize`
-- 

CREATE TABLE `xz_lottery_prize` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(50) NOT NULL,
  `nNum` int(5) NOT NULL default '0',
  `nOrder` int(5) NOT NULL default '0',
  `title2` varchar(50) NOT NULL,
  `pic` varchar(100) NOT NULL,
  `nTag` int(5) NOT NULL default '0',
  `url` varchar(200) NOT NULL,
  `pic2` varchar(200) NOT NULL,
  `pic3` varchar(200) NOT NULL,
  `txt` varchar(200) NOT NULL,
  `nMoney` decimal(10,2) NOT NULL default '0.00',
  `nThemeId` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- 
-- 导出表中的数据 `xz_lottery_prize`
-- 

INSERT INTO `xz_lottery_prize` VALUES (1, 'iPhones XS max', 100, 0, '特等奖', 'Uploadfiles/6fd36c29c007e1fd1459e92c2345e941.jpg', 0, '', '', '', '玻璃水、防冻液、洗车桶、洗车刷、停车卡、工具箱、应急手电筒、擦车巾、方向盘套、脚垫、坐垫、静电贴', 0.00, 0);
INSERT INTO `xz_lottery_prize` VALUES (2, 'iPhone X', 1, 0, '一等奖', 'Uploadfiles/05301147a860937ee2d0bfea6e7e1b4b.jpg', 0, '', '', '', '', 0.00, 0);
INSERT INTO `xz_lottery_prize` VALUES (3, 'iPad mini', 0, 0, '二等奖', 'Uploadfiles/b8a695b7b10471f1ab670b2d0a243939.jpg', 0, '', '', '', '领取奖品需要支付10元', 0.00, 0);
INSERT INTO `xz_lottery_prize` VALUES (4, '免单', 0, 0, '三等奖', 'Uploadfiles/7ce0621682b3a74502a22e0303a8c996.png', 0, '', '', '', '', 0.00, 0);
INSERT INTO `xz_lottery_prize` VALUES (5, '1元红包', 0, 0, '幸运奖', 'Uploadfiles/ab62304855b6599fff95421a3354cef3.png', 0, '', '', '', '', 0.00, 0);
INSERT INTO `xz_lottery_prize` VALUES (6, '谢谢参与', 0, 0, '六等奖', 'Uploadfiles/da85bf82d5bbbc704d1c18097331b434.png', 1, '', '', '', '', 0.00, 0);
INSERT INTO `xz_lottery_prize` VALUES (10, '小鲜电影', 0, 0, '一等奖', '', 0, '', '', '', '', 0.00, 1);
INSERT INTO `xz_lottery_prize` VALUES (11, '小鲜电影', 0, 0, '', '', 2, '', '', '', '', 0.00, 0);

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_msg`
-- 

CREATE TABLE `xz_msg` (
  `id` int(10) NOT NULL auto_increment,
  `msg_content` varchar(200) NOT NULL,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- 导出表中的数据 `xz_msg`
-- 

INSERT INTO `xz_msg` VALUES (1, '姓名:　测试 电话:　13800138000 收货地址: 浙江省杭州市西湖区1111', '2018-09-27 18:47:42');
INSERT INTO `xz_msg` VALUES (2, '中文', '2018-09-27 19:18:54');

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_product`
-- 

CREATE TABLE `xz_product` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(100) NOT NULL,
  `pic` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `money` int(5) NOT NULL default '0',
  `nOrder` int(2) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- 导出表中的数据 `xz_product`
-- 


-- --------------------------------------------------------

-- 
-- 表的结构 `xz_share`
-- 

CREATE TABLE `xz_share` (
  `id` int(10) NOT NULL auto_increment,
  `nUserId` int(10) NOT NULL,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `fopenId` varchar(100) NOT NULL,
  `fireNum` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- 导出表中的数据 `xz_share`
-- 

INSERT INTO `xz_share` VALUES (1, 1, '2018-12-05 18:36:52', '', 1);

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_stock`
-- 

CREATE TABLE `xz_stock` (
  `id` int(10) NOT NULL auto_increment,
  `nPrizeId` int(10) NOT NULL default '0',
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `nStatus` int(2) NOT NULL default '0',
  `nThemeId` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1246 ;

-- 
-- 导出表中的数据 `xz_stock`
-- 

INSERT INTO `xz_stock` VALUES (106, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (107, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (108, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (109, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (110, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (111, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (112, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (113, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (114, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (115, 1, '2019-01-01 12:12:14', 1, 0);
INSERT INTO `xz_stock` VALUES (116, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (117, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (118, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (119, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (120, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (121, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (122, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (123, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (124, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (125, 1, '2019-01-07 14:07:04', 1, 0);
INSERT INTO `xz_stock` VALUES (1235, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1234, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1233, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1232, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1231, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1230, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1229, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1228, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1227, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1226, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1225, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1224, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1223, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1222, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1221, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1220, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1219, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1218, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1217, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1216, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1215, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1214, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1213, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1212, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1211, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1210, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1209, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1208, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1207, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1206, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1205, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1204, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1203, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1202, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1201, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1200, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1199, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1198, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1197, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1196, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1195, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1194, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1193, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1192, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1191, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1190, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1189, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1188, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1187, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1186, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1185, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1184, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1183, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1182, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1181, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1180, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1179, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1178, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1177, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1176, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1175, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1174, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1173, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1172, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1171, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1170, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1169, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1168, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1167, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1166, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1165, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1164, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1163, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1162, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1161, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1160, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1159, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1158, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1157, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1156, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1155, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1154, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1153, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1152, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1151, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1150, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1149, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1148, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1147, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1146, 1, '2019-02-17 00:48:27', 1, 0);
INSERT INTO `xz_stock` VALUES (1145, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1144, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1143, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1142, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1141, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1140, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1139, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1138, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1137, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1136, 1, '2019-02-17 00:48:27', 0, 0);
INSERT INTO `xz_stock` VALUES (1135, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1134, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1133, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1132, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1131, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1130, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1129, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1128, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1127, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1126, 1, '2019-01-27 21:04:10', 1, 0);
INSERT INTO `xz_stock` VALUES (1236, 10, '2019-02-18 22:35:29', 1, 1);
INSERT INTO `xz_stock` VALUES (1237, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1238, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1239, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1240, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1241, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1242, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1243, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1244, 10, '2019-02-18 22:35:29', 0, 1);
INSERT INTO `xz_stock` VALUES (1245, 10, '2019-02-18 22:35:29', 0, 1);

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_user`
-- 

CREATE TABLE `xz_user` (
  `id` int(10) NOT NULL auto_increment,
  `username` varchar(20) NOT NULL,
  `pswd` varchar(40) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `nStatus` int(2) NOT NULL default '0',
  `email` varchar(50) NOT NULL,
  `qq` varchar(30) NOT NULL,
  `birthday` date NOT NULL,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `icon_path` varchar(200) NOT NULL,
  `nPoint` int(10) NOT NULL default '0',
  `profile` text NOT NULL,
  `addr` varchar(200) NOT NULL,
  `nSex` int(1) NOT NULL default '0',
  `weixin` varchar(30) NOT NULL,
  `realName` varchar(30) NOT NULL,
  `sPicPath` varchar(100) NOT NULL,
  `nType` int(1) NOT NULL default '0',
  `preLogintime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `lastLogintime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `nMoeny` decimal(10,0) NOT NULL default '0',
  `zip` varchar(20) NOT NULL,
  `wxopenId` varchar(100) NOT NULL,
  `fireNum` int(5) NOT NULL default '0',
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `nFUserId` int(10) NOT NULL default '0',
  `subscribe` bigint(2) NOT NULL default '0',
  `nThemeId` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=28 ;

-- 
-- 导出表中的数据 `xz_user`
-- 

INSERT INTO `xz_user` VALUES (26, 'test', '', '', 0, '', '', '0000-00-00', '2019-01-20 16:01:11', '', 0, '', '', 0, '', '', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', 0, '', '', 0, 0, 0);
INSERT INTO `xz_user` VALUES (27, '', 'e10adc3949ba59abbe56e057f20f883e', '13800138000', 0, '', '', '0000-00-00', '2019-02-26 09:47:37', '', 0, '', '', 0, '', '', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', 8, '', '', 0, 0, 0);

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_visit`
-- 

CREATE TABLE `xz_visit` (
  `id` int(11) NOT NULL auto_increment,
  `subtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ip` varchar(30) NOT NULL,
  `browse` varchar(200) NOT NULL,
  `nUserId` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

-- 
-- 导出表中的数据 `xz_visit`
-- 

INSERT INTO `xz_visit` VALUES (2, '2018-12-15 19:35:15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (3, '2018-12-16 09:50:49', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (4, '2018-12-16 09:54:17', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (5, '2018-12-16 10:18:26', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (6, '2018-12-16 10:18:47', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (7, '2018-12-16 10:23:36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (8, '2018-12-16 10:24:21', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (9, '2018-12-16 10:24:56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (10, '2018-12-16 10:26:01', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (11, '2018-12-16 10:26:48', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (12, '2018-12-16 10:27:03', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (13, '2018-12-16 10:29:04', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (14, '2018-12-16 10:29:06', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (15, '2018-12-16 10:29:32', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (16, '2018-12-16 10:29:57', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (17, '2018-12-16 10:31:04', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (18, '2018-12-16 10:33:37', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (19, '2018-12-16 10:34:38', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (20, '2018-12-16 10:46:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (21, '2018-12-16 10:46:35', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (22, '2018-12-16 10:47:59', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (23, '2018-12-16 10:53:11', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (24, '2018-12-16 10:55:20', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (25, '2018-12-16 12:50:34', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (26, '2018-12-16 13:44:15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (27, '2018-12-16 13:45:53', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (28, '2018-12-16 13:46:37', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);
INSERT INTO `xz_visit` VALUES (29, '2018-12-16 13:47:06', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36', 3);

-- --------------------------------------------------------

-- 
-- 表的结构 `xz_webconfig`
-- 

CREATE TABLE `xz_webconfig` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `web_name` varchar(100) NOT NULL,
  `home_title` varchar(100) NOT NULL,
  `logo_path` varchar(100) NOT NULL,
  `js_code` text NOT NULL,
  `contactInfo` text NOT NULL,
  `web_copyright` text NOT NULL,
  `txt1` varchar(100) NOT NULL,
  `txt2` text NOT NULL,
  `sHour` varchar(10) NOT NULL,
  `nTimes` int(5) NOT NULL default '0',
  `sharetime` int(2) NOT NULL default '0',
  `fireNum2` int(2) NOT NULL default '0',
  `nLotTimes` int(25) NOT NULL default '0',
  `sharenum` int(5) NOT NULL default '0',
  `startTime` datetime NOT NULL,
  `endTime` datetime NOT NULL,
  `initNum` int(11) NOT NULL default '0',
  `dj_pswd` varchar(50) NOT NULL,
  `bg_url` varchar(200) NOT NULL,
  `music_url` varchar(200) NOT NULL,
  `nMusicTag` int(2) NOT NULL default '0',
  `nNeedGZ` int(2) NOT NULL default '0',
  `ewm_pic` varchar(200) NOT NULL,
  `nThemeId` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- 
-- 导出表中的数据 `xz_webconfig`
-- 

INSERT INTO `xz_webconfig` VALUES (1, '幸运大抽奖', '', '', '', '', '', '感谢参与，请留意大屏幕的中奖信息', '这里填写活动规则', '15:00', 8, 0, 2, 1, 0, '2019-01-01 00:00:01', '2019-01-09 23:59:59', 0, '123456', 'Uploadfiles/26e2614a7ce03cad456e2df384b96e6c.jpg', '', 0, 0, '', 0);
INSERT INTO `xz_webconfig` VALUES (4, '2', '', '', '', '', '', '', '', '', 0, 0, 0, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, '', 0);
INSERT INTO `xz_webconfig` VALUES (5, '九宫格', '', '', '', '', '', '', '', '', 0, 0, 0, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, '', '', '', 0, 0, '', 1);
