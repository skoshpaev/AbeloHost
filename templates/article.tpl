{extends file="layout.tpl"}
{block name="content"}
<nav class="breadcrumbs" aria-label="Путь к странице"><a href="/">Главная</a><span aria-hidden="true">/</span>{if $categories}<a href="/categories/{$categories[0].slug}">{$categories[0].name}</a><span aria-hidden="true">/</span>{/if}<span>Статья</span></nav>
<article class="full-article">
    <header class="article-heading">
        <div class="article-tags">{foreach $categories as $category}<a href="/categories/{$category.slug}">{$category.name}</a>{/foreach}</div>
        <h1>{$article.title}</h1>
        <p class="article-lead">{$article.description}</p>
        <div class="article-info"><time datetime="{$article.published_at}">{$article.published_at}</time><span>Просмотры: {$article.views}</span><span>Редакция «Поле»</span></div>
    </header>
    <img class="article-cover" src="{$article.image_path}" alt="Иллюстрация к статье «{$article.title}»" width="720" height="480">
    <div class="article-body">{foreach $paragraphs as $paragraph}<p>{$paragraph}</p>{/foreach}</div>
    <div class="article-end"><span aria-hidden="true">✳</span><p>Спасибо, что читаете внимательно.</p><a href="/">Все темы журнала ↗</a></div>
</article>
{if $similar}
<section class="related-section" aria-labelledby="related-title">
    <div class="section-heading"><div><span class="eyebrow">Продолжить чтение</span><h2 id="related-title">Похожие статьи</h2></div><span class="related-note">Ещё немного любопытства ↗</span></div>
    <div class="article-grid">{foreach $similar as $post}{include file="partials/article-card.tpl" post=$post}{/foreach}</div>
</section>
{/if}
{/block}
