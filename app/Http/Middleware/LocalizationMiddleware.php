<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 定义应用支持的语言列表 (你需要根据你的应用实际情况修改)
        $supportedLocales = ['zh', 'en'];

        // 尝试从浏览器获取最偏好的、且应用支持的语言
        $locale = $request->getPreferredLanguage($supportedLocales);

        // 如果获取到支持的语言，则设置应用的语言环境
        if ($locale) {
            $shortLocale = substr($locale, 0, 2);
            if (in_array($shortLocale, $supportedLocales)) {
                App::setLocale($shortLocale);
            } else {
                App::setLocale(config('app.locale'));
            }
        }

        return $next($request);
    }
}
