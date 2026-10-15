{extends file="layout.tpl"}
{block name="content"}
<section class="hero" aria-labelledby="hero-title">
    <div class="hero-copy">
        <p class="eyebrow"><span class="status-dot" aria-hidden="true"></span> Идеи, истории, наблюдения</p>
        <h1 id="hero-title">Место для<br>новых <em>мыслей.</em></h1>
        <p class="hero-description">О мире вокруг и том, что делает его интереснее. Читайте, замечайте и находите своё.</p>
        <a class="button button--dark" href="#categories">Открыть журнал <span aria-hidden="true">↓</span></a>
        <div class="hero-caption"><span class="caption-line" aria-hidden="true"></span> Для тех, кому всегда любопытно</div>
    </div>
    <div class="hero-art">
        <img src="/assets/images/hero.svg" alt="Солнечный пейзаж, открытая книга и растущая ветка" width="760" height="640">
        <div class="art-caption"><span>Смотрите шире</span><span aria-hidden="true">✳</span></div>
    </div>
</section>
<div class="section-label" id="categories"><span>Свежие истории по темам</span><span>Есть о чём подумать <span aria-hidden="true">↙</span></span></div>
    {foreach $categories as $category}
        <section class="category-section" aria-labelledby="category-{$category.slug}">
            <div class="section-heading">
                <div><span class="eyebrow">Рубрика</span><h2 id="category-{$category.slug}">{$category.name}<span class="category-count">{$category.article_count}</span></h2><p>{$category.description}</p></div>
                <a class="button button--outline" href="/categories/{$category.slug}">Все статьи <span aria-hidden="true">↗</span></a>
            </div>
            <div class="article-grid">
            {foreach $groups[$category.id] as $post}
                {include file="partials/article-card.tpl" post=$post}
            {/foreach}
            </div>
        </section>
    {foreachelse}
        <div class="empty-state"><h2>Истории ещё впереди</h2><p>Скоро здесь появятся первые статьи.</p></div>
    {/foreach}
<aside class="closing-note"><span aria-hidden="true">✳</span><p>Хорошая история начинается<br>с простого <em>любопытства.</em></p><a href="#main">Вернуться к началу ↑</a></aside>
{/block}
