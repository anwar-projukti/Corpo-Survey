<div
    x-data="{
        get rows() { return question.config?.matrixRows || []; },
        get columns() { return question.config?.matrixColumns || []; },
        addRow() { saveConfig({ matrixRows: [...this.rows, ''] }); },
        addColumn() { saveConfig({ matrixColumns: [...this.columns, ''] }); },
        updateRow(index, value) {
            const rows = [...this.rows]; rows[index] = value;
            saveConfig({ matrixRows: rows });
        },
        updateColumn(index, value) {
            const columns = [...this.columns]; columns[index] = value;
            saveConfig({ matrixColumns: columns });
        },
        removeRow(index) { saveConfig({ matrixRows: this.rows.filter((_, i) => i !== index) }); },
        removeColumn(index) { saveConfig({ matrixColumns: this.columns.filter((_, i) => i !== index) }); },
    }"
    class="space-y-3"
>
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.matrix_rows') }}</label>
        <div class="mt-1 space-y-1">
            <template x-for="(row, index) in rows" :key="index">
                <div class="flex items-center gap-1.5">
                    <input
                        type="text"
                        :value="row"
                        @blur="updateRow(index, $event.target.value)"
                        :disabled="!isDraftEditable"
                        class="flex-1 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1 text-sm disabled:opacity-70"
                    >
                    <button x-show="isDraftEditable" type="button" @click="removeRow(index)" class="text-gray-300 dark:text-gray-600 hover:text-red-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>
        <button x-show="isDraftEditable" type="button" @click="addRow()" class="mt-1 text-sm text-brand-600 dark:text-brand-400 hover:underline">
            + {{ __('surveys.builder.add_matrix_row') }}
        </button>
    </div>

    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.matrix_columns') }}</label>
        <div class="mt-1 space-y-1">
            <template x-for="(column, index) in columns" :key="index">
                <div class="flex items-center gap-1.5">
                    <input
                        type="text"
                        :value="column"
                        @blur="updateColumn(index, $event.target.value)"
                        :disabled="!isDraftEditable"
                        class="flex-1 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1 text-sm disabled:opacity-70"
                    >
                    <button x-show="isDraftEditable" type="button" @click="removeColumn(index)" class="text-gray-300 dark:text-gray-600 hover:text-red-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>
        <button x-show="isDraftEditable" type="button" @click="addColumn()" class="mt-1 text-sm text-brand-600 dark:text-brand-400 hover:underline">
            + {{ __('surveys.builder.add_matrix_column') }}
        </button>
    </div>
</div>
