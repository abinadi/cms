<template>
    <v-select
        ref="input"
        @input="update"
        :name="name"
        :clearable="config.clearable"
        :disabled="config.disabled || isReadOnly"
        :options="options"
        :placeholder="config.placeholder"
        :reduce="selection => selection.value"
        :searchable="config.searchable"
        :taggable="config.taggable"
        :push-tags="config.push_tags"
        :multiple="config.multiple"
        :reset-on-options-change="typeof config.reset_on_options_change === 'boolean' ? config.reset_on_options_change : true"
        :close-on-select="!config.taggable"
        :value="value" />
</template>

<script>
import HasInputOptions from './HasInputOptions.js'

export default {

    mixins: [Fieldtype, HasInputOptions],

    computed: {
        options() {
            return this.normalizeInputOptions(this.config.options);
        }
    },

    methods: {
        handleUpdate(value) {
            this.update(value.value)
        },

        focus() {
            this.$refs.input.focus();
        },

        getReplicatorPreviewText() {
            // @TODO
        },
    }
};
</script>
