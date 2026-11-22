{extends file='layout.tpl.html'}
{block FOOTER}{/block}
{block ASIDE}{/block}
{block MAIN}
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
    {* Блок 2: Заголовок и фильтр карточек *}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">Структура таблиц и связи</h2>
        <div style="width: 280px;">
            <input type="search" id="tableFilter" class="form-control form-control-sm" placeholder="Фильтр по названию таблицы...">
        </div>
    </div>
        {* Сетка карточек таблиц на Bootstrap Grid *}
{block TABLES_GRID}
{include file="dev/TABLES_GRID.tpl" }
{/block}

    {* Блок 1: ER-диаграмма *}

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">ER-диаграмма базы данных</span>
            <span class="badge text-bg-secondary">{$totalTables} табл.</span>
            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">HideDiagram</button>
        </div>
        <div class="card-body overflow-x-auto bg-white" id="collapseExample">
            <pre class="mermaid m-0">{$mermaidCode|escape}</pre>
        </div>
    </div>



</div>
{/block}







{block SCRIPTS}
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

<script nonce="{$nonce}">
{literal}
    function syncMasterCheckbox(tableEl, colName) {
        const master = tableEl.querySelector('.js-toggle-col[data-col="' + colName + '"]');
        const items  = Array.from(tableEl.querySelectorAll('input[data-col-item="' + colName + '"]'));
        if (!master || items.length === 0) return;

        const checkedCount = items.filter(cb => cb.checked).length;
        master.checked = checkedCount === items.length;
        master.indeterminate = checkedCount > 0 && checkedCount < items.length;
    }

    document.querySelectorAll('#tablesGrid table').forEach(tableEl => {
        ['displayed', 'filter', 'search', 'sort'].forEach(col => syncMasterCheckbox(tableEl, col));
    });

    document.getElementById('tablesGrid')?.addEventListener('change', function (e) {
        const target = e.target;
        const tableEl = target.closest('table');
        if (!tableEl) return;

        if (target.classList.contains('js-toggle-col')) {
            const colName = target.dataset.col;
            tableEl.querySelectorAll('input[data-col-item="' + colName + '"]').forEach(cb => {
                cb.checked = target.checked;
            });
            target.indeterminate = false;
        } else if (target.dataset.colItem) {
            syncMasterCheckbox(tableEl, target.dataset.colItem);
        }
    });
{/literal}
</script>














{/block}
