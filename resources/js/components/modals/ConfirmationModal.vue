<template>
    <modal name="confirmation-modal" :pivotY="0.1" :overflow="false">
        <div class="confirmation-modal flex flex-col h-full">
            <div class="text-lg font-medium p-2 pb-0">
                {{ __(title) }}
            </div>
            <div class="flex-1 px-2 py-3 text-grey">
                <p v-if="bodyText" v-text="bodyText" />
                <slot v-else>
                    <p>{{ __('Are you sure?') }}</p>
                </slot>
            </div>
            <div class="p-2 bg-grey-20 border-t flex items-center justify-end text-sm">
                <button class="text-grey hover:text-grey-90" @click="$emit('cancel')">{{ __('Cancel') }}</button>
                <button class="btn ml-2" :class="buttonClass" v-text="buttonText" @click="$emit('confirm')" />
            </div>
        </div>
    </modal>
</template>

<script>
export default {
    props: {
        title: {
            type: String,
            required: true
        },
        bodyText: {
            type: String
        },
        buttonText: {
            type: String,
            default: 'Confirm'
        },
        danger: {
            type: Boolean,
            default: false
        }
    },

    computed: {
        buttonClass() {
            return this.danger ? 'btn-danger' : 'btn-primary';
        }
    },

    methods: {
        dismiss() {
            this.$emit('cancel')
        }
    },

    created() {
        this.$mousetrap.bind('esc', this.dismiss)
    },
}
</script>
