<template>

    <div>
        <data-list-action
            v-for="action in sortedActions"
            :key="action.handle"
            :action="action"
            :selections="1"
            @selected="run"
        >
            <div slot-scope="{ action, select }">
                <button
                    v-text="action.title"
                    :class="{ warning: action.dangerous }"
                    @click="select" />
            </div>
        </data-list-action>
    </div>

</template>


<script>
import Actions from './Actions.vue';

export default {

    mixins: [Actions],

    props: {
        actions: Array,
        item: { required: true }
    },

    computed: {

        selections() {
            return [this.item];
        },

    }

}
</script>
