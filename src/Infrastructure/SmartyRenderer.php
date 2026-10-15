<?php
declare(strict_types=1);

namespace App\Infrastructure;

use RuntimeException;
use Smarty\Smarty;

final class SmartyRenderer
{
    private Smarty $smarty;

    public function __construct(string $root)
    {
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($root . '/templates');
        $directory = $root . '/storage/smarty';
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
        $this->smarty->assign(array_merge(['page_title' => 'Блог'], $data));
        return $this->smarty->fetch($template);
    }
}
