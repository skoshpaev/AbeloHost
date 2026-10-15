{extends file="layout.tpl"}
{block name="content"}
    <section class="error-page">
        <p class="eyebrow">Иногда маршрут меняется</p>
        <span class="error-number">{$status}</span>
        <h1>{$message}</h1>
        <p>Другие истории ждут на главной странице.</p>
        <a class="button button--dark" href="/">На главную <span aria-hidden="true">↗</span></a>
    </section>
{/block}
