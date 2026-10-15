{extends file="layout.tpl"}
{block name="content"}
    <h1>Блог</h1>
    {foreach $categories as $category}
        <section>
            <h2>{$category.name}</h2>
            <p>{$category.description}</p>
            {foreach $groups[$category.id] as $post}
                <article>
                    <h3><a href="/articles/{$post.slug}">{$post.title}</a></h3>
                    <p>{$post.description}</p>
                </article>
            {/foreach}
            <a href="/categories/{$category.slug}">Все статьи</a>
        </section>
    {foreachelse}
        <p>Статей пока нет.</p>
    {/foreach}
{/block}
