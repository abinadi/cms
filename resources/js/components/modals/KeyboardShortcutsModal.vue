<template>
    <modal v-if="open" name="keyboard-shortcuts" width="380" height="auto" :adaptive="true" :pivotY=".1" @closed="open = false" v-on-clickaway="close">
        <h1 class="p-2 bg-grey-20 border-b text-center">
            {{ __('Keyboard Shortcuts') }}
        </h1>
        <div class="p-4 relative">
            <div class="shortcut-pair">
                <span class="shortcut-key">
                    <span class="shortcut">shift</span><span class="shortcut-joiner">+</span><span class="shortcut">?</span>
                </span>
                <span class="shortcut-value">{{ __('Show Keyboard Shortcuts') }}</span>
            </div>

            <div class="shortcut-pair">
                <span class="shortcut-key">
                    <span class="shortcut">/</span> <span class="shortcut-joiner">or</span>
                    <span class="shortcut">ctrl</span><span class="shortcut-joiner">+</span><span class="shortcut">f</span>
                </span>
                <span class="shortcut-value">{{ __('Focus Search') }}</span>
            </div>

            <div class="shortcut-pair">
                <span class="shortcut-key">
                    <span class="shortcut">cmd</span><span class="shortcut-joiner">+</span><span class="shortcut">s</span>
                </span>
                <span class="shortcut-value">{{ __('Save Content') }}</span>
            </div>

            <div class="shortcut-pair">
                <span class="shortcut-key">
                    <span class="shortcut">cmd</span><span class="shortcut-joiner">+</span><span class="shortcut">\</span>
                </span>
                <span class="shortcut-value">{{ __('Toggle Sidebar') }}</span>
            </div>

            <div class="shortcut-pair mb-0">
                <span class="shortcut-key">
                    <span class="shortcut">esc</span>
                </span>
                <span class="shortcut-value">{{ __('Close Modal') }}</span>
            </div>
        </div>
    </modal>
</template>

<script>
import { mixin as clickaway } from 'vue-clickaway';

export default {
    mixins: [ clickaway ],

    data() {
        return {
            open: false
        }
    },

    watch: {

        open(open) {
            if (open) {
                this.$mousetrap.bind('esc', () => this.open = false);
            } else {
                this.$mousetrap.unbind('esc');
            }
        },
    },

    methods: {
        close() {
            this.open = false;
        }
    },

    created() {
        this.$mousetrap.bind('?', () => this.open = !this.open);

        this.$events.$on('keyboard-shortcuts.open', () => {
           this.open = true;
       });
    },
}
</script>
