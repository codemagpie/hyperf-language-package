<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
return [
    'db_connection' => 'default', // 数据库连接
    'replace_symbol' => ':fill', // 替换符号,用于替换语言中的变量,如 `:fill`,其中fill代表替换的位置,`:`代表替换符号。例子: `${fill}`, `{{fill}}`,等等
    'locale' => 'zh_CN', // 默认语言
    'fallback_locale' => 'en', // 如果使用默认语言找不到值,则使用这个语言,设置为null时,就不会使用这个语言
    'default_value' => null, // 如果找不到值,则使用这个默认值, 设置为null则返回的是词条编码
    'route_prefix' => '/douyu', // 路由前缀
    'load_modules' => [0], // 加载的模块id，会将模块下的所有翻译配置加载到内存中,提高性能
    'enable_process' => true, // 启用自定义进程刷新配置.
    'refresh_rate' => 60, // 刷新频率,单位秒。enable_process为true时生效.每次刷新都会查下数据库中是否有更新翻译配置并刷新到内存中.
];
