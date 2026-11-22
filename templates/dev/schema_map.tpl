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
                <span><strong class="text-danger-emphasis">╌╌</strong> Неявный *_id (<code>relations_id</code>)</span>
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
    // 1. Снимок состояния всех чекбоксов внутри конкретной формы
    function getFormSnapshot(form) {
        return Array.from(form.querySelectorAll('input[type="checkbox"][name]'))
            .map(cb => cb.name + ':' + cb.value + '=' + (cb.checked ? '1' : '0'))
            .join('|');
    }

    // 2. Проверка наличия несохранённых изменений и подсветка карточки/кнопки
    function updateDirtyState(form) {
        if (!form) return;
        const isDirty = getFormSnapshot(form) !== form.dataset.initialState;
        const btn = form.querySelector('button[type="submit"]');

        form.classList.toggle('border-danger', isDirty);
        form.classList.toggle('border-2', isDirty);

        if (btn && !btn.disabled) {
            if (isDirty) {
                btn.classList.remove('btn-outline-primary', 'btn-success', 'btn-outline-danger');
                btn.classList.add('btn-danger', 'text-dark', 'fw-semibold');
                btn.textContent = 'Сохранить';
            } else {
                btn.classList.remove('btn-danger', 'text-dark', 'fw-semibold', 'btn-outline-danger');
                btn.classList.add('btn-outline-primary');
                btn.textContent = 'Сохранить';
            }
        }
    }

    // 3. Синхронизация мастер-чекбокса в шапке колонки
    function syncMasterCheckbox(tableEl, colName) {
        const master = tableEl.querySelector('.js-toggle-col[data-col="' + colName + '"]');
        const items  = Array.from(tableEl.querySelectorAll('input[data-col-item="' + colName + '"]'));
        if (!master || items.length === 0) return;

        const checkedCount = items.filter(cb => cb.checked).length;
        master.checked = checkedCount === items.length;
        master.indeterminate = checkedCount > 0 && checkedCount < items.length;
    }

    // Инициализация при загрузке страницы: запоминаем эталонное состояние каждой карточки
    document.querySelectorAll('#tablesGrid form').forEach(form => {
        form.dataset.initialState = getFormSnapshot(form);
        const tableEl = form.querySelector('table');
        if (tableEl) {
            ['displayed', 'filter', 'search', 'sort'].forEach(col => syncMasterCheckbox(tableEl, col));
        }
    });

    // Обработка любых изменений чекбоксов
    document.getElementById('tablesGrid')?.addEventListener('change', function (e) {
        const target = e.target;
        const tableEl = target.closest('table');
        const form = target.closest('form');
        if (!tableEl || !form) return;

        if (target.classList.contains('js-toggle-col')) {
            const colName = target.dataset.col;
            tableEl.querySelectorAll('input[data-col-item="' + colName + '"]').forEach(cb => {
                cb.checked = target.checked;
            });
            target.indeterminate = false;
        } else if (target.dataset.colItem) {
            syncMasterCheckbox(tableEl, target.dataset.colItem);
        }

        updateDirtyState(form);
    });

    // AJAX-сохранение карточки
    document.getElementById('tablesGrid')?.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');

        if (btn) {
            btn.disabled = true;
            btn.classList.remove('btn-danger', 'text-dark', 'fw-semibold');
            btn.classList.add('btn-outline-primary');
            btn.textContent = 'Сохранение...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Ошибка при сохранении');
            }

            // Обновляем эталонный снимок после успешного сохранения
            form.dataset.initialState = getFormSnapshot(form);
            form.classList.remove('border-danger', 'border-2');

            if (btn) {
                btn.classList.remove('btn-outline-primary', 'btn-outline-danger');
                btn.classList.add('btn-success');
                btn.textContent = '✓ Сохранено';

                setTimeout(() => {
                    btn.disabled = false;
                    // Возвращаем актуальное состояние (если пользователь успел кликнуть что-то за эти 1.5 сек — снова станет жёлтой)
                    updateDirtyState(form);
                }, 1500);
            }
        } catch (err) {
            console.error(err);
            if (btn) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-outline-danger');
                btn.textContent = 'Ошибка!';
                btn.disabled = false;
            }
            alert('Не удалось сохранить настройки: ' + err.message);
        }
    });

    // Предупреждение при попытке закрыть/обновить страницу, если остались несохранённые карточки
    window.addEventListener('beforeunload', function (e) {
        const hasUnsaved = Array.from(document.querySelectorAll('#tablesGrid form'))
            .some(form => form.dataset.initialState !== undefined && getFormSnapshot(form) !== form.dataset.initialState);

        if (hasUnsaved) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
{/literal}
</script>














{/block}
