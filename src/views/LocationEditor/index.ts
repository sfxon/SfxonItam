import { createApp } from 'vue'
import App from './LocationEditor.vue'

const el = document.getElementById('sfxonitamlocationeditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitamlocationeditor')
