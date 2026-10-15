<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{$page_description}">
    <meta name="theme-color" content="#f5f3ed">
    <title>{$page_title}</title>
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/main.css?v={$css_version}">
</head>
<body>
<a class="skip-link" href="#main">Перейти к содержимому</a>
<header class="site-header container">
    <a class="wordmark" href="/" aria-label="Поле — главная страница">поле<span class="wordmark-dot">.</span></a>
    <span class="header-note">Журнал для любопытных</span>
    <a class="header-link" href="/#categories">Найти свою тему <span aria-hidden="true">↗</span></a>
</header>
<nav class="category-nav container" aria-label="Категории">
    <a href="/" class="nav-item {if $active_category == ''}is-active{/if}">Все темы</a>
    {foreach $navigation as $item}
        <a href="/categories/{$item.slug}" class="nav-item {if $active_category == $item.slug}is-active{/if}" {if $active_category == $item.slug}aria-current="page"{/if}>{$item.name}</a>
    {/foreach}
</nav>
<main id="main" class="container">
    {block name="content"}{/block}
</main>
<footer class="site-footer container">
    <div><a class="wordmark wordmark--small" href="/">поле<span class="wordmark-dot">.</span></a><p>Больше интереса к обычным вещам.</p></div>
    <div class="footer-right"><span>Технологии · Культура · Жизнь</span><span>© {$year} Поле</span></div>
</footer>
</body>
</html>
