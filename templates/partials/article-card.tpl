<article class="article-card">
    <a class="card-image-link" href="/articles/{$post.slug}" tabindex="-1" aria-hidden="true">
        <img src="{$post.image_path}" alt="" width="720" height="480" loading="lazy">
        <span class="image-arrow" aria-hidden="true">↗</span>
    </a>
    <div class="card-meta"><time datetime="{$post.published_at}">{$post.published_at}</time><span aria-label="Просмотры: {$post.views}"><span aria-hidden="true">◉</span> {$post.views}</span></div>
    <h3><a href="/articles/{$post.slug}">{$post.title}</a></h3>
    <p>{$post.description}</p>
</article>
