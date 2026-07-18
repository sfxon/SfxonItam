import { createApp } from 'vue'
import App from './CustomFieldEditor.vue'

const el = document.getElementById('sfxonitamcustomfieldeditor')
const customFieldGroupId = parseInt(el.dataset.customFieldGroupId)
let customFieldGroup = {}
let foreignKeyTargets = []

try {
    customFieldGroup = JSON.parse(el.dataset.customFieldGroup)
} catch (e) {
    console.error('Fehler beim Parsen von customFieldGroup:', e)
}

try {
    foreignKeyTargets = JSON.parse(el.dataset.foreignKeyTargets)
} catch (e) {
    console.error('Fehler beim Parsen von foreignKeyTargets:', e)
}

const app = createApp(App, {
    customFieldGroupId,
    customFieldGroup,
    foreignKeyTargets,
})

app.mount('#sfxonitamcustomfieldeditor')
