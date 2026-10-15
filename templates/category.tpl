{extends file="layout.tpl"}
{block name="content"}
    <p><a href="/">← На главную</a></p>
    <header>
        <h1>{$category.name}</h1>
        <p>{$category.description}</p>
    </header>
    <p>Статей: {$pagination.total}</p>
    <form method="get" action="/categories/{$category.slug}">
        <label for="sort">Сортировка</label>
        <select id="sort" name="sort">
            <option value="date" {if $sort == 'date'}selected{/if}>Сначала новые</option>
            <option value="views" {if $sort == 'views'}selected{/if}>Сначала популярные</option>
        </select>
        <button type="submit">Применить</button>
    </form>
    {foreach $articles as $article}
        <article>
            <h2><a href="/articles/{$article.slug}">{$article.title}</a></h2>
            <p>{$article.description}</p>
            <time datetime="{$article.published_at}">{$article.published_at}</time>
            <span>Просмотров: {$article.views}</span>
        </article>
    {foreachelse}
        <p>В этой категории пока нет опубликованных статей.</p>
    {/foreach}
    {include file="partials/pagination.tpl"}
{/block}
