<template>

    <publish-field-meta
        :config="config"
        :initial-value="value"
        :initial-meta="meta"
    >
    <div slot-scope="{ meta, value, loading: loadingMeta }" :class="classes">
        <label class="publish-field-label" :class="{'font-bold': config.bold}">
            <span class="cursor-pointer" :class="{'font-mono bg-grey-20 py-px px-sm text-xs': showHandle}" v-text="labelText" @click="toggleLabel" />
            <i class="required ml-sm" v-if="config.required">*</i>
            <avatar v-if="isLocked" :user="lockingUser" class="w-4 rounded-full -mt-px ml-1 mr-1" v-tooltip="lockingUser.name" />
            <span v-if="isReadOnly" class="text-grey-50 font-normal text-2xs mx-sm">
                {{ isLocked ? __('Locked') : __('Read Only') }}
            </span>
            <svg-icon name="translate" class="h-4 ml-sm w-4 text-grey-60" v-if="$config.get('sites').length > 1 && config.localizable" v-tooltip.top="__('Localizable field')" />

            <button
                v-if="!isReadOnly"
                v-show="syncable && isSynced"
                class="outline-none"
                @click="$emit('desynced')"
            >
                <svg-icon name="hyperlink" class="h-4 ml-sm w-4 text-grey-60"
                    v-tooltip.top="__('messages.field_synced_with_origin')" />
            </button>

            <button
                v-if="!isReadOnly"
                v-show="syncable && !isSynced"
                class="outline-none"
                @click="$emit('synced')"
            >
                <svg-icon name="hyperlink-broken" class="h-4 ml-sm w-4 text-grey-60"
                    v-tooltip.top="__('messages.field_desynced_from_origin')" />
            </button>
        </label>

        <div
            class="help-block -mt-1"
            v-if="config.instructions"
            v-html="$options.filters.markdown(config.instructions)" />

        <loading-graphic v-if="loadingMeta" :size="16" :inline="true" />

        <slot name="fieldtype" v-if="!loadingMeta">
            <component
                :is="fieldtypeComponent"
                :config="config"
                :value="value"
                :meta="meta"
                :handle="config.handle"
                :name-prefix="namePrefix"
                :error-key-prefix="errorKeyPrefix"
                :read-only="isReadOnly"
                @input="$emit('input', $event)"
                @meta-updated="$emit('meta-updated', $event)"
                @focus="focused"
                @blur="blurred"
            /> <!-- TODO: name prop should include prefixing when used recursively like inside a grid. -->
        </slot>

        <div v-if="hasError">
            <small class="help-block text-red mt-1 mb-0" v-for="(error, i) in errors" :key="i" v-text="error" />
        </div>
    </div>
    </publish-field-meta>

</template>

<script>
export default {

    props: {
        config: {
            type: Object,
            required: true
        },
        value: {
            required: true
        },
        meta: {
        },
        errors: {
            type: Array
        },
        readOnly: Boolean,
        syncable: Boolean,
        namePrefix: String,
        errorKeyPrefix: String,
    },

    data() {
        return {
            showHandle: false
        }
    },

    inject: {
        storeName: { default: null }
    },

    computed: {

        fieldtypeComponent() {
            return `${this.config.component || this.config.type}-fieldtype`;
        },

        hasError() {
            return this.errors && this.errors.length > 0;
        },

        isReadOnly() {
            if (this.storeState.isRoot === false && !this.config.localizable) return true;

            return this.isLocked || this.readOnly || this.config.read_only || false;
        },

        classes() {
            return [
                'form-group publish-field',
                `${this.config.component || this.config.type}-fieldtype`,
                `field-${tailwind_width_class(this.config.width)}`,
                this.isReadOnly ? 'read-only-field' : '',
                this.config.classes || '',
                { 'has-error': this.hasError }
            ];
        },

        locks() {
            return this.storeState.fieldLocks || {};
        },

        isLocked() {
            return Object.keys(this.locks).includes(this.config.handle);
        },

        lockingUser() {
            if (this.isLocked) {
                let user = this.locks[this.config.handle];
                if (typeof user === 'object') return user;
            }
        },

        isSynced() {
            if (!this.syncable) return;
            return !this.storeState.localizedFields.includes(this.config.handle);
        },

        storeState() {
            return this.$store.state.publish[this.storeName] || {};
        },

        labelText() {
            if (this.showHandle) return this.config.handle
            return this.config.display
                || Vue.$options.filters.titleize(Vue.$options.filters.deslugify(this.config.handle));
        }

    },

    methods: {

        focused() {
            if (!this.isLocked) {
                this.$emit('focus');
            }
        },

        blurred() {
            if (!this.isLocked) {
                this.$emit('blur');
            }
        },

        toggleLabel() {
            this.showHandle = ! this.showHandle
        }

    }
}

</script>
