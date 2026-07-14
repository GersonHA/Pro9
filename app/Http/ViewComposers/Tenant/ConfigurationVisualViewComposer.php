<?php

namespace App\Http\ViewComposers\Tenant;

use App\Http\Resources\Tenant\ConfigurationResource;
use App\Models\Tenant\Configuration;

class ConfigurationVisualViewComposer
{
    public function compose($view)
    {
        $configuration = Configuration::first();
        if(is_null($configuration->visual)) {
            $defaults = [
                'bg' => 'light',
                'header' => 'light',
                'sidebars' => 'light',
            ];
            $configuration->visual = $defaults;
            $configuration->save();
        }
        $configuration = Configuration::first();
        $record = new ConfigurationResource($configuration);

        // Override per-user (PORT pro8 2026-03 + plan #02b): si el admin tiene el
        // switch `theme_per_user` en ON y el usuario tiene theme_color, reemplaza la
        // config visual global con la suya. Si theme_per_user=false, ignora el
        // theme del usuario (tema único de empresa).
        $theme_per_user = (bool) ($configuration->theme_per_user ?? true);
        $user = auth()->user();
        if ($theme_per_user && $user && $user->theme_color) {
            $userTheme = json_decode($user->theme_color, true);
            if (is_array($userTheme)) {
                $visual = (array) $record->visual;
                $visual = array_merge($visual, $userTheme);
                if (isset($userTheme['sidebar_mode'])) {
                    $visual['sidebar_mode'] = $userTheme['sidebar_mode'];
                }
                if (isset($userTheme['skin_id'])) {
                    $visual['skin_id'] = $userTheme['skin_id'];
                }
                $record->visual = (object) $visual;
            }
        }

        $view->visual = $record->visual;
    }
}
