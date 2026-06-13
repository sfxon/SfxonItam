import { createApp } from 'vue'
import App from './CustomFieldEditor.vue'

const el = document.getElementById('sfxonitamcustomfieldeditor')
const customFieldGroupId = parseInt(el.dataset.customFieldGroupId)
const app = createApp(App, {
    customFieldGroupId,
})

app.mount('#sfxonitamcustomfieldeditor')
