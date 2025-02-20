<template>

    <div class="h-full overflow-auto p-4 bg-grey-30 h-full">

        <div v-if="loading" class="absolute pin z-200 flex items-center justify-center text-center">
            <loading-graphic />
        </div>

        <div v-if="!loading" class="flex items-center mb-3 -mt-1">
            <h1 class="flex-1">
                <small class="block text-xs text-grey-70 font-medium leading-none mt-1 flex items-center">
                    <svg-icon class="h-4 w-4 mr-1 inline-block text-grey-70" :name="fieldtype.icon"></svg-icon>
                    {{ fieldtype.title }}
                </small>
                {{ values.display || config.display || config.handle }}
            </h1>
            <button
                class="text-grey-50 hover:text-grey-80 mr-3 text-sm"
                @click.prevent="close"
                v-text="__('Cancel')"
            ></button>
            <button
                class="btn btn-primary"
                @click.prevent="commit"
                v-text="__('Finish')"
            ></button>
        </div>

        <div class="publish-tabs tabs">
            <a :class="{ 'active': activeTab === 'settings' }"
                @click="activeTab = 'settings'"
                v-text="__('Settings')"
            ></a>
            <a class="z-5" :class="{ 'active': activeTab === 'conditions' }"
                @click="activeTab = 'conditions'"
                v-text="__('Conditions')"
            ></a>
            <a :class="{ 'active': activeTab === 'validation' }"
                @click="activeTab = 'validation'"
                v-text="__('Validation')"
            ></a>
        </div>

        <div class="card rounded-tl-none" v-if="!loading">

            <publish-container
                :name="publishContainer"
                :blueprint="blueprint"
                :values="values"
                :meta="meta"
                :is-root="true"
                @updated="values = $event"
            >
                <div class="publish-fields" v-show="activeTab === 'settings'" slot-scope="{ setFieldValue }">

                    <form-group
                        handle="display"
                        :display="__('Display')"
                        :instructions="__('messages.fields_display_instructions')"
                        width="50"
                        autofocus
                        :value="values.display"
                        @input="updateField('display', $event, setFieldValue)"
                    />

                    <form-group
                        handle="handle"
                        :display="__('Handle')"
                        :instructions="__('messages.fields_handle_instructions')"
                        width="50"
                        :value="values.handle"
                        @input="updateField('handle', $event, setFieldValue); isHandleModified = true"
                    />

                    <form-group
                        fieldtype="text"
                        handle="instructions"
                        :display="__('Instructions')"
                        :instructions="__('messages.fields_instructions_instructions')"
                        :value="values.instructions"
                        @input="updateField('instructions', $event, setFieldValue)"
                    />

                    <publish-fields
                        v-if="blueprint.sections.length"
                        class="w-full"
                        :fields="blueprint.sections[0].fields"
                        @updated="(handle, value) => updateField(handle, value, setFieldValue)"
                    />

                </div>
            </publish-container>

            <div class="publish-fields" v-show="activeTab === 'conditions'">
                <field-conditions-builder
                    :config="config"
                    :suggestable-fields="suggestableConditionFields"
                    @updated="updateFieldConditions" />
            </div>

            <div class="publish-fields" v-show="activeTab === 'validation'">
                <field-validation-builder
                    :config="config"
                    @updated="updateField('validate', $event)" />

            </div>
        </div>
    </div>

</template>

<script>
import PublishField from '../publish/Field.vue';
import { ValidatesFieldConditions, FieldConditionsBuilder, FIELD_CONDITIONS_KEYS } from '../field-conditions/FieldConditions.js';
import FieldValidationBuilder from '../field-validation/Builder.vue';

export default {

    components: {
        PublishField,
        FieldConditionsBuilder,
        FieldValidationBuilder,
    },

    mixins: [
        ValidatesFieldConditions,
    ],

    props: {
        config: Object,
        overrides: { type: Array, default: () => [] },
        type: String,
        root: Boolean,
        suggestableConditionFields: Array,
    },

    model: {
        prop: 'config',
        event: 'input'
    },

    data: function() {
        return {
            values: null,
            meta: null,
            editedFields: clone(this.overrides),
            isHandleModified: true,
            activeTab: 'settings',
            storeName: 'base',
            fieldtype: null,
            loading: true,
            blueprint: null,
        };
    },

    computed: {
        publishContainer() {
            return `field-settings-${this._uid}`;
        },

        selectedWidth: function() {
            var width = this.config.width || 100;
            var found = _.findWhere(this.widths, {value: width});
            return found.text;
        },

        fieldtypeConfig() {
            return this.fieldtype.config;
        },

        canBeLocalized: function() {
            return this.root && Object.keys(Statamic.$config.get('locales')).length > 1 && this.fieldtype.canBeLocalized;
        },

        canBeValidated: function() {
            return this.fieldtype.canBeValidated;
        },

        canHaveDefault: function() {
            return this.fieldtype.canHaveDefault;
        },

        hasExtras() {
            return this.filteredFieldtypeConfig.length > 0;
        },

        filteredFieldtypeConfig() {
            if (this.type === 'grid') {
                return _.filter(this.fieldtypeConfig, config => config.handle !== 'fields');
            }

            if (['replicator', 'bard'].includes(this.type)) {
                return _.filter(this.fieldtypeConfig, config => config.handle !== 'sets');
            }

            return this.fieldtypeConfig;
        }
    },

    created() {
        // For new fields, we'll slugify the display name into the field name.
        // If they edit the handle, we'll stop.
        if (this.config.isNew && !this.config.isMeta) {
            this.isHandleModified = false;

            this.$watch('values.display', function(display) {
                if (! this.isHandleModified) {
                    const handle = this.$slugify(display, '_');
                    this.updateField('handle', handle);
                }
            });
        }

        this.load();
    },

    methods: {

        focus() {
            this.$els.display.select();
        },

        configFieldClasses(field) {
            return [
                `form-group p-2 m-0 ${field.type}-fieldtype`,
                tailwind_width_class(field.width)
            ];
        },

        updateField(handle, value, setStoreValue=null) {
            this.values[handle] = value;
            this.markFieldEdited(handle);

            if (setStoreValue) {
                setStoreValue(handle, value);
            }
        },

        updateFieldConditions(conditions) {
            let values = {};

            _.each(this.values, (value, key) => {
                if (! FIELD_CONDITIONS_KEYS.includes(key)) {
                    values[key] = value;
                }
            });

            this.values = {...values, ...conditions};

            if (Object.keys(conditions).length > 0) {
                this.markFieldEdited(Object.keys(conditions)[0]);
            }
        },

        markFieldEdited(handle) {
            if (this.editedFields.indexOf(handle) === -1) {
                this.editedFields.push(handle);
            }
        },

        commit() {
            this.$axios.post(cp_url('fields/update'), {
                type: this.type,
                values: this.values
            }).then(response => {
                this.$emit('committed', response.data, this.editedFields);
                this.close();
            });
        },

        close() {
            this.$emit('closed');
        },

        load() {
            this.$axios.post(cp_url('fields/edit'), {
                type: this.type,
                values: this.config
            }).then(response => {
                this.loading = false;
                this.fieldtype = response.data.fieldtype;
                this.blueprint = response.data.blueprint;
                this.values = response.data.values;
                this.meta = {...response.data.meta};
            })
        }

    }

};
</script>
