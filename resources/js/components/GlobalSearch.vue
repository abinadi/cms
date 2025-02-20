<template>
    <div>
        <div class="global-search" :class="{'dirty': isDirty}" v-on-clickaway="reset" v-cloak>
            <div class="state-container w-4 h-4 text-grey-50" @click="focus">
                <svg-icon name="magnifying-glass"></svg-icon>
            </div>

            <input type="text"
                autocomplete="off"
                class="search-input"
                ref="input"
                name="search"
                v-model="query"
                @keydown.up.prevent="moveUp"
                @keydown.down.prevent="moveDown"
                @keydown.meta.enter.prevent="hitNewWindow"
                @keyup.enter.prevent="hit"
                @keydown.esc.prevent="reset"
                @focus="focused = true"
                :placeholder="placeholder"
                tabindex="-1"
            />

            <span v-if="! (isDirty || searching)" class="rounded px-sm pb-px text-2xs border text-grey-60">/</span>
            <loading-graphic v-if="searching" :size="14" :inline="true" text="" class="global-search-loading-indicator" />

            <div v-show="focused && (hasResults || hasFavorites)" class="global-search-results">

                <div v-if="hasResults" v-for="(result, index) in results" class="global-search-result-item p-1 flex items-center" :class="{ 'active': current == index }" @click="hit" @mousemove="setActive(index)">
                    <svg-icon :name="getResultIcon(result)" class="icon"></svg-icon>
                    <div class="flex-1 ml-1 title" v-html="result.title"></div>
                    <span class="rounded px-sm py-px text-2xs uppercase bg-grey-20 text-grey">
                        <template v-if="result.is_entry">{{ result.collection }}</template>
                        <template v-if="result.is_user">{{ __('User') }}</template>
                        <template v-if="result.is_asset">{{ result.container }}</template>
                    </span>
                </div>

                <div v-if="! hasResults && hasFavorites">
                    <div class="px-1.5 py-1 text-grey uppercase text-3xs">{{ __('Your Favorites') }}</div>

                    <div v-for="(favorite, index) in favorites" class="global-search-result-item flex items-center" :class="{ 'active': current == index }" @mousemove="setActive(index)">
                        <div class="flex items-center flex-1 p-1" @click="hit">
                            <svg-icon name="pin" class="icon"></svg-icon>
                            <div class="ml-1 title" v-text="favorite.name"></div>
                        </div>
                        <div class="p-1 text-grey-60 hover:text-grey-80" @click="removeFavorite(favorite)">&times;</div>
                    </div>

                    <div class="text-grey text-xs px-1.5 py-1 border-t text-center">
                        <b class="tracking-wide uppercase text-3xs">{{ __('Pro Tip')}}:</b>
                        <span v-html="__('messages.global_search_open_using_slash')" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
import { mixin as clickaway } from 'vue-clickaway';

export default {
    mixins: [ clickaway ],

    props: {
        endpoint: String,
        placeholder: String
    },

    data() {
        return {
            results: [],
            query: '',
            current: -1,
            searching: false,
            focused: false
        }
    },

    computed: {
        hasResults() {
            return this.results.length > 0;
        },

        favorites() {
            return this.$preferences.get('favorites', []);
        },

        hasFavorites() {
            return this.favorites.length > 0;
        },

        isEmpty() {
            return !this.query && !this.searching;
        },

        isDirty() {
            return !!this.query && !this.searching;
        },
    },

    methods: {
        update: _.debounce(function () {
            if (!this.query) {
                this.reset();
                this.searching = false;
                return;
            }

            let payload = {params: { q: this.query }};

            this.$axios.get(this.endpoint, payload)
                .then(response => {
                    this.results = response.data;
                    this.current = -1;
                    this.searching = false;
                });
        }, 300),

        reset() {
            this.results = [];
            this.query = '';
            this.focused = false;
            this.searching = false;
        },

        setActive(index) {
            this.current = index;
        },

        focus() {
            this.$refs.input.focus();
            this.focused = true;
        },

        hit() {
            if (this.hasResults) {
                window.location.href = this.results[this.current].edit_url;
            } else {
                window.location.href = `${this.$config.get('cpRoot')}/${this.favorites[this.current].url}`;
            }
        },

        hitNewWindow() {
            if (this.hasResults) {
                var win = window.open(this.results[this.current].edit_url, '_blank').focus();
            } else {
                window.open(this.results[this.current].url, '_blank').focus();
            }
        },

        moveUp() {
            if (this.current > 0) this.current--;
        },

        moveDown() {
            if (this.hasResults) {
                if (this.current < this.results.length-1) this.current++;
            } else {
                if (this.current < this.favorites.length-1) this.current++;
            }
        },

        getResultIcon(result) {
            if (result.is_asset) return 'assets'
            if (result.is_user) return 'user'
            return 'content-writing';
        },

        removeFavorite(favorite) {
            this.$preferences.remove('favorites', favorite).then(response => {
                this.$toast.success(__('Favorite removed'));
            });
        }
    },

    watch: {
        query(query) {
            this.searching = true;
            this.update();
        },

        searching(searching) {
            // this.$progress.loading('global-search', searching);
        }
    },

    created() {
        this.$events.$on('favorites.added', this.focus);
    },

    mounted() {
        this.$mousetrap.bind(['/', 'ctrl+f', 'alt+f', 'shift+f'], e => {
            e.preventDefault();
            this.focus();
        });
    }
};
</script>
