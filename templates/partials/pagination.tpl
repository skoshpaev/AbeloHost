{if $pagination.pages > 1}
<nav class="pagination" aria-label="Страницы списка">
    {if $pagination.page > 1}
        <a class="page-direction" href="/categories/{$category.slug}?sort={$sort}&amp;page={$pagination.page - 1}" rel="prev">← <span>Назад</span></a>
    {else}<span class="page-direction is-disabled">← <span>Назад</span></span>{/if}
    <div class="page-numbers">
        {$previous = 0}
        {foreach $pagination.numbers as $number}
            {if $previous > 0 && $number > $previous + 1}<span class="page-ellipsis">…</span>{/if}
            {if $number == $pagination.page}
                <span class="page-number is-current" aria-current="page">{$number}</span>
            {else}
                <a class="page-number" aria-label="Страница {$number}" href="/categories/{$category.slug}?sort={$sort}&amp;page={$number}">{$number}</a>
            {/if}
            {$previous = $number}
        {/foreach}
    </div>
    {if $pagination.page < $pagination.pages}
        <a class="page-direction" href="/categories/{$category.slug}?sort={$sort}&amp;page={$pagination.page + 1}" rel="next"><span>Дальше</span> →</a>
    {else}<span class="page-direction is-disabled"><span>Дальше</span> →</span>{/if}
</nav>
{/if}
