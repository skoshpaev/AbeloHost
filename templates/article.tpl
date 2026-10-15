{extends file="layout.tpl"}
{block name="content"}
    <p><a href="/">← На главную</a></p>
    <nav aria-label="Категории статьи">
        {foreach $categories as $category}
            <a href="/categories/{$category.slug}">{$category.name}</a>
        {/foreach}
    </nav>
    <article>
        <h1>{$article.title}</h1>
        <p>{$article.description}</p>
        <img src="{$article.image_path}" alt="Иллюстрация к статье «{$article.title}»" width="720" height="480">
        <p><time datetime="{$article.published_at}">{$article.published_at}</time> · Просмотров: {$article.views}</p>
        <div>
            {foreach $paragraphs as $paragraph}
                <p>{$paragraph}</p>
            {/foreach}
        </div>
    </article>
    {if $similar}
        <section aria-labelledby="similar-articles">
            <h2 id="similar-articles">Похожие статьи</h2>
            {foreach $similar as $post}
                <article>
                    <h3><a href="/articles/{$post.slug}">{$post.title}</a></h3>
                    <p>{$post.description}</p>
                    <span>Просмотров: {$post.views}</span>
                </article>
            {/foreach}
        </section>
    {/if}
{/block}
