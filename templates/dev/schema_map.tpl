{extends file='layout.tpl.html'}

{block CONTAINER}
<div class="py-3">

    {* Шапка с легендой и кнопкой пересинхронизации *}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-2 border-bottom">
        <div>
            <h1 class="h4 mb-1">{$pageTitle|escape}</h1>
            <div class="small text-body-secondary d-flex flex-wrap gap-3">
                <span><strong class="text-primary">━━</strong> Жёсткий FK (<code>relations_map</code>)</span>
                <span><strong class="text-warning-emphasis">╌╌</strong> Неявный *_id (<code>relations_id</code>)</span>
            </div>
        </div>

        <form method="post" action="/dev/schema-map/sync" class="m-0">
            <button type="submit" class="btn btn-primary btn-sm">
                ↻ Пересинхронизировать схемы
            </button>
        </form>
    </div>

    {if $synced}
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            Схемы таблиц и карта связей успешно обновлены из базы данных (ручные настройки сохранены).
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    {/if}

    {* Блок 1: ER-диаграмма *}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">ER-диаграмма базы данных</span>
            <span class="badge text-bg-secondary">{$totalTables} табл.</span>
        </div>
        <div class="card-body overflow-x-auto bg-white">
            <pre class="mermaid m-0">{$mermaidCode|escape}</pre>
        </div>
    </div>

    {* Блок 2: Заголовок и фильтр карточек *}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">Структура таблиц и связи</h2>
        <div style="width: 280px;">
            <input type="search" id="tableFilter" class="form-control form-control-sm" placeholder="Фильтр по названию таблицы...">
        </div>
    </div>

    {* Сетка карточек таблиц на Bootstrap Grid *}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="tablesGrid">
        {foreach $tables as $table}
            <div class="col table-card-col" data-table="{$table.name|escape}">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
                        <span class="font-monospace fw-bold text-body">{$table.name|escape}</span>
                        <span class="badge rounded-pill text-bg-light border text-body-secondary">
                            полей: {$table.fields|count}
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0 font-monospace small">
                            <tbody>
                            {foreach $table.fields as $field}
                                <tr>
                                    <td class="ps-3 py-1">
                                        {$field|escape}
                                        {if $field === $table.primaryKey}
                                            <span class="badge text-bg-warning ms-1" title="Primary Key">PK</span>
                                        {/if}
                                        {if isset($table.fkColumns[$field])}
                                            {if $table.fkColumns[$field] === 'foreign_key'}
                                                <span class="badge text-bg-primary ms-1" title="Строгий FOREIGN KEY">FK</span>
                                            {else}
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" title="Неявная связь *_id">ID</span>
                                            {/if}
                                        {/if}
                                    </td>
                                    <td class="pe-3 py-1 text-end">
                                        <span class="fw-semibold text-body">{$table.fieldTypes[$field]|default:'mixed'|escape}</span>
                                        <span class="text-body-secondary">({$table.sqlTypes[$field]|default:''|escape})</span>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>

                    {if $table.relations}
                        <div class="card-footer bg-body-tertiary pt-2 pb-2">
                            <div class="small fw-semibold text-body-secondary mb-1">
                                Связи ({$table.relations|count}):
                            </div>
                            <div class="d-flex flex-wrap gap-1 font-monospace" style="font-size: 0.75rem;">
                                {foreach $table.relations as $relName => $rel}
                                    {if $rel.type === 'hasMany'}
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle text-wrap text-start"
                                              title="Обратная связь (1:N)">
                                            hasMany: {$relName|escape} → {$rel.table|escape}.{$rel.foreign_key|escape}
                                        </span>
                                    {elseif ($rel.source|default:'') === 'implicit_id'}
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-wrap text-start"
                                              title="Неявная связь *_id (display: {$rel.display_field|default:'id'|escape})">
                                            belongsTo: {$relName|escape} → {$rel.table|escape} ({$rel.foreign_key|escape})
                                        </span>
                                    {else}
                                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle text-wrap text-start"
                                              title="Жёсткий FOREIGN KEY (display: {$rel.display_field|default:'id'|escape})">
                                            belongsTo: {$relName|escape} → {$rel.table|escape} ({$rel.foreign_key|escape})
                                        </span>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>

</div>
{/block}

{block scripts}
    {$smarty.block.parent}
    <script src="/lib/mermaid/mermaid.min.js" nonce="{$nonce}"></script>
    <script nonce="{$nonce}">
        mermaid.initialize({
            startOnLoad: true,
            theme: 'default',
            securityLevel: 'loose',
            er: { useMaxWidth: false }
        });

        document.getElementById('tableFilter')?.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            document.querySelectorAll('#tablesGrid .table-card-col').forEach(col => {
                const name = (col.dataset.table || '').toLowerCase();
                col.classList.toggle('d-none', !name.includes(query));
            });
        });
    </script>
{/block}
