<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

/**
 * Helper to build files content from a template name and a series of variables that get replaced
 * in the template.
 */
class FileContentBuilder
{
    /**
     * Build a file content starting form a template and a set of replacement variables.
     *
     * @param  Paths $paths
     * @param  string $template
     * @param  array $vars
     * @return string file content on success, false on failure
     */
    public function build(Paths $paths, string $template, array $vars = []): string
    {
        $template = $paths->template($template);

        if (!$template || !is_file($template) || !is_readable($template)) {
            throw new \Exception("Can't build file from template {$template}: file not found.");
        }

        $templateContent = @file_get_contents($template);
        if (!$templateContent) {
            throw new \Exception("Can't build file from empty template {$template}.");
        }

        return $this->render($templateContent, $vars);
    }

    /**
     * @param  string $content
     * @param  array $vars
     * @return string
     */
    public function render(string $content, array $vars): string
    {
        $patterns = [];
        $replacements = [];

        foreach ($vars as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            $patterns[] = "~\{{3}\s*{$key}\s*\}{3}~i";
            $replacements[] = (string)$value;
        }

        $content = preg_replace($patterns, $replacements, $content);

        return $content ?? '';
    }
}
