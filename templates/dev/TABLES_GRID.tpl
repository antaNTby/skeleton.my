{block TABLES_GRID}
<div class="row row-cols-1 row-cols-xl-2 g-3" id="tablesGrid">
    {foreach $tables as $table}
    <div class="col table-card-col" data-table="{$table.name|escape}">
        <form method="post" action="/dev/schema-map/update" class="card h-100 shadow-sm m-0">
            <input type="hidden" name="table" value="{$table.name|escape}">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-monospace fw-bold text-body">{$table.name|escape}</span>
                    <span class="badge rounded-pill text-bg-light border text-body-secondary">
                        полей: {$table.fields|count}
                    </span>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.78rem;">
                    Сохранить
                </button>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm table-hover table-striped align-middle mb-0 font-monospace small">
                    <thead class="table-light text-body-secondary" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-1 fw-semibold">Поле</th>
                            <th class="py-1 text-end fw-semibold">Тип</th>
                            <th class="py-1 text-center fw-semibold" style="width: 52px;" title="displayed_fields (Отображение в списке)">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>Показ</span>
                                    <input class="form-check-input m-0 js-toggle-col" type="checkbox" data-col="displayed" title="Выбрать / снять все">
                                </div>
                            </th>
                            <th class="py-1 text-center fw-semibold" style="width: 52px;" title="filter_fields (Фильтрация)">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>Фильтр</span>
                                    <input class="form-check-input m-0 js-toggle-col" type="checkbox" data-col="filter" title="Выбрать / снять все">
                                </div>
                            </th>
                            <th class="py-1 text-center fw-semibold" style="width: 52px;" title="search_fields (Поиск)">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>Поиск</span>
                                    <input class="form-check-input m-0 js-toggle-col" type="checkbox" data-col="search" title="Выбрать / снять все">
                                </div>
                            </th>
                            <th class="pe-3 py-1 text-center fw-semibold" style="width: 52px;" title="sort_fields (Сортировка)">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span>Сорт.</span>
                                    <input class="form-check-input m-0 js-toggle-col" type="checkbox" data-col="sort" title="Выбрать / снять все">
                                </div>
                            </th>
                        </tr>
                    </thead>
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
                            <td class="py-1 text-end text-nowrap">
                                <span class="fw-semibold text-body">{$table.fieldTypes[$field]|default:'mixed'|escape}</span>
                                <span class="text-body-secondary">({$table.sqlTypes[$field]|default:''|escape})</span>
                            </td>
                            <td class="py-1 text-center">
                                <input class="form-check-input m-0" type="checkbox" name="displayed_fields[]" data-col-item="displayed" value="{$field|escape}" {if isset($table.displayedMap[$field])}checked{/if}> </td> <td class="py-1 text-center">
                                <input class="form-check-input m-0" type="checkbox" name="filter_fields[]" data-col-item="filter" value="{$field|escape}" {if isset($table.filterMap[$field])}checked{/if}> </td> <td class="py-1 text-center">
                                <input class="form-check-input m-0" type="checkbox" name="search_fields[]" data-col-item="search" value="{$field|escape}" {if isset($table.searchMap[$field])}checked{/if}> </td> <td class="pe-3 py-1 text-center">
                                <input class="form-check-input m-0" type="checkbox" name="sort_fields[]" data-col-item="sort" value="{$field|escape}" {if isset($table.sortMap[$field])}checked{/if}> </td> </tr> {/foreach} </tbody> </table> </div> {if $table.relations} <div class="card-footer bg-body-tertiary pt-2 pb-2">
                                <div class="small fw-semibold text-body-secondary mb-1">
                                    Связи ({$table.relations|count}):
                                </div>
                                <div class="d-flex flex-wrap gap-1 font-monospace" style="font-size: 0.75rem;">
                                    {foreach $table.relations as $relName => $rel}
                                    {if $rel.type === 'hasMany'}
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle text-wrap text-start" title="Обратная связь (1:N)">
                                        hasMany: {$relName|escape} → {$rel.table|escape}.{$rel.foreign_key|escape}
                                    </span>
                                    {elseif ($rel.source|default:'') === 'implicit_id'}
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-wrap text-start" title="Неявная связь *_id (display: {$rel.display_field|default:'id'|escape})">
                                        belongsTo: {$relName|escape} → {$rel.table|escape} ({$rel.foreign_key|escape})
                                    </span>
                                    {else}
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle text-wrap text-start" title="Жёсткий FOREIGN KEY (display: {$rel.display_field|default:'id'|escape})">
                                        belongsTo: {$relName|escape} → {$rel.table|escape} ({$rel.foreign_key|escape})
                                    </span>
                                    {/if}
                                    {/foreach}
                                </div>
            </div>
            {/if}
        </form>
    </div>
    {/foreach}
</div>
{/block}
