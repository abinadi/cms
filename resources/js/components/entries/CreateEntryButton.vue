<template>

    <dropdown-list class="inline-block" :show-dropdown-if="blueprints.length > 1">
        <template v-slot:trigger>
            <button
                class="btn btn-primary"
                @click="create"
                v-text="__('Create Entry')" />
        </template>

        <div v-for="blueprint in blueprints" :key="blueprint.handle">
            <dropdown-item :text="blueprint.title" @click="select(blueprint.handle)" />
        </div>
    </dropdown-list>

</template>

<script>
export default {

    props: {
        url: String,
        blueprints: Array
    },

    methods: {

        create() {
            if (this.blueprints.length === 1) this.select();
        },

        select(blueprint) {
            let url = this.url;

            if (blueprint) {
                url = url += `?blueprint=${blueprint}`;
            }

            window.location = url;
        }

    }

}
</script>
