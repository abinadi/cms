<template>

<div>
    <select ref="tags" multiple tabindex="0" ></select>

    <input type="hidden" :name="name" :value="JSON.stringify(data)" class="input-text" />
</div>

</template>

<script>
export default {

    props: [
        'data',
        'suggestions',
        'maxItems',
        'create',
        'name',
        'disabled'
    ],

    mounted() {

        let self = this;

        // Ensure we have an array
        if (typeof this.data === 'string') {
            this.data = [this.data];
        }

        $(this.$refs.tags).selectize({
            options: this.suggestions,
            items: this.data,
            maxItems: this.maxItems,
            plugins: ['drag_drop', 'remove_button'],
            onChange: function(value) {
                self.$emit('input', value);
            },
            create: this.create
        });

        if (this.disabled) {
            this.$refs.tags.selectize.disable();
        }

    },


    methods: {

        focus() {
            this.$refs.tags.selectize.focus();
        }

    }

}
</script>
