<template>

    <div class="relationship-input">

        <relationship-select-field
            v-if="!initializing && usesSelectField"
            :config="config"
            :items="items"
            :multiple="maxItems > 1"
            :typeahead="mode === 'typeahead'"
            :taggable="taggable"
            :read-only="readOnly"
            :url="selectionsUrl"
            @input="selectFieldSelected"
        />

        <loading-graphic v-if="initializing" :inline="true" />

        <template v-if="!initializing && !usesSelectField">
            <div ref="items" class="relationship-input-items outline-none">
                <component
                    :is="itemComponent"
                    v-for="(item, i) in items"
                    :key="item.id"
                    :item="item"
                    :config="config"
                    :status-icon="statusIcons"
                    :editable="canEdit"
                    :sortable="!readOnly && canReorder"
                    :read-only="readOnly"
                    :form-component="formComponent"
                    :form-component-props="formComponentProps"
                    class="item outline-none"
                    @removed="remove(i)"
                />
            </div>

            <div class="text-xs text-grey" v-if="maxItemsReached && maxItems != 1">
                <span>{{ __('Maximum items selected:')}}</span>
                <span>{{ maxItems }}/{{ maxItems }}</span>
            </div>
            <div v-if="canSelectOrCreate" class="relative" :class="{ 'mt-2': items.length > 0 }" >
                <div class="flex flex-wrap items-center text-sm -mb-1">
                    <div class="relative mb-1">
                        <create-button
                            v-if="canCreate"
                            :creatables="creatables"
                            :site="site"
                            :component="formComponent"
                            :component-props="formComponentProps"
                            @created="itemCreated"
                        />
                    </div>
                    <button ref="existing" class="text-blue hover:text-grey-80 flex mb-1 outline-none" @click.prevent="isSelecting = true">
                        <svg-icon name="hyperlink" class="mr-sm h-4 w-4 flex items-center"></svg-icon>
                        {{ __('Link Existing Item') }}
                    </button>
                </div>
            </div>

            <stack name="item-selector" v-if="isSelecting" @closed="isSelecting = false">
                <item-selector
                    slot-scope="{ close }"
                    :url="selectionsUrl"
                    :site="site"
                    initial-sort-column="title"
                    initial-sort-direction="asc"
                    :initial-selections="selections"
                    :max-selections="maxItems"
                    :search="search"
                    :exclusions="exclusions"
                    @selected="selectionsUpdated"
                    @closed="close"
                />
            </stack>

            <input v-if="name" type="hidden" :name="name" :value="JSON.stringify(value)" />
        </template>
    </div>

</template>

<script>
import Popper from 'vue-popperjs';
import RelatedItem from './Item.vue';
import ItemSelector from './Selector.vue';
import CreateButton from './CreateButton.vue';
import {Sortable, Plugins} from '@shopify/draggable';
import RelationshipSelectField from './SelectField.vue';

export default {

    props: {
        name: String,
        value: { required: true },
        config: Object,
        initialData: Array,
        maxItems: Number,
        itemComponent: {
            type: String,
            default: 'RelatedItem',
        },
        itemDataUrl: String,
        selectionsUrl: String,
        statusIcons: Boolean,
        site: String,
        search: Boolean,
        canEdit: Boolean,
        canCreate: Boolean,
        canReorder: Boolean,
        readOnly: Boolean,
        exclusions: Array,
        creatables: Array,
        formComponent: String,
        formComponentProps: Object,
        mode: {
            type: String,
            default: 'default',
        },
        taggable: Boolean,
    },

    components: {
        Popper,
        ItemSelector,
        RelatedItem,
        CreateButton,
        RelationshipSelectField,
    },

    data() {
        return {
            isSelecting: false,
            isCreating: false,
            selections: this.value,
            itemData: [],
            initializing: true,
            loading: true,
            inline: false,
        }
    },

    computed: {

        items() {
            return this.selections.map(selection => {
                const data = _.findWhere(this.itemData, { id: selection });

                if (! data) return { id: selection, title: selection };

                return data;
            });
        },

        maxItemsReached() {
            return this.selections.length >= this.maxItems;
        },

        canSelectOrCreate() {
            return !this.readOnly && !this.maxItemsReached;
        },

        usesSelectField() {
            return ['select', 'typeahead'].includes(this.mode);
        }

    },

    mounted() {
        this.initializeData().then(() => {
            this.initializing = false;
            this.$nextTick(() => this.makeSortable());
        });
    },

    watch: {

        selections(selections) {
            this.$emit('input', this.selections);
        },

        value(value) {
            if (JSON.stringify(value) == JSON.stringify(this.selections)) return;
            this.selectionsUpdated(value);
        },

        loading: {
            immediate: true,
            handler(loading) {
                this.$progress.loading(`relationship-fieldtype-${this._uid}`, loading);
            }
        },

        isSelecting(selecting) {
            this.$emit(selecting ? 'focus' : 'blur');
        },

        itemData(data, olddata) {
            if (this.initializing) return;
            this.$emit('item-data-updated', data);
        }

    },

    methods: {

        remove(index) {
            this.selections.splice(index, 1);
        },

        selectionsUpdated(selections) {
            this.getDataForSelections(selections);
        },

        initializeData() {
            if (!this.initialData) {
                return this.getDataForSelections(this.selections);
            }

            this.itemData = this.initialData;
            this.loading = false;
            return Promise.resolve();
        },

        getDataForSelections(selections) {
            this.loading = true;
            const params = { site: this.site, selections };

            return this.$axios.get(this.itemDataUrl, { params }).then(response => {
                this.loading = false;

                this.itemData = response.data.data;
                this.selections = this.itemData.map(item => {
                    return item.id;
                });
            });
        },

        makeSortable() {
            new Sortable(this.$refs.items, {
                draggable: '.item',
                handle: '.item-move',
                mirror: { constrainDimensions: true, xAxis: false },
                swapAnimation: { vertical: true },
                plugins: [Plugins.SwapAnimation],
                delay: 200
            }).on('drag:start', e => {
                this.selections.length === 1 ? e.cancel() : this.$emit('focus');
            }).on('drag:stop', e => {
                this.$emit('blur');
            }).on('sortable:stop', e => {
                this.selections.splice(e.newIndex, 0, this.selections.splice(e.oldIndex, 1)[0]);
            })
        },

        itemCreated(item) {
            this.selections.push(item.id);
            this.itemData.push(item);
        },

        selectFieldSelected(selectedItemData) {
            this.itemData = selectedItemData.map(item => ({ id: item.id, title: item.title }));
            this.selections = selectedItemData.map(item => item.id)
        }

    }

}
</script>
