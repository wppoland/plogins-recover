<?php

declare(strict_types=1);

namespace Recover\Util;

defined('ABSPATH') || exit;

use const Recover\PLUGIN_DIR;

/**
 * Loads templates with theme override support.
 *
 * Lookup order:
 * 1. {theme}/recover/{template}.php
 * 2. {plugin}/templates/{template}.php
 */
final class TemplateLoader
{
    private const THEME_DIR = 'recover';

    /**
     * Render a template and return the HTML.
     *
     * @param array<string, mixed> $args
     */
    public function render(string $template, array $args = []): string
    {
        ob_start();
        $this->include($template, $args);

        return (string) ob_get_clean();
    }

    /**
     * Include a template directly (outputs to buffer).
     *
     * @param array<string, mixed> $args
     */
    public function include(string $template, array $args = []): void
    {
        $path = $this->locate($template);
        if ($path === null) {
            return;
        }

        /**
         * Filter template arguments before rendering.
         *
         * @param array<string, mixed> $args     Template arguments.
         * @param string               $template Template name.
         */
        $args = apply_filters('recover/template/args', $args, $template);

        // Prefix every template variable with `recover_` to keep templates within
        // the plugin's variable namespace (per WordPress.org coding standards).
        $recover_args = [];
        foreach ($args as $recover_args_key => $recover_args_value) {
            if (! is_string($recover_args_key) || $recover_args_key === '') {
                continue;
            }
            $recover_args[str_starts_with($recover_args_key, 'recover_') ? $recover_args_key : 'recover_' . $recover_args_key] = $recover_args_value;
        }

        unset($args, $recover_args_key, $recover_args_value);

        extract($recover_args, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

        include $path;
    }

    /**
     * Locate a template file. Returns null if not found.
     */
    public function locate(string $template): ?string
    {
        $template = ltrim($template, '/');
        if (! str_ends_with($template, '.php')) {
            $template .= '.php';
        }

        $themePath = locate_template(self::THEME_DIR . '/' . $template);
        if ($themePath !== '') {
            /** @var string */
            return apply_filters('recover/template/path', $themePath, $template);
        }

        $pluginPath = PLUGIN_DIR . '/templates/' . $template;
        if (file_exists($pluginPath)) {
            /** @var string */
            return apply_filters('recover/template/path', $pluginPath, $template);
        }

        return null;
    }
}
