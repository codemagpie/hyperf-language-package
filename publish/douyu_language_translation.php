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
];
