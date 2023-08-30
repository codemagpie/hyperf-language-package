<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */

use CodeMagpie\HyperfLanguagePackage\Config;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\CreateModuleCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\CreateTransConfigCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\UpdateModuleCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\UpdateTransConfigCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Meta\Translation;
use CodeMagpie\HyperfLanguagePackage\LanguageService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Collection;

$config = ApplicationContext::getContainer()->get(Config::class);
$prefix = $config->getRoutePrefix();
Router::addGroup($prefix, function () {
    Router::post('/lang/module', function (RequestInterface $request, LanguageService $languageService) {
        $languageService->createModule(new CreateModuleCommand($request->all()));
        return [];
    });

    Router::put('/lang/module', function (RequestInterface $request, LanguageService $languageService) {
        $languageService->updateModule(new UpdateModuleCommand($request->all()));
        return [];
    });

    Router::delete('/lang/module', function (RequestInterface $request, LanguageService $languageService) {
        $languageService->delModule((int) $request->input('id'));
        return [];
    });

    Router::get('/lang/modules', function (RequestInterface $request, LanguageService $languageService) {
        $params = $request->all();
        return $languageService->getModules($params['name'] ?? '', (int) ($params['page'] ?? 1), (int) ($params['page_size'] ?? 10));
    });

    Router::get('/lang/modules/tree', function (RequestInterface $request, LanguageService $languageService) {
        $params = $request->all();
        return $languageService->getModulesTree($params['parent_ids'] ?? [0]);
    });

    Router::get('/lang/sub_modules', function (RequestInterface $request, LanguageService $languageService) {
        $params = $request->all();
        return $languageService->getSubModules((int) ($params['parent_id'] ?? 0), $params['name'] ?? '');
    });

    Router::post('/lang/trans_config', function (RequestInterface $request, LanguageService $languageService) {
        $params = $request->all();
        $params['translations'] = Collection::make($params['translations'])->map(function ($item) {
            return new Translation($item);
        })->all();
        $command = new CreateTransConfigCommand($params);
        $languageService->createTransConfig($command);
        return [];
    });

    Router::put('/lang/trans_config', function (RequestInterface $request, LanguageService $languageService) {
        $params = $request->all();
        $params['translations'] = Collection::make($params['translations'])->map(function ($item) {
            return new Translation($item);
        })->all();
        $command = new UpdateTransConfigCommand($params);
        $languageService->updateTransConfig($command);
        return [];
    });

    Router::delete('/lang/config', function (RequestInterface $request, LanguageService $languageService) {
        $languageService->delConfig((int) $request->input('id'));
        return [];
    });

    Router::get('/lang/configs', function (RequestInterface $request, LanguageService $languageService) {
        return $languageService->getConfigs($request->all());
    });

    Router::get('/lang/config_info', function (RequestInterface $request, LanguageService $languageService) {
        $params = $request->all();
        return $languageService->getConfigInfo((int) ($params['id'] ?? 0));
    });
});
