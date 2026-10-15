{extends file="layout.tpl"}
{block name="content"}
<nav class="breadcrumbs" aria-label="Путь к странице"><a href="/">Главная</a><span aria-hidden="true">/</span><span>{$category.name}</span></nav>
<header class="page-heading">
    <p class="eyebrow">Рубрика журнала</p>
    <h1>{$category.name}<span class="heading-spark" aria-hidden="true">✳</span></h1>
    <p>{$category.description}</p>
</header>
<div class="list-toolbar">
    <span class="results-count">Статей: <strong>{$pagination.total}</strong></span>
    <form method="get" action="/categories/{$category.slug}" class="sort-form">
        <label for="sort">Сначала</label>
        <select name="sort" id="sort">
            <option value="date" {if $sort == 'date'}selected{/if}>Новые</option>
            <option value="views" {if $sort == 'views'}selected{/if}>Популярные</option>
        </select>
        <button type="submit" class="button button--small">Применить</button>
    </form>
</div>
<div class="article-grid category-grid">
    {foreach $articles as $post}
        {include file="partials/article-card.tpl" post=$post}
    {foreachelse}
        <div class="empty-state"><span aria-hidden="true">✳</span><h2>Пока здесь тихо</h2><p>В этой категории ещё нет опубликованных статей.</p><a href="/" class="text-link">Посмотреть другие темы ↗</a></div>
    {/foreach}
</div>
{include file="partials/pagination.tpl"}
{/block}
