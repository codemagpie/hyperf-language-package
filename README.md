# 多语言
## 简介
这个包,只适用于hyperf框架,用于多语言的配置,原理是使用包里面的翻译器替换掉hyperf框架子自带的翻译器。
## 安装
```shell
composer douyu/hyperf-language-package
```
## 使用说明
### 1.创建表
ps: 请根据自己的需求添加表前缀.
```sql
-- 创建语言模块配置表
CREATE TABLE `language_module` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
    `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级id',
    `name` varchar(100) NOT NULL COMMENT '名称',
    `description` varchar(1000) NOT NULL DEFAULT '' COMMENT '描述',
    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`) COMMENT '名称索引',
    KEY `idx_parent_id` (`parent_id`) COMMENT '父级id索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='多语言模块表';

-- 创建多语言配置表
CREATE TABLE `language_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
    `module_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模块id',
    `entry_name` varchar(100) NOT NULL COMMENT '词条名称',
    `entry_code` varchar(100) NOT NULL COMMENT '词条编码',
    `description` varchar(1000) NOT NULL DEFAULT '' COMMENT '描述',
    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_entry_code` (`entry_code`) COMMENT '词条唯一索引',
    KEY `idx_entry_name` (`entry_name`) COMMENT '词条名称索引',
    KEY `idx_module_id_entry_code` (`module_id`, `entry_code`) COMMENT '模块id和词条编码索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='多语言配置表';

-- 创建多语言翻译表
CREATE TABLE `m_language_translation` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
    `entry_code` varchar(100) NOT NULL COMMENT '词条编码',
    `locale` varchar(10) NOT NULL COMMENT '语言区域',
    `translation` text NULL COMMENT '翻译',
    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_entry_code_locale` (`entry_code`, `locale`) COMMENT '词条和语言区域唯一索引',
    KEY `idx_locale` (`locale`) COMMENT '语言索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='多语言翻译表';
```
## 配置文件说明