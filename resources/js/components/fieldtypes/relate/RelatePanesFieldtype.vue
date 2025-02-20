<template>

    <div class="relate-panes clearfix" :class="{ 'max-selected': maxSelected }">

        <div class="relate-pane pane-suggestions">
            <div class="pane-header">
                <input type="text"
                       class="input-text relate-search"
                       placeholder="Filter"
                       ref="filter"
                       v-model="search"
                       @keydown.enter="selectActive"
                       @keyup.up="goUp"
                       @keyup.down="goDown"
                />
            </div>

            <div class="relate-items">
                <div class="item"
                     v-for="(item, $index) in availableSuggestions"
                     :key="item.value"
                     :class="{ 'active': $index === active }"
                     @click.prevent="select(item)"
                >
                    <span v-html="item.text"></span>
                    <span class="icon icon-chevron-right"></span>
                </div>
            </div>
        </div>

        <div class="relate-pane pane-selections">
            <div class="pane-header">Selected</div>
            <div class="relate-items" ref="sortable">
                <div class="item" v-for="item in selected" :key="item.value">
                    <span class="item-remove" @click.prevent="remove(item)">&times;</span>
                    <span v-html="item.text"></span>
                </div>
            </div>
        </div>

        <input type="hidden" :name="name" :value="JSON.stringify(data)" />
    </div>

</template>

<script>
export default {

    props: [
        'name',
        'value',
        'suggestions',
        'maxItems'
    ],


    data() {
        return {
            data: this.value,
            search: null,
            active: -1
        }
    },


    computed: {

        availableSuggestions: function() {
            var self = this;

            return _.reject(self.suggestions, function(suggestion) {
                var hasBeenSelected = _.contains(self.data, suggestion.value);

                var matchesSearchTerm = true;
                if (self.search) {
                    matchesSearchTerm = suggestion.text.toLowerCase().indexOf(self.search.toLowerCase()) !== -1;
                }

                return hasBeenSelected || !matchesSearchTerm;
            });
        },

        selected: function() {
            var self = this;

            return _.map(self.data, function(item) {
                return _.findWhere(self.suggestions, { value: item });
            });
        },

        maxSelected: function() {
            if (this.maxItems) {
                return this.data.length >= this.maxItems;
            } else {
                return false;
            }
        }

    },


    methods: {

        initSortable: function() {
            var self = this;

            $(this.$refs.sortable).sortable({
                axis: 'y',
                placeholder: 'item-placeholder',
                forcePlaceholderSize: true,
                revert: 175,
                start: function(e, ui) {
                    ui.item.data('start', ui.item.index())
                },
                update: function(e, ui) {
                    var start = ui.item.data('start'),
                    end   = ui.item.index();

                    self.data.splice(end, 0, self.data.splice(start, 1)[0]);
                }
            });
        },

        select: function(item) {
            if (! this.maxSelected) {
                this.data.push(item.value);
            }
        },

        remove: function(item) {
            var index = _.indexOf(this.data, item.value);
            this.data.splice(index, 1);
        },

        goUp: function() {
            this.active--;

            if (this.active < 0) {
                this.active = 0;
            }
        },

        goDown: function() {
            this.active++;

            if (this.active >= this.availableSuggestions.length-1) {
                this.active = this.availableSuggestions.length-1;
            }
        },

        selectActive: function() {
            var item = this.availableSuggestions[this.active];
            this.select(item);

            if (this.active >= this.availableSuggestions.length) {
                this.active = this.availableSuggestions.length-1;
            }
        },

        focus() {
            this.$refs.filter.focus();
        }

    },

    watch: {
        search: function() {
            if (this.availableSuggestions.length <= this.active) {
                this.active = this.availableSuggestions.length-1;
            }
        },

        data: function(data) {
            this.$emit('input', data);

            this.$nextTick(function() {
                $(this.$refs.sortable).sortable('refresh');
            });
        }
    },

    mounted() {
        this.initSortable();
    }

}
</script>
