<?php
declare(strict_types=1);

namespace App\Infrastructure;

use RuntimeException;
use Smarty\Smarty;

final class SmartyRenderer
{
    private Smarty $smarty;
    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($this->root . '/templates');
        $directory = $this->root . '/storage/smarty';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create template compilation directory.');
        }
        $this->smarty->setCompileDir($directory);
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        $this->smarty->escape_html = true;
    }

    public function render(string $template, array $data = []): string
    {
        $this->smarty->clearAllAssign();
        $cssPath = $this->root . '/public/assets/css/main.css';
        $defaults = [
            'page_title' => 'Блог',
            'page_description' => 'Журнал для любопытных: технологии, культура, дизайн и повседневная жизнь.',
            'navigation' => $data['categories'] ?? (isset($data['category']) ? [$data['category']] : []),
            'active_category' => $data['category']['slug'] ?? '',
            'css_version' => is_file($cssPath) ? (string)filemtime($cssPath) : '1',
            'year' => gmdate('Y'),
        ];
        $this->smarty->assign(array_merge($defaults, $data));
        return $this->smarty->fetch($template);
    }
}
