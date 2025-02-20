<template>
    <div class="code-fieldtype-container relative" :class="themeClass">
        <div v-text="modeLabel" class="code-mode"></div>
        <div ref="codemirror"></div>
    </div>
</template>

<style src="codemirror/theme/material.css"></style>

<script>
import CodeMirror from 'codemirror'

// Addons
import 'codemirror/addon/edit/matchbrackets'

// Keymaps
import 'codemirror/keymap/sublime'
import 'codemirror/keymap/vim'

// Modes
import 'codemirror/mode/css/css'
import 'codemirror/mode/clike/clike'
import 'codemirror/mode/diff/diff'
import 'codemirror/mode/go/go'
import 'codemirror/mode/gfm/gfm'
import 'codemirror/mode/handlebars/handlebars'
import 'codemirror/mode/haml/haml'
import 'codemirror/mode/htmlmixed/htmlmixed'
import 'codemirror/mode/javascript/javascript'
import 'codemirror/mode/markdown/markdown'
import 'codemirror/mode/nginx/nginx'
import 'codemirror/mode/php/php'
import 'codemirror/mode/python/python'
import 'codemirror/mode/ruby/ruby'
import 'codemirror/mode/shell/shell'
import 'codemirror/mode/sql/sql'
import 'codemirror/mode/twig/twig'
import 'codemirror/mode/vue/vue'
import 'codemirror/mode/xml/xml'
import 'codemirror/mode/yaml/yaml'
import 'codemirror/mode/yaml-frontmatter/yaml-frontmatter'

export default {

    mixins: [Fieldtype],

    data() {
        return {
            codemirror: null
        }
    },

    computed: {
        modeLabel() {
            var label = this.config.mode.replace('text/x-', '')
            return label.replace('htmlmixed', 'html');
        },
        exactTheme() {
            return (this.config.theme === 'light') ? 'default' : 'material'
        },
        themeClass() {
            return 'theme-' + this.config.theme;
        }
    },

    mounted() {
        this.codemirror = CodeMirror(this.$refs.codemirror, {
            value: this.value || '',
            mode: this.config.mode,
            addModeClass: true,
            keyMap: this.config.key_map,
            tabSize: this.config.indent_size,
            indentWithTabs: this.config.indent_type !== 'spaces',
            lineNumbers: this.config.line_numbers,
            lineWrapping: this.config.line_wrapping,
            matchBrackets: true,
            readOnly: this.isReadOnly ? 'nocursor' : false,
            theme: this.exactTheme,
        });

        this.codemirror.on('change', (cm) => {
            this.update(cm.doc.getValue());
        });

        // Refresh to ensure proper size
        // Most applicable when loaded by another field like Bard, etc
        this.$nextTick(function() {
            this.codemirror.refresh();
        })
    },

    watch: {
        value(value, oldValue) {
            if (value == this.codemirror.doc.getValue()) return;
            this.codemirror.doc.setValue(value);
        }
    },

    methods: {
        focus() {
            this.codemirror.focus();
        }
    }

};
</script>
